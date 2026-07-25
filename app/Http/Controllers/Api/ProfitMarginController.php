<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProfitMarginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfitMarginController extends Controller
{
    public function __construct(private readonly ProfitMarginService $service) {}

    public function products(Request $request): JsonResponse
    {
        return response()->json(['products' => $this->service->productMargins($request->user()->company_id)]);
    }

    public function invoices(Request $request): JsonResponse
    {
        return response()->json($this->service->invoiceMargins(
            $request->user()->company_id,
            $request->query('from'),
            $request->query('to'),
        ));
    }
}
