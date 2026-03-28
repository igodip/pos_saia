<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_login_with_hardcoded_backend_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'admin123',
            'device_name' => 'test-suite',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.username', 'admin')
            ->assertJsonPath('user.role', 'admin');

        $this->assertDatabaseHas('users', [
            'email' => 'admin@pos-saia.local',
            'role' => UserRole::ADMIN->value,
        ]);
    }

    public function test_invalid_hardcoded_backend_credentials_are_rejected(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['username']);
    }

    public function test_can_create_product(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::ACCOUNTING]));

        $response = $this->postJson('/api/products', [
            'sku' => 'PRD-001',
            'name' => 'Arabica Coffee',
            'vat_rate' => 22,
            'default_cost' => 4.50,
            'default_price' => 9.90,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $response->assertCreated()->assertJsonPath('data.sku', 'PRD-001');
    }

    public function test_can_create_supplier(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::ACCOUNTING]));

        $response = $this->postJson('/api/suppliers', [
            'company_name' => 'Coffee Beans SRL',
            'vat_number' => 'IT12345678901',
        ]);

        $response->assertCreated()->assertJsonPath('data.company_name', 'Coffee Beans SRL');
    }

    public function test_can_create_purchase_invoice_draft_and_add_items(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::ACCOUNTING]));

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

        $invoiceResponse = $this->postJson('/api/purchase-invoices', [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'invoice_number' => 'INV-100',
            'invoice_date' => '2026-03-19',
            'taxable_amount' => 100,
            'vat_amount' => 22,
            'total_amount' => 122,
        ]);

        $invoiceResponse->assertCreated();
        $invoiceId = $invoiceResponse->json('data.id');

        $itemResponse = $this->postJson("/api/purchase-invoices/{$invoiceId}/items", [
            'product_variant_id' => $variant->id,
            'description' => 'Coffee 1kg',
            'qty' => 10,
            'unit_price' => 5,
            'discount_amount' => 0,
            'vat_rate' => 22,
            'line_total' => 50,
        ]);

        $itemResponse->assertCreated()->assertJsonPath('data.qty', '10.000');
    }

    public function test_confirm_purchase_invoice_generates_stock_movements_and_blocks_double_confirm(): void
    {
        $user = User::factory()->create(['role' => UserRole::WAREHOUSE]);
        Sanctum::actingAs($user);

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
            'invoice_number' => 'INV-101',
            'invoice_date' => '2026-03-19',
            'status' => 'draft',
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

        $confirmResponse = $this->postJson("/api/purchase-invoices/{$invoice->id}/confirm");
        $confirmResponse->assertOk()->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('stock_movements', [
            'reference_table' => 'purchase_invoices',
            'reference_id' => $invoice->id,
            'movement_type' => 'purchase_load',
        ]);

        $secondConfirm = $this->postJson("/api/purchase-invoices/{$invoice->id}/confirm");
        $secondConfirm->assertUnprocessable();
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_stock_adjustment_and_stock_snapshot_work(): void
    {
        $user = User::factory()->create(['role' => UserRole::WAREHOUSE]);
        Sanctum::actingAs($user);

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

        $adjustment = $this->postJson('/api/stock-adjustments', [
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'movement_type' => 'adjustment_in',
            'qty' => 4,
            'unit_cost' => 3,
            'notes' => 'Initial correction',
        ]);

        $adjustment->assertCreated()->assertJsonPath('data.movement_type', 'adjustment_in');

        $stock = $this->getJson('/api/stock');
        $stock->assertOk()->assertJsonPath('data.0.current_qty', 4);
    }

    public function test_viewer_cannot_manage_products(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::VIEWER]));

        $response = $this->postJson('/api/products', [
            'sku' => 'PRD-001',
            'name' => 'Arabica Coffee',
            'vat_rate' => 22,
        ]);

        $response->assertForbidden();
    }

    public function test_can_delete_unused_master_data_records(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::ACCOUNTING]));

        $product = Product::query()->create([
            'sku' => 'PRD-DELETE',
            'name' => 'Unused Product',
            'vat_rate' => 22,
            'default_cost' => 1,
            'default_price' => 2,
            'reorder_level' => 0,
            'is_active' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'PRD-DELETE-V1',
            'variant_name' => 'Base',
            'default_cost' => 1,
            'default_price' => 2,
            'is_active' => true,
        ]);
        $supplier = Supplier::query()->create(['company_name' => 'Unused Supplier']);
        $warehouse = Warehouse::query()->create(['name' => 'Unused Warehouse', 'code' => 'UNUSED']);

        $this->deleteJson("/api/product-variants/{$variant->id}")->assertNoContent();
        $this->deleteJson("/api/products/{$product->id}")->assertNoContent();
        $this->deleteJson("/api/suppliers/{$supplier->id}")->assertNoContent();
        $this->deleteJson("/api/warehouses/{$warehouse->id}")->assertNoContent();

        $this->assertSoftDeleted('product_variants', ['id' => $variant->id]);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
        $this->assertSoftDeleted('warehouses', ['id' => $warehouse->id]);
    }

    public function test_cannot_delete_variant_with_inventory_history(): void
    {
        $user = User::factory()->create(['role' => UserRole::WAREHOUSE]);
        Sanctum::actingAs($user);

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

        $this->postJson('/api/stock-adjustments', [
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'movement_type' => 'adjustment_in',
            'qty' => 2,
            'unit_cost' => 3,
        ])->assertCreated();

        $this->deleteJson("/api/product-variants/{$variant->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_variant']);
    }

    public function test_can_delete_draft_purchase_invoice_and_its_items(): void
    {
        $user = User::factory()->create(['role' => UserRole::ACCOUNTING]);
        Sanctum::actingAs($user);

        $supplier = Supplier::query()->create(['company_name' => 'Draft Supplier']);
        $warehouse = Warehouse::query()->create(['name' => 'Draft Warehouse', 'code' => 'DRAFT']);
        $product = Product::query()->create([
            'sku' => 'P3',
            'name' => 'Coffee',
            'vat_rate' => 22,
            'default_cost' => 3,
            'default_price' => 8,
            'reorder_level' => 1,
            'is_active' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'P3-1KG',
            'variant_name' => '1kg',
            'default_cost' => 3,
            'default_price' => 8,
            'is_active' => true,
        ]);

        $invoice = PurchaseInvoice::query()->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'invoice_number' => 'INV-DELETE',
            'invoice_date' => '2026-03-28',
            'status' => 'draft',
            'currency' => 'EUR',
            'taxable_amount' => 50,
            'vat_amount' => 11,
            'total_amount' => 61,
            'created_by' => $user->id,
        ]);
        $item = $invoice->items()->create([
            'product_variant_id' => $variant->id,
            'description' => 'Coffee 1kg',
            'qty' => 5,
            'unit_price' => 5,
            'discount_amount' => 0,
            'vat_rate' => 22,
            'line_total' => 25,
        ]);

        $this->deleteJson("/api/purchase-invoices/{$invoice->id}/items/{$item->id}")->assertNoContent();

        $this->assertDatabaseMissing('purchase_invoice_items', ['id' => $item->id]);

        $invoice->items()->create([
            'product_variant_id' => $variant->id,
            'description' => 'Coffee 1kg',
            'qty' => 5,
            'unit_price' => 5,
            'discount_amount' => 0,
            'vat_rate' => 22,
            'line_total' => 25,
        ]);

        $this->deleteJson("/api/purchase-invoices/{$invoice->id}")->assertNoContent();

        $this->assertDatabaseMissing('purchase_invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('purchase_invoice_items', ['purchase_invoice_id' => $invoice->id]);
    }
}
