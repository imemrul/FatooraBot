<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ImpersonationLog;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Scopes\TenantScope;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminService
{
    // ── Platform Dashboard ──

    public function getPlatformStats(): array
    {
        $totalCompanies = Company::count();
        $activeCompanies = Company::where('is_active', true)->count();
        $totalUsers = User::withoutGlobalScopes()->count();
        $totalInvoices = Invoice::withoutGlobalScopes()->count();

        $monthlyRevenue = Invoice::withoutGlobalScopes()
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereYear('issue_date', now()->year)
            ->whereMonth('issue_date', now()->month)
            ->sum('total');

        $totalCollected = Invoice::withoutGlobalScopes()
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->sum('paid_amount');

        // Growth: new companies this month vs last month
        $thisMonth = Company::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $lastMonth = Company::whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->count();
        $growthPct = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : 0;

        // Subscriptions by plan
        $planBreakdown = Subscription::active()
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->selectRaw('plans.name, plans.slug, count(*) as count')
            ->groupBy('plans.name', 'plans.slug')
            ->get();

        // MRR calculation
        $mrr = Subscription::active()
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->selectRaw("SUM(CASE WHEN subscriptions.billing_cycle = 'monthly' THEN plans.price_monthly WHEN subscriptions.billing_cycle = 'yearly' THEN plans.price_yearly / 12 ELSE 0 END) as mrr")
            ->value('mrr');

        // Tenant growth trend (last 6 months)
        $growthTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $growthTrend[] = [
                'month' => $m->format('M'),
                'count' => Company::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count(),
            ];
        }

        return [
            'total_companies' => $totalCompanies,
            'active_companies' => $activeCompanies,
            'total_users' => $totalUsers,
            'total_invoices' => $totalInvoices,
            'platform_revenue' => round((float) $monthlyRevenue, 2),
            'total_collected' => round((float) $totalCollected, 2),
            'mrr' => round((float) ($mrr ?? 0), 2),
            'new_companies_this_month' => $thisMonth,
            'growth_pct' => $growthPct,
            'plan_breakdown' => $planBreakdown,
            'growth_trend' => $growthTrend,
        ];
    }

    // ── Tenant Management ──

    public function listTenants(int $perPage = 20, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        $query = Company::withCount(['users', 'invoices', 'clients', 'products'])
            ->with('subscription.plan')
            ->latest();

        if ($search) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'ilike', "%{$escaped}%")
                    ->orWhere('email', 'ilike', "%{$escaped}%");
            });
        }

        if ($status === 'active') $query->where('is_active', true);
        if ($status === 'inactive') $query->where('is_active', false);

        return $query->paginate($perPage);
    }

    public function getTenantDetail(int $companyId): array
    {
        $company = Company::withCount(['users', 'invoices', 'clients', 'products'])
            ->with('subscription.plan')
            ->findOrFail($companyId);

        $users = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->select('id', 'name', 'email', 'is_active', 'created_at')
            ->get();

        $invoiceStats = Invoice::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue,
                COALESCE(SUM(total), 0) as total_value,
                COALESCE(SUM(paid_amount), 0) as total_paid
            ")
            ->first();

        return [
            'company' => $company,
            'users' => $users,
            'invoice_stats' => $invoiceStats,
        ];
    }

    public function toggleTenantStatus(int $companyId): Company
    {
        $company = Company::findOrFail($companyId);
        $company->update(['is_active' => !$company->is_active]);

        return $company->fresh();
    }

    // ── User Management ──

    public function listAllUsers(int $perPage = 20, ?string $search = null): LengthAwarePaginator
    {
        $query = User::withoutGlobalScopes()
            ->with('company:id,name')
            ->latest();

        if ($search) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'ilike', "%{$escaped}%")
                    ->orWhere('email', 'ilike', "%{$escaped}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function toggleUserStatus(int $userId): User
    {
        $user = User::withoutGlobalScopes()->findOrFail($userId);
        $user->update(['is_active' => !$user->is_active]);

        return $user->fresh();
    }

    public function resetUserPassword(int $userId, string $newPassword): void
    {
        $user = User::withoutGlobalScopes()->findOrFail($userId);
        $user->update(['password' => Hash::make($newPassword)]);
    }

    // ── Impersonation ──

    public function startImpersonation(User $superAdmin, int $companyId): array
    {
        $company = Company::findOrFail($companyId);

        // Find the owner of the target company
        $owner = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereHas('roles', fn ($q) => $q->where('name', 'owner'))
            ->first();

        if (!$owner) {
            $owner = User::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->first();
        }

        if (!$owner) {
            throw new \RuntimeException('No active user found in this company.');
        }

        ImpersonationLog::create([
            'super_admin_id' => $superAdmin->id,
            'company_id' => $companyId,
            'action' => 'started',
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        // Create a temporary token for the impersonated user
        $token = $owner->createToken('impersonation')->plainTextToken;

        return [
            'token' => $token,
            'user' => $owner->load('company'),
            'company' => $company,
        ];
    }

    public function endImpersonation(User $superAdmin, int $companyId): void
    {
        ImpersonationLog::create([
            'super_admin_id' => $superAdmin->id,
            'company_id' => $companyId,
            'action' => 'ended',
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    // ── Subscription Management ──

    public function listPlans(): \Illuminate\Database\Eloquent\Collection
    {
        return Plan::where('is_active', true)->orderBy('sort_order')->get();
    }

    public function assignPlan(int $companyId, int $planId, string $cycle = 'monthly'): Subscription
    {
        $plan = Plan::findOrFail($planId);

        // Cancel existing active subscription
        Subscription::where('company_id', $companyId)
            ->active()
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return Subscription::create([
            'company_id' => $companyId,
            'plan_id' => $planId,
            'billing_cycle' => $cycle,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => $cycle === 'yearly' ? now()->addYear() : now()->addMonth(),
        ]);
    }

    public function cancelSubscription(int $companyId): void
    {
        Subscription::where('company_id', $companyId)
            ->active()
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }

    public function getImpersonationLogs(int $perPage = 20): LengthAwarePaginator
    {
        return ImpersonationLog::with(['superAdmin:id,name,email', 'company:id,name'])
            ->latest('created_at')
            ->paginate($perPage);
    }
}
