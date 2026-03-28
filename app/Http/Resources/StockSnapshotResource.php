<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->warehouse_name,
            'warehouse_code' => $this->warehouse_code,
            'product_variant_id' => $this->product_variant_id,
            'product_name' => $this->product_name,
            'variant_name' => $this->variant_name,
            'variant_sku' => $this->variant_sku,
            'current_qty' => $this->current_qty,
            'reorder_level' => $this->reorder_level,
            'last_movement_at' => $this->last_movement_at,
        ];
    }
}
