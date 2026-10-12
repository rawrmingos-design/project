<?php

namespace Tests\Feature\Tenancy;

use App\Models\SettingWeb;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Halaman status untuk tenant non-aktif menggantikan 404 polos.
 *
 * Konvensi: `config('app.url')` menentukan host platform; subdomain di bawahnya
 * otomatis dikenali ResolveTenant (lihat TenantStorefrontPhase2Test).
 */
class InactiveTenantStatusPageTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://topupengine.test';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => self::BASE,
            'tenancy.disabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        app()->forgetInstance('tenant');
        parent::tearDown();
    }

    public function test_suspended_subdomain_menampilkan_halaman_status_bukan_404(): void
    {
        $this->makeTenant([
            'name' => 'Warung Topup',
            'subdomain' => 'warung-topup',
            'status' => Tenant::STATUS_SUSPENDED,
        ]);

        $this->get('https://warung-topup.topupengine.test/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/TenantStatus')
                ->where('status', Tenant::STATUS_SUSPENDED)
                // D9: hanya label pertama yang disensor.
                ->where('address', 'w**********p.topupengine.test')
                // D13.5: noindex lewat meta props, bukan header.
                ->where('meta.robots', 'noindex, nofollow')
            );
    }

    public function test_pending_payment_subdomain_menampilkan_halaman_status(): void
    {
        $this->makeTenant([
            'name' => 'Toko Baru',
            'subdomain' => 'toko-baru',
            'status' => Tenant::STATUS_PENDING_PAYMENT,
        ]);

        $this->get('https://toko-baru.topupengine.test/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/TenantStatus')
                ->where('status', Tenant::STATUS_PENDING_PAYMENT)
            );
    }

    public function test_tenant_cancelled_tetap_404(): void
    {
        // D11: nama sudah dilepas, halaman penjelasan tidak berlaku.
        $this->makeTenant([
            'name' => 'Mantan Topup',
            'subdomain' => 'mantan-topup',
            'status' => Tenant::STATUS_CANCELLED,
        ]);

        $this->get('https://mantan-topup.topupengine.test/')->assertNotFound();
    }

    public function test_subdomain_tak_dikenal_tetap_404(): void
    {
        $this->get('https://tidak-ada.topupengine.test/')->assertNotFound();
    }

    public function test_tenant_aktif_tidak_dicegat_halaman_status(): void
    {
        $this->makeTenant([
            'name' => 'Toko Jaya',
            'subdomain' => 'toko-jaya',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        // Tenant aktif tetap dilayani storefront-nya sendiri.
        $this->get('https://toko-jaya.topupengine.test/')
            ->assertOk()
            ->assertDontSee('TenantStatus');
    }

    public function test_kill_switch_menonaktifkan_halaman_status(): void
    {
        config(['tenancy.disabled' => true]);

        $this->makeTenant([
            'name' => 'Warung Topup',
            'subdomain' => 'warung-topup',
            'status' => Tenant::STATUS_SUSPENDED,
        ]);

        $this->get('https://warung-topup.topupengine.test/')->assertNotFound();
    }

    public function test_props_tidak_membocorkan_identitas_tenant_maupun_owner(): void
    {
        $owner = User::factory()->create([
            'role' => 'Gold',
            'email' => 'rahasia-owner@example.test',
            'no_wa' => '6285712345678',
        ]);

        Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Warung Topup',
            'subdomain' => 'warung-topup',
            'tier' => 'starter',
            'status' => Tenant::STATUS_SUSPENDED,
        ]);

        $props = null;

        $this->get('https://warung-topup.topupengine.test/')
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$props) {
                $props = json_encode($page->toArray());
            });

        $this->assertIsString($props);

        // D9/D10/D13.4: nama tenant utuh, subdomain utuh, email & WA owner
        // tidak boleh ikut terkirim ke klien.
        $this->assertStringNotContainsString('Warung Topup', $props);
        $this->assertStringNotContainsString('warung-topup', $props);
        $this->assertStringNotContainsString('rahasia-owner@example.test', $props);
        $this->assertStringNotContainsString('6285712345678', $props);
    }

    public function test_halaman_status_hidup_di_luar_tenant_context(): void
    {
        // D2: kalau TenantContext diset, global scope tenant akan mengosongkan
        // halaman ini sendiri. Middleware harus membersihkannya di akhir.
        $this->makeTenant([
            'name' => 'Warung Topup',
            'subdomain' => 'warung-topup',
            'status' => Tenant::STATUS_SUSPENDED,
        ]);

        $this->get('https://warung-topup.topupengine.test/')->assertOk();

        $this->assertFalse(app(TenantContext::class)->has());
    }

    public function test_kontak_memakai_wa_dari_setting(): void
    {
        // D8: url_wa terisi → dipakai apa adanya.
        $this->createSettings([
            'url_wa' => 'https://wa.me/6285123031674',
            'nomor_admin' => '6285792464508',
        ]);

        $this->makeSuspendedWarung();

        $this->get('https://warung-topup.topupengine.test/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('contactUrl', 'https://wa.me/6285123031674')
                ->where('contactIsWhatsapp', true)
            );
    }

    public function test_kontak_jatuh_ke_nomor_admin_kalau_url_wa_kosong(): void
    {
        $this->createSettings([
            'url_wa' => '',
            'nomor_admin' => '6285792464508',
        ]);

        $this->makeSuspendedWarung();

        $this->get('https://warung-topup.topupengine.test/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('contactUrl', 'https://wa.me/6285792464508')
                ->where('contactIsWhatsapp', true)
            );
    }

    public function test_kontak_fallback_platform_kalau_keduanya_kosong(): void
    {
        // Q5: url_wa + nomor_admin dua-duanya kosong → label netral "Hubungi kami",
        // bukan klaim "WhatsApp" yang tidak benar.
        $this->createSettings([
            'url_wa' => '',
            'nomor_admin' => '',
        ]);

        $this->makeSuspendedWarung();

        $this->get('https://warung-topup.topupengine.test/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('contactUrl', self::BASE)
                ->where('contactIsWhatsapp', false)
            );
    }

    public function test_tidak_ada_props_waktu_suspend(): void
    {
        // Q6: baris "Sejak (kapan disuspend)" tidak ditampilkan.
        $this->makeSuspendedWarung();

        $this->get('https://warung-topup.topupengine.test/')
            ->assertOk()
            ->assertInertia(function (Assert $page) {
                $props = $page->toArray();

                foreach (['suspendedAt', 'suspended_at', 'since', 'sinceSuspended', 'suspendedSince'] as $key) {
                    $this->assertArrayNotHasKey($key, $props, "Props '{$key}' tidak boleh dikirim (Q6).");
                }
            });
    }

    public function test_halaman_platform_slash_id_juga_dicegat(): void
    {
        // Grup tenant hanya menangani `/`; homepage platform hidup di `/id`.
        // Tanpa ini, subdomain tenant non-aktif tetap menyajikan homepage
        // platform lengkap di `/id`.
        $this->makeSuspendedWarung();

        $this->get('https://warung-topup.topupengine.test/id')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/TenantStatus'));

        $this->get('https://warung-topup.topupengine.test/id/price-list')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/TenantStatus'));
    }

    public function test_request_non_get_tidak_ditulis_ulang(): void
    {
        // Menulis ulang POST akan mengubah arti request (mis. forgot-password
        // di subdomain tenant non-aktif) tanpa alasan.
        $this->makeSuspendedWarung();

        $this->post('https://warung-topup.topupengine.test/id/forgot-password', [
            'email' => 'apa-saja@example.test',
        ])->assertStatus(302);
    }

    public function test_tenant_inactive_berjalan_sebelum_tenant_required(): void
    {
        // Urutan middleware yang menentukan: `tenant.inactive` harus lebih dulu
        // supaya 404 `tenant.required` tidak pernah tercapai untuk tenant
        // non-aktif. Kalau urutannya terbalik, halaman status hilang tanpa
        // satu pun test di atas gagal — jadi dikunci di sini.
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn ($route) => $route->getName() === 'tenant.home');

        $this->assertNotNull($route, 'Route tenant.home tidak ditemukan.');

        $middleware = $route->gatherMiddleware();
        $inactive = array_search('tenant.inactive', $middleware, true);
        $required = array_search('tenant.required', $middleware, true);

        $this->assertNotFalse($inactive, 'tenant.inactive tidak terpasang di grup tenant.');
        $this->assertNotFalse($required, 'tenant.required tidak terpasang di grup tenant.');
        $this->assertLessThan(
            $required,
            $inactive,
            'tenant.inactive harus SEBELUM tenant.required, bukan sesudahnya.'
        );
    }

    public function test_halaman_status_tidak_memakai_layout_storefront(): void
    {
        // Anti-slop statis: halaman ini milik PLATFORM — tidak boleh mewarisi
        // warna merek tenant atau menarik Navbar/Footer storefront, dan tidak
        // boleh menyentuh config storefront.
        $files = [
            base_path('resources/js/public/Pages/Public/TenantStatus.jsx'),
            base_path('resources/js/public/Layouts/TenantShell.jsx'),
            base_path('resources/js/public/Pages/Public/TenantBilling.jsx'),
        ];

        $terlarang = [
            'PublicLayout', 'siteConfig', 'navbar', 'Navbar', 'Footer',
            'gradient', 'purple', 'indigo', 'violet',
            'rounded-2xl', 'rounded-3xl', 'shadow-2xl',
        ];

        foreach ($files as $file) {
            $this->assertFileExists($file, "Komponen halaman status hilang: {$file}");

            // Komentar sengaja ikut dibuang: keduanya menjelaskan MENGAPA
            // `PublicLayout` tidak dipakai, dan itu justru dokumentasi yang
            // diinginkan. Yang diperiksa adalah kodenya.
            $isi = (string) file_get_contents($file);
            $isi = (string) preg_replace('#/\*.*?\*/#s', '', $isi);
            $isi = (string) preg_replace('#^\s*//.*$#m', '', $isi);

            foreach ($terlarang as $kata) {
                $this->assertStringNotContainsString(
                    $kata,
                    $isi,
                    basename($file) . " menyentuh '{$kata}' — di luar design system (§1) atau di luar peran halaman platform."
                );
            }

            $this->assertDoesNotMatchRegularExpression(
                '/#[0-9a-fA-F]{6}\b/',
                $isi,
                basename($file) . ' memakai hex warna langsung; pakai token di tailwind.config.js.'
            );
        }
    }

    public function test_halaman_status_dikirim_dengan_meta_noindex(): void
    {
        // Footer halaman mengklaim "tidak diindeks mesin pencari", jadi klaim
        // itu harus benar-benar terpasang: `meta.robots` dibaca
        // `app-inertia.blade.php` di jalur non-SSR dan `TenantShell` di jalur SSR.
        $this->makeSuspendedWarung();

        $this->get('https://warung-topup.topupengine.test/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/TenantStatus')
                ->where('meta.robots', 'noindex, nofollow')
                // D9: canonical kosong supaya blade tidak menurunkan URL dari
                // `url()->current()` (host tenant mentah).
                ->where('meta.canonical', '')
            );
    }

    public function test_shell_halaman_status_merender_robots(): void
    {
        // Static: `TenantShell` adalah satu-satunya yang merender tag SEO di
        // jalur SSR (blade melewatkannya saat SSR aktif), jadi tanpa <Head>
        // di sini `noindex` hilang sepenuhnya di produksi.
        $isi = (string) file_get_contents(base_path('resources/js/public/Layouts/TenantShell.jsx'));
        $isi = (string) preg_replace('#/\*.*?\*/#s', '', $isi);
        $isi = (string) preg_replace('#^\s*//.*$#m', '', $isi);

        $this->assertStringContainsString('<Head', $isi, 'TenantShell tidak punya blok <Head>.');
        $this->assertStringContainsString('name="robots"', $isi, 'TenantShell tidak merender meta robots.');
        $this->assertStringContainsString(
            'noindex',
            $isi,
            'robots di TenantShell bukan noindex — halaman status akan terindeks.'
        );
        $this->assertStringNotContainsString(
            'SeoHead',
            $isi,
            'TenantShell tidak boleh memakai SeoHead: dia membaca siteConfig/seoDefaults '
                . 'yang sengaja dibuang dari halaman platform, dan menurunkan canonical dari '
                . 'window.location.href (host tenant mentah).'
        );
    }

    public function test_suspended_menyediakan_jalur_ke_halaman_tagihan(): void
    {
        // Task 6: tokonya sendiri tidak melayani apa pun saat ditangguhkan, jadi
        // tautan tagihan adalah satu-satunya jalan pulang untuk membayar.
        [$tenant, $invoice] = $this->makeSuspendedWarungWithInvoice();

        $response = $this->get('https://warung-topup.topupengine.test/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/TenantStatus')
                ->where('status', Tenant::STATUS_SUSPENDED)
                ->where('meta.robots', 'noindex, nofollow')
            );

        $props = $response->viewData('page')['props'];

        $this->assertStringContainsString('/tagihan/', (string) $props['billingUrl']);
        $this->assertStringContainsString(
            (string) $invoice->fresh()->public_token,
            (string) $props['billingUrl'],
            'Tautan harus memakai token yang tersimpan, bukan token baru.'
        );
        $this->assertStringNotContainsString('duitku', (string) $props['billingUrl']);
    }

    public function test_props_halaman_status_tetap_tidak_membocorkan_identitas(): void
    {
        $this->makeSuspendedWarungWithInvoice();

        $response = $this->get('https://warung-topup.topupengine.test/')->assertOk();

        $props = json_encode($response->viewData('page')['props'], JSON_THROW_ON_ERROR);

        foreach ([
            'Warung Topup',                 // nama tenant
            'owner-status@example.test',    // email owner
            'siteConfig',
            'seoDefaults',
            'gateway_ref',
            'SUB-',
        ] as $bocor) {
            $this->assertStringNotContainsString($bocor, $props, "Halaman status membocorkan identitas.");
        }

        // Yang boleh tampil hanya alamat tersensor.
        $this->assertStringContainsString('w**********p', $props);
    }

    public function test_pending_payment_juga_dapat_tautan_tagihan(): void
    {
        // Data staging membantah asumsi awal: tenant `toko` berstatus
        // `pending_payment` memegang tagihan Rp 1.500.000 yang menggantung.
        // Menyembunyikan tautannya berarti menahan satu-satunya jalan bayar
        // tenant baru.
        [$tenant, $invoice] = $this->makeWarungWithPendingInvoice(Tenant::STATUS_PENDING_PAYMENT);

        $response = $this->get('https://warung-topup.topupengine.test/')->assertOk();
        $props = $response->viewData('page')['props'];

        $this->assertSame(Tenant::STATUS_PENDING_PAYMENT, $props['status']);
        $this->assertStringContainsString('/tagihan/', (string) $props['billingUrl']);
        $this->assertStringContainsString((string) $invoice->fresh()->public_token, (string) $props['billingUrl']);
    }

    public function test_tenant_tanpa_tagihan_pending_tidak_menawarkan_tautan_mati(): void
    {
        // Jangan pernah merender tombol yang menuntun ke halaman 404. Tenant 2 di
        // staging persis kasus ini: `suspended`, tapi invoice-nya sudah `paid`.
        $this->makeTenant([
            'name' => 'Warung Topup',
            'subdomain' => 'warung-topup',
            'status' => Tenant::STATUS_SUSPENDED,
        ]);

        $this->get('https://warung-topup.topupengine.test/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('billingUrl', null));
    }

    public function test_tautan_tagihan_di_halaman_status_tidak_merotasi_token(): void
    {
        // D13.1: token yang sudah dikirim lewat email/WA tidak boleh berubah
        // hanya karena halaman status dibuka.
        [, $invoice] = $this->makeSuspendedWarungWithInvoice();

        $tokenAwal = $invoice->ensurePublicToken();

        for ($i = 0; $i < 3; $i++) {
            $this->get('https://warung-topup.topupengine.test/')->assertOk();
        }

        $this->assertSame($tokenAwal, $invoice->fresh()->public_token);
    }

    public function test_halaman_status_menyebut_pemulihan_otomatis(): void
    {
        // Task 6: penutup siklus — pengunjung tahu situsnya kembali sendiri
        // begitu pembayaran tercatat. Dikunci statis karena kalimat ini hidup di
        // komponen (blade melewatkan tag saat SSR aktif).
        $this->makeSuspendedWarungWithInvoice();

        $response = $this->get('https://warung-topup.topupengine.test/')->assertOk();

        $this->assertSame(
            'Public/TenantStatus',
            $response->viewData('page')['component']
        );

        $komponen = (string) file_get_contents(base_path('resources/js/public/Pages/Public/TenantStatus.jsx'));

        $this->assertStringContainsString(
            'aktif kembali otomatis',
            $komponen,
            'Halaman status harus menyebut pemulihan otomatis.'
        );
    }

    private function makeSuspendedWarungWithInvoice(): array
    {
        return $this->makeWarungWithPendingInvoice(Tenant::STATUS_SUSPENDED);
    }

    /** Tenant `warung-topup` dengan satu tagihan perpanjangan yang menggantung. */
    private function makeWarungWithPendingInvoice(string $status): array
    {
        $owner = User::factory()->create([
            'name' => 'Rahasia Status',
            'role' => 'Gold',
            'email' => 'owner-status@example.test',
        ]);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Warung Topup',
            'subdomain' => 'warung-topup',
            'tier' => 'starter',
            'status' => $status,
        ]);

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'tier' => 'starter',
            'price' => 500000,
            'status' => Subscription::STATUS_ACTIVE,
            'gateway_ref' => 'SUB-STATUS-' . strtoupper(uniqid()),
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->subDays(5),
        ]);

        $invoice = SubscriptionInvoice::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => 510000,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => 'duitku',
            'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
            'due_date' => now()->subDay(),
            'metadata' => ['source' => 'billing_renewal', 'kind' => 'renewal', 'late_fee' => 10000],
        ]);

        return [$tenant, $invoice];
    }

    private function makeSuspendedWarung(): void
    {
        $this->makeTenant([
            'name' => 'Warung Topup',
            'subdomain' => 'warung-topup',
            'status' => Tenant::STATUS_SUSPENDED,
        ]);
    }

    private function makeTenant(array $attributes = []): Tenant
    {
        $owner = User::factory()->create(['role' => 'Gold']);

        return Tenant::query()->create(array_merge([
            'owner_user_id' => $owner->id,
            'name' => 'Tenant Store',
            'subdomain' => 'tenant-store',
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
        ], $attributes));
    }

    private function createSettings(array $overrides = []): void
    {
        SettingWeb::query()->create(array_merge([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Demo storefront',
            'keywords' => 'top up game',
            'logo_header' => 'assets/logo/logo.webp',
            'logo_footer' => 'assets/logo/footer.webp',
            'logo_favicon' => 'assets/logo/favicon.webp',
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/testweb',
            'url_tiktok' => 'https://tiktok.com/@testweb',
            'url_youtube' => 'https://youtube.com/@testweb',
            'url_fb' => 'https://facebook.com/testweb',
            'topupindo_api' => 'demo-topupindo-key',
            'paydisini_apikey' => 'demo-paydisini-key',
            'order_prefik' => 'TST',
            'warna1' => '#0f172a',
            'warna2' => '#ea580c',
            'warna3' => '#f59e0b',
            'warna4' => '#fb923c',
            'public_theme' => 'default',
        ], $overrides));
    }
}
