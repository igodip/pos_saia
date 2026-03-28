<?php

namespace App\Http\Controllers\Api;

use App\Enums\PurchaseInvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseInvoiceRequest;
use App\Http\Requests\UpdatePurchaseInvoiceRequest;
use App\Http\Resources\PurchaseInvoiceResource;
use App\Models\PurchaseInvoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PurchaseInvoiceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('view-purchase-invoices');

        $query = PurchaseInvoice::query()
            ->with(['supplier', 'warehouse'])
            ->when(request('supplier_id'), fn ($q, $supplierId) => $q->where('supplier_id', $supplierId))
            ->when(request('warehouse_id'), fn ($q, $warehouseId) => $q->where('warehouse_id', $warehouseId))
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('date_from'), fn ($q, $date) => $q->whereDate('invoice_date', '>=', $date))
            ->when(request('date_to'), fn ($q, $date) => $q->whereDate('invoice_date', '<=', $date))
            ->orderByDesc('invoice_date');

        return PurchaseInvoiceResource::collection($query->paginate());
    }

    public function store(StorePurchaseInvoiceRequest $request): JsonResponse
    {
        $invoice = PurchaseInvoice::query()->create([
            ...$request->validated(),
            'status' => PurchaseInvoiceStatus::DRAFT,
            'created_by' => $request->user()->id,
            'currency' => $request->input('currency', config('inventory.default_currency')),
        ]);

        return (new PurchaseInvoiceResource($invoice->load(['supplier', 'warehouse', 'items', 'attachments'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(PurchaseInvoice $purchaseInvoice): PurchaseInvoiceResource
    {
        Gate::authorize('view-purchase-invoices');

        return new PurchaseInvoiceResource($purchaseInvoice->load([
            'supplier',
            'warehouse',
            'attachments',
            'items.productVariant.product',
        ]));
    }

    public function update(UpdatePurchaseInvoiceRequest $request, PurchaseInvoice $purchaseInvoice): PurchaseInvoiceResource
    {
        $purchaseInvoice->update($request->validated());

        return new PurchaseInvoiceResource($purchaseInvoice->load([
            'supplier',
            'warehouse',
            'attachments',
            'items.productVariant.product',
        ]));
    }

    public function destroy(Request $request, PurchaseInvoice $purchaseInvoice): JsonResponse
    {
        Gate::authorize('manage-purchase-invoices');

        if ($purchaseInvoice->status === PurchaseInvoiceStatus::CONFIRMED) {
            throw ValidationException::withMessages([
                'status' => 'Confirmed purchase invoices cannot be deleted.',
            ]);
        }

        DB::transaction(function () use ($purchaseInvoice): void {
            $purchaseInvoice->attachments()->delete();
            $purchaseInvoice->items()->delete();
            $purchaseInvoice->auditLogs()->delete();
            $purchaseInvoice->forceDelete();
        });

        return response()->json(status: 204);
    }
}
