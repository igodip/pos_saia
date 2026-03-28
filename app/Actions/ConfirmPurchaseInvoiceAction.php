<?php

namespace App\Actions;

use App\Enums\PurchaseInvoiceStatus;
use App\Enums\StockMovementType;
use App\Exceptions\PurchaseInvoiceAlreadyConfirmedException;
use App\Exceptions\PurchaseInvoiceHasNoItemsException;
use App\Models\PurchaseInvoice;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class ConfirmPurchaseInvoiceAction
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function handle(PurchaseInvoice $purchaseInvoice, User $user): PurchaseInvoice
    {
        return DB::transaction(function () use ($purchaseInvoice, $user): PurchaseInvoice {
            $lockedInvoice = PurchaseInvoice::query()
                ->with(['items.productVariant.product', 'supplier', 'warehouse', 'attachments'])
                ->lockForUpdate()
                ->findOrFail($purchaseInvoice->id);

            if ($lockedInvoice->status === PurchaseInvoiceStatus::CONFIRMED) {
                throw new PurchaseInvoiceAlreadyConfirmedException('Purchase invoice already confirmed.');
            }

            if ($lockedInvoice->status !== PurchaseInvoiceStatus::DRAFT) {
                throw new PurchaseInvoiceAlreadyConfirmedException('Only draft purchase invoices can be confirmed.');
            }

            if (! $lockedInvoice->items()->exists()) {
                throw new PurchaseInvoiceHasNoItemsException('Purchase invoice has no items.');
            }

            foreach ($lockedInvoice->items as $item) {
                $lockedInvoice->warehouse->stockMovements()->create([
                    'product_variant_id' => $item->product_variant_id,
                    'movement_type' => StockMovementType::PURCHASE_LOAD,
                    'direction' => StockMovementType::PURCHASE_LOAD->direction(),
                    'qty' => $item->qty,
                    'unit_cost' => $item->unit_price,
                    'reference_table' => 'purchase_invoices',
                    'reference_id' => $lockedInvoice->id,
                    'notes' => sprintf('Invoice %s confirmation', $lockedInvoice->invoice_number),
                    'created_by' => $user->id,
                    'created_at' => now(),
                ]);
            }

            $lockedInvoice->forceFill([
                'status' => PurchaseInvoiceStatus::CONFIRMED,
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
            ])->save();

            $this->auditLogService->record('purchase_invoice_confirmed', $lockedInvoice, $user->id, [
                'invoice_number' => $lockedInvoice->invoice_number,
                'items_count' => $lockedInvoice->items->count(),
            ]);

            return $lockedInvoice->fresh(['items.productVariant.product', 'supplier', 'warehouse', 'attachments']);
        });
    }
}
