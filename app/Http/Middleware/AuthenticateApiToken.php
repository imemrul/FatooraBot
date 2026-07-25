<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Scopes\TenantScope;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next, string $scope = '*'): Response
    {
        $bearer = $request->bearerToken();

        if (!$bearer) {
            return response()->json([
                'error' => 'unauthenticated',
                'message' => 'API token required. Pass Bearer token in Authorization header.',
            ], 401);
        }

        $token = ApiToken::findByPlainToken($bearer);

        if (!$token || !$token->isValid()) {
            return response()->json([
                'error' => 'invalid_token',
                'message' => 'Invalid or expired API token.',
            ], 401);
        }

        if ($scope !== '*' && !$token->hasScope($scope)) {
            return response()->json([
                'error' => 'insufficient_scope',
                'message' => "Token missing required scope: {$scope}",
            ], 403);
        }

        $key = 'api_token:' . $token->id;
        if (RateLimiter::tooManyAttempts($key, $token->rate_limit)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'error' => 'rate_limit_exceeded',
                'message' => 'Too many requests.',
                'retry_after' => $retryAfter,
            ], 429)->header('Retry-After', $retryAfter);
        }

        RateLimiter::hit($key, 60);

        $token->markUsed();

        TenantScope::setApiCompanyId($token->company_id);
        $request->setUserResolver(fn () => $token->creator);

        return $next($request);
    }
}
