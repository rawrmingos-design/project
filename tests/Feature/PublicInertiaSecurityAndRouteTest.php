<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Berita;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\Rating;
use App\Models\SettingWeb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicInertiaSecurityAndRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seedSettings('bangjeff');
    }

    #[Test]
    public function bangjeff_article_content_is_sanitized_before_reaching_the_inertia_page(): void
    {
        Artikel::create([
            'title' => 'Artikel Aman',
            'slug' => 'artikel-aman',
            'content' => '<p>Konten <strong>aman</strong>.</p><script>alert(1)</script><img src="x" onerror="alert(2)"><a href="javascript:alert(3)">Link</a>',
            'status' => 'active',
        ]);

        $this->get('/id/artikel/artikel-aman')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Articles/Show')
                ->where('article.content', fn (string $content) => str_contains($content, '<strong>aman</strong>')
                    && ! str_contains($content, '<script')
                    && ! str_contains($content, 'onerror')
                    && ! str_contains(strtolower($content), 'javascript:'))
            );
    }

    #[Test]
    public function bangjeff_popup_description_is_sanitized_before_reaching_the_inertia_page(): void
    {
        Berita::create([
            'tipe' => 'popup',
            'judul' => 'Info penting',
            'deskripsi' => '<p>Promo <em>aman</em>.</p><svg onload="alert(1)"></svg><a href="javascript:alert(2)">Klik</a>',
        ]);

        $this->get('/id')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Home')
                ->where('popup.description', fn (string $content) => str_contains($content, '<em>aman</em>')
                    && ! str_contains($content, 'onload')
                    && ! str_contains(strtolower($content), 'javascript:'))
            );
    }

    #[Test]
    public function recent_transaction_scope_stays_private_for_users_and_guests(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['username' => 'owner']);
        User::factory()->create(['username' => 'other-user']);

        $this->createTransaction('INV-OWNER-001', 'owner');
        $this->createTransaction('INV-OTHER-001', 'other-user');

        $this->actingAs($owner)
            ->get('/id/invoices')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/CheckTransactions')
                ->where('recentTransactionsScope.key', 'auth-user')
                ->has('recentTransactions', 1)
                ->where('recentTransactions.0.invoiceId', 'INV-OWNER-001')
            );

        $this->app['auth']->forgetGuards();

        $this->get('/id/invoices')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('recentTransactionsScope.key', 'guest-empty')
                ->has('recentTransactions', 0)
            );

        $this->withSession(['public_recent_order_ids' => ['INV-OTHER-001']])
            ->get('/id/invoices')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('recentTransactionsScope.key', 'guest-session')
                ->has('recentTransactions', 1)
                ->where('recentTransactions.0.invoiceId', 'INV-OTHER-001')
            );
    }

    #[Test]
    public function dashboard_uses_monetary_balance_and_preserves_affiliate_ctas(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'username' => 'dashboard-balance-owner',
            'balance' => 125000,
            'point_balance' => 37,
            'affiliate_status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->get('/id/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Dashboard')
                ->where('dashboard.credits.coinName', 'Saldo')
                ->where('dashboard.credits.coinSymbol', 'Rp')
                ->where('dashboard.credits.amount', 125000)
                ->where('dashboard.credits.showTopUp', true)
                ->where('dashboard.credits.showRedeem', false)
            );

        $this->setTheme('default');

        $this->get('/id/dashboard')
            ->assertOk()
            ->assertViewIs('template.dashboard')
            ->assertSee('Saldo')
            ->assertSee('Rp 125.000');
    }

    #[Test]
    public function active_affiliate_dashboard_keeps_redeem_cta_with_zero_monetary_balance(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'username' => 'dashboard-affiliate-owner',
            'balance' => 0,
            'point_balance' => 250,
            'affiliate_status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/id/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('dashboard.credits.amount', 0)
                ->where('dashboard.credits.showTopUp', false)
                ->where('dashboard.credits.showRedeem', true)
                ->where('dashboard.links.redeem', route('withdrawal'))
            );
    }

    #[Test]
    public function guest_cannot_access_dashboard(): void
    {
        $this->get('/id/dashboard')->assertRedirect();
    }

    #[Test]
    public function order_pages_keep_supported_inertia_and_legacy_fallback_boundaries(): void
    {
        $supportedCategory = Kategori::factory()->create([
            'kode' => 'mobile-legends',
            'tipe' => 'game',
        ]);
        $unsupportedCategory = Kategori::factory()->create([
            'kode' => 'giftskin',
            'tipe' => 'giftskin',
        ]);

        $this->get("/id/{$supportedCategory->kode}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Order'));

        $this->get("/id/{$unsupportedCategory->kode}")
            ->assertOk()
            ->assertViewIs('template.order');

        $this->setTheme('default');

        $this->get("/id/{$supportedCategory->kode}")
            ->assertOk()
            ->assertViewIs('template.order');
    }

    #[Test]
    public function valid_information_pages_dual_render_without_bangjeff_legacy_redirects(): void
    {
        $category = Kategori::factory()->create(['nama' => 'Mobile Legends']);
        Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => '86 Diamond',
            'harga_member' => 20000,
            'harga_gold' => 19000,
            'harga_platinum' => 18000,
        ]);

        Pembelian::create([
            'order_id' => 'INV-REVIEW-001',
            'username' => 'reviewer',
            'user_id' => '12345678',
            'layanan' => '86 Diamond',
            'harga' => 20000,
            'profit' => 0,
            'status' => 'Sukses',
            'tipe_transaksi' => 'game',
        ]);
        Pembayaran::create([
            'order_id' => 'INV-REVIEW-001',
            'harga' => 20000,
            'no_pembayaran' => 'PAY-REVIEW-001',
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'QRIS',
        ]);
        Rating::create([
            'rating_id' => 'INV-REVIEW-001',
            'kategori_id' => (string) $category->id,
            'bintang' => '5',
            'comment' => 'Pelayanan cepat',
            'username' => 'reviewer',
            'layanan' => '86 Diamond',
            'no_pembeli' => '08123456789',
        ]);

        $this->get('/id/price-list')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PriceList')
                ->has('priceList.categories', 1)
                ->has('priceList.products', 1)
                ->where('priceList.products.0.name', '86 Diamond')
            );

        $this->get('/id/reviews')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Reviews')
                ->has('reviews', 1)
                ->where('reviews.0.comment', 'Pelayanan cepat')
            );

        $this->get('/id/forgot-password')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/ForgotPassword'));

        $this->get('/id/cek-region')->assertRedirect('/id');

        $this->setTheme('default');

        $this->get('/id/price-list')->assertOk()->assertViewIs('template.pricelist');
        $this->get('/id/reviews')->assertOk()->assertViewIs('template.ratingcust');
        $this->get('/id/forgot-password')->assertOk()->assertViewIs('template.forgotpassword');
    }

    private function createTransaction(string $orderId, string $username): void
    {
        Pembelian::create([
            'order_id' => $orderId,
            'username' => $username,
            'user_id' => '12345678',
            'layanan' => 'Membership',
            'harga' => 15000,
            'profit' => 0,
            'status' => 'Sukses',
            'tipe_transaksi' => 'game',
        ]);
        Pembayaran::create([
            'order_id' => $orderId,
            'harga' => 15000,
            'no_pembayaran' => 'PAY-' . $orderId,
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'QRIS',
        ]);
    }

    private function setTheme(string $theme): void
    {
        SettingWeb::query()->whereKey(1)->update(['public_theme' => $theme]);
        Cache::flush();
    }

    private function seedSettings(string $theme): void
    {
        SettingWeb::create([
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
            'public_theme' => $theme,
        ]);
    }
}
