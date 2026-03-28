<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CreateStockAdjustmentAction;
use App\Actions\RecordStockCountAction;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\CalculateCurrentStockService;
use App\Services\HardcodedAdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        private readonly CalculateCurrentStockService $stockService,
        private readonly CreateStockAdjustmentAction $createStockAdjustmentAction,
        private readonly RecordStockCountAction $recordStockCountAction,
        private readonly HardcodedAdminUserService $adminUserService,
    ) {
    }

    public function index(): View
    {
        return view('admin.inventory', [
            'products' => Product::query()->with('variants')->orderBy('name')->get(),
            'variants' => ProductVariant::query()->with('product')->orderBy('variant_name')->get(),
            'suppliers' => Supplier::query()->orderBy('company_name')->get(),
            'warehouses' => Warehouse::query()->orderBy('name')->get(),
            'snapshot' => $this->stockService->snapshot(),
            'recentMovements' => StockMovement::query()
                ->with(['warehouse', 'productVariant.product'])
                ->latest('created_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'vat_rate' => ['required', 'numeric', 'min:0'],
            'default_cost' => ['nullable', 'numeric', 'min:0'],
            'default_price' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Product::query()->create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Prodotto creato.');
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product->id)],
            'barcode' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'vat_rate' => ['required', 'numeric', 'min:0'],
            'default_cost' => ['nullable', 'numeric', 'min:0'],
            'default_price' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Prodotto aggiornato.');
    }

    public function destroyProduct(Product $product): RedirectResponse
    {
        if ($product->variants()->exists()) {
            throw ValidationException::withMessages([
                'product' => 'Elimina prima le varianti del prodotto.',
            ]);
        }

        $product->delete();

        return back()->with('status', 'Prodotto eliminato.');
    }

    public function storeVariant(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'sku' => ['required', 'string', 'max:255', 'unique:product_variants,sku'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'variant_name' => ['required', 'string', 'max:255'],
            'attributes_json' => ['nullable', 'string'],
            'default_cost' => ['nullable', 'numeric', 'min:0'],
            'default_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ProductVariant::query()->create([
            ...$validated,
            'attributes_json' => $validated['attributes_json'] ? json_decode($validated['attributes_json'], true, 512, JSON_THROW_ON_ERROR) : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Variante creata.');
    }

    public function updateVariant(Request $request, ProductVariant $variant): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('product_variants', 'sku')->ignore($variant->id)],
            'barcode' => ['nullable', 'string', 'max:255'],
            'variant_name' => ['required', 'string', 'max:255'],
            'attributes_json' => ['nullable', 'string'],
            'default_cost' => ['nullable', 'numeric', 'min:0'],
            'default_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $variant->update([
            ...$validated,
            'attributes_json' => $validated['attributes_json'] ? json_decode($validated['attributes_json'], true, 512, JSON_THROW_ON_ERROR) : null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Variante aggiornata.');
    }

    public function destroyVariant(ProductVariant $variant): RedirectResponse
    {
        if ($variant->invoiceItems()->exists() || $variant->stockMovements()->exists() || $variant->stockCounts()->exists()) {
            throw ValidationException::withMessages([
                'variant' => 'La variante e\' gia\' usata nello storico e non puo\' essere eliminata.',
            ]);
        }

        $variant->delete();

        return back()->with('status', 'Variante eliminata.');
    }

    public function storeSupplier(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        Supplier::query()->create($validated);

        return back()->with('status', 'Fornitore creato.');
    }

    public function updateSupplier(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]));

        return back()->with('status', 'Fornitore aggiornato.');
    }

    public function destroySupplier(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchaseInvoices()->exists()) {
            throw ValidationException::withMessages([
                'supplier' => 'Il fornitore e\' collegato a fatture esistenti.',
            ]);
        }

        $supplier->delete();

        return back()->with('status', 'Fornitore eliminato.');
    }

    public function storeWarehouse(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:warehouses,code'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Warehouse::query()->create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Magazzino creato.');
    }

    public function updateWarehouse(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('warehouses', 'code')->ignore($warehouse->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $warehouse->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Magazzino aggiornato.');
    }

    public function destroyWarehouse(Warehouse $warehouse): RedirectResponse
    {
        if ($warehouse->purchaseInvoices()->exists() || $warehouse->stockMovements()->exists() || $warehouse->stockCounts()->exists()) {
            throw ValidationException::withMessages([
                'warehouse' => 'Il magazzino e\' gia\' usato nello storico e non puo\' essere eliminato.',
            ]);
        }

        $warehouse->delete();

        return back()->with('status', 'Magazzino eliminato.');
    }

    public function storeAdjustment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'movement_type' => ['required', Rule::in(['adjustment_in', 'adjustment_out'])],
            'qty' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->createStockAdjustmentAction->handle($validated, $this->adminUserService->ensureAdminUser());

        return back()->with('status', 'Rettifica registrata.');
    }

    public function storeCount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'counted_qty' => ['required', 'numeric', 'min:0'],
            'counted_at' => ['nullable', 'date'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->recordStockCountAction->handle($validated, $this->adminUserService->ensureAdminUser());

        return back()->with('status', 'Conteggio inventariale registrato.');
    }
}
