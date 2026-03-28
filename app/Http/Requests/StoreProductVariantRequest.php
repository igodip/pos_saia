<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesByAbility;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductVariantRequest extends FormRequest
{
    use AuthorizesByAbility;

    protected string $ability = 'manage-master-data';

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'sku' => ['required', 'string', 'max:255', 'unique:product_variants,sku'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'variant_name' => ['required', 'string', 'max:255'],
            'attributes_json' => ['nullable', 'array'],
            'default_cost' => ['nullable', 'numeric', 'min:0'],
            'default_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
