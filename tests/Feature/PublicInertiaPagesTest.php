<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Berita;
use App\Models\CategoryType;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicInertiaPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('inertia.testing.ensure_pages_exist', false);
    }

    public function test_homepage_renders_inertia_with_theme_fallback(): void
    {
        $this->withoutVite();
        $this->seedPublicHomepageData([
            'public_theme' => 'unknown-theme',
        ]);

        $response = $this->get('/id');

        $response->assertOk();
        $response->assertViewIs('template.id.index');
    }

    public function test_homepage_renders_inertia_for_bangjeff_theme(): void
    {
        $this->withoutVite();
        $this->seedPublicHomepageData([
            'public_theme' => 'bangjeff',
        ]);

        $response = $this->get('/id');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Home')
            ->where('theme.key', 'bangjeff')
            ->has('banners', 1)
            ->has('featuredCategories', 1)
            ->has('categoryTabs', 1)
            ->has('articles', 1)
        );
    }

    public function test_homepage_maps_modern_theme_to_bangjeff_inertia(): void
    {
        $this->withoutVite();
        $this->seedPublicHomepageData([
            'public_theme' => 'modern',
        ]);

        $response = $this->get('/id');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Home')
            ->where('theme.key', 'bangjeff')
        );
    }

    public function test_article_index_uses_blade_legacy_for_default_theme(): void
    {
        $this->withoutVite();
        $this->seedPublicHomepageData([
            'public_theme' => 'default',
        ]);

        $response = $this->get('/id/artikel');

        $response->assertOk();
        $response->assertViewIs('template.id.artikel.index');
    }

    public function test_article_pages_render_inertia_for_bangjeff_theme(): void
    {
        $this->withoutVite();
        $this->seedPublicHomepageData([
            'public_theme' => 'bangjeff',
        ]);

        Artikel::create([
            'title' => 'Artikel Kedua',
            'slug' => 'artikel-kedua',
            'thumbnail' => 'assets/articles/test-2.webp',
            'meta_description' => 'Meta artikel kedua',
            'content' => '<p>Isi artikel kedua</p>',
            'status' => 'active',
        ]);

        $indexResponse = $this->get('/id/artikel');
        $indexResponse->assertOk();
        $indexResponse->assertInertia(fn (Assert $page) => $page
            ->component('Public/Articles/Index')
            ->where('theme.key', 'bangjeff')
            ->has('featured')
            ->has('articles')
            ->has('pagination')
        );

        $showResponse = $this->get('/id/artikel/artikel-kedua');
        $showResponse->assertOk();
        $showResponse->assertInertia(fn (Assert $page) => $page
            ->component('Public/Articles/Show')
            ->where('article.slug', 'artikel-kedua')
            ->where('theme.key', 'bangjeff')
        );
    }

    public function test_homepage_filters_empty_category_tabs_and_uses_legacy_type_fallbacks(): void
    {
        $this->withoutVite();
        $this->seedPublicHomepageData([
            'public_theme' => 'bangjeff',
        ]);

        CategoryType::create([
            'name' => 'Specialist Mobile Legends',
            'slug' => 'specialist-mobile-legends',
            'sort' => 2,
        ]);

        CategoryType::create([
            'name' => 'App Premium',
            'slug' => 'app-premium',
            'sort' => 3,
        ]);

        Kategori::factory()->create([
            'category_type_id' => null,
            'nama' => 'Mlbb Via Login',
            'sub_nama' => 'Special login flow',
            'kode' => 'mlbb-vilog',
            'tipe' => 'vilogml',
            'thumbnail' => 'assets/thumbnail/mlbb-vilog.webp',
            'banner' => 'assets/banner/mlbb-vilog.webp',
            'status' => 'active',
        ]);

        $response = $this->get('/id');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Home')
            ->has('categoryTabs', 2)
            ->where('categoryTabs.0.slug', 'moba')
            ->where('categoryTabs.1.slug', 'specialist-mobile-legends')
            ->where('categoryTabs.1.items.0.slug', 'mlbb-vilog')
        );
    }

    public function test_supported_category_renders_inertia_order_page(): void
    {
        $this->withoutVite();
        $category = $this->seedPublicHomepageData([
            'public_theme' => 'bangjeff',
        ]);

        Method::create([
            'name' => 'QRIS Test',
            'code' => 'QRIS_TEST',
            'keterangan' => 'QRIS Manual Demo',
            'tipe' => 'qris',
            'payment' => 'manual',
            'images' => '/assets/payment/qris.webp',
            'statuspayment' => true,
        ]);

        Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => '86 Diamond',
            'status' => 'available',
            'product_logo' => '/assets/product_logo/diamond.webp',
        ]);

        $response = $this->get('/id/' . $category->kode);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Order')
            ->where('theme.key', 'bangjeff')
            ->where('category.slug', $category->kode)
            ->where('category.description', '<p>Demo deskripsi game untuk homepage inertia.</p>')
            ->has('products', 1)
            ->has('paymentMethods', 1)
            ->has('category.specialNotes')
        );
    }

    public function test_calculator_winrate_uses_blade_legacy_for_default_theme(): void
    {
        $this->withoutVite();
        $this->seedPublicHomepageData([
            'public_theme' => 'default',
        ]);

        $response = $this->get('/id/calculator/winrate');

        $response->assertOk();
        $response->assertViewIs('template.hitungwr');
    }

    public function test_calculator_routes_render_inertia_for_bangjeff_theme(): void
    {
        $this->withoutVite();
        $this->seedPublicHomepageData([
            'public_theme' => 'bangjeff',
        ]);

        $winrateResponse = $this->get('/id/calculator/winrate');
        $winrateResponse->assertOk();
        $winrateResponse->assertInertia(fn (Assert $page) => $page
            ->component('Public/Calculator')
            ->where('theme.key', 'bangjeff')
            ->where('calculator.type', 'winrate')
        );

        $magicWheelResponse = $this->get('/id/calculator/magic-wheel');
        $magicWheelResponse->assertOk();
        $magicWheelResponse->assertInertia(fn (Assert $page) => $page
            ->component('Public/Calculator')
            ->where('calculator.type', 'magic-wheel')
        );

        $zodiacResponse = $this->get('/id/calculator/zodiac');
        $zodiacResponse->assertOk();
        $zodiacResponse->assertInertia(fn (Assert $page) => $page
            ->component('Public/Calculator')
            ->where('calculator.type', 'zodiac')
        );
    }

    public function test_transaction_lookup_renders_inertia_for_bangjeff_theme(): void
    {
        $this->withoutVite();
        $this->seedPublicHomepageData([
            'public_theme' => 'bangjeff',
        ]);

        $response = $this->get('/id/invoices');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/CheckTransactions')
            ->where('theme.key', 'bangjeff')
            ->where('recentTransactionsScope.key', 'guest-empty')
            ->has('recentTransactions', 0)
        );
    }

    public function test_transaction_lookup_lists_only_authenticated_users_recent_transactions(): void
    {
        $this->withoutVite();
        $this->seedPublicHomepageData([
            'public_theme' => 'bangjeff',
        ]);

        $user = User::factory()->create([
            'username' => 'member-bangjeff',
        ]);

        $ownOrder = Pembelian::factory()->create([
            'order_id' => 'DMO-AUTH-001',
            'username' => $user->username,
            'harga' => 949,
            'status' => 'Proses',
        ]);

        Pembayaran::create([
            'order_id' => 'DMO-AUTH-001',
            'no_pembeli' => '6285792464508',
            'harga' => 949,
            'no_pembayaran' => 'VA-001',
            'status' => 'Belum Lunas',
            'metode' => 'QRIS',
        ]);

        Pembelian::factory()->create([
            'order_id' => 'DMO-AUTH-002',
            'username' => 'orang-lain',
            'harga' => 1200,
            'status' => 'Success',
        ]);

        Pembayaran::create([
            'order_id' => 'DMO-AUTH-002',
            'no_pembeli' => '628111111111',
            'harga' => 1200,
            'no_pembayaran' => 'VA-002',
            'status' => 'Lunas',
            'metode' => 'QRIS',
        ]);

        $response = $this->actingAs($user)->get('/id/invoices');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/CheckTransactions')
            ->where('recentTransactionsScope.key', 'auth-user')
            ->has('recentTransactions', 1)
            ->where('recentTransactions.0.invoiceId', $ownOrder->display_order_id)
            ->where('recentTransactions.0.phone', '6285792464508')
        );
    }

    public function test_transaction_lookup_lists_guest_session_transactions(): void
    {
        $this->withoutVite();
        $this->seedPublicHomepageData([
            'public_theme' => 'bangjeff',
        ]);

        $guestOrder = Pembelian::factory()->create([
            'order_id' => 'DMO-GUEST-001',
            'username' => 'Guest',
            'harga' => 28000,
            'status' => 'Proses',
        ]);

        Pembayaran::create([
            'order_id' => 'DMO-GUEST-001',
            'no_pembeli' => '628222222222',
            'harga' => 28000,
            'no_pembayaran' => 'VA-003',
            'status' => 'Belum Lunas',
            'metode' => 'QRIS',
        ]);

        Pembelian::factory()->create([
            'order_id' => 'DMO-GUEST-002',
            'username' => 'Guest',
            'harga' => 99999,
            'status' => 'Success',
        ]);

        $response = $this
            ->withSession(['public_recent_order_ids' => ['DMO-GUEST-001']])
            ->get('/id/invoices');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/CheckTransactions')
            ->where('recentTransactionsScope.key', 'guest-session')
            ->has('recentTransactions', 1)
            ->where('recentTransactions.0.invoiceId', $guestOrder->display_order_id)
            ->where('recentTransactions.0.phone', '628222222222')
        );
    }

    public function test_transaction_lookup_json_lookup_returns_redirect_without_mutating_recent_session_history(): void
    {
        $this->withoutVite();
        $this->seedPublicHomepageData([
            'public_theme' => 'bangjeff',
        ]);

        $order = Pembelian::factory()->create([
            'order_id' => 'DMO-LOOKUP-001',
            'status' => 'Proses',
        ]);

        $response = $this->postJson('/id/cari', [
            'id' => 'DMO-LOOKUP-001',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => true,
                'message' => 'Invoice ditemukan.',
                    'redirect_url' => route('pembelian', ['order' => $order->order_id]),
                ]);

        $response->assertSessionMissing('public_recent_order_ids');
        $this->assertSame(
            'DMO-LOOKUP-001',
            Cache::get('public:invoice-lookup:' . sha1('dmo-lookup-001'))
        );
    }

    public function test_supported_category_uses_blade_legacy_for_default_theme(): void
    {
        $this->withoutVite();
        $category = $this->seedPublicHomepageData([
            'public_theme' => 'default',
        ]);

        Method::create([
            'name' => 'QRIS Test',
            'code' => 'QRIS_TEST',
            'keterangan' => 'QRIS Manual Demo',
            'tipe' => 'qris',
            'payment' => 'manual',
            'images' => '/assets/payment/qris.webp',
            'statuspayment' => true,
        ]);

        Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => '86 Diamond',
            'status' => 'available',
            'product_logo' => '/assets/product_logo/diamond.webp',
        ]);

        $response = $this->get('/id/' . $category->kode);

        $response->assertOk();
        $response->assertViewIs('template.order');
    }

    public function test_bangjeff_theme_redirects_legacy_blade_pages_to_home_with_301(): void
    {
        $this->withoutVite();
        $this->seedPublicHomepageData([
            'public_theme' => 'bangjeff',
        ]);

        $this->get('/id/price-list')
            ->assertStatus(301)
            ->assertRedirect('/id');

        $this->get('/id/reviews')
            ->assertStatus(301)
            ->assertRedirect('/id');

        $this->get('/id/terms-and-condition')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Legal'));
    }

    public function test_bangjeff_theme_keeps_signin_and_signup_accessible(): void
    {
        $this->withoutVite();
        $this->seedPublicHomepageData([
            'public_theme' => 'bangjeff',
        ]);

        $this->get('/id/sign-in')->assertOk();
        $this->get('/id/sign-up')->assertOk();
    }

    public function test_joki_category_renders_inertia_order_page_with_special_fields(): void
    {
        $this->withoutVite();
        $this->seedPublicHomepageData([
            'public_theme' => 'bangjeff',
        ]);

        $category = Kategori::factory()->create([
            'nama' => 'Joki Rank Mobile Legends',
            'sub_nama' => 'Push rank bareng tim joki',
            'kode' => 'joki-rank-ml',
            'tipe' => 'joki',
            'thumbnail' => 'assets/thumbnail/joki-rank.webp',
            'banner' => 'assets/banner/joki-rank.webp',
            'status' => 'active',
        ]);

        Method::create([
            'name' => 'Manual Bank',
            'code' => 'MANUAL_BANK',
            'keterangan' => 'Transfer manual',
            'tipe' => 'bank',
            'payment' => 'manual',
            'images' => '/assets/payment/bank.webp',
            'statuspayment' => true,
        ]);

        Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => 'Joki Epic ke Mythic',
            'status' => 'available',
            'product_logo' => '/assets/product_logo/joki.webp',
        ]);

        $response = $this->get('/id/' . $category->kode);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Order')
            ->where('category.slug', $category->kode)
            ->where('category.orderMode', 'complex')
            ->has('category.specialFields')
        );
    }

    private function seedPublicHomepageData(array $settingOverrides = []): Kategori
    {
        SettingWeb::create(array_merge([
            'id' => 1,
            'judul_web' => 'PlayNusa Topup',
            'deskripsi_web' => 'Demo storefront',
            'keywords' => 'top up game',
            'logo_header' => 'assets/logo/logo.webp',
            'logo_footer' => 'assets/logo/footer.webp',
            'logo_favicon' => 'assets/logo/favicon.webp',
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/playnusa',
            'url_tiktok' => 'https://tiktok.com/@playnusa',
            'url_youtube' => 'https://youtube.com/@playnusa',
            'url_fb' => 'https://facebook.com/playnusa',
            'topupindo_api' => 'demo-topupindo-key',
            'paydisini_apikey' => 'demo-paydisini-key',
            'order_prefik' => 'DMO',
            'warna1' => '#0f172a',
            'warna2' => '#ea580c',
            'warna3' => '#f59e0b',
            'warna4' => '#fb923c',
            'public_theme' => 'default',
        ], $settingOverrides));

        Berita::create([
            'tipe' => 'banner',
            'judul' => 'Promo Banner',
            'deskripsi' => 'Banner test',
            'images' => 'assets/banner/test.webp',
        ]);

        Artikel::create([
            'title' => 'Artikel Test',
            'slug' => 'artikel-test',
            'thumbnail' => 'assets/articles/test.webp',
            'status' => 'active',
        ]);

        $type = CategoryType::create([
            'name' => 'MOBA',
            'slug' => 'moba',
            'sort' => 1,
        ]);

        return Kategori::factory()->create([
            'category_type_id' => $type->id,
            'nama' => 'Mobile Legends',
            'sub_nama' => 'Top up diamond',
            'kode' => 'mobile-legends',
            'tipe' => 'populer',
            'thumbnail' => 'assets/thumbnail/mobile-legends.webp',
            'banner' => 'assets/banner/mobile-legends.webp',
            'deskripsi_game' => '<p>Demo deskripsi game untuk homepage inertia.</p>',
            'status' => 'active',
        ]);
    }
}
