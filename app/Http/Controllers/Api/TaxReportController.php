<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TaxReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxReportController extends Controller
{
    public function __construct(private readonly TaxReportService $service) {}

    public function vatReturn(Request $request): JsonResponse
    {
        $request->validate(['from' => 'required|date', 'to' => 'required|date|after_or_equal:from']);
        return response()->json($this->service->vatReturn($request->user()->company_id, $request->from, $request->to));
    }
}
