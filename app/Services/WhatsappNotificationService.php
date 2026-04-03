<?php

namespace App\Services;

use App\Models\SettingWeb;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappNotificationService
{
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
            Log::debug('WhatsappNotificationService: Invoice WhatsApp disabled by admin setting.', [
                'order_id' => $data['order_id'] ?? null,
                'target' => $target,
                'template' => $templateSlug,
            ]);

            return ['success' => false, 'message' => 'Invoice WhatsApp disabled by admin setting'];
        }

        // 1. Get Template
        $template = WhatsappTemplate::where('slug', $templateSlug)->where('is_active', true)->first();

        if (!$template) {
            Log::warning("WhatsappNotificationService: Template '$templateSlug' not found or inactive.");
            return ['success' => false, 'message' => 'Template not found or inactive'];
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
    public function sendMessage(string $target, string $message): array
    {
        try {
            $api = SettingWeb::first();
            
            if (! $api) {
                Log::error('WhatsappNotificationService: Missing setting_webs configuration.');
                return ['success' => false, 'message' => 'Konfigurasi WA belum lengkap.'];
            }

            $provider = strtolower(trim((string) ($api->wa_provider ?? 'fonnte')));

            if ($provider === 'easywa') {
                return $this->sendViaEasyWa($api, $target, $message);
            }

            if (!$api->wa_key) {
                Log::error('WhatsappNotificationService: Missing configuration.');
                return ['success' => false, 'message' => 'Konfigurasi WA belum lengkap.'];
            }

            $response = Http::withHeaders([
                'Authorization' => $api->wa_key,
            ])->asForm()
                ->timeout(30)
                ->post('https://api.fonnte.com/send', [
                    'target' => $target,
                    'message' => $message,
                ]);

            Log::debug("WhatsappNotificationService Sent to $target", [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return $this->normalizeFonnteResponse($response);

        } catch (\Exception $e) {
            Log::error('WhatsappNotificationService Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'System Error: ' . $e->getMessage()];
        }
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

        $payload = [
            'number' => $target,
            'message' => $message,
            'type' => in_array($api->easywa_send_type, ['sync', 'async'], true) ? $api->easywa_send_type : 'sync',
        ];

        if (($payload['type'] ?? 'sync') === 'async' && (int) $api->easywa_send_delay > 0) {
            $payload['delay'] = (int) $api->easywa_send_delay;
        }

        $response = Http::withHeaders([
            'email' => $api->easywa_email,
            'secret-key' => $api->easywa_secret_key,
        ])->post('https://api.easywa.id/v1/send-message', $payload);

        Log::debug("WhatsappNotificationService EasyWA Sent to {$target}", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if (! $response->successful()) {
            return [
                'success' => false,
                'message' => 'EasyWA HTTP ' . $response->status(),
                'response' => $response->body(),
            ];
        }

        $decoded = $response->json();

        return [
            'success' => (bool) ($decoded['status'] ?? false),
            'message' => $decoded['msg'] ?? 'EasyWA request processed',
            'response' => $decoded,
        ];
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

        $response = Http::withHeaders([
            'email' => $api->easywa_email,
            'secret-key' => $api->easywa_secret_key,
        ])->get('https://api.easywa.id/v1/status');

        Log::debug('WhatsappNotificationService EasyWA Status', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if (! $response->successful()) {
            return [
                'success' => false,
                'message' => 'EasyWA HTTP ' . $response->status(),
                'response' => $response->body(),
            ];
        }

        $decoded = $response->json();
        $status = strtolower(trim((string) ($decoded['status'] ?? 'unknown')));
        $message = trim((string) ($decoded['msg'] ?? ''));

        return [
            'success' => in_array($status, ['ready', 'qr', 'starting'], true),
            'status' => $status,
            'message' => $message !== '' ? $message : 'EasyWA status fetched.',
            'response' => $decoded,
        ];
    }
}
