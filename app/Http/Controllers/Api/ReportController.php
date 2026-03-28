<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CalculateCurrentStockService;
use App\Services\InventoryValueReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function __construct(
        private readonly InventoryValueReportService $inventoryValueReportService,
        private readonly CalculateCurrentStockService $stockService,
    ) {
    }

    public function inventoryValue(): JsonResponse
    {
        Gate::authorize('view-reports');

        return response()->json([
            'data' => $this->inventoryValueReportService->handle(),
        ]);
    }

    public function stockByWarehouse(): JsonResponse
    {
        Gate::authorize('view-reports');

        return response()->json([
            'data' => $this->stockService->snapshot()->groupBy('warehouse_name'),
        ]);
    }

    public function lowStock(): JsonResponse
    {
        Gate::authorize('view-reports');

        return response()->json([
            'data' => $this->stockService->snapshot()->filter(
                fn ($row) => (float) $row->current_qty <= (float) $row->reorder_level
            )->values(),
        ]);
    }

    public function purchasesBySupplier(): JsonResponse
    {
        Gate::authorize('view-reports');

        $data = DB::table('purchase_invoices')
            ->join('suppliers', 'suppliers.id', '=', 'purchase_invoices.supplier_id')
            ->selectRaw('suppliers.id as supplier_id, suppliers.company_name, COUNT(*) as invoices_count, SUM(total_amount) as total_amount')
            ->where('status', 'confirmed')
            ->groupBy('suppliers.id', 'suppliers.company_name')
            ->orderByDesc('total_amount')
            ->get();

        return response()->json(['data' => $data]);
    }
}
