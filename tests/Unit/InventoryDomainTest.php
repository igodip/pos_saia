<?php

namespace Tests\Unit;

use App\Actions\ConfirmPurchaseInvoiceAction;
use App\Enums\PurchaseInvoiceStatus;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CalculateCurrentStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_net_stock_is_calculated_from_movements(): void
    {
        $warehouse = Warehouse::query()->create(['name' => 'Main', 'code' => 'MAIN']);
        $product = Product::query()->create([
            'sku' => 'P1',
            'name' => 'Coffee',
            'vat_rate' => 22,
            'default_cost' => 3,
            'default_price' => 8,
            'reorder_level' => 1,
            'is_active' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'P1-1KG',
            'variant_name' => '1kg',
            'default_cost' => 3,
            'default_price' => 8,
            'is_active' => true,
        ]);

        $warehouse->stockMovements()->create([
            'product_variant_id' => $variant->id,
            'movement_type' => 'adjustment_in',
            'direction' => 'IN',
            'qty' => 10,
            'created_at' => now(),
        ]);

        $warehouse->stockMovements()->create([
            'product_variant_id' => $variant->id,
            'movement_type' => 'manual_out',
            'direction' => 'OUT',
            'qty' => 3,
            'created_at' => now(),
        ]);

        $this->assertSame(7.0, app(CalculateCurrentStockService::class)->quantityFor($warehouse->id, $variant->id));
    }

    public function test_purchase_invoice_status_changes_to_confirmed_on_confirmation(): void
    {
        $user = User::factory()->create(['role' => UserRole::WAREHOUSE]);
        $supplier = Supplier::query()->create(['company_name' => 'Roasters', 'vat_number' => 'IT123']);
        $warehouse = Warehouse::query()->create(['name' => 'Main', 'code' => 'MAIN']);
        $product = Product::query()->create([
            'sku' => 'P1',
            'name' => 'Coffee',
            'vat_rate' => 22,
            'default_cost' => 3,
            'default_price' => 8,
            'reorder_level' => 1,
            'is_active' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'P1-1KG',
            'variant_name' => '1kg',
            'default_cost' => 3,
            'default_price' => 8,
            'is_active' => true,
        ]);
        $invoice = PurchaseInvoice::query()->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'invoice_number' => 'INV-102',
            'invoice_date' => '2026-03-19',
            'status' => PurchaseInvoiceStatus::DRAFT,
            'currency' => 'EUR',
            'taxable_amount' => 100,
            'vat_amount' => 22,
            'total_amount' => 122,
            'created_by' => $user->id,
        ]);
        $invoice->items()->create([
            'product_variant_id' => $variant->id,
            'description' => 'Coffee 1kg',
            'qty' => 10,
            'unit_price' => 5,
            'discount_amount' => 0,
            'vat_rate' => 22,
            'line_total' => 50,
        ]);

        $confirmed = app(ConfirmPurchaseInvoiceAction::class)->handle($invoice, $user);

        $this->assertSame(PurchaseInvoiceStatus::CONFIRMED, $confirmed->status);
    }
}
