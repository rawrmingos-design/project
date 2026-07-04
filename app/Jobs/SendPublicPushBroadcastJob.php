<?php

namespace App\Jobs;

use App\Models\PublicPushBroadcast;
use App\Services\PublicWebPushService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPublicPushBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $broadcastId)
    {
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(PublicWebPushService $service): void
    {
        $broadcast = PublicPushBroadcast::query()->find($this->broadcastId);

        if (! $broadcast || in_array($broadcast->status, ['cancelled', 'sending', 'sent'], true)) {
            return;
        }

        if (! $service->isConfigured()) {
            $broadcast->forceFill([
                'status' => 'failed',
                'failure_messages' => ['Konfigurasi VAPID web push belum lengkap.'],
                'finished_at' => now(),
            ])->save();

            throw new Exception('Konfigurasi VAPID web push belum lengkap.');
        }

        $broadcast->forceFill([
            'status' => 'sending',
            'started_at' => now(),
        ])->save();

        $result = $service->broadcastToActiveSubscriptions($broadcast->payload ?: [
            'title' => $broadcast->title,
            'body' => $broadcast->body,
            'url' => $broadcast->target_url,
            'icon' => asset('assets/pwa/icon-192.png'),
            'badge' => asset('assets/pwa/badge-72.png'),
            'tag' => 'public-broadcast-' . $broadcast->getKey(),
        ]);

        $broadcast->forceFill([
            'status' => 'sent',
            'success_count' => (int) ($result['success_count'] ?? 0),
            'failed_count' => (int) ($result['failed_count'] ?? 0),
            'total_count' => (int) ($result['total'] ?? 0),
            'failure_messages' => $result['failed_messages'] ?? [],
            'finished_at' => now(),
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        $broadcast = PublicPushBroadcast::query()->find($this->broadcastId);

        if ($broadcast && $broadcast->status !== 'sent') {
            $broadcast->forceFill([
                'status' => 'failed',
                'failure_messages' => array_values(array_filter([
                    $exception?->getMessage(),
                ])),
                'finished_at' => now(),
            ])->save();
        }

        Log::error('Public push broadcast job failed.', [
            'broadcast_id' => $this->broadcastId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
