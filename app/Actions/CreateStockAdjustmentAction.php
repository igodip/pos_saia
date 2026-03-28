<?php

namespace App\Actions;

use App\Enums\StockMovementType;
use App\Exceptions\InvalidStockAdjustmentException;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class CreateStockAdjustmentAction
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function handle(array $data, User $user): StockMovement
    {
        $movementType = StockMovementType::from($data['movement_type']);

        if (! in_array($movementType, [StockMovementType::ADJUSTMENT_IN, StockMovementType::ADJUSTMENT_OUT], true)) {
            throw new InvalidStockAdjustmentException('Invalid adjustment movement type.');
        }

        return DB::transaction(function () use ($data, $movementType, $user): StockMovement {
            $movement = StockMovement::query()->create([
                'warehouse_id' => $data['warehouse_id'],
                'product_variant_id' => $data['product_variant_id'],
                'movement_type' => $movementType,
                'direction' => $movementType->direction(),
                'qty' => $data['qty'],
                'unit_cost' => $data['unit_cost'] ?? null,
                'reference_table' => 'stock_adjustments',
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'created_at' => now(),
            ]);

            $this->auditLogService->record('stock_adjustment_created', $movement, $user->id, [
                'warehouse_id' => $movement->warehouse_id,
                'product_variant_id' => $movement->product_variant_id,
                'qty' => $movement->qty,
            ]);

            return $movement->fresh(['warehouse', 'productVariant.product', 'creator']);
        });
    }
}
