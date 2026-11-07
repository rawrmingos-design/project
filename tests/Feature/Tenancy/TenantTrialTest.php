<?php

namespace Tests\Feature\Tenancy;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\BillingRenewalService;
use App\Tenancy\TenantRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Uji coba 3 hari.
 *
 * Aturan yang dijaga:
 *  - Trial tidak pernah menghasilkan invoice. Kalau trial menagih, "gratis 3
 *    hari" jadi bohong dan user melihat tagihan di hari pertama.
 *  - Selama trial, toko harus BISA dipakai (status `active`, tidak disuspend).
 *  - Saat trial habis, invoice periode PENUH terbit — bukan tagihan sisa hari.
 *  - Setelah bayar, periode dihitung dari HARI PEMBAYARAN, bukan dari akhir
 *    trial, supaya user tidak kehilangan hari yang sudah dibayar.
 *  - Trial hanya sekali per toko: tidak bisa dipakai untuk menunda bayar
 *    berulang kali.
 */
uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config([
        'tenancy.disabled' => false,
        'tenancy.trial_enabled' => true,
        'tenancy.trial_days' => 3,
        'billing.renewal_enabled' => true,
        'billing.reminder_days_before' => 3,
        'billing.grace_days' => 3,
        'billing.late_fee' => 10000,
    ]);

    // Invoice trial-habis dibuat lewat gateway Duitku; tanpa kredensial tiruan
    // ini, pembuatan invoice melempar RuntimeException dan tes gagal karena
    // sebab yang salah (bukan karena logika trial).
    \App\Models\SettingWeb::query()->firstOrCreate(['id' => 1], [
        'judul_web' => 'Test Web',
        'deskripsi_web' => 'Test Description',
        'keywords' => 'test',
        'url_wa' => 'https://wa.me/628123456789',
        'url_ig' => 'https://instagram.com/test',
        'url_tiktok' => 'https://tiktok.com/@test',
        'url_youtube' => 'https://youtube.com/test',
        'url_fb' => 'https://facebook.com/test',
        'topupindo_api' => 'topupindo-test',
        'warna1' => '#111111',
        'warna2' => '#222222',
        'warna3' => '#333333',
        'warna4' => '#444444',
        'order_prefik' => 'INV',
        'paydisini_apikey' => 'paydisini-test-key',
        'tripay_api' => 'tripay-test-key',
        'tripay_merchant_code' => 'tripay-merchant-test',
        'tripay_private_key' => 'tripay-private-test',
        'duitku_merchant_code' => 'DTEST',
        'duitku_merchant_key' => 'duitku-test-key',
    ]);

    // Gateway pembayaran di-mock: tes ini menguji LOGIKA trial, bukan koneksi
    // ke Duitku. Tanpa mock, tes gagal karena "Merchant Not Found" — sebab yang
    // salah, dan hasilnya berubah-ubah tergantung jaringan.
    $this->mock(\App\Tenancy\DuitkuSubscriptionPaymentService::class, function ($mock) {
        $mock->shouldReceive('createAndStoreInvoice')
            ->andReturnUsing(fn ($invoice) => $invoice);
    });
});

function buatTenantTrial(int $sisaHari = 3): Tenant
{
    $owner = User::factory()->create(['role' => 'tenant_owner', 'balance' => 0]);

    $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Toko Trial',
            'subdomain' => 'toko-trial',
            'tier' => 'starter',
            'status' => Tenant::STATUS_TRIAL,
            'trial_ends_at' => now()->addDays($sisaHari),
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => 10],
        ]);

    $owner->forceFill(['tenant_id' => $tenant->id])->save();

    Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'tier' => 'starter',
        'duration_months' => 3,
        'price' => 0,
        'status' => Subscription::STATUS_ACTIVE,
        'current_period_start' => now(),
        'current_period_end' => now()->addDays($sisaHari),
    ]);

    return $tenant->fresh();
}

    // ------------------------------------------------------------------ state

    test('tenant trial punya masa aktif dan dihitung sisa harinya', function () {
        $tenant = buatTenantTrial(3);

        expect($tenant->isOnTrial())->toBeTrue();
        expect($tenant->trialDaysLeft())->toBe(3);
        expect($tenant->trial_ends_at)->not->toBeNull();
    });

    test('trial yang sudah lewat tidak dianggap trial lagi', function () {
        $tenant = buatTenantTrial(3);
        Carbon::setTestNow(now()->addDays(4));

        expect($tenant->fresh()->isOnTrial())->toBeFalse();
        expect($tenant->fresh()->trialDaysLeft())->toBe(0);

        Carbon::setTestNow();
    });

    test('tenant non-trial tidak punya sisa hari trial', function () {
        $tenant = buatTenantTrial(3);
        $tenant->forceFill(['status' => Tenant::STATUS_ACTIVE, 'trial_ends_at' => null])->save();

        expect($tenant->fresh()->isOnTrial())->toBeFalse();
        expect($tenant->fresh()->trialDaysLeft())->toBe(0);
    });

    // --------------------------------------------------------------- tagging

    test('trial TIDAK menagih walau sisa 1 hari', function () {
        $tenant = buatTenantTrial(1);

        $stats = app(BillingRenewalService::class)->renewDueSubscriptions(apply: true);

        expect((int) SubscriptionInvoice::query()->count())->toBe(0);
        expect($stats['created'])->toBe(0);
        expect($tenant->fresh()->status)->toBe(Tenant::STATUS_TRIAL);
    });

    test('trial yang habis tidak kena denda telat bayar', function () {
        buatTenantTrial(3);
        // Lewati akhir trial tanpa bayar → masuk tenggang.
        Carbon::setTestNow(now()->addDays(5));

        app(BillingRenewalService::class)->renewDueSubscriptions(apply: true);

        $invoice = SubscriptionInvoice::query()->first();

        // Invoice terbit (trial sudah selesai), tapi TANPA denda: user belum
        // pernah ditagih, jadi tidak mungkin dihitung "telat bayar".
        expect($invoice)->not->toBeNull();
        expect((int) data_get($invoice->metadata, 'late_fee'))->toBe(0);
        expect((int) $invoice->amount)->toBe(330000);

        Carbon::setTestNow();
    });

    test('tenant trial TIDAK disuspend walau masa trial lewat', function () {
        $tenant = buatTenantTrial(3);
        Carbon::setTestNow(now()->addDays(10));

        app(BillingRenewalService::class)->suspendOverdue(apply: true);

        expect($tenant->fresh()->status)->toBe(Tenant::STATUS_TRIAL);

        Carbon::setTestNow();
    });

    test('saat trial habis, invoice periode PENUH terbit sekali', function () {
        $tenant = buatTenantTrial(3);

        // Tepat setelah trial berakhir.
        Carbon::setTestNow(now()->addDays(4));

        app(BillingRenewalService::class)->renewDueSubscriptions(apply: true);

        $invoice = SubscriptionInvoice::query()->first();

        expect($invoice)->not->toBeNull();
        // Harga 3 bulan penuh, bukan harga sisa hari.
        expect((int) $invoice->amount)->toBe(330000);

        Carbon::setTestNow();
    });

    test('invoice trial-habis tidak terbit dua kali', function () {
        $tenant = buatTenantTrial(3);
        Carbon::setTestNow(now()->addDays(4));

        app(BillingRenewalService::class)->renewDueSubscriptions(apply: true);
        app(BillingRenewalService::class)->renewDueSubscriptions(apply: true);

        expect((int) SubscriptionInvoice::query()->count())->toBe(1);

        Carbon::setTestNow();
    });

    // --------------------------------------------------------------- daftar

    test('registrasi dengan trial membuat tenant trial tanpa invoice', function () {
        $hasil = app(TenantRegistrationService::class)->register([
            'name' => 'Pemilik Trial',
            'email' => 'trial-' . uniqid() . '@contoh.test',
            'password' => 'RahasiaKuat123!',
            'no_wa' => '081234567890',
            'store_name' => 'Toko Mulai',
            'subdomain' => 'toko-mulai-' . substr(uniqid(), -5),
            'tier' => 'starter',
            'duration_months' => 3,
            'trial' => true,
        ]);

        expect($hasil['tenant']->status)->toBe(Tenant::STATUS_TRIAL);
        expect($hasil['tenant']->trial_ends_at)->not->toBeNull();
        expect($hasil['invoice'])->toBeNull();
    });

    test('registrasi tanpa trial tetap membuat invoice seperti biasa', function () {
        $hasil = app(TenantRegistrationService::class)->register([
            'name' => 'Pemilik Bayar',
            'email' => 'bayar-' . uniqid() . '@contoh.test',
            'password' => 'RahasiaKuat123!',
            'no_wa' => '081234567890',
            'store_name' => 'Toko Bayar',
            'subdomain' => 'toko-bayar-' . substr(uniqid(), -5),
            'tier' => 'starter',
            'duration_months' => 3,
        ]);

        expect($hasil['invoice'])->not->toBeNull();
        expect($hasil['tenant']->status)->toBe(Tenant::STATUS_PENDING_PAYMENT);
    });

    test('trial bisa dimatikan lewat config tanpa mengubah kode', function () {
        config(['tenancy.trial_enabled' => false]);

        $hasil = app(TenantRegistrationService::class)->register([
            'name' => 'Pemilik Tanpa Trial',
            'email' => 'notrial-' . uniqid() . '@contoh.test',
            'password' => 'RahasiaKuat123!',
            'no_wa' => '081234567890',
            'store_name' => 'Toko Tanpa Trial',
            'subdomain' => 'toko-notrial-' . substr(uniqid(), -5),
            'tier' => 'starter',
            'duration_months' => 3,
            'trial' => true,
        ]);

        expect($hasil['tenant']->status)->toBe(Tenant::STATUS_PENDING_PAYMENT);
        expect($hasil['invoice'])->not->toBeNull();
    });

    // -------------------------------------------------------------- resolusi

    test('tenant trial tetap resolve di tengah masa uji', function () {
        $tenant = buatTenantTrial(3);

        expect(Tenant::query()
            ->where('subdomain', $tenant->subdomain)
            ->whereIn('status', [Tenant::STATUS_ACTIVE, Tenant::STATUS_TRIAL])
            ->exists())->toBeTrue();
    });

    test('tenant trial tidak dianggap halaman nonaktif', function () {
        $tenant = buatTenantTrial(3);

        expect(Tenant::query()->whereIn('status', [
            Tenant::STATUS_SUSPENDED,
            Tenant::STATUS_PENDING_PAYMENT,
        ])->whereKey($tenant->id)->exists())->toBeFalse();
    });

    test('setelah bayar dari trial, periode dihitung dari hari pembayaran', function () {
        $tenant = buatTenantTrial(3);
        Carbon::setTestNow(now()->addDays(4));

        app(BillingRenewalService::class)->renewDueSubscriptions(apply: true);
        $invoice = SubscriptionInvoice::query()->first();

        // Bayar 2 hari setelah invoice terbit.
        Carbon::setTestNow(now()->addDays(6));
        app(\App\Tenancy\TenantProvisioningService::class)->markInvoicePaid($invoice);

        $tenant->refresh();
        $subscription = Subscription::query()->where('tenant_id', $tenant->id)->first();

        expect($tenant->status)->toBe(Tenant::STATUS_ACTIVE);
        expect($tenant->trial_ends_at)->toBeNull();
        // 3 bulan penuh dari hari bayar, bukan dari akhir trial.
        expect($subscription->current_period_end->toDateString())
            ->toBe(now()->addMonths(3)->toDateString());

        Carbon::setTestNow();
    });
