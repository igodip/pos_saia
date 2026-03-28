<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesByAbility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StorePurchaseInvoiceAttachmentRequest extends FormRequest
{
    use AuthorizesByAbility;

    protected string $ability = 'manage-purchase-invoices';

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                File::types(['pdf'])->max(config('inventory.attachments.max_upload_kb')),
            ],
        ];
    }
}
