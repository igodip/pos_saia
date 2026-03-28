<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\PurchaseInvoiceAttachmentController;
use App\Http\Controllers\Api\PurchaseInvoiceConfirmationController;
use App\Http\Controllers\Api\PurchaseInvoiceController;
use App\Http\Controllers\Api\PurchaseInvoiceItemController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StockAdjustmentController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\StockCountController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:api');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy']);

    Route::apiResource('products', ProductController::class)->only(['store', 'index', 'show', 'update', 'destroy']);
    Route::apiResource('product-variants', ProductVariantController::class)->only(['store', 'index', 'show', 'update', 'destroy']);
    Route::apiResource('suppliers', SupplierController::class)->only(['store', 'index', 'show', 'update', 'destroy']);
    Route::apiResource('warehouses', WarehouseController::class)->only(['store', 'index', 'show', 'update', 'destroy']);
    Route::apiResource('purchase-invoices', PurchaseInvoiceController::class)->only(['store', 'index', 'show', 'update', 'destroy']);

    Route::post('/purchase-invoices/{purchaseInvoice}/items', [PurchaseInvoiceItemController::class, 'store']);
    Route::patch('/purchase-invoices/{purchaseInvoice}/items/{item}', [PurchaseInvoiceItemController::class, 'update']);
    Route::delete('/purchase-invoices/{purchaseInvoice}/items/{item}', [PurchaseInvoiceItemController::class, 'destroy']);
    Route::post('/purchase-invoices/{purchaseInvoice}/confirm', [PurchaseInvoiceConfirmationController::class, 'confirm']);
    Route::post('/purchase-invoices/{purchaseInvoice}/cancel', [PurchaseInvoiceConfirmationController::class, 'cancel']);
    Route::post('/purchase-invoices/{purchaseInvoice}/attachments', [PurchaseInvoiceAttachmentController::class, 'store']);

    Route::get('/stock', [StockController::class, 'index']);
    Route::get('/stock-movements', [StockMovementController::class, 'index']);
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store']);
    Route::post('/stock-counts', [StockCountController::class, 'store']);

    Route::get('/reports/inventory-value', [ReportController::class, 'inventoryValue']);
    Route::get('/reports/stock-by-warehouse', [ReportController::class, 'stockByWarehouse']);
    Route::get('/reports/low-stock', [ReportController::class, 'lowStock']);
    Route::get('/reports/purchases-by-supplier', [ReportController::class, 'purchasesBySupplier']);
});
