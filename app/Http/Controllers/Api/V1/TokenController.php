<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tokens = ApiToken::where('company_id', $request->user()->company_id)
            ->latest()
            ->get()
            ->map(fn (ApiToken $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'scopes' => $t->scopes,
                'rate_limit' => $t->rate_limit,
                'last_used_at' => $t->last_used_at?->toIso8601String(),
                'expires_at' => $t->expires_at?->toIso8601String(),
                'is_active' => $t->is_active,
                'created_at' => $t->created_at->toIso8601String(),
            ]);

        return response()->json(['data' => $tokens]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', 'in:products.read,products.write,customers.read,customers.write,invoices.read,invoices.write,inventory.read,inventory.write,webhooks.manage,*'],
            'rate_limit' => ['nullable', 'integer', 'min:10', 'max:1000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $tokenData = ApiToken::generateToken();

        $token = ApiToken::create([
            'company_id' => $request->user()->company_id,
            'created_by' => $request->user()->id,
            'name' => $validated['name'],
            'token' => $tokenData['hash'],
            'scopes' => $validated['scopes'] ?? ['*'],
            'rate_limit' => $validated['rate_limit'] ?? 60,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return response()->json([
            'token' => $tokenData['plain'],
            'id' => $token->id,
            'name' => $token->name,
            'scopes' => $token->scopes,
            'message' => 'Store this token securely. It will not be shown again.',
        ], 201);
    }

    public function destroy(ApiToken $token, Request $request): JsonResponse
    {
        if ($token->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $token->update(['is_active' => false]);

        return response()->json(['message' => 'Token revoked.']);
    }
}
