<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('view-master-data');

        return SupplierResource::collection(
            Supplier::query()
                ->search(request('search'))
                ->orderBy('company_name')
                ->paginate()
        );
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        return (new SupplierResource(Supplier::query()->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Supplier $supplier): SupplierResource
    {
        Gate::authorize('view-master-data');

        return new SupplierResource($supplier);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $supplier->update($request->validated());

        return new SupplierResource($supplier);
    }

    public function destroy(Request $request, Supplier $supplier): JsonResponse
    {
        Gate::authorize('manage-master-data');

        if ($supplier->purchaseInvoices()->exists()) {
            throw ValidationException::withMessages([
                'supplier' => 'Suppliers linked to purchase invoices cannot be deleted.',
            ]);
        }

        $supplier->delete();

        return response()->json(status: 204);
    }
}
