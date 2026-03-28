<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryValueReportService
{
    public function handle(): Collection
    {
        $lastCostSubquery = DB::table('stock_movements as sm')
            ->selectRaw('sm.warehouse_id, sm.product_variant_id, MAX(sm.id) as latest_id')
            ->whereNotNull('sm.unit_cost')
            ->groupBy('sm.warehouse_id', 'sm.product_variant_id');

        return DB::query()->fromSub(app(CalculateCurrentStockService::class)->snapshotQuery(), 'stock_snapshot')
            ->leftJoinSub($lastCostSubquery, 'latest_cost', function ($join): void {
                $join->on('latest_cost.warehouse_id', '=', 'stock_snapshot.warehouse_id')
                    ->on('latest_cost.product_variant_id', '=', 'stock_snapshot.product_variant_id');
            })
            ->leftJoin('stock_movements as last_movement', 'last_movement.id', '=', 'latest_cost.latest_id')
            ->join('warehouses', 'warehouses.id', '=', 'stock_snapshot.warehouse_id')
            ->join('product_variants', 'product_variants.id', '=', 'stock_snapshot.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->selectRaw('
                stock_snapshot.warehouse_id,
                warehouses.name as warehouse_name,
                stock_snapshot.product_variant_id,
                product_variants.sku as variant_sku,
                products.name as product_name,
                stock_snapshot.current_qty,
                COALESCE(last_movement.unit_cost, product_variants.default_cost, products.default_cost, 0) as unit_cost,
                stock_snapshot.current_qty * COALESCE(last_movement.unit_cost, product_variants.default_cost, products.default_cost, 0) as inventory_value
            ')
            ->orderBy('warehouses.name')
            ->orderBy('products.name')
            ->get();
    }
}
