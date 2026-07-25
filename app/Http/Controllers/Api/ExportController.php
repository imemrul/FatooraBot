<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function __construct(private readonly ExportService $service) {}

    public function invoices(Request $request) { return $this->service->exportInvoices($request->only('status', 'from', 'to')); }
    public function clients() { return $this->service->exportClients(); }
    public function products() { return $this->service->exportProducts(); }
    public function payments(Request $request) { return $this->service->exportPayments($request->only('from', 'to')); }
    public function expenses(Request $request) { return $this->service->exportExpenses($request->only('from', 'to')); }

    public function profitLoss(Request $request): JsonResponse
    {
        $request->validate(['from' => 'required|date', 'to' => 'required|date|after_or_equal:from']);
        return response()->json($this->service->profitLoss($request->user()->company_id, $request->from, $request->to));
    }
}
