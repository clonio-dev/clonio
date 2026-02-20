<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CloningRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  array{url: string, method?: string, headers?: array<string, string>, secret?: string}  $webhookConfig
     */
    public function __construct(
        public readonly CloningRun $run,
        public readonly array $webhookConfig,
        public readonly string $event,
    ) {}

    public function handle(): void
    {
        $url = $this->webhookConfig['url'];
        $method = mb_strtolower($this->webhookConfig['method'] ?? 'POST');
        $headers = $this->webhookConfig['headers'] ?? [];
        $secret = $this->webhookConfig['secret'] ?? null;

        $payload = [
            'event' => $this->event,
            'cloning_id' => $this->run->cloning_id,
            'run_id' => $this->run->id,
            'status' => $this->run->status->value,
            'started_at' => $this->run->started_at?->toIso8601String(),
            'finished_at' => $this->run->finished_at?->toIso8601String(),
            'audit_log_url' => $this->run->public_token
                ? url('/audit/' . $this->run->public_token)
                : null,
        ];

        if ($secret) {
            $headers['X-Webhook-Signature'] = hash_hmac('sha256', json_encode($payload), $secret);
        }

        try {
            $response = Http::withHeaders($headers)->$method($url, $payload);

            if ($response->failed()) {
                Log::warning('Webhook delivery failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'run_id' => $this->run->id,
                ]);

                $this->run->refresh()->addWebhookResult([
                    'status' => 'failed',
                    'event' => $this->event,
                    'url' => $url,
                    'http_status' => $response->status(),
                    'attempt' => $this->attempts(),
                    'message' => sprintf('Webhook for %s failed (HTTP %s)', $this->event, $response->status()),
                ]);

                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff);
                }
            } else {
                $this->run->refresh()->addWebhookResult([
                    'status' => 'success',
                    'event' => $this->event,
                    'url' => $url,
                    'http_status' => $response->status(),
                    'message' => sprintf('Webhook for %s dispatched successfully', $this->event),
                ]);
            }
        } catch (Throwable $throwable) {
            Log::error('Webhook dispatch error', [
                'url' => $url,
                'error' => $throwable->getMessage(),
                'run_id' => $this->run->id,
            ]);

            $this->run->refresh()->addWebhookResult([
                'status' => 'failed',
                'event' => $this->event,
                'url' => $url,
                'error' => $throwable->getMessage(),
                'attempt' => $this->attempts(),
                'message' => sprintf('Webhook for %s failed: %s', $this->event, $throwable->getMessage()),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
            }
        }
    }
}
