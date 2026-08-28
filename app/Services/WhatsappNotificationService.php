<?php

namespace App\Services;

use App\Models\SettingWeb;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsappNotificationService
{
    private const EASYWA_ASYNC_DELAY_SECONDS = 1;
    private const EASYWA_TIMEOUT_SECONDS = 8;
    private const EASYWA_CIRCUIT_BREAKER_THRESHOLD = 3;
    private const EASYWA_CIRCUIT_BREAKER_COOLDOWN_SECONDS = 120;
    private const EASYWA_CIRCUIT_BREAKER_FAILURES_KEY = 'whatsapp:easywa:failures';
    private const EASYWA_CIRCUIT_BREAKER_OPEN_UNTIL_KEY = 'whatsapp:easywa:open_until';

    public function getProviderStatus(): array
    {
        $api = SettingWeb::query()->first();

        if (! $api) {
            return ['success' => false, 'message' => 'Konfigurasi WhatsApp belum tersedia.'];
        }

        $provider = strtolower(trim((string) ($api->wa_provider ?? 'fonnte')));

        return match ($provider) {
            'easywa' => $this->getEasyWaStatus($api),
            default => [
                'success' => false,
                'message' => 'Cek status otomatis hanya tersedia untuk EasyWA pada implementasi ini.',
            ],
        };
    }

    /**
     * Send a WhatsApp notification using a template.
     *
     * @param string $target Phone number
     * @param string $templateSlug Slug of the template (e.g., 'transaction_success')
     * @param array $data Key-value pairs for variable replacement
     * @return array
     */
    public function sendNotification(string $target, string $templateSlug, array $data = []): array
    {
        $settings = SettingWeb::query()->first();

        if (
            str_starts_with($templateSlug, 'transaction_') &&
            $settings &&
            $settings->invoice_notify_via_whatsapp === false
        ) {
            return ['success' => false, 'message' => 'Invoice WhatsApp disabled by admin setting'];
        }

        // 1. Get Template
        $template = WhatsappTemplate::where('slug', $templateSlug)->where('is_active', true)->first();

        if (! $template) {
            Log::error('invoice_notification.template_unavailable', [
                'channel' => 'whatsapp',
                'order_id' => (string) ($data['order_id'] ?? 'unknown'),
                'template_slug' => $templateSlug,
            ]);

            return [
                'success' => false,
                'provider' => null,
                'error_code' => 'template_unavailable',
                'message' => 'Template not found or inactive',
            ];
        }

        // 2. Replace Variables
        $message = $this->replaceVariables($template->content, $data);

        // 3. Send Message
        return $this->sendMessage($target, $message);
    }

    /**
     * Replace variables in the template content.
     */
    protected function replaceVariables(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        return $content;
    }

    /**
     * Send raw message via Fonnte API.
     */
    public function sendMessage(string $target, string $message, ?string $url = null, ?string $customToken = null): array
    {
        try {
            $api = SettingWeb::first();

            if (! $api) {
                Log::error('WhatsappNotificationService: Missing setting_webs configuration.');

                return ['success' => false, 'message' => 'Konfigurasi WA belum lengkap.'];
            }

            // If a custom token is provided, bypass EasyWA and force the provider with the custom token
            // This allows the bot order gateway to use its own device (OpenWA) while the system uses another provider
            if ($customToken !== null) {
                // Custom token for bot order = OpenWA API key
                $payload = [
                    'chatId' => $this->toOpenWaChatId($target),
                    'text' => $message,
                ];

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $customToken,
                    'Accept' => 'application/json',
                ])->asJson()
                    ->timeout(30)
                    ->post(
                        $this->openWaEndpoint('send-text', $url),
                        $this->withMediaUrl($payload, $url)
                    );

                return $this->normalizeOpenWaResponse($response);
            }

            $provider = strtolower(trim((string) ($api->wa_provider ?? 'fonnte')));

            if ($provider === 'openwa') {
                if (! $api->wa_key) {
                    Log::error('WhatsappNotificationService: Missing OpenWA configuration.');

                    return ['success' => false, 'message' => 'Konfigurasi WA belum lengkap.'];
                }

                $payload = [
                    'chatId' => $this->toOpenWaChatId($target),
                    'text' => $message,
                ];

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $api->wa_key,
                    'Accept' => 'application/json',
                ])->asJson()
                    ->timeout(30)
                    ->post(
                        $this->openWaEndpoint('send-text', $url),
                        $this->withMediaUrl($payload, $url)
                    );

                return $this->normalizeOpenWaResponse($response);
            }

            if ($provider === 'easywa') {
                return $this->sendViaEasyWa($api, $target, $message);
            }

            if (! $api->wa_key) {
                Log::error('WhatsappNotificationService: Missing configuration.');

                return ['success' => false, 'message' => 'Konfigurasi WA belum lengkap.'];
            }

            $payload = [
                'target' => $target,
                'message' => $message,
            ];

            if ($url !== null) {
                $payload['url'] = $url;
            }

            $response = Http::withHeaders([
                'Authorization' => $api->wa_key,
            ])->asForm()
                ->timeout(30)
                ->post('https://api.fonnte.com/send', $payload);

            return $this->normalizeFonnteResponse($response);
        } catch (Throwable) {
            Log::error('WhatsappNotificationService exception.');

            return ['success' => false, 'message' => 'System Error.'];
        }
    }

    private function toOpenWaChatId(string $target): string
    {
        $digits = preg_replace('/\D+/', '', $target) ?? '';

        return $digits . '@s.whatsapp.net';
    }

    /**
     * Pick the OpenWA endpoint: send-image when a media URL is provided,
     * otherwise send-text. Returns the full URL.
     */
    private function openWaEndpoint(string $fallback, ?string $url): string
    {
        $endpoint = (trim((string) $url) !== '' && filter_var(trim((string) $url), FILTER_VALIDATE_URL) !== false)
            ? 'send-image'
            : $fallback;

        // Worker queue = console context → config('bot.openwa_session_id') kosong
        // (AppServiceProvider skip saat runningInConsole). Fallback ke DB.
        $sessionId = (string) config('bot.openwa_session_id');
        if ($sessionId === '') {
            $sessionId = (string) (SettingWeb::query()->value('openwa_session_id') ?? '');
        }

        return 'https://wagateway.jasakoding.web.id/api/sessions/'
            . $sessionId
            . '/messages/'
            . $endpoint;
    }

    /**
     * OpenWA send-image expects { chatId, url, caption } (or base64) — when a
     * media URL is present, move the text into `caption` and add `url`;
     * otherwise return the payload unchanged for send-text.
     */
    private function withMediaUrl(array $payload, ?string $url): array
    {
        $mediaUrl = trim((string) $url);

        if ($mediaUrl !== '' && filter_var($mediaUrl, FILTER_VALIDATE_URL) !== false) {
            $payload['caption'] = (string) ($payload['text'] ?? '');
            unset($payload['text']);
            $payload['url'] = $mediaUrl;
        }

        return $payload;
    }

    private function normalizeOpenWaResponse(Response $response): array
    {
        $decoded = json_decode($response->body(), true);

        if (! is_array($decoded)) {
            return [
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? 'OpenWA request processed.'
                    : 'OpenWA HTTP ' . $response->status(),
                'response' => $response->body(),
                'provider' => 'openwa',
                'http_status' => $response->status(),
            ];
        }

        // OpenWA returns {"messageId": "...", "timestamp": N} on 2xx — no success flag.
        // A failure is reported as a non-2xx status, so 2xx always means success.
        $messageId = (string) ($decoded['messageId'] ?? ($decoded['message_id'] ?? ($decoded['waMessageId'] ?? '')));

        return [
            'success' => $response->successful(),
            'message' => $response->successful()
                ? 'OpenWA request processed.'
                : 'OpenWA HTTP ' . $response->status(),
            'response' => $decoded,
            'provider' => 'openwa',
            'http_status' => $response->status(),
            'message_id' => $messageId,
        ];
    }

    public function sendTestMessage(string $target, string $message): array
    {
        return $this->sendMessage($target, $message);
    }

    private function sendViaEasyWa(SettingWeb $api, string $target, string $message): array
    {
        if (blank($api->easywa_email) || blank($api->easywa_secret_key)) {
            Log::error('WhatsappNotificationService: Missing EasyWA configuration.');

            return ['success' => false, 'message' => 'Konfigurasi EasyWA belum lengkap.'];
        }

        if ($this->isEasyWaCircuitOpen()) {
            return $this->easyWaCircuitOpenResponse();
        }

        $payload = [
            'number' => $target,
            'message' => $message,
            'type' => in_array($api->easywa_send_type, ['sync', 'async'], true) ? $api->easywa_send_type : 'sync',
        ];

        if (($payload['type'] ?? 'sync') === 'async') {
            $payload['delay'] = self::EASYWA_ASYNC_DELAY_SECONDS;
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(self::EASYWA_TIMEOUT_SECONDS)
                ->withHeaders([
                    'email' => $api->easywa_email,
                    'secret-key' => $api->easywa_secret_key,
                ])->post('https://api.easywa.id/v1/send-message', $payload);

            if (! $response->successful()) {
                $this->recordEasyWaFailure('send_http_error', [
                    'http_status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'message' => 'EasyWA HTTP ' . $response->status(),
                    'response' => $response->body(),
                    'provider' => 'easywa',
                    'http_status' => $response->status(),
                ];
            }

            $decoded = $response->json();
            $decoded = is_array($decoded) ? $decoded : [];
            $success = (bool) ($decoded['status'] ?? false);

            if (! $success) {
                $this->recordEasyWaFailure('send_failed_response');
            } else {
                $this->resetEasyWaCircuitBreaker();
            }

            return [
                'success' => $success,
                'message' => $decoded['msg'] ?? 'EasyWA request processed',
                'response' => $decoded,
                'provider' => 'easywa',
                'http_status' => $response->status(),
            ];
        } catch (ConnectionException $e) {
            $this->recordEasyWaFailure('send_connection_failed');

            return [
                'success' => false,
                'message' => 'Tidak dapat terhubung ke EasyWA API. Coba lagi nanti.',
                'provider' => 'easywa',
            ];
        } catch (Throwable $e) {
            $this->recordEasyWaFailure('send_failed');

            return [
                'success' => false,
                'message' => 'Error saat kirim EasyWA: ' . $e->getMessage(),
                'provider' => 'easywa',
            ];
        }
    }

    private function normalizeFonnteResponse(Response $response): array
    {
        $decoded = json_decode($response->body(), true);

        if (! is_array($decoded)) {
            return [
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? 'Fonnte request processed.'
                    : 'Fonnte HTTP ' . $response->status(),
                'response' => $response->body(),
                'provider' => 'fonnte',
                'http_status' => $response->status(),
            ];
        }

        $rawStatus = $decoded['status'] ?? $decoded['Status'] ?? null;
        $status = is_bool($rawStatus) ? $rawStatus : filter_var($rawStatus, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $detail = trim((string) ($decoded['detail'] ?? ''));
        $reason = trim((string) ($decoded['reason'] ?? ''));
        $process = strtolower(trim((string) ($decoded['process'] ?? '')));
        $requestId = $decoded['requestid'] ?? null;

        $message = $detail !== ''
            ? $detail
            : ($reason !== '' ? $reason : ($response->successful() ? 'Fonnte request processed.' : 'Fonnte HTTP ' . $response->status()));

        if ($process !== '') {
            $message .= ' (process: ' . $process . ')';
        }

        return [
            'success' => $response->successful() && $status !== false,
            'message' => $message,
            'response' => $decoded,
            'provider' => 'fonnte',
            'http_status' => $response->status(),
            'detail' => $detail,
            'reason' => $reason,
            'process' => $process,
            'request_id' => $requestId,
        ];
    }

    private function getEasyWaStatus(SettingWeb $api): array
    {
        if (blank($api->easywa_email) || blank($api->easywa_secret_key)) {
            return ['success' => false, 'message' => 'Konfigurasi EasyWA belum lengkap.'];
        }

        if ($this->isEasyWaCircuitOpen()) {
            return $this->easyWaCircuitOpenResponse();
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(self::EASYWA_TIMEOUT_SECONDS)
                ->withHeaders([
                    'email' => $api->easywa_email,
                    'secret-key' => $api->easywa_secret_key,
                ])
                ->get('https://api.easywa.id/v1/status');

            if (! $response->successful()) {
                $this->recordEasyWaFailure('status_http_error', [
                    'http_status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'EasyWA HTTP ' . $response->status(),
                    'response' => $response->body(),
                    'provider' => 'easywa',
                    'http_status' => $response->status(),
                ];
            }

            $decoded = $response->json();
            $status = strtolower(trim((string) ($decoded['status'] ?? 'unknown')));
            $message = trim((string) ($decoded['msg'] ?? ''));
            $success = in_array($status, ['ready', 'qr', 'starting'], true);

            if ($success) {
                $this->resetEasyWaCircuitBreaker();
            } else {
                $this->recordEasyWaFailure('status_unavailable', [
                    'status' => $status,
                    'response' => $decoded,
                ]);
            }

            return [
                'success' => $success,
                'status' => $status,
                'message' => $message !== '' ? $message : 'EasyWA status fetched.',
                'response' => $decoded,
                'provider' => 'easywa',
                'http_status' => $response->status(),
            ];
        } catch (ConnectionException $e) {
            $this->recordEasyWaFailure('status_connection_failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Tidak dapat terhubung ke EasyWA API. Coba lagi nanti.',
                'provider' => 'easywa',
            ];
        } catch (Throwable $e) {
            $this->recordEasyWaFailure('status_failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error saat cek status EasyWA: ' . $e->getMessage(),
                'provider' => 'easywa',
            ];
        }
    }

    private function isEasyWaCircuitOpen(): bool
    {
        $openUntil = (int) Cache::get(self::EASYWA_CIRCUIT_BREAKER_OPEN_UNTIL_KEY, 0);

        if ($openUntil <= 0) {
            return false;
        }

        if ($openUntil <= now()->timestamp) {
            Cache::forget(self::EASYWA_CIRCUIT_BREAKER_OPEN_UNTIL_KEY);
            Cache::forget(self::EASYWA_CIRCUIT_BREAKER_FAILURES_KEY);

            return false;
        }

        return true;
    }

    private function easyWaCircuitOpenResponse(): array
    {
        $retryAfter = max(1, (int) Cache::get(self::EASYWA_CIRCUIT_BREAKER_OPEN_UNTIL_KEY, now()->timestamp) - now()->timestamp);

        Log::warning('EasyWA circuit breaker open; skipping provider request.', [
            'retry_after_seconds' => $retryAfter,
        ]);

        return [
            'success' => false,
            'message' => 'EasyWA sedang bermasalah. Sistem akan coba lagi dalam beberapa menit.',
            'provider' => 'easywa',
            'retry_after_seconds' => $retryAfter,
        ];
    }

    private function recordEasyWaFailure(string $event, array $context = []): void
    {
        $failures = (int) Cache::get(self::EASYWA_CIRCUIT_BREAKER_FAILURES_KEY, 0) + 1;

        Cache::put(
            self::EASYWA_CIRCUIT_BREAKER_FAILURES_KEY,
            $failures,
            self::EASYWA_CIRCUIT_BREAKER_COOLDOWN_SECONDS * 2,
        );

        Log::warning('EasyWA provider request failed.', array_merge($context, [
            'event' => $event,
            'failure_count' => $failures,
        ]));

        if ($failures < self::EASYWA_CIRCUIT_BREAKER_THRESHOLD) {
            return;
        }

        Cache::put(
            self::EASYWA_CIRCUIT_BREAKER_OPEN_UNTIL_KEY,
            now()->addSeconds(self::EASYWA_CIRCUIT_BREAKER_COOLDOWN_SECONDS)->timestamp,
            self::EASYWA_CIRCUIT_BREAKER_COOLDOWN_SECONDS,
        );

        Log::error('EasyWA circuit breaker opened.', [
            'failure_count' => $failures,
            'cooldown_seconds' => self::EASYWA_CIRCUIT_BREAKER_COOLDOWN_SECONDS,
        ]);
    }

    private function resetEasyWaCircuitBreaker(): void
    {
        Cache::forget(self::EASYWA_CIRCUIT_BREAKER_FAILURES_KEY);
        Cache::forget(self::EASYWA_CIRCUIT_BREAKER_OPEN_UNTIL_KEY);
    }
}
