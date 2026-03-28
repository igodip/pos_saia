<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ConfirmPurchaseInvoiceAction;
use App\Enums\PurchaseInvoiceStatus;
use App\Exceptions\PurchaseInvoiceAlreadyConfirmedException;
use App\Exceptions\PurchaseInvoiceHasNoItemsException;
use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\AuditLogService;
use App\Services\HardcodedAdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseInvoiceController extends Controller
{
    public function __construct(
        private readonly ConfirmPurchaseInvoiceAction $confirmPurchaseInvoiceAction,
        private readonly AuditLogService $auditLogService,
        private readonly HardcodedAdminUserService $adminUserService,
    ) {
    }

    public function index(Request $request): View
    {
        $query = PurchaseInvoice::query()
            ->with(['supplier', 'warehouse'])
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->orderByDesc('invoice_date');

        return view('admin.invoices.index', [
            'invoices' => $query->paginate(15)->withQueryString(),
            'suppliers' => Supplier::query()->orderBy('company_name')->get(),
            'warehouses' => Warehouse::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'invoice_number' => ['required', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'taxable_amount' => ['required', 'numeric', 'min:0'],
            'vat_amount' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $invoice = PurchaseInvoice::query()->create([
            ...$validated,
            'status' => PurchaseInvoiceStatus::DRAFT,
            'currency' => $validated['currency'] ?? config('inventory.default_currency'),
            'created_by' => $this->adminUserService->ensureAdminUser()->id,
        ]);

        return redirect()->route('admin.invoices.show', $invoice)->with('status', 'Fattura creata.');
    }

    public function show(PurchaseInvoice $purchaseInvoice): View
    {
        return view('admin.invoices.show', [
            'invoice' => $purchaseInvoice->load([
                'supplier',
                'warehouse',
                'items.productVariant.product',
                'attachments',
            ]),
            'suppliers' => Supplier::query()->orderBy('company_name')->get(),
            'warehouses' => Warehouse::query()->orderBy('name')->get(),
            'variants' => ProductVariant::query()->with('product')->orderBy('variant_name')->get(),
        ]);
    }

    public function update(Request $request, PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        $this->ensureDraft($purchaseInvoice, 'Solo le fatture draft possono essere modificate.');

        $purchaseInvoice->update($request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'invoice_number' => ['required', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency' => ['required', 'string', 'size:3'],
            'taxable_amount' => ['required', 'numeric', 'min:0'],
            'vat_amount' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]));

        return back()->with('status', 'Fattura aggiornata.');
    }

    public function destroy(PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        if ($purchaseInvoice->status === PurchaseInvoiceStatus::CONFIRMED) {
            throw ValidationException::withMessages([
                'status' => 'Le fatture confermate non possono essere eliminate.',
            ]);
        }

        DB::transaction(function () use ($purchaseInvoice): void {
            $purchaseInvoice->attachments()->delete();
            $purchaseInvoice->items()->delete();
            $purchaseInvoice->auditLogs()->delete();
            $purchaseInvoice->forceDelete();
        });

        return redirect()->route('admin.invoices.index')->with('status', 'Fattura eliminata.');
    }

    public function storeItem(Request $request, PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        $this->ensureDraft($purchaseInvoice, 'Solo le fatture draft possono ricevere nuove righe.');

        $purchaseInvoice->items()->create($request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'description' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'vat_rate' => ['required', 'numeric', 'min:0'],
            'line_total' => ['required', 'numeric', 'min:0'],
        ]));

        return back()->with('status', 'Riga fattura aggiunta.');
    }

    public function updateItem(Request $request, PurchaseInvoice $purchaseInvoice, PurchaseInvoiceItem $item): RedirectResponse
    {
        $this->ensureDraft($purchaseInvoice, 'Solo le fatture draft possono modificare le righe.');
        abort_if($item->purchase_invoice_id !== $purchaseInvoice->id, 404);

        $item->update($request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'description' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'vat_rate' => ['required', 'numeric', 'min:0'],
            'line_total' => ['required', 'numeric', 'min:0'],
        ]));

        return back()->with('status', 'Riga fattura aggiornata.');
    }

    public function destroyItem(PurchaseInvoice $purchaseInvoice, PurchaseInvoiceItem $item): RedirectResponse
    {
        $this->ensureDraft($purchaseInvoice, 'Solo le fatture draft possono eliminare le righe.');
        abort_if($item->purchase_invoice_id !== $purchaseInvoice->id, 404);

        $item->delete();

        return back()->with('status', 'Riga fattura eliminata.');
    }

    public function confirm(PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        try {
            $this->confirmPurchaseInvoiceAction->handle($purchaseInvoice, $this->adminUserService->ensureAdminUser());
        } catch (PurchaseInvoiceAlreadyConfirmedException|PurchaseInvoiceHasNoItemsException $exception) {
            throw ValidationException::withMessages([
                'invoice' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'Fattura confermata e stock aggiornato.');
    }

    public function cancel(PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        if ($purchaseInvoice->status === PurchaseInvoiceStatus::CONFIRMED) {
            throw ValidationException::withMessages([
                'status' => 'Le fatture confermate non possono essere annullate nella fase 1.',
            ]);
        }

        DB::transaction(function () use ($purchaseInvoice): void {
            $purchaseInvoice->update(['status' => PurchaseInvoiceStatus::CANCELLED]);
            $this->auditLogService->record('purchase_invoice_cancelled', $purchaseInvoice, $this->adminUserService->ensureAdminUser()->id);
        });

        return back()->with('status', 'Fattura annullata.');
    }

    private function ensureDraft(PurchaseInvoice $purchaseInvoice, string $message): void
    {
        if ($purchaseInvoice->status !== PurchaseInvoiceStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => $message,
            ]);
        }
    }
}
