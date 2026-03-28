<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseInvoice;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\CalculateCurrentStockService;
use App\Services\InventoryValueReportService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly CalculateCurrentStockService $stockService,
        private readonly InventoryValueReportService $inventoryValueReportService,
    ) {
    }

    public function __invoke(): View
    {
        $summary = [
            'products' => Product::query()->count(),
            'variants' => ProductVariant::query()->count(),
            'suppliers' => Supplier::query()->count(),
            'warehouses' => Warehouse::query()->count(),
            'purchase_invoices' => PurchaseInvoice::query()->count(),
            'attachments' => Attachment::query()->count(),
            'stock_movements' => StockMovement::query()->count(),
        ];

        $statusBreakdown = PurchaseInvoice::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $inventoryValue = $this->inventoryValueReportService->handle()->sum('inventory_value');

        return view('admin.dashboard', [
            'summary' => $summary,
            'statusBreakdown' => $statusBreakdown,
            'lowStock' => $this->stockService->snapshot()->filter(
                fn ($row) => (float) $row->current_qty <= (float) $row->reorder_level
            )->take(8),
            'recentInvoices' => PurchaseInvoice::query()
                ->with(['supplier', 'warehouse'])
                ->latest('invoice_date')
                ->limit(8)
                ->get(),
            'recentMovements' => StockMovement::query()
                ->with(['warehouse', 'productVariant.product'])
                ->latest('created_at')
                ->limit(8)
                ->get(),
            'inventoryValue' => $inventoryValue,
        ]);
    }
}
