<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\PurchaseInvoiceController as AdminPurchaseInvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->name('login.store');

    Route::middleware('admin.panel')->group(function (): void {
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');

        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
        Route::post('/products', [InventoryController::class, 'storeProduct'])->name('products.store');
        Route::patch('/products/{product}', [InventoryController::class, 'updateProduct'])->name('products.update');
        Route::delete('/products/{product}', [InventoryController::class, 'destroyProduct'])->name('products.destroy');
        Route::post('/variants', [InventoryController::class, 'storeVariant'])->name('variants.store');
        Route::patch('/variants/{variant}', [InventoryController::class, 'updateVariant'])->name('variants.update');
        Route::delete('/variants/{variant}', [InventoryController::class, 'destroyVariant'])->name('variants.destroy');
        Route::post('/suppliers', [InventoryController::class, 'storeSupplier'])->name('suppliers.store');
        Route::patch('/suppliers/{supplier}', [InventoryController::class, 'updateSupplier'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [InventoryController::class, 'destroySupplier'])->name('suppliers.destroy');
        Route::post('/warehouses', [InventoryController::class, 'storeWarehouse'])->name('warehouses.store');
        Route::patch('/warehouses/{warehouse}', [InventoryController::class, 'updateWarehouse'])->name('warehouses.update');
        Route::delete('/warehouses/{warehouse}', [InventoryController::class, 'destroyWarehouse'])->name('warehouses.destroy');
        Route::post('/stock-adjustments', [InventoryController::class, 'storeAdjustment'])->name('stock-adjustments.store');
        Route::post('/stock-counts', [InventoryController::class, 'storeCount'])->name('stock-counts.store');

        Route::get('/purchase-invoices', [AdminPurchaseInvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/purchase-invoices', [AdminPurchaseInvoiceController::class, 'store'])->name('invoices.store');
        Route::get('/purchase-invoices/{purchaseInvoice}', [AdminPurchaseInvoiceController::class, 'show'])->name('invoices.show');
        Route::patch('/purchase-invoices/{purchaseInvoice}', [AdminPurchaseInvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('/purchase-invoices/{purchaseInvoice}', [AdminPurchaseInvoiceController::class, 'destroy'])->name('invoices.destroy');
        Route::post('/purchase-invoices/{purchaseInvoice}/items', [AdminPurchaseInvoiceController::class, 'storeItem'])->name('invoices.items.store');
        Route::patch('/purchase-invoices/{purchaseInvoice}/items/{item}', [AdminPurchaseInvoiceController::class, 'updateItem'])->name('invoices.items.update');
        Route::delete('/purchase-invoices/{purchaseInvoice}/items/{item}', [AdminPurchaseInvoiceController::class, 'destroyItem'])->name('invoices.items.destroy');
        Route::post('/purchase-invoices/{purchaseInvoice}/confirm', [AdminPurchaseInvoiceController::class, 'confirm'])->name('invoices.confirm');
        Route::post('/purchase-invoices/{purchaseInvoice}/cancel', [AdminPurchaseInvoiceController::class, 'cancel'])->name('invoices.cancel');
    });
});
