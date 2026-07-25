<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(private readonly SuperAdminService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->listTenants(
                $request->integer('per_page', 20),
                $request->string('search')->toString() ?: null,
                $request->string('status')->toString() ?: null,
            )
        );
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->getTenantDetail($id));
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $company = $this->service->toggleTenantStatus($id);

        return response()->json([
            'message' => $company->is_active ? 'Tenant activated.' : 'Tenant deactivated.',
            'is_active' => $company->is_active,
        ]);
    }

    public function assignPlan(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['nullable', 'in:monthly,yearly'],
        ]);

        $subscription = $this->service->assignPlan(
            $id,
            $validated['plan_id'],
            $validated['billing_cycle'] ?? 'monthly',
        );

        return response()->json([
            'message' => 'Plan assigned.',
            'subscription' => $subscription->load('plan'),
        ]);
    }

    public function cancelSubscription(int $id): JsonResponse
    {
        $this->service->cancelSubscription($id);

        return response()->json(['message' => 'Subscription cancelled.']);
    }

    public function impersonate(Request $request, int $id): JsonResponse
    {
        $result = $this->service->startImpersonation($request->user(), $id);

        return response()->json([
            'message' => "Now impersonating {$result['company']->name}.",
            'token' => $result['token'],
            'user' => $result['user'],
            'company' => $result['company'],
        ]);
    }

    public function stopImpersonation(Request $request, int $id): JsonResponse
    {
        $this->service->endImpersonation($request->user(), $id);

        return response()->json(['message' => 'Impersonation ended.']);
    }
}
