<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionInvoice;
use App\Tenancy\Concerns\ResolvesPlatformContact;
use App\Tenancy\Concerns\ResolvesTenantHost;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * S4: halaman sukses / aktivasi — `/tagihan/{token}/sukses` di host platform.
 *
 * Satu-satunya tujuan halaman ini: menghapus keraguan "uang saya masuk atau
 * tidak?" dan memberi satu langkah berikutnya yang jelas.
 *
 * ⚠️ Status TIDAK PERNAH ditandai sukses dari kedatangan pengunjung. Duitku
 * mengirim `returnUrl` begitu pembeli menekan kembali, sedangkan status resmi
 * datang dari callback (`resultCode 00` → `markInvoicePaid`). Pembeli bisa tiba
 * SEBELUM callback, jadi halaman ini membaca status invoice dan punya keadaan
 * tersendiri untuk "belum terkonfirmasi" — jangan pernah mengubahnya menjadi
 * tebakan dari request.
 *
 * Berbagi token dengan halaman tagihan (S3): token itu sudah ada di setiap tautan
 * yang dikirim (`ensurePublicToken()`), jadi tidak perlu kolom baru.
 */
class BillingSuccessPageController extends Controller
{
    use ResolvesPlatformContact;
    use ResolvesTenantHost;

    public function __invoke(Request $request, string $token): Response
    {
        // Sama seperti halaman tagihan: resolusi manual, tanpa route-model
        // binding, supaya setiap percobaan token tetap melewati limiter
        // (`SubstituteBindings` di grup `web` berjalan sebelum `throttle`, dan
        // token yang tidak cocok akan 404 lebih dulu tanpa pernah dihitung).
        $invoice = SubscriptionInvoice::query()
            ->where('public_token', $token)
            ->first();

        // Token tidak valid → 404. Invoice yang tokennya sudah dicabut juga 404:
        // tidak ada bedanya bagi pemilik tautan.
        abort_if($invoice === null, 404);

        $subscription = $invoice->subscription;
        $tenant = $subscription?->tenant;
        $contactUrl = $this->platformContactUrl();

        // Halaman platform: shared props storefront membawa `siteConfig` /
        // `seoDefaults` yang memuat `canonical` + URL aset dari host request.
        // Dibuang, bukan diwarisi.
        Inertia::flushShared();

        $billingUrl = $this->platformUrl(
            route('billing.public', ['token' => $invoice->ensurePublicToken()], absolute: false)
        );

        return Inertia::render('Public/TenantBillingSuccess', [
            'state' => $this->state($invoice),
            'invoice' => [
                'number' => 'INV-' . str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT),
                'amount' => (int) $invoice->amount,
                'status' => (string) $invoice->status,
                'tier' => (string) ($subscription?->tier ?? ''),
                'paidAt' => $invoice->paid_at?->toIso8601String(),
                'dueDate' => $invoice->due_date?->toIso8601String(),
                'periodEnd' => $subscription?->current_period_end?->toIso8601String(),
            ],
            // Toko milik pembeli sendiri — ini yang dia baru beli, jadi justru
            // harus tampil. Nama owner, email, dan nomor WA TIDAK ikut: itu data
            // pribadi yang tidak dibutuhkan halaman ini.
            'store' => [
                'name' => (string) ($tenant?->name ?? ''),
                'host' => $this->storeHost($tenant?->subdomain),
                'url' => $this->storeUrl($tenant?->subdomain),
            ],
            // D4: null kalau memang tidak ada yang perlu dibayar.
            'billingUrl' => $billingUrl,
            'contactUrl' => $contactUrl,
            'contactIsWhatsapp' => $this->contactIsWhatsapp($contactUrl),
            'meta' => [
                'robots' => 'noindex, nofollow',
                // Tanpa ini `app-inertia.blade.php` menurunkan canonical dari
                // `url()->current()`. Halaman ini `noindex`, jadi canonical
                // dikosongkan — bukan dibiarkan menunjuk ke sesuatu.
                'canonical' => '',
            ],
        ]);
    }

    /**
     * Keadaan halaman. Hanya `paid` yang boleh berbunyi "berhasil".
     */
    protected function state(SubscriptionInvoice $invoice): string
    {
        return match ($invoice->status) {
            SubscriptionInvoice::STATUS_PAID => 'paid',
            SubscriptionInvoice::STATUS_PENDING => 'confirming',
            SubscriptionInvoice::STATUS_EXPIRED => 'expired',
            SubscriptionInvoice::STATUS_CANCELLED => 'cancelled',
            // Status tak dikenal jangan pernah jatuh ke "sukses".
            default => 'confirming',
        };
    }

    /** Alamat toko baru, dipakai tombol utama. Kosong kalau subdomain tidak ada. */
    protected function storeHost(?string $subdomain): string
    {
        $subdomain = trim((string) $subdomain);
        $baseHost = $this->baseHost();

        if ($subdomain === '' || $baseHost === '') {
            return '';
        }

        return $subdomain . '.' . $baseHost;
    }

    /**
     * URL toko siap-klik. Skema diambil dari `config('app.url')`, bukan
     * diasumsikan `https` — kalau tidak, tombolnya bisa menunjuk skema yang tidak
     * pernah dipakai situs ini.
     */
    protected function storeUrl(?string $subdomain): string
    {
        $host = $this->storeHost($subdomain);
        $baseUrl = trim((string) config('app.url'));

        if ($host === '') {
            return '';
        }

        $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';

        return $scheme . '://' . $host;
    }
}
