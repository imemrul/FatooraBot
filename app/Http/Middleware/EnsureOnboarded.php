<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->company && !$user->company->isOnboarded()) {
            return response()->json([
                'message' => 'Please complete company setup first.',
                'redirect' => '/onboarding',
            ], 403);
        }

        return $next($request);
    }
}
