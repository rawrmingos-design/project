<?php

namespace App\Services;

use App\Models\SettingWeb;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\TransactionMail;

class EmailNotificationService
{
    /**
     * Send email notification to user.
     *
     * @param string $email
     * @param array $data
     * @return bool
     */
    public function sendTransactionEmail($email, $data)
    {
        if (empty($email)) {
            Log::warning("EmailNotificationService: No email provided for Order ID: " . ($data['order_id'] ?? 'Unknown'));
            return false;
        }

        try {
            $settings = SettingWeb::query()->first();

            if ($settings && $settings->invoice_notify_via_email === false) {
                Log::info('EmailNotificationService: Invoice email disabled by admin setting.', [
                    'order_id' => $data['order_id'] ?? null,
                    'email' => $email,
                ]);

                return false;
            }

            $this->applyMailConfiguration($settings);

            // Determine slug based on status
            $status = strtolower($data['status'] ?? '');
            $slug = 'transaction_pending'; // Default
            if ($status == 'success' || $status == 'sukses') {
                $slug = 'transaction_success';
            } elseif ($status == 'failed' || $status == 'gagal' || $status == 'expired') {
                $slug = 'transaction_failed';
            }

            // Fetch Template
            $template = \App\Models\EmailTemplate::where('slug', $slug)->where('is_active', true)->first();
            
            $subject = null;
            $content = null;

            if ($template) {
                $subject = $template->subject;
                $content = $template->content;

                // Replace variables
                foreach ($data as $key => $value) {
                    if (is_string($value) || is_numeric($value)) {
                        $subject = str_replace('{' . $key . '}', $value, $subject);
                        $content = str_replace('{' . $key . '}', $value, $content);
                    }
                }
                // Also replace {nickname} if not in data but in data (it should be)
                // Just in case, replace any remaining tags with empty or keep them?
                // Keeping them is better for debugging.
            }

            Mail::to($email)->send(new TransactionMail($data, $subject, $content));
            Log::info("Email sent successfully to {$email} for Order ID: " . ($data['order_id'] ?? 'Unknown'));
            return true;
        } catch (\Exception $e) {
            Log::error("EmailNotificationService Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send generic informational email that is not tied to transaction template slug.
     *
     * @param string $email
     * @param string $subject
     * @param string $contentHtml
     * @param array<string, mixed> $context
     */
    public function sendGenericEmail(string $email, string $subject, string $contentHtml, array $context = []): bool
    {
        if (empty($email)) {
            Log::warning('EmailNotificationService: No email provided for generic email.');

            return false;
        }

        try {
            $settings = SettingWeb::query()->first();
            $this->applyMailConfiguration($settings);

            $data = array_merge([
                'order_id' => (string) ($context['reference_id'] ?? 'GENERIC-NOTIFICATION'),
                'status' => (string) ($context['status'] ?? 'info'),
                'nickname' => (string) ($context['recipient_name'] ?? 'Member'),
            ], $context);

            Mail::to($email)->send(new TransactionMail($data, $subject, $contentHtml));

            Log::info('EmailNotificationService: Generic email sent.', [
                'email' => $email,
                'subject' => $subject,
                'reference_id' => $context['reference_id'] ?? null,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('EmailNotificationService Generic Error: ' . $e->getMessage(), [
                'email' => $email,
                'subject' => $subject,
            ]);

            return false;
        }
    }

    public function sendTestEmail(string $email): bool
    {
        if (empty($email)) {
            return false;
        }

        try {
            $settings = SettingWeb::query()->first();
            $this->applyMailConfiguration($settings);

            Mail::to($email)->send(new TransactionMail([
                'order_id' => 'TEST-' . now()->format('YmdHis'),
                'status' => 'Pending',
                'nickname' => 'Admin Test',
            ], 'Test Email Konfigurasi', '<p>Ini adalah email test dari halaman settings admin.</p>'));

            Log::info("EmailNotificationService: Test email sent successfully to {$email}");

            return true;
        } catch (\Exception $e) {
            Log::error("EmailNotificationService Test Error: " . $e->getMessage());

            return false;
        }
    }

    private function applyMailConfiguration(?SettingWeb $settings): void
    {
        config([
            'mail.default' => $settings?->mail_mailer ?: env('MAIL_MAILER', 'smtp'),
            'mail.mailers.smtp.host' => $settings?->mail_host ?: env('MAIL_HOST', 'smtp.mailgun.org'),
            'mail.mailers.smtp.port' => $settings?->mail_port ?: env('MAIL_PORT', 587),
            'mail.mailers.smtp.encryption' => $settings?->mail_encryption ?: env('MAIL_ENCRYPTION', 'tls'),
            'mail.mailers.smtp.username' => $settings?->mail_username ?: env('MAIL_USERNAME'),
            'mail.mailers.smtp.password' => $settings?->mail_password ?: env('MAIL_PASSWORD'),
            'mail.from.address' => $settings?->mail_from_address ?: env('MAIL_FROM_ADDRESS', 'hello@example.com'),
            'mail.from.name' => $settings?->mail_from_name ?: env('MAIL_FROM_NAME', 'Example'),
        ]);
    }
}
