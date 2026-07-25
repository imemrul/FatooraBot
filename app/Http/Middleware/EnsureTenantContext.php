<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return response()->json(['message' => 'No tenant context.'], 403);
        }

        $isActive = Cache::remember(
            "company_active:{$user->company_id}",
            60,
            fn () => $user->company->is_active,
        );

        if (!$isActive) {
            return response()->json(['message' => 'Company account is deactivated.'], 403);
        }

        return $next($request);
    }
}
