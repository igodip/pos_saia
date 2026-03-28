<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'variant_name' => $this->variant_name,
            'attributes' => $this->attributes_json,
            'default_cost' => $this->default_cost,
            'default_price' => $this->default_price,
            'is_active' => $this->is_active,
            'product' => ProductResource::make($this->whenLoaded('product')),
        ];
    }
}
