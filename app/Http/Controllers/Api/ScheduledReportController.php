<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduledReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduledReportController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['reports' => ScheduledReport::with('creator:id,name')->latest()->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'report_type' => 'required|in:sales_summary,expense_summary,profit_loss,aging_report,vat_summary',
            'frequency' => 'required|in:weekly,monthly',
            'email_to' => 'required|email',
        ]);
        $data['created_by'] = $request->user()->id;
        $data['next_send_at'] = $data['frequency'] === 'weekly' ? now()->addWeek() : now()->addMonth();
        return response()->json(['report' => ScheduledReport::create($data)], 201);
    }

    public function update(Request $request, ScheduledReport $scheduledReport): JsonResponse
    {
        $data = $request->validate([
            'report_type' => 'required|in:sales_summary,expense_summary,profit_loss,aging_report,vat_summary',
            'frequency' => 'required|in:weekly,monthly',
            'email_to' => 'required|email',
            'is_active' => 'nullable|boolean',
        ]);
        $scheduledReport->update($data);
        return response()->json(['report' => $scheduledReport->fresh()]);
    }

    public function destroy(ScheduledReport $scheduledReport): JsonResponse
    {
        $scheduledReport->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
