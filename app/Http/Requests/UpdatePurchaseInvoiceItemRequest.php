<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesByAbility;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseInvoiceItemRequest extends FormRequest
{
    use AuthorizesByAbility;

    protected string $ability = 'manage-purchase-invoices';

    public function rules(): array
    {
        return [
            'product_variant_id' => ['sometimes', 'exists:product_variants,id'],
            'description' => ['sometimes', 'string', 'max:255'],
            'qty' => ['sometimes', 'numeric', 'gt:0'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'vat_rate' => ['sometimes', 'numeric', 'min:0'],
            'line_total' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
