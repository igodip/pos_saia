<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesByAbility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    use AuthorizesByAbility;

    protected string $ability = 'manage-master-data';

    public function rules(): array
    {
        $productId = $this->route('product')->id;

        return [
            'sku' => ['sometimes', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:255'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'brand' => ['sometimes', 'nullable', 'string', 'max:255'],
            'vat_rate' => ['sometimes', 'numeric', 'min:0'],
            'default_cost' => ['sometimes', 'numeric', 'min:0'],
            'default_price' => ['sometimes', 'numeric', 'min:0'],
            'reorder_level' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
