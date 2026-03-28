<?php

namespace App\Http\Controllers\Api;

use App\Actions\RecordStockCountAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockCountRequest;
use Illuminate\Http\JsonResponse;

class StockCountController extends Controller
{
    public function __construct(private readonly RecordStockCountAction $recordStockCountAction)
    {
    }

    public function store(StoreStockCountRequest $request): JsonResponse
    {
        $stockCount = $this->recordStockCountAction->handle($request->validated(), $request->user());

        return response()->json([
            'data' => [
                'id' => $stockCount->id,
                'warehouse_id' => $stockCount->warehouse_id,
                'product_variant_id' => $stockCount->product_variant_id,
                'counted_qty' => $stockCount->counted_qty,
                'system_qty' => $stockCount->system_qty,
                'difference_qty' => $stockCount->difference_qty,
            ],
        ], 201);
    }
}
