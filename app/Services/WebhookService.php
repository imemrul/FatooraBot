<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    private const BLOCKED_HOSTS = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];
    private const BLOCKED_CIDRS = ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '169.254.0.0/16'];

    public function dispatch(int $companyId, string $event, array $data): int
    {
        $webhooks = Webhook::where('company_id', $companyId)
            ->where('is_active', true)
            ->get()
            ->filter(fn (Webhook $w) => $w->listensTo($event));

        $dispatched = 0;

        foreach ($webhooks as $webhook) {
            // Queue instead of synchronous
            dispatch(function () use ($webhook, $event, $data) {
                $this->send($webhook, $event, $data);
            })->onQueue('webhooks')->afterCommit();

            $dispatched++;
        }

        return $dispatched;
    }

    public function send(Webhook $webhook, string $event, array $data, int $attempt = 1): WebhookLog
    {
        if (!$this->isUrlSafe($webhook->url)) {
            return WebhookLog::create([
                'webhook_id' => $webhook->id,
                'event' => $event,
                'payload' => $data,
                'status' => 'failed',
                'response_body' => 'Blocked: URL points to internal/private network.',
                'attempt' => $attempt,
            ]);
        }

        $payload = [
            'event' => $event,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
            'webhook_id' => $webhook->id,
        ];

        $signature = hash_hmac('sha256', json_encode($payload), $webhook->secret);

        $log = WebhookLog::create([
            'webhook_id' => $webhook->id,
            'event' => $event,
            'payload' => $payload,
            'status' => 'pending',
            'attempt' => $attempt,
        ]);

        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withHeaders([
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $event,
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'FatooraBot-Webhook/1.0',
                ])
                ->post($webhook->url, $payload);

            $log->update([
                'response_code' => $response->status(),
                'response_body' => mb_substr($response->body(), 0, 1000),
                'status' => $response->successful() ? 'success' : 'failed',
            ]);

            if ($response->successful()) {
                $webhook->update(['failure_count' => 0]);
            } else {
                $this->recordFailure($webhook);
            }
        } catch (\Exception $e) {
            Log::warning('Webhook delivery failed', [
                'webhook_id' => $webhook->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            $log->update([
                'response_body' => mb_substr($e->getMessage(), 0, 1000),
                'status' => 'failed',
            ]);

            $this->recordFailure($webhook);
        }

        return $log;
    }

    private function isUrlSafe(string $url): bool
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';

        if (in_array($host, self::BLOCKED_HOSTS)) {
            return false;
        }

        $ip = gethostbyname($host);
        if ($ip === $host) {
            return true; // DNS resolution failed, let HTTP client handle it
        }

        foreach (self::BLOCKED_CIDRS as $cidr) {
            if ($this->ipInCidr($ip, $cidr)) {
                return false;
            }
        }

        return true;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);

        return (ip2long($ip) & ~((1 << (32 - (int) $bits)) - 1)) === ip2long($subnet);
    }

    private function recordFailure(Webhook $webhook): void
    {
        $webhook->increment('failure_count');

        if ($webhook->fresh()->failure_count >= 10) {
            $webhook->update(['is_active' => false]);
            Log::warning('Webhook auto-disabled after 10 failures', ['webhook_id' => $webhook->id]);
        }
    }
}
