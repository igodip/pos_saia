<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseInvoiceAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\PurchaseInvoice;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;

class PurchaseInvoiceAttachmentController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function store(StorePurchaseInvoiceAttachmentRequest $request, PurchaseInvoice $purchaseInvoice): JsonResponse
    {
        $disk = config('inventory.attachments.disk');
        $file = $request->file('file');
        $path = $file->store('purchase-invoices', $disk);

        $attachment = $purchaseInvoice->attachments()->create([
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        $this->auditLogService->record('purchase_invoice_attachment_uploaded', $purchaseInvoice, $request->user()->id, [
            'attachment_id' => $attachment->id,
        ]);

        return (new AttachmentResource($attachment))
            ->response()
            ->setStatusCode(201);
    }
}
