<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'product_variant_id' => $this->product_variant_id,
            'movement_type' => $this->movement_type?->value,
            'direction' => $this->direction?->value,
            'qty' => $this->qty,
            'unit_cost' => $this->unit_cost,
            'reference_table' => $this->reference_table,
            'reference_id' => $this->reference_id,
            'notes' => $this->notes,
            'created_at' => optional($this->created_at)->toISOString(),
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse')),
            'product_variant' => ProductVariantResource::make($this->whenLoaded('productVariant')),
        ];
    }
}
