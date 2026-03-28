<?php

namespace App\Http\Controllers\Api;

use App\Actions\ConfirmPurchaseInvoiceAction;
use App\Enums\PurchaseInvoiceStatus;
use App\Exceptions\PurchaseInvoiceAlreadyConfirmedException;
use App\Exceptions\PurchaseInvoiceHasNoItemsException;
use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseInvoiceResource;
use App\Models\PurchaseInvoice;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PurchaseInvoiceConfirmationController extends Controller
{
    public function __construct(
        private readonly ConfirmPurchaseInvoiceAction $confirmPurchaseInvoiceAction,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function confirm(PurchaseInvoice $purchaseInvoice): PurchaseInvoiceResource
    {
        Gate::authorize('confirm-purchase-invoices');

        try {
            return new PurchaseInvoiceResource(
                $this->confirmPurchaseInvoiceAction->handle($purchaseInvoice, request()->user())
            );
        } catch (PurchaseInvoiceAlreadyConfirmedException|PurchaseInvoiceHasNoItemsException $exception) {
            throw ValidationException::withMessages([
                'invoice' => $exception->getMessage(),
            ]);
        }
    }

    public function cancel(PurchaseInvoice $purchaseInvoice): JsonResponse
    {
        Gate::authorize('manage-purchase-invoices');

        if ($purchaseInvoice->status === PurchaseInvoiceStatus::CONFIRMED) {
            throw ValidationException::withMessages([
                'status' => 'Confirmed purchase invoices cannot be cancelled in phase 1.',
            ]);
        }

        DB::transaction(function () use ($purchaseInvoice): void {
            $purchaseInvoice->update(['status' => PurchaseInvoiceStatus::CANCELLED]);

            $this->auditLogService->record('purchase_invoice_cancelled', $purchaseInvoice, request()->user()->id);
        });

        return response()->json([
            'message' => 'Purchase invoice cancelled.',
        ]);
    }
}
