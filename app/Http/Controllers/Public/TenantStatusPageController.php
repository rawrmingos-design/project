<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SettingWeb;
use App\Models\Tenant;
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
    public function __invoke(Request $request): Response
    {
        $contactUrl = $this->contactUrl();

        return Inertia::render('Public/TenantStatus', [
            'status' => (string) $request->attributes->get('tenant_inactive_status', ''),
            'address' => (string) $request->attributes->get('tenant_inactive_address', ''),
            'contactUrl' => $contactUrl,
            'contactIsWhatsapp' => str_contains($contactUrl, 'wa.me'),
            // D13: tautan tagihan ber-token ditambahkan bersama Task 3.
            // Q6: tidak ada props waktu suspend — jangan tambahkan.
            'meta' => [
                'robots' => 'noindex, nofollow',
            ],
        ]);
    }

    /**
     * Hanya `suspended` dan `pending_payment` yang mendapat halaman status.
     * `active` dilayani jalur normal; `cancelled` → 404 (D11).
     */
    public static function isInactiveStatus(string $status): bool
    {
        return in_array($status, [Tenant::STATUS_SUSPENDED, Tenant::STATUS_PENDING_PAYMENT], true);
    }

    /**
     * D8: WA platform (setting_webs.url_wa) → WA admin (nomor_admin) →
     * aplikasi. Q5: kalau dua-duanya kosong, label di halaman jadi netral
     * "Hubungi kami" (lihat `contactIsWhatsapp`).
     */
    protected function contactUrl(): string
    {
        $setting = SettingWeb::query()->find(1);

        $langsung = trim((string) ($setting?->url_wa ?? ''));

        if ($langsung !== '') {
            return $langsung;
        }

        $admin = preg_replace('/\D+/', '', (string) ($setting?->nomor_admin ?? '')) ?? '';

        if ($admin !== '') {
            return 'https://wa.me/' . $admin;
        }

        return (string) config('app.url');
    }
}
