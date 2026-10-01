<?php

namespace Tests\Feature\Bot;

use App\Models\CategoryType;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Paket;
use App\Services\Bot\Adapters\TelegramAdapter;
use App\Services\Bot\BotNumericMenuStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pesan KEDUA berisi tombol navigasi (D1, keputusan user).
 *
 * Telegram hanya mengizinkan SATU `reply_markup` per pesan. Di layar menu yang
 * menang adalah keyboard ANGKA yang menetap, jadi tombol pindah halaman
 * kehilangan tempatnya. Tanpa pesan kedua, user melihat "📄 Halaman 1 / 2"
 * tanpa cara berpindah — daftar terlihat lengkap padahal terpotong.
 *
 * Yang dijaga file ini: pesan kedua muncul HANYA saat ada yang bisa dituju.
 * Daftar satu halaman tidak punya halaman lain, jadi pesannya murni noise —
 * teksnya mengajak "pindah halaman" padahal tidak ada halaman tujuan, dan
 * tombol aksi di dalamnya sudah terlihat di keyboard yang menetap di bawah.
 *
 * Fixture WAJIB menyemai layanan + paket, bukan kategori kosong. Bot Telegram
 * hanya memajang kategori yang punya layanan berpaket (kategori begitu membuka
 * layar kosong), jadi katalog tanpa paket membuat Menu Utama kosong dan test
 * ini hijau/hampa karena alasan yang tidak ada hubungannya dengan navigasi.
 */
class TelegramNavigationMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram-bot-api.token' => 'test-token',
            'services.telegram-bot-api.bot_scope' => 'default',
            'services.telegram-bot-api.order_enabled' => true,
        ]);

        Cache::flush();

        Http::fake([
            'api.telegram.org/*getChatMember*' => Http::response([
                'ok' => true,
                'result' => ['status' => 'member', 'user' => ['id' => 1]],
            ]),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]]),
        ]);
    }

    /**
     * Menu Utama berisi TIPE kategori, bukan kategori.
     *
     * Jadi jumlah halaman ditentukan oleh banyaknya `CategoryType`, BUKAN
     * banyaknya kategori: 12 kategori di dalam satu tipe tetap satu halaman.
     * Fixture yang salah di sini membuat test paginasi hijau/hampa untuk alasan
     * yang tidak ada hubungannya dengan paginasi.
     */
    private function seedTypes(int $count): void
    {
        foreach (range(1, $count) as $i) {
            $type = CategoryType::query()->create([
                'name' => "Tipe {$i}",
                'slug' => "tipe-{$i}",
                'sort' => $i,
            ]);

            $kategori = Kategori::query()->create([
                'nama' => "Kategori {$i}",
                'sub_nama' => "Kategori {$i}",
                'category_type_id' => $type->id,
                'status' => 'active',
                'tipe' => 'game',
            ]);

            $this->seedPackagedService($kategori->id, "Layanan {$i}");
        }
    }

    /**
     * Semai satu layanan yang terikat paket.
     *
     * Bot Telegram hanya memajang kategori yang punya layanan berpaket: kategori
     * tanpa paket membuka layar KOSONG, jadi menu menyembunyikannya. Fixture yang
     * cuma menyemai kategori kosong membuat daftar menu kosong, dan test
     * navigasi jadi hijau/hampa.
     */
    private function seedPackagedService(int $kategoriId, string $nama): void
    {
        $layanan = Layanan::factory()->create([
            'kategori_id' => $kategoriId,
            'layanan' => $nama,
            'harga_member' => 1000,
            'status' => 'available',
        ]);

        $paket = Paket::query()->firstOrCreate(['nama' => '⚡ Proses Instant']);
        $paket->layanan()->syncWithoutDetaching([$layanan->id => ['product_logo' => null]]);
    }

    /** Satu tipe dengan beberapa kategori. */
    private function seedCategories(int $count): void
    {
        $type = CategoryType::query()->create([
            'name' => 'Top Up Games',
            'slug' => 'top-up-games',
            'sort' => 1,
        ]);

        // Skema asli memakai `nama`/`sub_nama` (bukan `name`), dan `sub_nama`
        // TIDAK nullable — fixture yang mengarang nama kolom gagal sebelum
        // perilaku bot sempat diuji.
        foreach (range(1, $count) as $i) {
            $kategori = Kategori::query()->create([
                'nama' => "Game {$i}",
                'sub_nama' => "Game {$i}",
                'category_type_id' => $type->id,
                'status' => 'active',
                'tipe' => 'game',
            ]);

            $this->seedPackagedService($kategori->id, "Diamond {$i}");
        }
    }

    private function send(string $text, int $updateId = 800001): void
    {
        /** @var TelegramAdapter $adapter */
        $adapter = app(TelegramAdapter::class);

        $adapter->handle(Request::create('/api/webhooks/bot/telegram', 'POST', [
            'update_id' => $updateId,
            'message' => [
                'message_id' => $updateId,
                'chat' => ['id' => 88770011],
                'from' => ['id' => 88770011, 'first_name' => 'Navigasi'],
                'text' => $text,
            ],
        ]));
    }

    /**
     * Semua pesan (teks + inline) yang dikirim ke Telegram, berurutan.
     *
     * @return array<int, array{text: string, inline: array<int, array<int, array<string, mixed>>>}>
     */
    private function sentMessages(): array
    {
        $messages = [];

        foreach (Http::recorded() as [$request]) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            // `getChatMember` dan `sendChatAction` bukan pesan.
            if (str_contains($path, 'getChatMember') || str_contains($path, 'sendChatAction')) {
                continue;
            }

            $data = $request->data();

            $messages[] = [
                'text' => (string) ($data['text'] ?? $data['caption'] ?? ''),
                'inline' => (array) ($data['reply_markup']['inline_keyboard'] ?? []),
            ];
        }

        return $messages;
    }

    /** @return array<int, string> */
    private function flatTexts(): array
    {
        return array_map(static fn (array $m): string => $m['text'], $this->sentMessages());
    }

    public function test_daftar_satu_halaman_tidak_mengirim_pesan_navigasi(): void
    {
        // 2 kategori, halaman Telegram memuat 8 -> cuma SATU halaman.
        $this->seedCategories(2);

        $this->send('menu');

        $texts = $this->flatTexts();

        foreach ($texts as $text) {
            $this->assertStringNotContainsString(
                'Pindah halaman',
                $text,
                'Daftar satu halaman tidak punya halaman tujuan, jadi pesan navigasi cuma noise.',
            );
        }

        // Pesan menu tetap terkirim dan memuat daftar bernomor.
        $this->assertNotEmpty($texts);
        $this->assertStringContainsString('LIST PRODUCT', implode("\n", $texts));
    }

    public function test_pesan_navigasi_hanya_membawa_tombol_pindah_halaman(): void
    {
        // Halaman Telegram memuat 8 tipe; 12 tipe -> dua halaman, jadi navigasi
        // memang dibutuhkan (bukan sekadar tombol yang dikirim membabi buta).
        $this->seedTypes(12);

        $this->send('menu');

        $messages = $this->sentMessages();

        $navigation = array_values(array_filter(
            $messages,
            static fn (array $m): bool => str_contains($m['text'], 'Pindah halaman'),
        ));

        $this->assertCount(1, $navigation, 'Daftar >1 halaman harus punya tepat satu pesan navigasi.');

        $labels = [];
        $callbacks = [];
        foreach ($navigation[0]['inline'] as $row) {
            foreach ($row as $button) {
                $labels[] = (string) ($button['text'] ?? '');
                $callbacks[] = (string) ($button['callback_data'] ?? '');
            }
        }

        $this->assertNotEmpty($callbacks, 'Pesan navigasi tanpa tombol tidak berguna.');

        foreach ($callbacks as $callback) {
            $this->assertStringStartsWith(
                'menu page:',
                $callback,
                "Pesan navigasi hanya boleh memuat tombol pindah halaman, dapat '{$callback}'.",
            );
        }

        // Tombol aksi sudah ada di keyboard yang menetap; mengulanginya di sini
        // hanya menduplikasi tombol yang sudah terlihat.
        $this->assertNotContains('🏆 Leaderboard', $labels);
        $this->assertNotContains('🇮🇩 Bahasa', $labels);
    }

    public function test_nomor_yang_diketuk_memilih_kategori(): void
    {
        $this->seedCategories(2);

        $this->send('menu', 800010);
        Http::fake([
            'api.telegram.org/*getChatMember*' => Http::response([
                'ok' => true,
                'result' => ['status' => 'member', 'user' => ['id' => 1]],
            ]),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 2]]),
        ]);

        $this->send('1', 800011);

        $joined = implode("\n", $this->flatTexts());

        // Tekan 1 di Menu Utama membuka layar "Pilih Game" milik TIPE pertama
        // (bukan langsung daftar layanan): Menu Utama berisi TIPE, layar
        // berikutnya berisi kategori di dalamnya.
        $this->assertStringContainsString('Top Up Games', $joined, 'Nomor 1 harus membuka tipe kategori pertama.');
        $this->assertStringNotContainsString('kedaluwarsa', $joined, 'Peta nomor tidak boleh dianggap kedaluwarsa.');
    }

    public function test_angka_murni_saat_mengisi_detail_tidak_ditelan_jadi_pemilihan(): void
    {
        // ID game adalah ANGKA murni. Kalau ditelan jadi pemilihan nomor,
        // pesanan user hilang tanpa pesan error.
        $this->seedCategories(2);
        $this->send('menu', 800020);

        Http::fake([
            'api.telegram.org/*getChatMember*' => Http::response([
                'ok' => true,
                'result' => ['status' => 'member', 'user' => ['id' => 1]],
            ]),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 3]]),
        ]);

        // Layar layanan -> bot meminta User ID, dan state percakapan tercatat.
        $this->send('kategori top-up-games', 800021);
        $this->send('Game 1', 800022);

        $store = app(BotNumericMenuStore::class);
        $state = $store->get('telegram:default:88770011');
        $this->assertNotEmpty(
            $state['entries'] ?? [],
            'Peta nomor harus tersimpan supaya penjagaan langkah percakapan teruji.',
        );

        $before = count($this->flatTexts());
        $this->send('1840180550', 800023);
        $after = $this->flatTexts();

        $this->assertGreaterThan($before, count($after), 'Bot harus tetap membalas pesan User ID.');

        $joined = implode("\n", array_slice($after, $before));

        // Kalau angka ditelan, jawabannya adalah daftar kategori — bukan langkah
        // berikutnya dari percakapan.
        $this->assertStringNotContainsString('LIST PRODUCT', $joined, 'User ID tidak boleh berubah jadi pemilihan kategori.');
    }
}
