<?php

namespace App\Http\Controllers\Api;

use App\DTOs\InvoiceDTO;
use App\DTOs\InvoiceItemDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmOrderRequest;
use App\Http\Requests\ParseOrderRequest;
use App\Http\Resources\InvoiceResource;
use App\Services\InvoiceService;
use App\Services\WhatsAppOrderParserService;
use Illuminate\Http\JsonResponse;

class OrderParserController extends Controller
{
    public function __construct(
        private readonly WhatsAppOrderParserService $parser,
        private readonly InvoiceService $invoiceService,
    ) {}

    public function parse(ParseOrderRequest $request): JsonResponse
    {
        $result = $this->parser->parse($request->validated('message'));

        return response()->json($result->toArray());
    }

    public function confirm(ConfirmOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        $items = array_map(fn (array $item) => new InvoiceItemDTO(
            description: $item['description'],
            quantity: (float) $item['quantity'],
            unit_price: (float) $item['unit_price'],
            vat_rate: (float) ($item['vat_rate'] ?? 5.00),
            product_id: $item['product_id'] ?? null,
        ), $data['items']);

        $dto = new InvoiceDTO(
            client_id: (int) $data['client_id'],
            issue_date: now()->toDateString(),
            due_date: $data['delivery_date'],
            items: $items,
            notes: $data['notes'] ?? "Created from WhatsApp order",
        );

        $invoice = $this->invoiceService->create($dto);

        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }
}
