<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AgingReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgingReportController extends Controller
{
    public function __construct(private readonly AgingReportService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->getAgingSummary($request->user()->company_id));
    }
}
