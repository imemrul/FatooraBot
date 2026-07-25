<?php

namespace App\Http\Controllers\Api;

use App\DTOs\StockMovementDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockMovementRequest;
use App\Http\Resources\InventoryLevelResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $service) {}

    public function levels(Request $request): AnonymousResourceCollection
    {
        return InventoryLevelResource::collection(
            $this->service->getLevels($request->integer('per_page', 20))
        );
    }

    public function movements(Request $request): AnonymousResourceCollection
    {
        return StockMovementResource::collection(
            $this->service->getMovements($request->integer('per_page', 20))
        );
    }

    public function move(StoreStockMovementRequest $request): JsonResponse
    {
        $dto = StockMovementDTO::fromArray($request->validated());

        $movement = match ($dto->type) {
            'stock_in' => $this->service->stockIn($dto),
            'stock_out' => $this->service->stockOut($dto),
            'transfer' => $this->service->transfer($dto),
        };

        $movement->load(['product', 'warehouse', 'toWarehouse', 'creator']);

        return (new StockMovementResource($movement))
            ->response()
            ->setStatusCode(201);
    }

    public function alerts(): JsonResponse
    {
        $alerts = $this->service->getAlerts();

        return response()->json([
            'low_stock' => ProductResource::collection($alerts['low_stock']),
            'out_of_stock' => ProductResource::collection($alerts['out_of_stock']),
        ]);
    }
}
