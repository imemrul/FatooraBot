<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\SuperAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function __construct(private readonly SuperAdminService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => Plan::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', 'unique:plans,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'max_users' => ['required', 'integer', 'min:1'],
            'max_invoices_per_month' => ['required', 'integer', 'min:1'],
            'max_products' => ['required', 'integer', 'min:1'],
            'max_warehouses' => ['required', 'integer', 'min:1'],
            'max_api_tokens' => ['nullable', 'integer', 'min:0'],
            'feature_whatsapp_parser' => ['nullable', 'boolean'],
            'feature_api_access' => ['nullable', 'boolean'],
            'feature_webhooks' => ['nullable', 'boolean'],
            'feature_audit_log' => ['nullable', 'boolean'],
            'feature_pdf_invoices' => ['nullable', 'boolean'],
            'feature_payment_reminders' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $plan = Plan::create($validated);

        return response()->json(['data' => $plan], 201);
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'price_monthly' => ['sometimes', 'numeric', 'min:0'],
            'price_yearly' => ['sometimes', 'numeric', 'min:0'],
            'max_users' => ['sometimes', 'integer', 'min:1'],
            'max_invoices_per_month' => ['sometimes', 'integer', 'min:1'],
            'max_products' => ['sometimes', 'integer', 'min:1'],
            'max_warehouses' => ['sometimes', 'integer', 'min:1'],
            'max_api_tokens' => ['nullable', 'integer', 'min:0'],
            'feature_whatsapp_parser' => ['nullable', 'boolean'],
            'feature_api_access' => ['nullable', 'boolean'],
            'feature_webhooks' => ['nullable', 'boolean'],
            'feature_audit_log' => ['nullable', 'boolean'],
            'feature_pdf_invoices' => ['nullable', 'boolean'],
            'feature_payment_reminders' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $plan->update($validated);

        return response()->json(['data' => $plan->fresh()]);
    }

    public function destroy(Plan $plan): JsonResponse
    {
        if ($plan->subscriptions()->active()->exists()) {
            return response()->json(['message' => 'Cannot delete plan with active subscriptions.'], 422);
        }

        $plan->delete();

        return response()->json(null, 204);
    }

    public function impersonationLogs(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->getImpersonationLogs($request->integer('per_page', 20))
        );
    }
}
