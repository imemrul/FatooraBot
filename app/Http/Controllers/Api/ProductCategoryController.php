<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Services\ProductCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function __construct(private readonly ProductCategoryService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(['categories' => $this->service->list()]);
    }

    public function all(): JsonResponse
    {
        return response()->json(['categories' => $this->service->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
        ]);
        return response()->json(['category' => $this->service->create($data)], 201);
    }

    public function update(Request $request, ProductCategory $productCategory): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);
        return response()->json(['category' => $this->service->update($productCategory, $data)]);
    }

    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        $this->service->delete($productCategory);
        return response()->json(['message' => 'Deleted.']);
    }
}
