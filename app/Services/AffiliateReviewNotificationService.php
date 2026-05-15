<?php

namespace App\Services;

use App\Models\SettingWeb;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AffiliateReviewNotificationService
{
    public function __construct(
        private readonly WhatsappNotificationService $whatsappNotificationService,
        private readonly EmailNotificationService $emailNotificationService,
    ) {
    }

    /**
     * @return array{
     *   decision:string,
     *   wa:array{enabled:bool,attempted:bool,success:?bool},
     *   email:array{enabled:bool,attempted:bool,success:?bool}
     * }
     */
    public function notifyReviewDecision(User $user, string $decision, ?string $reviewNote = null): array
    {
        $decision = strtolower(trim($decision));
        if (! in_array($decision, ['active', 'rejected'], true)) {
            return [
                'decision' => $decision,
                'wa' => ['enabled' => false, 'attempted' => false, 'success' => null],
                'email' => ['enabled' => false, 'attempted' => false, 'success' => null],
            ];
        }

        $settings = SettingWeb::query()->first();
        $waEnabled = $this->isChannelEnabled($settings, 'affiliate_notify_via_whatsapp', 'invoice_notify_via_whatsapp');
        $emailEnabled = $this->isChannelEnabled($settings, 'affiliate_notify_via_email', 'invoice_notify_via_email');
        $siteName = (string) ($settings?->judul_web ?: config('app.name', 'Platform'));
        $username = (string) ($user->username ?: $user->name ?: 'Member');
        $dashboardUrl = url('/id/dashboard');
        $affiliateUrl = url('/id/affiliate');
        $safeNote = Str::limit(trim(strip_tags((string) $reviewNote)), 260);

        $isApproved = $decision === 'active';
        $headline = $isApproved ? 'Pengajuan affiliate kamu disetujui.' : 'Pengajuan affiliate kamu ditolak.';
        $actionText = $isApproved
            ? "Kamu sudah bisa mulai share referral dari dashboard: {$dashboardUrl}"
            : "Silakan cek detail di halaman affiliate lalu ajukan ulang jika sudah siap: {$affiliateUrl}";

        $noteText = $safeNote !== '' ? "Catatan admin: {$safeNote}" : 'Catatan admin: -';
        $statusLabel = $isApproved ? 'APPROVED' : 'REJECTED';

        $waMessage = implode("\n", [
            "*{$siteName}*",
            $headline,
            "Status: {$statusLabel}",
            "User: {$username}",
            $noteText,
            $actionText,
        ]);

        $emailSubject = $isApproved
            ? "[{$siteName}] Pengajuan Affiliate Disetujui"
            : "[{$siteName}] Pengajuan Affiliate Ditolak";

        $emailHtml = $this->buildEmailHtml($siteName, $headline, $statusLabel, $username, $safeNote, $actionText, $affiliateUrl);

        $waResult = null;
        $emailResult = null;

        if ($waEnabled && filled($user->no_wa)) {
            try {
                $waResult = $this->whatsappNotificationService->sendMessage((string) $user->no_wa, $waMessage);
            } catch (\Throwable $exception) {
                Log::warning('affiliate.review.notification.whatsapp_failed', [
                    'user_id' => $user->id,
                    'decision' => $decision,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($emailEnabled && filled($user->email)) {
            try {
                $emailResult = $this->emailNotificationService->sendGenericEmail(
                    (string) $user->email,
                    $emailSubject,
                    $emailHtml,
                    [
                        'reference_id' => 'AFF-REVIEW-' . $user->id,
                        'status' => $decision,
                        'recipient_name' => $username,
                    ]
                );
            } catch (\Throwable $exception) {
                Log::warning('affiliate.review.notification.email_failed', [
                    'user_id' => $user->id,
                    'decision' => $decision,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        Log::info('affiliate.review.notification.sent', [
            'user_id' => $user->id,
            'decision' => $decision,
            'wa_enabled' => $waEnabled,
            'wa_attempted' => $waEnabled && filled($user->no_wa),
            'wa_success' => is_array($waResult) ? (bool) ($waResult['success'] ?? false) : null,
            'email_enabled' => $emailEnabled,
            'email_attempted' => $emailEnabled && filled($user->email),
            'email_success' => is_bool($emailResult) ? $emailResult : null,
        ]);

        return [
            'decision' => $decision,
            'wa' => [
                'enabled' => $waEnabled,
                'attempted' => $waEnabled && filled($user->no_wa),
                'success' => is_array($waResult) ? (bool) ($waResult['success'] ?? false) : null,
            ],
            'email' => [
                'enabled' => $emailEnabled,
                'attempted' => $emailEnabled && filled($user->email),
                'success' => is_bool($emailResult) ? $emailResult : null,
            ],
        ];
    }

    private function buildEmailHtml(
        string $siteName,
        string $headline,
        string $statusLabel,
        string $username,
        string $safeNote,
        string $actionText,
        string $affiliateUrl
    ): string {
        $statusColor = $statusLabel === 'APPROVED' ? '#16a34a' : '#ef4444';
        $note = $safeNote !== '' ? e($safeNote) : '-';

        return <<<HTML
            <h2 style="margin:0 0 12px;">{$headline}</h2>
            <p style="margin:0 0 8px;">Halo <strong>{$username}</strong>, status pengajuan affiliate kamu di <strong>{$siteName}</strong> sudah diperbarui.</p>
            <p style="margin:0 0 10px;">Status: <strong style="color: {$statusColor};">{$statusLabel}</strong></p>
            <p style="margin:0 0 10px;"><strong>Catatan admin:</strong> {$note}</p>
            <p style="margin:0 0 14px;">{$actionText}</p>
            <p style="margin:0;">Buka halaman affiliate: <a href="{$affiliateUrl}">{$affiliateUrl}</a></p>
        HTML;
    }

    private function isChannelEnabled(?SettingWeb $settings, string $affiliateField, string $fallbackField): bool
    {
        if (! $settings) {
            return true;
        }

        $affiliateValue = data_get($settings, $affiliateField);
        if ($affiliateValue !== null) {
            return (bool) $affiliateValue;
        }

        $fallbackValue = data_get($settings, $fallbackField);
        if ($fallbackValue !== null) {
            return (bool) $fallbackValue;
        }

        return true;
    }
}
