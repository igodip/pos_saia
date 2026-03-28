<?php

namespace App\Actions;

use App\Enums\StockMovementType;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\CalculateCurrentStockService;
use Illuminate\Support\Facades\DB;

class RecordStockCountAction
{
    public function __construct(
        private readonly CalculateCurrentStockService $stockService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function handle(array $data, User $user): StockCount
    {
        return DB::transaction(function () use ($data, $user): StockCount {
            $systemQty = $this->stockService->quantityFor($data['warehouse_id'], $data['product_variant_id']);
            $difference = (float) $data['counted_qty'] - $systemQty;

            $stockCount = StockCount::query()->create([
                'warehouse_id' => $data['warehouse_id'],
                'product_variant_id' => $data['product_variant_id'],
                'counted_qty' => $data['counted_qty'],
                'system_qty' => $systemQty,
                'difference_qty' => $difference,
                'counted_at' => $data['counted_at'] ?? now(),
                'counted_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($difference !== 0.0) {
                $movementType = $difference > 0 ? StockMovementType::ADJUSTMENT_IN : StockMovementType::ADJUSTMENT_OUT;

                StockMovement::query()->create([
                    'warehouse_id' => $data['warehouse_id'],
                    'product_variant_id' => $data['product_variant_id'],
                    'movement_type' => $movementType,
                    'direction' => $movementType->direction(),
                    'qty' => abs($difference),
                    'unit_cost' => $data['unit_cost'] ?? null,
                    'reference_table' => 'stock_counts',
                    'reference_id' => $stockCount->id,
                    'notes' => $data['notes'] ?? 'Stock count reconciliation',
                    'created_by' => $user->id,
                    'created_at' => now(),
                ]);
            }

            $this->auditLogService->record('stock_count_recorded', $stockCount, $user->id, [
                'system_qty' => $systemQty,
                'difference_qty' => $difference,
            ]);

            return $stockCount->fresh(['warehouse', 'productVariant.product']);
        });
    }
}
