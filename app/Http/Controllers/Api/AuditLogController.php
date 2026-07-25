<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user')
            ->latest('created_at');

        if ($request->filled('model')) {
            $map = [
                'invoice' => 'App\\Models\\Invoice',
                'product' => 'App\\Models\\Product',
                'client' => 'App\\Models\\Client',
                'payment' => 'App\\Models\\InvoicePayment',
                'stock_movement' => 'App\\Models\\StockMovement',
                'sales_order' => 'App\\Models\\SalesOrder',
            ];
            $type = $map[$request->input('model')] ?? $request->input('model');
            $query->where('auditable_type', $type);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->input('to') . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('auditable_label', 'ilike', "%{$s}%")
                    ->orWhere('user_name', 'ilike', "%{$s}%");
            });
        }

        $logs = $query->paginate($request->integer('per_page', 30));

        $logs->getCollection()->transform(fn (AuditLog $log) => [
            'id' => $log->id,
            'user_id' => $log->user_id,
            'user_name' => $log->user_name,
            'action' => $log->action,
            'model' => $log->model_name,
            'model_id' => $log->auditable_id,
            'label' => $log->auditable_label,
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
            'changed_fields' => $log->changed_fields,
            'ip_address' => $log->ip_address,
            'created_at' => $log->created_at->toIso8601String(),
        ]);

        return response()->json($logs);
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        return response()->json([
            'id' => $auditLog->id,
            'user_id' => $auditLog->user_id,
            'user_name' => $auditLog->user_name,
            'action' => $auditLog->action,
            'model' => $auditLog->model_name,
            'model_id' => $auditLog->auditable_id,
            'label' => $auditLog->auditable_label,
            'old_values' => $auditLog->old_values,
            'new_values' => $auditLog->new_values,
            'changed_fields' => $auditLog->changed_fields,
            'ip_address' => $auditLog->ip_address,
            'user_agent' => $auditLog->user_agent,
            'created_at' => $auditLog->created_at->toIso8601String(),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $today = AuditLog::where('company_id', $companyId)
            ->whereDate('created_at', now())
            ->count();

        $byAction = AuditLog::where('company_id', $companyId)
            ->selectRaw("action, count(*) as count")
            ->groupBy('action')
            ->pluck('count', 'action');

        $byModel = AuditLog::where('company_id', $companyId)
            ->selectRaw("auditable_type, count(*) as count")
            ->groupBy('auditable_type')
            ->get()
            ->mapWithKeys(fn ($r) => [class_basename($r->auditable_type) => $r->count]);

        $topUsers = AuditLog::where('company_id', $companyId)
            ->whereNotNull('user_id')
            ->selectRaw("user_id, user_name, count(*) as count")
            ->groupBy('user_id', 'user_name')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        return response()->json([
            'today_count' => $today,
            'by_action' => $byAction,
            'by_model' => $byModel,
            'top_users' => $topUsers,
        ]);
    }
}
