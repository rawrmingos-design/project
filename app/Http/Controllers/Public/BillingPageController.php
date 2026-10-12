<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionInvoice;
use App\Tenancy\Concerns\ResolvesPlatformContact;
use App\Tenancy\SubscriptionPaymentLinkResolver;
use App\Tenancy\TenantAddressMasker;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * D13: halaman tagihan publik ber-token — `/tagihan/{token}` di host platform.
 *
 * Tanpa login, di luar TenantContext, dan di luar grup rute tenant. Grup tenant
 * selalu berakhir di `abort_unless(context->has(), 404)`, dan di host platform
 * tidak ada konteks tenant — halaman ini akan 404 selamanya kalau ditaruh di sana.
 *
 * D13.4: yang tampil hanya data tagihan. Tidak ada nama owner, email, nomor WA,
 * nama tenant, atau subdomain — tautan ini bisa dibuka siapa saja yang punya URL-nya.
 */
class BillingPageController extends Controller
{
    use ResolvesPlatformContact;

    public function __invoke(Request $request, string $token): Response
    {
        // Sengaja TIDAK memakai route-model binding: `SubstituteBindings` ada di
        // grup `web` dan berjalan SEBELUM `throttle:public-billing`, sehingga
        // token yang tidak cocok akan 404 lebih dulu dan limiter — termasuk
        // batas per-IP yang menahan penebakan token — tidak pernah dihitung.
        // Resolusi manual di sini membuat setiap percobaan tetap melewati limiter.
        $invoice = SubscriptionInvoice::query()
            ->where('public_token', $token)
            ->first();

        // D13.6: token di-null saat lunas, jadi invoice lunas tidak akan ketemu
        // di sini — tautan lamanya memang harus 404. Token tidak valid juga 404,
        // tanpa membedakan "tidak ada" dari "sudah tidak berlaku".
        abort_if($invoice === null, 404);

        $contactUrl = $this->platformContactUrl();
        $subscription = $invoice->subscription;
        $tenant = $subscription?->tenant;

        // Sama seperti halaman status: shared props storefront memuat
        // `siteConfig`/`seoDefaults` — di dalamnya ada `canonical` dan URL aset
        // yang dibangun dari host request. Halaman tagihan adalah halaman
        // platform, jadi props itu harus dibuang, bukan diwarisi.
        Inertia::flushShared();

        return Inertia::render('Public/TenantBilling', [
            'invoice' => [
                'number' => 'INV-' . str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT),
                'amount' => (int) $invoice->amount,
                'status' => (string) $invoice->status,
                'dueDate' => $invoice->due_date?->toIso8601String(),
                'periodEnd' => $subscription?->current_period_end?->toIso8601String(),
                'tier' => (string) ($subscription?->tier ?? ''),
            ],
            // Alamat tenant disensor (D9) — boleh tampil, ini bukan data pelanggan.
            // Membantu owner mengenali tagihan ini milik toko yang mana.
            'address' => $this->maskedTenantAddress($tenant?->subdomain),
            // D4: null kalau tidak ada yang perlu dibayar (lunas / kedaluwarsa /
            // nominal nol). Tombol bayar tidak dirender saat null.
            'payUrl' => app(SubscriptionPaymentLinkResolver::class)->resolve($invoice),
            'contactUrl' => $contactUrl,
            'contactIsWhatsapp' => $this->contactIsWhatsapp($contactUrl),
            'meta' => [
                'robots' => 'noindex, nofollow',
                // D9: tanpa ini `app-inertia.blade.php` menurunkan canonical dari
                // `url()->current()`, yang membocorkan alamat yang seharusnya
                // tersembunyi. Halaman ini juga `noindex`, jadi tidak butuh canonical.
                'canonical' => '',
            ],
        ]);
    }

    /**
     * Label tenant yang disensor (D13.4) — tanpa basis domain platform.
     *
     * Header halaman tagihan tidak boleh menyebut merek tenant, dan alamat
     * lengkap pun tidak perlu: yang ditampilkan hanya labelnya (`w**********p`).
     */
    protected function maskedTenantAddress(?string $subdomain): string
    {
        if (blank($subdomain)) {
            return '';
        }

        return app(TenantAddressMasker::class)->maskLabel((string) $subdomain);
    }
}
