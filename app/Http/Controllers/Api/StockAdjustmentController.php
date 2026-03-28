<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateStockAdjustmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Http\Resources\StockMovementResource;
use Illuminate\Http\JsonResponse;

class StockAdjustmentController extends Controller
{
    public function __construct(private readonly CreateStockAdjustmentAction $createStockAdjustmentAction)
    {
    }

    public function store(StoreStockAdjustmentRequest $request): JsonResponse
    {
        return (new StockMovementResource(
            $this->createStockAdjustmentAction->handle($request->validated(), $request->user())
        ))->response()->setStatusCode(201);
    }
}
