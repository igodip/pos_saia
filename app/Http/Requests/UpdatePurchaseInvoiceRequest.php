<?php

namespace App\Http\Requests;

use App\Enums\PurchaseInvoiceStatus;
use App\Http\Requests\Concerns\AuthorizesByAbility;
use App\Models\PurchaseInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdatePurchaseInvoiceRequest extends FormRequest
{
    use AuthorizesByAbility;

    protected string $ability = 'manage-purchase-invoices';

    public function rules(): array
    {
        return [
            'supplier_id' => ['sometimes', 'exists:suppliers,id'],
            'warehouse_id' => ['sometimes', 'exists:warehouses,id'],
            'invoice_number' => ['sometimes', 'string', 'max:255'],
            'invoice_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'taxable_amount' => ['sometimes', 'numeric', 'min:0'],
            'vat_amount' => ['sometimes', 'numeric', 'min:0'],
            'total_amount' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    protected function passedValidation(): void
    {
        /** @var PurchaseInvoice $invoice */
        $invoice = $this->route('purchase_invoice');

        if ($invoice->status !== PurchaseInvoiceStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => 'Only draft purchase invoices can be updated.',
            ]);
        }
    }
}
