<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProductVariantController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('view-master-data');

        return ProductVariantResource::collection(
            ProductVariant::query()
                ->with('product')
                ->search(request('search'))
                ->orderBy('variant_name')
                ->paginate()
        );
    }

    public function store(StoreProductVariantRequest $request): JsonResponse
    {
        $variant = ProductVariant::query()->create($request->validated());

        return (new ProductVariantResource($variant->load('product')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ProductVariant $productVariant): ProductVariantResource
    {
        Gate::authorize('view-master-data');

        return new ProductVariantResource($productVariant->load('product'));
    }

    public function update(UpdateProductVariantRequest $request, ProductVariant $productVariant): ProductVariantResource
    {
        $productVariant->update($request->validated());

        return new ProductVariantResource($productVariant->load('product'));
    }

    public function destroy(Request $request, ProductVariant $productVariant): JsonResponse
    {
        Gate::authorize('manage-master-data');

        if (
            $productVariant->invoiceItems()->exists()
            || $productVariant->stockMovements()->exists()
            || $productVariant->stockCounts()->exists()
        ) {
            throw ValidationException::withMessages([
                'product_variant' => 'This variant is already used in inventory history and cannot be deleted.',
            ]);
        }

        $productVariant->delete();

        return response()->json(status: 204);
    }
}
