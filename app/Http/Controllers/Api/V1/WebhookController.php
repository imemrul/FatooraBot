<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\WebhookResource;
use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    private const VALID_EVENTS = [
        '*',
        'invoice.created', 'invoice.updated', 'invoice.paid',
        'customer.created', 'customer.updated',
        'product.created', 'product.updated',
        'inventory.stock_in', 'inventory.stock_out',
    ];

    public function index(Request $request): JsonResponse
    {
        $webhooks = Webhook::where('company_id', $request->user()->company_id)
            ->latest()
            ->get();

        return WebhookResource::collection($webhooks)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', 'in:' . implode(',', self::VALID_EVENTS)],
        ]);

        $secret = Str::random(64);

        $webhook = Webhook::create([
            'company_id' => $request->user()->company_id,
            'url' => $validated['url'],
            'secret' => $secret,
            'events' => $validated['events'],
            'is_active' => true,
        ]);

        return response()->json([
            'webhook' => (new WebhookResource($webhook))->resolve(),
            'secret' => $secret,
            'message' => 'Store this secret securely. It will not be shown again.',
        ], 201);
    }

    public function show(Webhook $webhook): WebhookResource
    {
        return new WebhookResource($webhook);
    }

    public function update(Request $request, Webhook $webhook): WebhookResource
    {
        $validated = $request->validate([
            'url' => ['sometimes', 'url', 'max:500'],
            'events' => ['sometimes', 'array', 'min:1'],
            'events.*' => ['string', 'in:' . implode(',', self::VALID_EVENTS)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $webhook->update($validated);

        if (isset($validated['is_active']) && $validated['is_active']) {
            $webhook->update(['failure_count' => 0]);
        }

        return new WebhookResource($webhook->fresh());
    }

    public function destroy(Webhook $webhook): JsonResponse
    {
        $webhook->delete();

        return response()->json(null, 204);
    }

    public function logs(Webhook $webhook, Request $request): JsonResponse
    {
        $logs = WebhookLog::where('webhook_id', $webhook->id)
            ->latest()
            ->paginate($request->integer('per_page', 25));

        return response()->json($logs);
    }

    public function events(): JsonResponse
    {
        return response()->json(['events' => self::VALID_EVENTS]);
    }
}
