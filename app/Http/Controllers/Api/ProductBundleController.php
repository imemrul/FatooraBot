<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductBundle;
use App\Services\ProductBundleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductBundleController extends Controller
{
    public function __construct(private readonly ProductBundleService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(['bundles' => $this->service->list()]);
    }

    public function store(Request $request): JsonResponse
    {
        $cid = $request->user()->company_id;
        $request->validate([
            'name' => 'required|string|max:255', 'sku' => 'nullable|string|max:50',
            'description' => 'nullable|string', 'bundle_price' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => "required|exists:products,id,company_id,{$cid}",
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);
        $bundle = $this->service->create($request->except('items'), $request->items);
        return response()->json(['bundle' => $bundle], 201);
    }

    public function update(Request $request, ProductBundle $productBundle): JsonResponse
    {
        $cid = $request->user()->company_id;
        $request->validate([
            'name' => 'required|string|max:255', 'sku' => 'nullable|string|max:50',
            'description' => 'nullable|string', 'bundle_price' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => "required|exists:products,id,company_id,{$cid}",
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);
        $bundle = $this->service->update($productBundle, $request->except('items'), $request->items);
        return response()->json(['bundle' => $bundle]);
    }

    public function destroy(ProductBundle $productBundle): JsonResponse
    {
        $this->service->delete($productBundle);
        return response()->json(['message' => 'Deleted.']);
    }

    public function expand(ProductBundle $productBundle): JsonResponse
    {
        return response()->json(['items' => $this->service->expand($productBundle)]);
    }
}
