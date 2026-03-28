<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class WarehouseController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('view-master-data');

        return WarehouseResource::collection(
            Warehouse::query()
                ->search(request('search'))
                ->orderBy('name')
                ->paginate()
        );
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        return (new WarehouseResource(Warehouse::query()->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Warehouse $warehouse): WarehouseResource
    {
        Gate::authorize('view-master-data');

        return new WarehouseResource($warehouse);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): WarehouseResource
    {
        $warehouse->update($request->validated());

        return new WarehouseResource($warehouse);
    }

    public function destroy(Request $request, Warehouse $warehouse): JsonResponse
    {
        Gate::authorize('manage-master-data');

        if (
            $warehouse->purchaseInvoices()->exists()
            || $warehouse->stockMovements()->exists()
            || $warehouse->stockCounts()->exists()
        ) {
            throw ValidationException::withMessages([
                'warehouse' => 'Warehouses linked to inventory documents or movements cannot be deleted.',
            ]);
        }

        $warehouse->delete();

        return response()->json(status: 204);
    }
}
