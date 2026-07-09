<?php

namespace App\Jobs;

use App\Models\SettingWeb;
use App\Models\SubscriptionInvoice;
use App\Services\EmailNotificationService;
use App\Services\WhatsappNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTenantNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const EVENT_REGISTRATION_INVOICE = 'registration_invoice';
    public const EVENT_ACTIVATED = 'activated';
    public const EVENT_INVOICE_EXPIRED = 'invoice_expired';

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly int $invoiceId,
        public readonly string $event,
    ) {
    }

    public function handle(EmailNotificationService $emailService, WhatsappNotificationService $whatsappService): void
    {
        $invoice = SubscriptionInvoice::query()
            ->with('subscription.tenant.owner')
            ->find($this->invoiceId);

        if (! $invoice || ! $invoice->subscription?->tenant?->owner) {
            Log::warning('SendTenantNotificationJob: invoice or tenant owner not found.', [
                'invoice_id' => $this->invoiceId,
                'event' => $this->event,
            ]);

            return;
        }

        $settings = SettingWeb::query()->find(1);
        $payload = $this->payload($invoice, $settings);
        $subject = $this->subject();
        $emailHtml = $this->emailHtml($payload);
        $whatsappMessage = $this->whatsappMessage($payload);
        $owner = $invoice->subscription->tenant->owner;

        if (($settings?->tenant_notify_via_email ?? true) && filled($owner->email)) {
            $emailService->sendGenericEmail((string) $owner->email, $subject, $emailHtml, [
                'reference_id' => $payload['invoice_id'],
                'recipient_name' => $payload['owner_name'],
                'status' => $this->event,
            ]);
        }

        if (($settings?->tenant_notify_via_whatsapp ?? true) && filled($owner->no_wa)) {
            $result = $whatsappService->sendNotification((string) $owner->no_wa, $this->templateSlug(), $payload);

            if (! ($result['success'] ?? false)) {
                $whatsappService->sendMessage((string) $owner->no_wa, $whatsappMessage);
            }
        }
    }

    private function templateSlug(): string
    {
        return match ($this->event) {
            self::EVENT_ACTIVATED => 'tenant_activated',
            self::EVENT_INVOICE_EXPIRED => 'tenant_invoice_expired',
            default => 'tenant_registration_invoice',
        };
    }

    private function subject(): string
    {
        return match ($this->event) {
            self::EVENT_ACTIVATED => 'Website Reseller Topup kamu sudah aktif',
            self::EVENT_INVOICE_EXPIRED => 'Invoice Reseller Topup kamu expired',
            default => 'Invoice Reseller Topup kamu sudah dibuat',
        };
    }

    /**
     * @return array<string, string>
     */
    private function payload(SubscriptionInvoice $invoice, ?SettingWeb $settings): array
    {
        $tenant = $invoice->subscription->tenant;
        $owner = $tenant->owner;
        $tenantUrl = $this->tenantUrl((string) $tenant->subdomain);
        $paymentUrl = (string) data_get($invoice->metadata, 'duitku.payment_url', '');
        $supportUrl = (string) ($settings?->url_wa ?: url('/id'));

        return [
            'owner_name' => (string) ($owner->name ?: $owner->username ?: 'Owner'),
            'store_name' => (string) $tenant->name,
            'subdomain' => (string) $tenant->subdomain,
            'tenant_url' => $tenantUrl,
            'dashboard_url' => rtrim($tenantUrl, '/') . '/dashboard',
            'tier' => (string) $invoice->subscription->tier,
            'amount' => 'Rp ' . number_format((int) $invoice->amount, 0, ',', '.'),
            'payment_url' => $paymentUrl,
            'due_date' => $invoice->due_date?->format('d M Y H:i') ?? '-',
            'invoice_id' => (string) $invoice->id,
            'gateway_ref' => (string) $invoice->gateway_ref,
            'support_url' => $supportUrl,
        ];
    }

    /**
     * @param array<string, string> $payload
     */
    private function emailHtml(array $payload): string
    {
        $template = match ($this->event) {
            self::EVENT_ACTIVATED => '<p>Halo <strong>{owner_name}</strong>,</p><p>Website Reseller Topup <strong>{store_name}</strong> sudah aktif.</p><ul><li>Website: <a href="{tenant_url}">{tenant_url}</a></li><li>Dashboard: <a href="{dashboard_url}">{dashboard_url}</a></li></ul><p>Silakan login dan mulai atur toko kamu.</p>',
            self::EVENT_INVOICE_EXPIRED => '<p>Halo <strong>{owner_name}</strong>,</p><p>Invoice Reseller Topup untuk <strong>{store_name}</strong> sudah expired.</p><p>Hubungi support untuk membuat invoice baru: <a href="{support_url}">{support_url}</a></p>',
            default => '<p>Halo <strong>{owner_name}</strong>,</p><p>Invoice Reseller Topup untuk <strong>{store_name}</strong> sudah dibuat.</p><ul><li>Paket: {tier}</li><li>Nominal: {amount}</li><li>Jatuh tempo: {due_date}</li></ul><p>Bayar di sini: <a href="{payment_url}">{payment_url}</a></p>',
        };

        return $this->replace($template, $payload);
    }

    /**
     * @param array<string, string> $payload
     */
    private function whatsappMessage(array $payload): string
    {
        $template = match ($this->event) {
            self::EVENT_ACTIVATED => "✅ *Reseller Topup Aktif*\n\nHalo {owner_name}, website *{store_name}* sudah aktif.\n\nWebsite: {tenant_url}\nDashboard: {dashboard_url}",
            self::EVENT_INVOICE_EXPIRED => "⚠️ *Invoice Reseller Topup Expired*\n\nHalo {owner_name}, invoice untuk *{store_name}* sudah expired.\nHubungi support: {support_url}",
            default => "🧾 *Invoice Reseller Topup Dibuat*\n\nHalo {owner_name}, invoice untuk *{store_name}* sudah dibuat.\nPaket: {tier}\nNominal: {amount}\nJatuh tempo: {due_date}\nBayar: {payment_url}",
        };

        return $this->replace($template, $payload);
    }

    /**
     * @param array<string, string> $payload
     */
    private function replace(string $template, array $payload): string
    {
        foreach ($payload as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        return $template;
    }

    private function tenantUrl(string $subdomain): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($appUrl, PHP_URL_HOST) ?: parse_url(url('/'), PHP_URL_HOST);

        return $scheme . '://' . $subdomain . '.' . $host;
    }
}
