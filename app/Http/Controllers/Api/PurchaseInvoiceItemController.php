<?php

namespace App\Http\Controllers\Api;

use App\Enums\PurchaseInvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseInvoiceItemRequest;
use App\Http\Requests\UpdatePurchaseInvoiceItemRequest;
use App\Http\Resources\PurchaseInvoiceItemResource;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PurchaseInvoiceItemController extends Controller
{
    public function store(StorePurchaseInvoiceItemRequest $request, PurchaseInvoice $purchaseInvoice): JsonResponse
    {
        $this->ensureDraft($purchaseInvoice);

        $item = $purchaseInvoice->items()->create($request->validated());

        return (new PurchaseInvoiceItemResource($item->load('productVariant.product')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdatePurchaseInvoiceItemRequest $request, PurchaseInvoice $purchaseInvoice, PurchaseInvoiceItem $item): PurchaseInvoiceItemResource
    {
        $this->ensureDraft($purchaseInvoice);

        abort_if($item->purchase_invoice_id !== $purchaseInvoice->id, 404);

        $item->update($request->validated());

        return new PurchaseInvoiceItemResource($item->load('productVariant.product'));
    }

    public function destroy(Request $request, PurchaseInvoice $purchaseInvoice, PurchaseInvoiceItem $item): JsonResponse
    {
        $this->ensureDraft($purchaseInvoice);

        abort_if($item->purchase_invoice_id !== $purchaseInvoice->id, 404);

        $item->delete();

        return response()->json(status: 204);
    }

    private function ensureDraft(PurchaseInvoice $purchaseInvoice): void
    {
        if ($purchaseInvoice->status !== PurchaseInvoiceStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => 'Only draft purchase invoices can be edited.',
            ]);
        }
    }
}
