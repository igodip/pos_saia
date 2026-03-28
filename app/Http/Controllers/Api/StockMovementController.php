<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class StockMovementController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('view-stock');

        return StockMovementResource::collection(
            StockMovement::query()
                ->with(['warehouse', 'productVariant.product'])
                ->when(request('warehouse_id'), fn ($q, $warehouseId) => $q->where('warehouse_id', $warehouseId))
                ->when(request('product_variant_id'), fn ($q, $variantId) => $q->where('product_variant_id', $variantId))
                ->when(request('movement_type'), fn ($q, $type) => $q->where('movement_type', $type))
                ->orderByDesc('created_at')
                ->paginate()
        );
    }
}
