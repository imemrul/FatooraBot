<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\CommandRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function __construct(private readonly CommandRouter $router) {}

    /**
     * Webhook verification (GET) — Meta sends this to verify the endpoint.
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Webhook receiver (POST) — Meta sends incoming messages here.
     */
    public function receive(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Extract message from Meta webhook payload
        $entry = $payload['entry'][0] ?? null;
        $changes = $entry['changes'][0] ?? null;
        $value = $changes['value'] ?? null;
        $messages = $value['messages'] ?? [];

        foreach ($messages as $msg) {
            $phone = $msg['from'] ?? null;
            if (!$phone) continue;

            $text = null;
            $buttonId = null;

            if ($msg['type'] === 'text') {
                $text = $msg['text']['body'] ?? '';
            } elseif ($msg['type'] === 'interactive') {
                $buttonId = $msg['interactive']['button_reply']['id']
                    ?? $msg['interactive']['list_reply']['id']
                    ?? null;
                $text = $buttonId;
            }

            if ($text || $buttonId) {
                // Dispatch to queue for async processing
                dispatch(function () use ($phone, $text, $buttonId) {
                    app(CommandRouter::class)->handle($phone, $text ?? '', $buttonId);
                })->afterCommit();
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Test endpoint — simulate a WhatsApp message (for development).
     */
    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        $this->router->handle($request->phone, $request->message);

        $lastReply = \App\Models\WhatsAppMessage::where('phone', \App\Models\WhatsAppPhone::normalize($request->phone))
            ->where('direction', 'outbound')
            ->latest()
            ->first();

        return response()->json([
            'reply' => $lastReply?->body,
            'type' => $lastReply?->message_type,
            'status' => $lastReply?->status,
        ]);
    }
}
