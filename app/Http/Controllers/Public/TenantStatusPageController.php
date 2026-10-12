<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Tenancy\Concerns\ResolvesPlatformContact;
use App\Tenancy\Concerns\ResolvesTenantHost;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman status tenant non-aktif (D1–D11).
 *
 * Halaman ini hidup DI LUAR TenantContext — kalau konteks diset, global scope
 * tenant akan mengosongkan halaman ini sendiri (tidak ada baris dengan
 * tenant_id milik tenant non-aktif).
 *
 * Siapa yang sampai ke sini sudah diputuskan middleware
 * {@see \App\Http\Middleware\RenderInactiveTenantStatus}; controller hanya
 * menyusun props. Tanpa autentikasi: owner yang lupa password tetap harus
 * bisa melihat status dan membayar tagihannya.
 */
class TenantStatusPageController extends Controller
{
    use ResolvesPlatformContact;
    use ResolvesTenantHost;

    public function __invoke(Request $request): Response
    {
        $contactUrl = $this->platformContactUrl();
        $status = (string) $request->attributes->get('tenant_inactive_status', '');

        $tenantId = $request->attributes->get('tenant_inactive_id');

        return Inertia::render('Public/TenantStatus', [
            'status' => $status,
            'address' => (string) $request->attributes->get('tenant_inactive_address', ''),
            // Task 6: jalur pulang untuk pengunjung yang menemukan halaman ini.
            // Selalu lewat halaman tagihan ber-token — tautan gateway tidak
            // pernah dikirim ke pengunjung.
            'billingUrl' => $this->billingUrl($tenantId, $status),
            'contactUrl' => $contactUrl,
            'contactIsWhatsapp' => $this->contactIsWhatsapp($contactUrl),
            // Q6: tidak ada props waktu suspend — jangan tambahkan.
            'meta' => [
                'robots' => 'noindex, nofollow',
                // D9: tanpa ini `app-inertia.blade.php` menurunkan canonical
                // dari `url()->current()` = host tenant MENTAH, jadi alamat
                // yang disensor bocor balik ke dalam markup di jalur non-SSR.
                // Halaman platform tidak punya canonical yang masuk akal; mata
                // rantainya sudah `noindex`, jadi cukup dikosongkan.
                'canonical' => '',
            ],
        ]);
    }

    /**
     * Tautan ke halaman tagihan ber-token, atau null kalau memang tidak ada
     * tagihan tertunggak.
     *
     * Sengaja tidak dibatasi ke `suspended` saja. Tenant `pending_payment` juga
     * punya tagihan pertama yang belum dibayar — dan itulah justru satu-satunya
     * cara pengunjungnya bisa membawa situs ini hidup. Data staging
     * membuktikannya: tenant `toko` berstatus `pending_payment` memegang tagihan
     * Rp 1.500.000 yang menggantung. Menyembunyikan tautannya di situ berarti
     * menahan satu-satunya jalan pulang.
     *
     * Kalau tidak ada tagihan pending, hasilnya null — jangan pernah merender
     * tombol yang menuntun ke halaman 404.
     */
    protected function billingUrl(mixed $tenantId, string $status): ?string
    {
        if (blank($tenantId)) {
            return null;
        }

        $invoice = SubscriptionInvoice::query()
            ->whereHas('subscription', fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('status', SubscriptionInvoice::STATUS_PENDING)
            ->latest('id')
            ->first();

        if ($invoice === null) {
            return null;
        }

        // Token diterbitkan di sini kalau belum ada — idempoten, dan tidak
        // menimpa token yang sudah terkirim lewat email/WA (D13.1).
        //
        // Hostnya WAJIB ditempel manual: `route()` polos memakai host request
        // yang sedang berjalan, dan di sini request-nya datang dari subdomain
        // tenant — tautannya jadi menunjuk ke tempat yang `RequirePlatformHost`
        // mem-404-kan (terbukti 404 di staging).
        return $this->platformUrl(
            route('billing.public', ['token' => $invoice->ensurePublicToken()], absolute: false)
        );
    }

    /**
     * Hanya `suspended` dan `pending_payment` yang mendapat halaman status.
     * `active` dilayani jalur normal; `cancelled` → 404 (D11).
     */
    public static function isInactiveStatus(string $status): bool
    {
        return in_array($status, [Tenant::STATUS_SUSPENDED, Tenant::STATUS_PENDING_PAYMENT], true);
    }
}
