<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'vat_number' => $this->vat_number,
            'tax_code' => $this->tax_code,
            'address' => $this->address,
            'email' => $this->email,
            'phone' => $this->phone,
            'payment_terms' => $this->payment_terms,
            'notes' => $this->notes,
        ];
    }
}
