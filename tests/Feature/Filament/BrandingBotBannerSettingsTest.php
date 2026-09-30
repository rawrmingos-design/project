<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Pages\Settings\BrandingSettings;
use App\Models\SettingWeb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\AdminTestCase;

/**
 * Banner Menu Utama bot adalah field BARU di halaman Settings > Branding.
 *
 * `SettingsSectionPage::save()` memfilter state dengan whitelist subclass dan
 * MEMBUANG field yang tidak terdaftar TANPA pesan error: notifikasi "Berhasil"
 * tetap muncul, tapi kolomnya tersimpan NULL. Karena itu test di bawah menekan
 * `save()` lalu membaca ulang dari DB -- `assertFormFieldExists()` saja akan
 * tetap hijau walau field-nya dibuang.
 *
 * PENTING: fixture memakai BERKAS NYATA di `public/assets/bot/`. `FileUpload`
 * memangkas state kalau berkasnya tidak ada di disk, jadi fixture fiktif membuat
 * form tampak "field tidak disentuh" dan test mengukur hal yang salah. Fixture
 * dihapus di tearDown.
 */
class BrandingBotBannerSettingsTest extends AdminTestCase
{
    use RefreshDatabase;

    private const BANNER_A = 'assets/bot/test-banner-a.webp';

    private const BANNER_B = 'assets/bot/test-banner-b.webp';

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([self::BANNER_A, self::BANNER_B] as $path) {
            $absolute = public_path($path);
            @mkdir(dirname($absolute), 0775, true);
            // Isi apa pun: yang diuji perilaku form, bukan validitas gambar.
            file_put_contents($absolute, 'fixture');
        }
    }

    protected function tearDown(): void
    {
        foreach ([self::BANNER_A, self::BANNER_B] as $path) {
            @unlink(public_path($path));
        }

        @rmdir(public_path('assets/bot'));

        parent::tearDown();
    }

    private function createSettings(array $overrides = []): SettingWeb
    {
        return SettingWeb::query()->create(array_merge([
            'id' => 1,
            'judul_web' => 'Test Topup',
            'deskripsi_web' => 'Deskripsi test',
            'keywords' => 'topup,test',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'dummy-api',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => 'dummy-paydisini',
            'order_prefik' => 'INV',
            'public_theme' => 'default',
        ], $overrides));
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
    }

    public function test_field_banner_ada_di_halaman_branding(): void
    {
        $this->actingAsAdmin();
        $this->createSettings();

        Livewire::test(BrandingSettings::class)
            ->assertFormFieldExists('bot_menu_banner');
    }

    /**
     * FileUpload menyimpan NILAI sebagai ARRAY path, bukan string tunggal.
     * Mengisi dengan string membuat validasi FileUpload melempar TypeError
     * ("Argument #2 ($value) must be of type array") -- dan test jadi gagal di
     * tempat yang salah, terlihat seperti bug whitelist padahal bukan.
     *
     * Catatan: `assertHasNoFormErrors()` juga tidak dipakai. Di Filament 4
     * penolong itu tidak menerima nama schema dan jatuh ke
     * getMountedActionSchemaName() yang null untuk halaman (bukan action).
     * Pembacaan ulang dari DB di bawah adalah bukti yang lebih kuat.
     */
    public function test_nilai_banner_tersimpan_ke_database_bukan_dibuang_whitelist(): void
    {
        $this->actingAsAdmin();
        $this->createSettings();

        Livewire::test(BrandingSettings::class)
            ->fillForm(['bot_menu_banner' => [self::BANNER_A]], 'form')
            ->call('save');

        $this->assertSame(
            self::BANNER_A,
            SettingWeb::query()->findOrFail(1)->bot_menu_banner,
            'Nilai harus tersimpan ke DB. Kalau NULL, field-nya dibuang filterStateByWhitelist().'
        );
    }

    public function test_banner_diganti_dengan_gambar_lain(): void
    {
        // Dua pasangan nilai BERBEDA: kalau save() gagal, nilai lama tertinggal
        // dan assertion bisa hijau palsu. Nilai lama sengaja beda dari yang baru.
        $this->actingAsAdmin();
        $this->createSettings(['bot_menu_banner' => self::BANNER_A]);

        Livewire::test(BrandingSettings::class)
            ->fillForm(['bot_menu_banner' => [self::BANNER_B]], 'form')
            ->call('save');

        $this->assertSame(self::BANNER_B, SettingWeb::query()->findOrFail(1)->bot_menu_banner);
    }

    /**
     * RISIKO NYATA yang ditemukan saat mengerjakan task ini: `logo_header`,
     * `logo_footer`, `logo_favicon`, `seasonal_background_image`, dan
     * `pwa_icon_source` masing-masing dilindungi daftar "jangan timpa dengan
     * nilai kosong" di `save()`. Sebelum `bot_menu_banner` ditambahkan ke daftar
     * itu, Simpan biasa MENGHAPUS banner diam-diam. Test ini mengunci proteksinya.
     */
    public function test_simpan_biasa_tidak_menghapus_banner(): void
    {
        $this->actingAsAdmin();
        $this->createSettings(['bot_menu_banner' => self::BANNER_A]);

        Livewire::test(BrandingSettings::class)
            ->call('save');

        $this->assertSame(
            self::BANNER_A,
            SettingWeb::query()->findOrFail(1)->bot_menu_banner,
            'Simpan tanpa menyentuh banner tidak boleh menghapusnya.'
        );
    }

    public function test_simpan_tetap_aman_kalau_berkas_banner_hilang_dari_disk(): void
    {
        // Berkas hilang (mis. dibersihkan manual) -> state form menjadi ABSENT.
        // Proteksi "jangan timpa dengan kosong" harus menahan nilainya supaya
        // Simpan berikutnya tidak menghapus referensi tanpa sepengetahuan admin.
        $this->actingAsAdmin();
        $this->createSettings(['bot_menu_banner' => self::BANNER_A]);

        @unlink(public_path(self::BANNER_A));

        Livewire::test(BrandingSettings::class)
            ->call('save');

        $this->assertSame(
            self::BANNER_A,
            SettingWeb::query()->findOrFail(1)->bot_menu_banner,
            'Berkas hilang di disk tidak boleh membuat Simpan menghapus nilainya.'
        );
    }
}
