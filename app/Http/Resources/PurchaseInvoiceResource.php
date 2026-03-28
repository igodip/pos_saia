<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'warehouse_id' => $this->warehouse_id,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => optional($this->invoice_date)->toDateString(),
            'due_date' => optional($this->due_date)->toDateString(),
            'status' => $this->status?->value,
            'currency' => $this->currency,
            'taxable_amount' => $this->taxable_amount,
            'vat_amount' => $this->vat_amount,
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,
            'confirmed_at' => optional($this->confirmed_at)->toISOString(),
            'supplier' => SupplierResource::make($this->whenLoaded('supplier')),
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse')),
            'items' => PurchaseInvoiceItemResource::collection($this->whenLoaded('items')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
