<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesByAbility;
use Illuminate\Foundation\Http\FormRequest;

class StoreStockCountRequest extends FormRequest
{
    use AuthorizesByAbility;

    protected string $ability = 'manage-stock';

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'counted_qty' => ['required', 'numeric', 'min:0'],
            'counted_at' => ['nullable', 'date'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
