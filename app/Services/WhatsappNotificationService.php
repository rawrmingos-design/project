<?php

namespace App\Services;

use App\Models\SettingWeb;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WhatsappNotificationService
{
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
            
            if (!$api || !$api->wa_key) {
                Log::error('WhatsappNotificationService: Missing configuration.');
                return ['success' => false, 'message' => 'Konfigurasi WA belum lengkap.'];
            }

            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.fonnte.com/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30, // Increased timeout
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => [
                    'target' => $target,
                    'message' => $message,
                ],
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $api->wa_key,
                ],
            ]);

            $response = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);

            if ($error) {
                Log::error('WhatsappNotificationService Curl Error', ['error' => $error]);
                return ['success' => false, 'message' => 'Connection Error: ' . $error];
            }

            Log::info("WhatsappNotificationService Sent to $target", ['response' => $response]);
            return ['success' => true, 'response' => $response];

        } catch (\Exception $e) {
            Log::error('WhatsappNotificationService Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'System Error: ' . $e->getMessage()];
        }
    }
}
