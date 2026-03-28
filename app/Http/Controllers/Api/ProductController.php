<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('view-master-data');

        return ProductResource::collection(
            Product::query()
                ->with('variants')
                ->search(request('search'))
                ->orderBy('name')
                ->paginate()
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::query()->create($request->validated());

        return (new ProductResource($product->load('variants')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        Gate::authorize('view-master-data');

        return new ProductResource($product->load('variants'));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return new ProductResource($product->load('variants'));
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        Gate::authorize('manage-master-data');

        if ($product->variants()->exists()) {
            throw ValidationException::withMessages([
                'product' => 'Delete product variants before deleting the product.',
            ]);
        }

        $product->delete();

        return response()->json(status: 204);
    }
}
