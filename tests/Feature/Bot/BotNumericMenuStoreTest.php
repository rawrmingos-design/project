<?php

namespace Tests\Feature\Bot;

use App\Services\Bot\BotNumericMenuStore;
use Tests\TestCase;

/**
 * Peta nomor → perintah untuk layar Telegram.
 *
 * Kenapa peta ini TERPISAH dari yang sudah ada di jalur WhatsApp, bukan dipakai
 * bersama:
 *
 * Jalur WA menyimpan peta di dalam adapter (`FonnteAdapter`, `OpenWaAdapter`)
 * dengan kunci `whatsapp:<nomor>` dan `source = whatsapp_gateway`, dan peta itu
 * dibaca HANYA di dalam adapter yang sama. Ekstraksi ke service bersama berarti
 * menyentuh jalur WhatsApp yang sedang melayani produksi — dan itu hanya
 * menguntungkan kerapian, bukan fitur. Karena itu Telegram memakai store
 * sendiri; perilaku WA tidak tersentuh sama sekali.
 *
 * Yang diuji di sini adalah sifat yang membuat tombol angka TIDAK menjadi jebakan
 * (angka yang mengeksekusi perintah dari layar lampau):
 *
 * 1. Terpisah per pengirim.
 * 2. Punya masa berlaku yang bisa habis.
 * 3. Menolak state skema lama alih-alih menafsirkannya.
 */
class BotNumericMenuStoreTest extends TestCase
{
    private const USER = 'telegram:default:9876';
    private const OTHER = 'telegram:default:1111';

    private function store(): BotNumericMenuStore
    {
        return app(BotNumericMenuStore::class);
    }

    private function menu(array $entries = null): array
    {
        return [
            'menu' => 'categories',
            'parent_menu' => null,
            'page' => 1,
            'entries' => $entries ?? [
                '1' => ['type' => 'content', 'label' => 'Top Up Games', 'command' => 'kategori top-up-games'],
                '2' => ['type' => 'content', 'label' => 'App Premium', 'command' => 'kategori app-premium'],
            ],
        ];
    }

    public function test_peta_disimpan_per_pengirim(): void
    {
        $this->store()->put(self::USER, $this->menu(), 'LIST PRODUCT ...');

        $state = $this->store()->get(self::USER);

        $this->assertIsArray($state);
        $this->assertSame('kategori top-up-games', $state['entries']['1']['command']);
    }

    public function test_pengirim_lain_tidak_melihat_peta_orang_lain(): void
    {
        $this->store()->put(self::USER, $this->menu(), 'LIST PRODUCT ...');

        // Kalau ini gagal, dua user berbeda berebut satu daftar dan nomor yang
        // diketuk bisa mengeksekusi perintah milik user lain.
        $this->assertNull($this->store()->get(self::OTHER));
    }

    public function test_peta_memuat_revisi_dan_masa_berlaku(): void
    {
        $state = $this->store()->put(self::USER, $this->menu(), 'LIST PRODUCT ...');

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{16}$/', (string) $state['revision']);
        $this->assertSame(1, $state['schema_version']);
        $this->assertSame('telegram_gateway', $state['source']);
        $this->assertSame(1, $state['page']);
        $this->assertLessThan($state['expires_at'], $state['created_at']);
    }

    public function test_peta_kedaluwarsa_tidak_dipakai(): void
    {
        $this->store()->put(self::USER, $this->menu(), 'LIST PRODUCT ...');

        // Lewati jauh melewati TTL: angka yang diketuk setelah ini bisa
        // mengeksekusi perintah dari layar yang sudah lama ditinggalkan.
        $this->travel(BotNumericMenuStore::TTL_MINUTES + 1)->minutes();

        $this->assertNull($this->store()->get(self::USER));
        $this->assertSame('expired', $this->store()->resolve(self::USER, 1)['status']);
    }

    public function test_melihat_menu_lagi_menyegarkan_masa_berlaku(): void
    {
        $this->store()->put(self::USER, $this->menu(), 'LIST PRODUCT ...');
        $first = $this->store()->get(self::USER);

        $this->travel(BotNumericMenuStore::TTL_MINUTES - 1)->minutes();

        // Menu dirender ulang (user membuka `menu` lagi) -> peta ditulis ulang.
        $this->store()->put(self::USER, $this->menu(), 'LIST PRODUCT ... (baru)');
        $second = $this->store()->get(self::USER);

        $this->assertNotNull($second, 'Peta harus masih hidup setelah dirender ulang.');
        $this->assertNotSame($first['revision'], $second['revision'], 'Revisi harus berganti.');
        $this->assertTrue(
            \Illuminate\Support\Carbon::parse($second['expires_at'])->greaterThan(
                \Illuminate\Support\Carbon::parse($first['expires_at']),
            ),
            'Masa berlaku harus terdorong maju.',
        );
    }

    public function test_skema_basi_dari_versi_lama_ditolak_bukan_ditafsirkan(): void
    {
        $state = $this->store()->put(self::USER, $this->menu(), 'LIST PRODUCT ...');

        // Skema versi berikutnya bisa mengubah arti sebuah field. Menafsirkan
        // state lama dengan aturan baru berisiko mengeksekusi perintah yang
        // salah, jadi state itu harus DITOLAK.
        $state['schema_version'] = 99;
        \Illuminate\Support\Facades\Cache::put($this->store()->key(self::USER), $state, 600);

        $this->assertNull($this->store()->get(self::USER));
        $this->assertSame('expired', $this->store()->resolve(self::USER, 1)['status']);
    }

    public function test_state_rusak_ditolak(): void
    {
        $state = $this->store()->put(self::USER, $this->menu(), 'LIST PRODUCT ...');

        // Entri dengan nomor di luar batas jenisnya = state rusak.
        $state['entries']['77'] = ['type' => 'content', 'label' => 'X', 'command' => 'kategori x'];
        \Illuminate\Support\Facades\Cache::put($this->store()->key(self::USER), $state, 600);

        $this->assertNull($this->store()->get(self::USER));
    }

    public function test_nomor_valid_menghasilkan_perintah(): void
    {
        $this->store()->put(self::USER, $this->menu(), 'LIST PRODUCT ...');

        $this->assertSame(
            ['status' => 'ok', 'command' => 'kategori app-premium'],
            $this->store()->resolve(self::USER, 2),
        );
    }

    public function test_nomor_di_luar_daftar_ditolak_dengan_teks_menu(): void
    {
        $this->store()->put(self::USER, $this->menu(), 'LIST PRODUCT ...');

        $result = $this->store()->resolve(self::USER, 7);

        $this->assertSame('invalid', $result['status']);
        $this->assertStringContainsString('LIST PRODUCT', $result['rendered_text']);
    }

    public function test_tanpa_peta_sama_sekali_dianggap_kedaluwarsa(): void
    {
        // User baru mengetuk angka tanpa pernah membuka menu.
        $this->assertSame('expired', $this->store()->resolve(self::USER, 1)['status']);
    }

    public function test_nomor_halaman_didaftarkan_seperti_whatsapp(): void
    {
        $entries = $this->menu()['entries'];
        $entries['98'] = ['type' => 'navigation_previous', 'label' => '⬅️ Prev', 'command' => 'menu page:1'];
        $entries['99'] = ['type' => 'navigation_next', 'label' => 'Next ➡️', 'command' => 'menu page:3'];

        $this->store()->put(self::USER, $this->menu($entries), 'LIST PRODUCT ...');

        $this->assertSame('ok', $this->store()->resolve(self::USER, 99)['status']);
        $this->assertSame('menu page:3', $this->store()->resolve(self::USER, 99)['command']);
    }

    public function test_membuka_halaman_tanpa_peta_tidak_menggantung(): void
    {
        // `menu page:2` diketik langsung oleh user (bukan lewat tombol) juga
        // harus melewati store ini dengan aman.
        $this->assertNull($this->store()->get(self::USER));
    }
}
