<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockSnapshotResource;
use App\Services\CalculateCurrentStockService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class StockController extends Controller
{
    public function __construct(private readonly CalculateCurrentStockService $stockService)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('view-stock');

        return StockSnapshotResource::collection(
            $this->stockService->snapshot(array_filter([
                'warehouse_id' => request('warehouse_id'),
                'product_variant_id' => request('product_variant_id'),
            ]))
        );
    }
}
