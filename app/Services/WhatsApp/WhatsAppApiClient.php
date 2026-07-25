<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppApiClient
{
    private string $apiUrl;
    private string $token;
    private string $phoneNumberId;

    public function __construct()
    {
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', '');
        $this->token = config('services.whatsapp.token', '');
        $this->apiUrl = "https://graph.facebook.com/v21.0/{$this->phoneNumberId}/messages";
    }

    public function sendText(string $to, string $text, ?int $companyId = null): ?string
    {
        return $this->send($to, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $text],
        ], $text, 'text', $companyId);
    }

    public function sendButtons(string $to, string $body, array $buttons, ?int $companyId = null): ?string
    {
        $btnPayload = array_map(fn ($b) => [
            'type' => 'reply',
            'reply' => ['id' => $b['id'], 'title' => substr($b['title'], 0, 20)],
        ], array_slice($buttons, 0, 3));

        return $this->send($to, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $body],
                'action' => ['buttons' => $btnPayload],
            ],
        ], $body, 'interactive', $companyId);
    }

    public function sendDocument(string $to, string $url, string $filename, ?string $caption = null, ?int $companyId = null): ?string
    {
        $doc = ['link' => $url, 'filename' => $filename];
        if ($caption) $doc['caption'] = $caption;

        return $this->send($to, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'document',
            'document' => $doc,
        ], $caption ?? $filename, 'document', $companyId);
    }

    private function send(string $to, array $payload, string $logBody, string $type, ?int $companyId): ?string
    {
        $messageId = null;

        if ($this->token && $this->phoneNumberId) {
            try {
                $response = Http::withToken($this->token)->post($this->apiUrl, $payload);
                $messageId = $response->json('messages.0.id');
            } catch (\Throwable $e) {
                Log::error('WhatsApp send failed', ['error' => $e->getMessage(), 'to' => $to]);
            }
        }

        WhatsAppMessage::create([
            'company_id' => $companyId,
            'phone' => $to,
            'direction' => 'outbound',
            'wa_message_id' => $messageId,
            'body' => $logBody,
            'message_type' => $type,
            'payload' => $payload,
            'status' => $messageId ? 'sent' : ($this->token ? 'failed' : 'mock'),
        ]);

        return $messageId;
    }
}
