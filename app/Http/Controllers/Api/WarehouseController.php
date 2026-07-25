<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WarehouseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return WarehouseResource::collection(
            Warehouse::latest()->paginate($request->integer('per_page', 50))
        );
    }

    public function all(): JsonResponse
    {
        return response()->json([
            'data' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name', 'location']),
        ]);
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $warehouse = Warehouse::create($request->validated());

        return (new WarehouseResource($warehouse))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Warehouse $warehouse): WarehouseResource
    {
        return new WarehouseResource($warehouse);
    }

    public function update(StoreWarehouseRequest $request, Warehouse $warehouse): WarehouseResource
    {
        $warehouse->update($request->validated());

        return new WarehouseResource($warehouse->fresh());
    }

    public function destroy(Warehouse $warehouse): JsonResponse
    {
        if ($warehouse->inventoryLevels()->where('quantity', '>', 0)->exists()) {
            return response()->json([
                'message' => 'Cannot delete warehouse with stock. Transfer stock first.',
            ], 422);
        }

        $warehouse->delete();

        return response()->json(null, 204);
    }
}
