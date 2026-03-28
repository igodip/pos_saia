<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_can_login_and_view_admin_dashboard(): void
    {
        $response = $this->post('/admin/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/admin');

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee('Stato attuale del backend')
            ->assertSee('POS Saia Admin');
    }

    public function test_admin_can_register_stock_adjustment_from_panel(): void
    {
        $this->post('/admin/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

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

        $this->post('/admin/stock-adjustments', [
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'movement_type' => 'adjustment_in',
            'qty' => 6,
            'unit_cost' => 3,
            'notes' => 'Admin panel test',
        ])->assertRedirect();

        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'movement_type' => 'adjustment_in',
            'created_by' => 1,
        ]);
    }

    public function test_admin_can_create_and_confirm_purchase_invoice_from_panel(): void
    {
        $this->post('/admin/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $supplier = Supplier::query()->create(['company_name' => 'Roasters']);
        $warehouse = Warehouse::query()->create(['name' => 'Main', 'code' => 'MAIN']);
        $product = Product::query()->create([
            'sku' => 'P2',
            'name' => 'Coffee',
            'vat_rate' => 22,
            'default_cost' => 3,
            'default_price' => 8,
            'reorder_level' => 1,
            'is_active' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'P2-1KG',
            'variant_name' => '1kg',
            'default_cost' => 3,
            'default_price' => 8,
            'is_active' => true,
        ]);

        $createResponse = $this->post('/admin/purchase-invoices', [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'invoice_number' => 'INV-WEB-001',
            'invoice_date' => '2026-03-28',
            'taxable_amount' => 100,
            'vat_amount' => 22,
            'total_amount' => 122,
        ]);

        $invoice = PurchaseInvoice::query()->firstOrFail();

        $createResponse->assertRedirect(route('admin.invoices.show', $invoice));

        $this->post("/admin/purchase-invoices/{$invoice->id}/items", [
            'product_variant_id' => $variant->id,
            'description' => 'Coffee 1kg',
            'qty' => 10,
            'unit_price' => 5,
            'discount_amount' => 0,
            'vat_rate' => 22,
            'line_total' => 50,
        ])->assertRedirect();

        $this->post("/admin/purchase-invoices/{$invoice->id}/confirm")
            ->assertRedirect();

        $this->assertDatabaseHas('purchase_invoices', [
            'id' => $invoice->id,
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'reference_table' => 'purchase_invoices',
            'reference_id' => $invoice->id,
            'movement_type' => 'purchase_load',
        ]);
    }
}
