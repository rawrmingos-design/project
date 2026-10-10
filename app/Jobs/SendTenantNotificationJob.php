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
    public const EVENT_RENEWAL_INVOICE = 'renewal_invoice';
    public const EVENT_PAYMENT_REMINDER = 'payment_reminder';
    public const EVENT_SUSPENDED = 'suspended';

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
            self::EVENT_RENEWAL_INVOICE => 'tenant_renewal_invoice',
            self::EVENT_PAYMENT_REMINDER => 'tenant_payment_reminder',
            self::EVENT_SUSPENDED => 'tenant_suspended',
            default => 'tenant_registration_invoice',
        };
    }

    private function subject(): string
    {
        return match ($this->event) {
            self::EVENT_ACTIVATED => 'Website Reseller Topup kamu sudah aktif',
            self::EVENT_INVOICE_EXPIRED => 'Invoice Reseller Topup kamu expired',
            self::EVENT_RENEWAL_INVOICE => 'Waktunya perpanjang langganan Reseller Topup kamu',
            self::EVENT_PAYMENT_REMINDER => 'Pembayaran langganan kamu sudah lewat jatuh tempo',
            self::EVENT_SUSPENDED => 'Website Reseller Topup kamu ditangguhkan sementara',
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

        $denda = (int) data_get($invoice->metadata, 'late_fee', 0);

        return [
            'owner_name' => (string) ($owner->name ?: $owner->username ?: 'Owner'),
            'store_name' => (string) $tenant->name,
            'subdomain' => (string) $tenant->subdomain,
            'tenant_url' => $tenantUrl,
            'dashboard_url' => rtrim($tenantUrl, '/') . '/dashboard',
            'tier' => (string) $invoice->subscription->tier,
            'amount' => 'Rp ' . number_format((int) $invoice->amount, 0, ',', '.'),
            'late_fee' => $denda > 0 ? 'Rp ' . number_format($denda, 0, ',', '.') : '',
            'period_end' => $invoice->subscription->current_period_end?->format('d M Y') ?? '-',
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
            self::EVENT_RENEWAL_INVOICE => '<p>Halo <strong>{owner_name}</strong>,</p><p>Langganan <strong>{store_name}</strong> berakhir pada <strong>{period_end}</strong>. Invoice perpanjangan sudah dibuat.</p><ul><li>Nominal: {amount}</li><li>Jatuh tempo: {due_date}</li></ul><p>Bayar di sini: <a href="{payment_url}">{payment_url}</a></p><p>Bayar sebelum periode berakhir supaya website kamu tidak terganggu.</p>',
            self::EVENT_PAYMENT_REMINDER => '<p>Halo <strong>{owner_name}</strong>,</p><p>Pembayaran langganan <strong>{store_name}</strong> sudah <strong>lewat jatuh tempo</strong>.</p><ul><li>Yang harus dibayar: {amount}</li>{late_fee_line}</ul><p>Bayar sekarang: <a href="{payment_url}">{payment_url}</a></p><p>Kalau tidak dibayar, website kamu akan ditangguhkan sementara.</p>',
            self::EVENT_SUSPENDED => '<p>Halo <strong>{owner_name}</strong>,</p><p>Website <strong>{store_name}</strong> <strong>ditangguhkan sementara</strong> karena langganan belum diperpanjang.</p><p>Data dan nama domain kamu masih aman. Bayar tagihan untuk mengaktifkan kembali: <a href="{payment_url}">{payment_url}</a></p><p>Butuh bantuan? <a href="{support_url}">{support_url}</a></p>',
            default => '<p>Halo <strong>{owner_name}</strong>,</p><p>Invoice Reseller Topup untuk <strong>{store_name}</strong> sudah dibuat.</p><ul><li>Paket: {tier}</li><li>Nominal: {amount}</li><li>Jatuh tempo: {due_date}</li></ul><p>Bayar di sini: <a href="{payment_url}">{payment_url}</a></p>',
        };

        return $this->replace($template, $this->withLateFeeLine($payload));
    }

    /**
     * @param array<string, string> $payload
     */
    private function whatsappMessage(array $payload): string
    {
        $template = match ($this->event) {
            self::EVENT_ACTIVATED => "✅ *Reseller Topup Aktif*\n\nHalo {owner_name}, website *{store_name}* sudah aktif.\n\nWebsite: {tenant_url}\nDashboard: {dashboard_url}",
            self::EVENT_INVOICE_EXPIRED => "⚠️ *Invoice Reseller Topup Expired*\n\nHalo {owner_name}, invoice untuk *{store_name}* sudah expired.\nHubungi support: {support_url}",
            self::EVENT_RENEWAL_INVOICE => "🧾 *Perpanjang Langganan*\n\nHalo {owner_name}, langganan *{store_name}* berakhir {period_end}.\nNominal: {amount}\nJatuh tempo: {due_date}\n\nBayar: {payment_url}",
            self::EVENT_PAYMENT_REMINDER => "⏰ *Pembayaran Lewat Jatuh Tempo*\n\nHalo {owner_name}, langganan *{store_name}* belum dibayar.\nYang harus dibayar: {amount}\n{late_fee_line}\nBayar sekarang: {payment_url}\n\nKalau tidak dibayar, website ditangguhkan sementara.",
            self::EVENT_SUSPENDED => "🔒 *Website Ditangguhkan Sementara*\n\nHalo {owner_name}, website *{store_name}* ditangguhkan karena langganan belum diperpanjang.\n\nData dan nama domain kamu masih aman. Bayar untuk mengaktifkan kembali:\n{payment_url}",
            default => "🧾 *Invoice Reseller Topup Dibuat*\n\nHalo {owner_name}, invoice untuk *{store_name}* sudah dibuat.\nPaket: {tier}\nNominal: {amount}\nJatuh tempo: {due_date}\nBayar: {payment_url}",
        };

        return $this->replace($template, $this->withLateFeeLine($payload));
    }

    /**
     * Baris denda hanya muncul kalau memang ada denda, supaya pesan tanpa denda
     * tidak menampilkan baris kosong.
     *
     * @param array<string, string> $payload
     * @return array<string, string>
     */
    private function withLateFeeLine(array $payload): array
    {
        $payload['late_fee_line'] = ($payload['late_fee'] ?? '') !== ''
            ? 'Denda keterlambatan: ' . $payload['late_fee']
            : '';

        return $payload;
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
