<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Pages\Settings\BrandingSettings;
use App\Filament\Admin\Pages\Settings\NotificationsSettings;
use App\Models\SettingWeb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\AdminTestCase;

/**
 * Banner Menu Utama bot tinggal di Settings > Notifications, section
 * 'Konfigurasi Telegram' -- satu tempat dengan bot yang memakainya.
 *
 * Dua aturan yang dijaga test ini:
 *
 * 1. **Whitelist per halaman.** `SettingsSectionPage::save()` memfilter state
 *    dengan whitelist SUBCLASS halaman yang sedang dibuka. Field yang tidak
 *    terdaftar di halaman itu dibuang diam-diam: notifikasi "Berhasil" tetap
 *    muncul, tapi kolomnya tersimpan NULL. Jadi test menekan `save()` lalu
 *    membaca ulang dari DB -- `assertFormFieldExists()` saja tetap hijau walau
 *    field-nya dibuang.
 *
 * 2. **Proteksi nilai kosong.** `bot_menu_banner` harus ada di daftar "jangan
 *    timpa dengan nilai kosong" milik `save()`. Tanpa itu, Simpan biasa
 *    (mis. admin cuma mengubah isian lain) MENGHAPUS banner, karena
 *    `FileUpload` mengembalikan state KOSONG baik saat field tidak disentuh
 *    maupun saat sengaja dikosongkan -- keduanya tidak bisa dibedakan.
 *
 * PENTING #1 -- fixture memakai BERKAS NYATA di `public/assets/bot/`.
 *   `FileUpload` memangkas state kalau berkasnya tidak ada di disk, jadi fixture
 *   fiktif membuat form tampak "field tidak disentuh" dan test mengukur hal yang
 *   salah.
 *
 * PENTING #2 -- setiap `fillForm` WAJIB menyertakan `mail_mailer => 'smtp'`.
 *   `phpunit.xml` menyetel `MAIL_MAILER=array`, sedangkan dropdown `mail_mailer`
 *   di halaman Notifications hanya menerima `smtp`/`log`. Tanpa nilai yang valid,
 *   `getState()` melempar ValidationException dan `save()` BERHENTI SEBELUM
 *   MENULIS APA PUN -- halaman Branding lolos hanya karena tidak punya field itu.
 *
 * PENTING #3 -- proxy "save() benar-benar jalan" TIDAK boleh memakai field yang
 *   tidak ada di halaman ini. `judul_web` dipakai di percobaan pertama dan
 *   hasilnya menyesatkan: `getState()` membuang field yang tidak ada di schema,
 *   jadi proxy-nya selalu gagal walau banner tersimpan BENAR. Pakai
 *   `invoice_notify_via_email` -- toggle yang ADA di halaman ini, terdaftar di
 *   whitelist, dan ikut ditulis. `assertSimpanBenarBenarJalan()` di bawah
 *   mengunci asumsi itu.
 */
class NotificationsBotBannerSettingsTest extends AdminTestCase
{
    use RefreshDatabase;

    private const BANNER_A = 'assets/bot/test-banner-a.webp';

    private const BANNER_B = 'assets/bot/test-banner-b.webp';

    /**
     * Nilai `mail_mailer` yang valid menurut opsi dropdown halaman ini.
     * Tanpa ini, `save()` tidak pernah sampai ke penulisan DB.
     */
    private const VALID_MAIL_MAILER = 'smtp';

    protected function setUp(): void
    {
        parent::setUp();

        // Field-nya (dan seluruh section 'Konfigurasi Telegram') hanya tampil
        // saat fitur bot order menyala -- sama seperti di staging maupun di
        // server yang bot Telegram-nya aktif.
        config(['bot.order_enabled' => true]);

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

    /**
     * Kunci anti-hijau-palsu. Dipanggil di setiap test yang menekan `save()`.
     *
     * Kenapa perlu: dua mekanisme berbeda bisa membuat test "Simpan tidak
     * menghapus banner" hijau tanpa membuktikan apa pun -- whitelist yang
     * membuang field, atau validasi gagal yang membuat `save()` berhenti lebih
     * awal. Kalau `save()` memang tidak jalan, nilai banner yang lama otomatis
     * tetap ada, dan assertion-nya hijau palsu.
     *
     * `invoice_notify_via_email` dipakai sebagai saksi karena ADA di halaman ini,
     * terdaftar di whitelist, dan nilainya awal `true` -- membalikkannya jadi
     * `false` membuktikan seluruh rantai simpan benar-benar dieksekusi.
     */
    private function assertSimpanBenarBenarJalan(): void
    {
        $this->assertFalse(
            (bool) SettingWeb::query()->findOrFail(1)->invoice_notify_via_email,
            'save() tidak menulis apa pun -- kemungkinan validasi form gagal (mis. mail_mailer).'
        );
    }

    public function test_field_banner_ada_di_halaman_notifications(): void
    {
        $this->actingAsAdmin();
        $this->createSettings();

        Livewire::test(NotificationsSettings::class)
            ->assertFormFieldExists('bot_menu_banner');
    }

    /**
     * Lokasi PERSISNYA: di dalam Section 'Konfigurasi Telegram', bukan sekadar
     * "ada di halaman Notifications". `assertFormFieldExists()` tidak bisa
     * membedakan keduanya, jadi jalur komponen ditelusuri manual. Section ini
     * ikut hilang saat `bot.order_enabled` mati, jadi field-nya pun tidak
     * pernah tampil sebagai yatim di halaman tanpa bot.
     */
    public function test_field_banner_berada_di_section_konfigurasi_telegram(): void
    {
        $this->actingAsAdmin();
        $this->createSettings();

        $schema = Livewire::test(NotificationsSettings::class)->instance()->getSchema('form');

        $trail = [];
        $walk = function ($containers, string $path) use (&$walk, &$trail): void {
            foreach ($containers as $comp) {
                $name = method_exists($comp, 'getName') ? (string) $comp->getName() : '';
                $heading = method_exists($comp, 'getHeading') ? (string) ($comp->getHeading() ?? '') : '';
                $here = $path . ('' !== $heading ? '/' . $heading : '');

                if ('bot_menu_banner' === $name) {
                    $trail[] = $here;
                }

                if (method_exists($comp, 'getChildComponents')) {
                    $kids = $comp->getChildComponents();
                    if (! empty($kids)) {
                        $walk($kids, $here);
                    }
                }
            }
        };

        $walk($schema->getComponents(), 'form');

        $this->assertCount(1, $trail, 'Field harus ada tepat satu kali, dan bukan di dua section sekaligus.');
        $this->assertStringContainsString('Konfigurasi Telegram', $trail[0]);
    }

    /**
     * Field-nya pindah dari Branding ke Notifications. Kalau suatu saat ada yang
     * menambahkannya kembali ke Branding, akan ada DUA field dengan nama sama di
     * dua halaman, dan whitelist yang berbeda membuat perilakunya sulit ditebak.
     * Test ini mengunci lokasinya.
     */
    public function test_field_banner_tidak_lagi_di_halaman_branding(): void
    {
        $this->actingAsAdmin();
        $this->createSettings();

        Livewire::test(BrandingSettings::class)
            ->assertFormFieldDoesNotExist('bot_menu_banner');
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

        Livewire::test(NotificationsSettings::class)
            ->fillForm([
                'mail_mailer' => self::VALID_MAIL_MAILER,
                // Saksi bahwa save() benar-benar menulis (lihat assertSimpanBenarBenarJalan).
                'invoice_notify_via_email' => false,
                'bot_menu_banner' => [self::BANNER_A],
            ], 'form')
            ->call('save');

        $this->assertSimpanBenarBenarJalan();
        $this->assertSame(
            self::BANNER_A,
            SettingWeb::query()->findOrFail(1)->bot_menu_banner,
            'Nilai harus tersimpan ke DB. Kalau NULL, field-nya dibuang filterStateByWhitelist().'
        );
    }

    public function test_banner_diganti_dengan_gambar_lain(): void
    {
        // Dua nilai BERBEDA: kalau save() gagal, nilai lama tertinggal dan
        // assertion bisa hijau palsu. Nilai lama sengaja beda dari yang baru.
        $this->actingAsAdmin();
        $this->createSettings(['bot_menu_banner' => self::BANNER_A]);

        Livewire::test(NotificationsSettings::class)
            ->fillForm([
                'mail_mailer' => self::VALID_MAIL_MAILER,
                'invoice_notify_via_email' => false,
                'bot_menu_banner' => [self::BANNER_B],
            ], 'form')
            ->call('save');

        $this->assertSimpanBenarBenarJalan();
        $this->assertSame(self::BANNER_B, SettingWeb::query()->findOrFail(1)->bot_menu_banner);
    }

    /**
     * RISIKO NYATA: `logo_header`, `logo_footer`, `logo_favicon`,
     * `seasonal_background_image`, dan `pwa_icon_source` masing-masing
     * dilindungi daftar "jangan timpa dengan nilai kosong" di `save()`.
     * Tanpa `bot_menu_banner` di daftar itu, Simpan biasa MENGHAPUS banner
     * diam-diam. Test ini mengunci proteksinya di halaman yang memilikinya.
     *
     * Perhatikan saksi `invoice_notify_via_email` tetap dibalik: itu bukti
     * save() memang menulis.
     */
    public function test_simpan_biasa_tidak_menghapus_banner(): void
    {
        $this->actingAsAdmin();
        $this->createSettings(['bot_menu_banner' => self::BANNER_A]);

        Livewire::test(NotificationsSettings::class)
            ->fillForm([
                'mail_mailer' => self::VALID_MAIL_MAILER,
                'invoice_notify_via_email' => false,
            ], 'form')
            ->call('save');

        $this->assertSimpanBenarBenarJalan();
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

        Livewire::test(NotificationsSettings::class)
            ->fillForm([
                'mail_mailer' => self::VALID_MAIL_MAILER,
                'invoice_notify_via_email' => false,
            ], 'form')
            ->call('save');

        $this->assertSimpanBenarBenarJalan();
        $this->assertSame(
            self::BANNER_A,
            SettingWeb::query()->findOrFail(1)->bot_menu_banner,
            'Berkas hilang di disk tidak boleh membuat Simpan menghapus nilainya.'
        );
    }
}
