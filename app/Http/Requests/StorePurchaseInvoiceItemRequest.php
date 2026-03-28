<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesByAbility;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseInvoiceItemRequest extends FormRequest
{
    use AuthorizesByAbility;

    protected string $ability = 'manage-purchase-invoices';

    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'description' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'vat_rate' => ['required', 'numeric', 'min:0'],
            'line_total' => ['required', 'numeric', 'min:0'],
        ];
    }
}
