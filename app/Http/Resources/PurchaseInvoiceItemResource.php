<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseInvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'description' => $this->description,
            'qty' => $this->qty,
            'unit_price' => $this->unit_price,
            'discount_amount' => $this->discount_amount,
            'vat_rate' => $this->vat_rate,
            'line_total' => $this->line_total,
            'product_variant' => ProductVariantResource::make($this->whenLoaded('productVariant')),
        ];
    }
}
