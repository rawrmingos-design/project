<?php

namespace Tests\Feature\Bot;

use App\Models\CategoryType;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Paket;
use App\Services\Bot\Adapters\TelegramAdapter;
use App\Services\Bot\BotCommandParser;
use App\Services\Bot\BotGatewayCapabilities;
use App\Services\Bot\BotMessageFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tombol "kembali" di bot Telegram.
 *
 * Kenapa tombol ini ada di KEYBOARD BAWAH, bukan tombol inline:
 * Telegram hanya mengizinkan SATU `reply_markup` per pesan, dan di layar daftar
 * yang menang adalah keyboard nomor. Tombol inline `back` yang sudah ditulis
 * layar-layar itu karena sebab yang sama TIDAK PERNAH terkirim — dibuktikan
 * runtime: semua layar daftar nol tombol inline. Menaruhnya di keyboard membuat
 * "kembali" benar-benar terlihat, tanpa memperebutkan slot dengan tombol pindah
 * halaman.
 *
 * Tombol reply keyboard mengirim LABELNYA sebagai pesan biasa, jadi labelnya
 * wajib dikenali parser. Test ini mengunci rantai itu utuh: label dirender →
 * dikenali → mengarah ke layar SEBELUMNYA.
 */
class TelegramBackButtonTest extends TestCase
{
    use RefreshDatabase;

    private const FROM = 6252007210;

    /** Label tombol kembali yang dirender ke keyboard Telegram. */
    private const LABEL = '⬅️ Kembali';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeTelegram();
        config([
            'services.telegram-bot-api.token' => 'dummy-token',
            'services.telegram-bot-api.bot_scope' => 'default',
            'services.telegram-bot-api.order_enabled' => true,
        ]);
        Cache::flush();

        $type = CategoryType::query()->create(['name' => 'Top Up Games', 'slug' => 'top-up-games', 'sort' => 1]);
        $kat = Kategori::query()->create([
            'nama' => 'Free Fire', 'sub_nama' => 'Free Fire', 'kode' => 'free-fire',
            'category_type_id' => $type->id, 'status' => 'active', 'tipe' => 'game',
        ]);
        $lay = Layanan::factory()->create([
            'kategori_id' => $kat->id, 'layanan' => 'Diamond Free Fire 70',
            'harga_member' => 10000, 'status' => 'available',
        ]);
        $paket = Paket::query()->firstOrCreate(['nama' => 'Instant']);
        $paket->layanan()->syncWithoutDetaching([$lay->id => ['product_logo' => null]]);
    }

    private function fakeTelegram(): void
    {
        Http::fake([
            'api.telegram.org/*getChatMember*' => Http::response([
                'ok' => true,
                'result' => ['status' => 'member', 'user' => ['id' => 1]],
            ]),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]]),
        ]);
    }

    /** Kirim pesan seperti user mengetik / menekan tombol. */
    private function send(string $text, int $updateId): void
    {
        app(TelegramAdapter::class)->handle(Request::create('/api/webhooks/bot/telegram', 'POST', [
            'update_id' => $updateId,
            'message' => [
                'message_id' => $updateId,
                'chat' => ['id' => self::FROM, 'type' => 'private'],
                'from' => ['id' => self::FROM, 'first_name' => 'Test'],
                'text' => $text,
            ],
        ]));
    }

    /**
     * Pesan UTAMA dari balasan terakhir.
     *
     * Pesan navigasi pindah halaman dikirim sebagai pesan KEDUA, dan pesan itu
     * tidak memuat keyboard aksi — jadi pesan utama dikenali dari adanya
     * `reply_markup.keyboard`.
     *
     * @return array{text: string, tombol: array<int, string>}
     */
    private function balasanTerakhir(): array
    {
        $hasil = ['text' => '', 'tombol' => []];

        foreach (Http::recorded() as [$req]) {
            $path = (string) parse_url($req->url(), PHP_URL_PATH);

            if (str_contains($path, 'getChatMember') || str_contains($path, 'sendChatAction')
                || str_contains($path, 'answerCallbackQuery')) {
                continue;
            }

            $data = $req->data();
            $markup = $data['reply_markup'] ?? [];

            if (! isset($markup['keyboard'])) {
                continue;
            }

            $tombol = [];

            foreach ($markup['keyboard'] as $row) {
                foreach ($row as $button) {
                    $tombol[] = (string) ($button['text'] ?? '');
                }
            }

            $hasil = [
                'text' => (string) ($data['text'] ?? $data['caption'] ?? ''),
                'tombol' => $tombol,
            ];
        }

        $this->fakeTelegram();

        return $hasil;
    }

    /**
     * INTI: setiap layar daftar (Menu Utama → Pilih Game → Daftar Produk)
     * menyediakan tombol "kembali" di keyboard bawah.
     */
    public function test_tombol_kembali_ada_di_setiap_layar_daftar(): void
    {
        $this->send('menu', 900001);
        $menu = $this->balasanTerakhir();

        $this->send('kategori top-up-games', 900002);
        $game = $this->balasanTerakhir();

        $this->send('layanan free-fire', 900003);
        $produk = $this->balasanTerakhir();

        foreach (['Menu Utama' => $menu, 'Pilih Game' => $game, 'Daftar Produk' => $produk] as $nama => $layar) {
            $this->assertContains(
                self::LABEL,
                $layar['tombol'],
                "Layar {$nama} tidak menyediakan tombol \"kembali\" di keyboard bawah.",
            );
        }
    }

    /** Label yang dirender ke keyboard harus dikenali parser — kalau tidak, tombolnya mati. */
    public function test_label_tombol_kembali_dikenali_parser(): void
    {
        $this->assertTrue(
            BotCommandParser::isKnownLabel(self::LABEL),
            'Label tombol "kembali" dirender ke user tapi TIDAK dikenali parser — menekannya jadi perintah tak dikenal.',
        );

        $this->assertSame('back', app(BotCommandParser::class)->parse(self::LABEL)['command']);
    }

    /**
     * Arah tombol "kembali" = layar SEBELUMNYA, bukan selalu Menu Utama.
     *
     * Dari Daftar Produk user mengharapkan daftar game di kategorinya, bukan
     * terlempar ke menu. Ini yang membedakannya dari tombol "Buka Menu".
     */
    public function test_tombol_kembali_mengarah_ke_layar_sebelumnya(): void
    {
        $this->send('menu', 900011);
        $this->send('kategori top-up-games', 900012);
        $this->send('layanan free-fire', 900013);

        $produk = $this->balasanTerakhir();
        $this->assertStringContainsString('Diamond Free Fire 70', $produk['text'], 'Prasyarat: sedang di layar Daftar Produk.');

        // Kembali dari Daftar Produk → Pilih Game.
        $this->send(self::LABEL, 900014);
        $game = $this->balasanTerakhir();

        $this->assertStringContainsString(
            'Pilih Game',
            $game['text'],
            'Tombol "kembali" dari Daftar Produk harus membuka Pilih Game.',
        );

        // Kembali dari Pilih Game → Menu Utama.
        $this->send(self::LABEL, 900015);
        $menu = $this->balasanTerakhir();

        $this->assertStringContainsString(
            'Top Up Games',
            $menu['text'],
            'Tombol "kembali" dari Pilih Game harus membuka Menu Utama.',
        );
        $this->assertStringNotContainsString(
            'Pilih Game',
            $menu['text'],
            'Menu Utama bukan layar Pilih Game.',
        );
    }

    /**
     * Kembali di layar paling atas tidak boleh error: arahnya jatuh ke Menu
     * Utama, dan tombolnya tetap tersedia.
     */
    public function test_kembali_dari_menu_utama_tetap_membuka_menu(): void
    {
        $this->send('menu', 900021);
        $this->send(self::LABEL, 900022);

        $menu = $this->balasanTerakhir();

        $this->assertStringContainsString('Top Up Games', $menu['text']);
        $this->assertContains(self::LABEL, $menu['tombol']);
    }

    /**
     * Tombol "kembali" ikut bahasa aktif — sama seperti tombol tetangganya.
     *
     * KEDUA varian tetap harus dikenali parser: user yang mengganti bahasa
     * sementara keyboard lama masih terpasang tidak boleh menemukan tombol mati.
     */
    public function test_tombol_kembali_mengikuti_bahasa_aktif(): void
    {
        app()->setLocale('en');

        try {
            $keyboard = app(BotMessageFormatter::class)->defaultReplyKeyboard(
                BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_TELEGRAM),
            );

            $tombol = [];

            foreach ($keyboard['keyboard'] as $row) {
                foreach ($row as $button) {
                    $tombol[] = (string) ($button['text'] ?? '');
                }
            }

            $this->assertContains('⬅️ Back', $tombol, 'Keyboard bahasa Inggris harus berlabel "⬅️ Back".');
            $this->assertNotContains('⬅️ Kembali', $tombol, 'Keyboard bahasa Inggris tidak boleh berlabel Indonesia.');
        } finally {
            app()->setLocale('id');
        }

        // Varian lama (Indonesia) tetap hidup walau bahasa sudah berganti.
        $this->assertSame('back', app(BotCommandParser::class)->parse('⬅️ Kembali')['command']);
        $this->assertSame('back', app(BotCommandParser::class)->parse('⬅️ Back')['command']);
    }

    /**
     * WhatsApp TIDAK ikut berubah.
     *
     * Jalur WhatsApp sudah punya sarana "kembali" sendiri (angka `0` beserta
     * barisnya di daftar), jadi menambahkan baris keyboard di sana akan
     * menduplikasi tombol yang sudah ada.
     */
    public function test_whatsapp_tidak_mendapat_baris_kembali(): void
    {
        $wa = app(BotMessageFormatter::class)->defaultReplyKeyboard(
            BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_WHATSAPP),
        );

        $label = [];

        foreach ($wa['keyboard'] as $row) {
            foreach ($row as $button) {
                $label[] = (string) ($button['text'] ?? '');
            }
        }

        $this->assertNotContains(self::LABEL, $label, 'WhatsApp tidak boleh mendapat baris "kembali" baru.');
    }
}
