<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\ProductDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $service,
        private readonly WebhookService $webhooks,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = Product::latest()
            ->when($request->has('sku'), fn ($q) => $q->where('sku', $request->input('sku')))
            ->when($request->has('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->paginate($request->integer('per_page', 25));

        return ProductResource::collection($products)->response();
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ]);

        $product = $this->service->create(ProductDTO::fromArray($validated));

        $this->webhooks->dispatch(
            $request->user()->company_id,
            'product.created',
            (new ProductResource($product))->resolve(),
        );

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function update(Request $request, Product $product): ProductResource
    {
        $validated = $request->validate([
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ]);

        $updated = $this->service->update($product, ProductDTO::fromArray($validated));

        $this->webhooks->dispatch(
            $request->user()->company_id,
            'product.updated',
            (new ProductResource($updated))->resolve(),
        );

        return new ProductResource($updated);
    }
}
