<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesByAbility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    use AuthorizesByAbility;

    protected string $ability = 'manage-master-data';

    public function rules(): array
    {
        $variantId = $this->route('product_variant')->id;

        return [
            'product_id' => ['sometimes', 'exists:products,id'],
            'sku' => ['sometimes', 'string', 'max:255', Rule::unique('product_variants', 'sku')->ignore($variantId)],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:255'],
            'variant_name' => ['sometimes', 'string', 'max:255'],
            'attributes_json' => ['sometimes', 'nullable', 'array'],
            'default_cost' => ['sometimes', 'numeric', 'min:0'],
            'default_price' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
