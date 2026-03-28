<?php

namespace App\Services;

use App\Enums\StockMovementDirection;
use App\Models\StockMovement;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalculateCurrentStockService
{
    public function quantityFor(int $warehouseId, int $productVariantId): float
    {
        return (float) StockMovement::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_variant_id', $productVariantId)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN direction = ? THEN qty ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN direction = ? THEN qty ELSE 0 END), 0) as net_qty
            ", [StockMovementDirection::IN->value, StockMovementDirection::OUT->value])
            ->value('net_qty');
    }

    public function snapshotQuery(): Builder
    {
        return DB::table('stock_movements')
            ->selectRaw("
                warehouse_id,
                product_variant_id,
                SUM(CASE WHEN direction = ? THEN qty ELSE -qty END) as current_qty,
                MAX(created_at) as last_movement_at
            ", [StockMovementDirection::IN->value])
            ->groupBy('warehouse_id', 'product_variant_id');
    }

    public function snapshot(array $filters = []): Collection
    {
        $query = DB::query()->fromSub($this->snapshotQuery(), 'stock_snapshot')
            ->join('warehouses', 'warehouses.id', '=', 'stock_snapshot.warehouse_id')
            ->join('product_variants', 'product_variants.id', '=', 'stock_snapshot.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->select([
                'stock_snapshot.warehouse_id',
                'stock_snapshot.product_variant_id',
                'stock_snapshot.current_qty',
                'stock_snapshot.last_movement_at',
                'warehouses.name as warehouse_name',
                'warehouses.code as warehouse_code',
                'product_variants.sku as variant_sku',
                'product_variants.variant_name',
                'products.name as product_name',
                'products.reorder_level',
            ]);

        if (isset($filters['warehouse_id'])) {
            $query->where('stock_snapshot.warehouse_id', $filters['warehouse_id']);
        }

        if (isset($filters['product_variant_id'])) {
            $query->where('stock_snapshot.product_variant_id', $filters['product_variant_id']);
        }

        return $query->orderBy('warehouses.name')->orderBy('products.name')->get();
    }
}
