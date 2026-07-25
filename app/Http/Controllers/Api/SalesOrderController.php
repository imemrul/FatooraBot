<?php

namespace App\Http\Controllers\Api;

use App\DTOs\SalesOrderDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalesOrderRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\SalesOrderResource;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SalesOrderController extends Controller
{
    public function __construct(private readonly SalesOrderService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return SalesOrderResource::collection(
            $this->service->list(
                $request->integer('per_page', 15),
                $request->string('status')->toString() ?: null,
            )
        );
    }

    public function store(StoreSalesOrderRequest $request): JsonResponse
    {
        $order = $this->service->create(
            SalesOrderDTO::fromArray($request->validated())
        );

        return (new SalesOrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function show(SalesOrder $salesOrder): SalesOrderResource
    {
        return new SalesOrderResource($this->service->find($salesOrder->id));
    }

    public function update(StoreSalesOrderRequest $request, SalesOrder $salesOrder): SalesOrderResource
    {
        $updated = $this->service->update(
            $salesOrder,
            SalesOrderDTO::fromArray($request->validated())
        );

        return new SalesOrderResource($updated);
    }

    public function destroy(SalesOrder $salesOrder): JsonResponse
    {
        $this->service->delete($salesOrder);

        return response()->json(null, 204);
    }

    public function confirm(SalesOrder $salesOrder): SalesOrderResource
    {
        return new SalesOrderResource($this->service->confirm($salesOrder));
    }

    public function deliver(SalesOrder $salesOrder): SalesOrderResource
    {
        return new SalesOrderResource($this->service->deliver($salesOrder));
    }

    public function cancel(SalesOrder $salesOrder): SalesOrderResource
    {
        return new SalesOrderResource($this->service->cancel($salesOrder));
    }

    public function convertToInvoice(SalesOrder $salesOrder): JsonResponse
    {
        $invoice = $this->service->convertToInvoice($salesOrder);

        return response()->json([
            'order' => new SalesOrderResource($salesOrder->fresh(['client', 'creator', 'warehouse', 'items', 'invoice'])),
            'invoice' => new InvoiceResource($invoice),
        ], 201);
    }
}
