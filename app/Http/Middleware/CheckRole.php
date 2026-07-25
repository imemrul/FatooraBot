<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Usage: middleware('role:owner') or middleware('role:owner,accountant')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->hasAnyRole($roles)) {
            return response()->json([
                'message' => 'You do not have the required role to perform this action.',
            ], 403);
        }

        return $next($request);
    }
}
