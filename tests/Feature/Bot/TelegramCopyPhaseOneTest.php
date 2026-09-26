<?php

namespace Tests\Feature\Bot;

use App\Models\CategoryType;
use App\Models\InboundSourcePolicy;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Services\Bot\BotMessageFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Fase 1: copy bot Telegram boleh dipindah ke file lang, TAPI hanya boleh
 * muncul berbahasa Indonesia selama user belum memilih bahasa lain.
 *
 * Kenapa test ini ada:
 * `__()` memakai `app()->getLocale()`, dan locale itu bisa berubah per-request.
 * Kalau suatu saat `LanguageDetectMiddleware` ikut terpasang di grup rute bot
 * (sekarang grup `api`, jadi TIDAK terpasang), maka header `Accept-Language`
 * yang dikirim TELEGRAM — bukan user — akan menentukan bahasa balasan bot.
 * Test ini mengunci: apa pun header yang masuk, teks yang dilihat user tetap
 * IDENTIK dengan baseline sebelum refactor.
 *
 * Kalau test ini merah, artinya Fase 1 mengubah perilaku. Itu regresi, bukan
 * sekadar "kebetulan bahasa Inggris".
 */
class TelegramCopyPhaseOneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.telegram-bot-api.token' => 'dummy-token']);
        config(['services.telegram-bot-api.webhook_secret' => 'dummy-secret']);

        // Seed setting_webs dulu. Tanpa ini, AppServiceProvider membaca
        // bot_order_tg_enabled=false dari DB saat request HTTP dan menimpa
        // config() di atas — webhook lalu menolak memproses order.
        DB::table('setting_webs')->updateOrInsert(
            ['id' => 1],
            [
                'judul_web' => 'Test Store',
                'deskripsi_web' => 'Test Description',
                'keywords' => 'test',
                'url_wa' => 'https://wa.me/628123456789',
                'url_ig' => 'https://instagram.com/test',
                'url_tiktok' => 'https://tiktok.com/@test',
                'url_youtube' => 'https://youtube.com/test',
                'url_fb' => 'https://facebook.com/test',
                'topupindo_api' => 'test',
                'warna1' => '#000000',
                'warna2' => '#000000',
                'warna3' => '#000000',
                'warna4' => '#000000',
                'paydisini_apikey' => 'test',
                'order_prefik' => 'TRX',
                'nomor_admin' => '628123456789',
                'bot_order_tg_enabled' => 1,
                'bot_order_wa_enabled' => 1,
            ],
        );
        config(['app.name' => 'Test Store']);

        // Rute bot dijaga middleware inbound.whitelist; mode 'disabled' =
        // tidak memblokir request lokal (pola sama dengan BotWebhookTest).
        InboundSourcePolicy::query()->create([
            'source_domain' => 'bot_webhook',
            'source_name' => 'telegram',
            'mode' => 'disabled',
            'is_active' => true,
        ]);
    }

    private function postTelegramAsBot(array $data, array $headers = [])
    {
        return $this->postJson('/api/webhooks/bot/telegram', $data, array_merge([
            'X-Telegram-Bot-Api-Secret-Token' => config('services.telegram-bot-api.webhook_secret', ''),
        ], $headers));
    }

    /** Teks yang benar-benar dilihat user (MarkdownV2 di-unescape). */
    private function visibleText(?string $escaped): string
    {
        return preg_replace('/\\\\(.)/u', '$1', (string) $escaped);
    }

    public function test_intro_dan_tagline_tetap_indonesia_walau_header_minta_inggris(): void
    {
        CategoryType::query()->create(['name' => '🎮 Top Up', 'slug' => 'top-up', 'sort' => 1]);
        $kategori = Kategori::factory()->create(['category_type_id' => 1, 'kode' => 'mlbb', 'status' => 'active']);
        Layanan::factory()->create(['kategori_id' => $kategori->id, 'status' => 'available']);

        Http::fake([
            'https://api.telegram.org/*/sendMessage' => Http::response(['ok' => true]),
        ]);

        $this->postTelegramAsBot([
            'message' => [
                'chat' => ['id' => 12345, 'type' => 'private'],
                'from' => ['id' => 9876, 'language_code' => 'en'],
                'text' => '/menu',
                'message_id' => 111,
            ],
        ], ['Accept-Language' => 'en-US,en;q=0.9'])->assertOk();

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'sendMessage')) {
                return false;
            }

            $text = $this->visibleText($request['text']);

            return str_contains($text, '👋 *Selamat datang di Test Store*')
                && str_contains($text, 'Penuhi kebutuhan game & aplikasi premium kamu, semua dari satu tempat.')
                && str_contains($text, '🏠 *Menu Utama*')
                && str_contains($text, 'Pilih kategori di bawah untuk mulai. 👇')
                && ! str_contains($text, 'Welcome to')
                && ! str_contains($text, 'Main Menu');
        }, 'Balasan ke user Telegram harus tetap Bahasa Indonesia (baseline sebelum refactor).');
    }

    /**
     * Task 1.2: panduan `/help`.
     *
     * Karena `formatHelp()` channel-aware (garis bawah hanya di Telegram),
     * cabangnya dipisah eksplisit: Telegram ambil dari file lang, WhatsApp
     * tetap literal. Test ini mengunci DUA-DUANYA.
     */
    public function test_panduan_telegram_identik_dengan_baseline(): void
    {
        config(['services.telegram-bot-api.admin_contact_url' => 'https://t.me/alexander_vors']);

        $help = app(BotMessageFormatter::class)->formatHelp(
            \App\Services\Bot\BotGatewayCapabilities::forSource('telegram_gateway'),
        );

        $text = $help['text'];

        // Judul pakai garis bawah (khusus Telegram), bukan tebal.
        $this->assertStringContainsString('__📖 Panduan Singkat__', $text);
        $this->assertStringContainsString('__🛒 Cara Order__', $text);
        $this->assertStringContainsString('__🔎 Cek & Kelola__', $text);
        $this->assertStringContainsString('__❓ Butuh Bantuan?__', $text);

        // Empat langkah order.
        $this->assertStringContainsString('1. Tekan *🛍️ Buka Menu*', $text);
        $this->assertStringContainsString('2. Pilih layanan, lalu pilih nominalnya', $text);
        $this->assertStringContainsString('3. Masukkan detail kontak untuk bukti pembayaran', $text);
        $this->assertStringContainsString('4. Pilih pembayaran, lalu selesaikan pembayaran', $text);

        // Daftar cek & kelola, termasuk label tombol yang BELUM diterjemahkan.
        $this->assertStringContainsString('• *📦 Cek Status* — status pesanan terakhir', $text);
        $this->assertStringContainsString('• *📜 Riwayat Order* — daftar pesananmu', $text);
        $this->assertStringContainsString('• *🔍 Cek ID Game* — pastikan nama akun benar dulu', $text);
        $this->assertStringContainsString('• *❌ Batal Transaksi* — batalkan pesanan yang belum dibayar', $text);

        // Tautan admin bisa dipencet dan URL-nya masuk utuh (placeholder :url).
        $this->assertStringContainsString('[💬 Klik di sini](https://t.me/alexander_vors)', $text);
    }

    public function test_panduan_tanpa_url_admin_memakai_teks_fallback(): void
    {
        config(['services.telegram-bot-api.admin_contact_url' => '']);

        $text = app(BotMessageFormatter::class)->formatHelp(
            \App\Services\Bot\BotGatewayCapabilities::forSource('telegram_gateway'),
        )['text'];

        $this->assertStringContainsString('Ketik /admin untuk menghubungi admin kalau ada kendala. 🙏', $text);
        $this->assertStringNotContainsString('Klik di sini', $text);
    }

    public function test_panduan_whatsapp_tetap_literal_indonesia(): void
    {
        config(['services.telegram-bot-api.admin_contact_url' => 'https://t.me/alexander_vors']);

        $text = app(BotMessageFormatter::class)->formatHelp(
            \App\Services\Bot\BotGatewayCapabilities::forSource('whatsapp_gateway'),
        )['text'];

        // WhatsApp tidak punya `__garis bawah__` — judul harus TETAP tebal.
        $this->assertStringContainsString('*📖 Panduan Singkat*', $text);
        $this->assertStringNotContainsString('__', $text, 'WhatsApp tidak boleh menerima penanda garis bawah Telegram.');

        // URL mentah, bukan sintaks tautan Telegram.
        $this->assertStringContainsString('Hubungi admin di https://t.me/alexander_vors, atau ketik /admin. 🙏', $text);
    }

    // ==================================================================
    // Task 1.3 — alur checkout.
    //
    // CATATAN PENTING soal cara menguji: default locale test ini adalah `id`,
    // jadi memanggil jalur Telegram dengan locale `id` menghasilkan teks yang
    // SAMA dengan literal lama — tidak membuktikan apa pun. Untuk membuktikan
    // file lang benar-benar dipakai, locale harus diset `en`.
    // ==================================================================

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function quoteData(array $overrides = []): array
    {
        return [
            'ok' => true,
            'data' => array_merge([
                'base_amount' => 10000,
                'payment_fee' => 1000,
                'gateway_fee' => 500,
                'total_amount' => 11500,
                'discount' => 0,
                'category_code' => 'mobile-legends',
                'service_name' => 'Mobile Legends',
                'category_name' => 'Games',
                'payment_method' => ['name' => 'QRIS', 'code' => 'qris'],
                'service_id' => 123,
                'requires_zone_id' => false,
                'custom_inputs' => [],
            ], $overrides),
        ];
    }

    /** @return array{0: array<string,mixed>, 1: array<string,mixed>} */
    private function confirmationInput(): array
    {
        $quote = ['data' => [
            'total_amount' => 11500,
            'service_name' => 'Mobile Legends',
            'payment_method' => ['name' => 'QRIS'],
        ]];
        $payload = [
            'uid' => '1234567',
            'zone' => '1234',
            'input_label' => 'User ID',
            'nickname' => 'Budi',
        ];

        return [$quote, $payload];
    }

    public function test_checkout_telegram_locale_id_identik_baseline(): void
    {
        app()->setLocale('id');

        // false = mode non-conversational: inilah yang menampilkan baris
        // perintah `invoice ...` yang harus DIKETIK user.
        $text = app(BotMessageFormatter::class)
            ->formatPriceQuote($this->quoteData(), false, 'telegram_gateway')['text'];

        $this->assertStringContainsString('🧾 *Cek Pesanan*', $text);
        // Perataan kolom harga harus utuh — jumlah spasi ini disengaja.
        $this->assertStringContainsString('Harga       Rp 10.000', $text);
        $this->assertStringContainsString('Admin       Rp 1.500', $text);
        $this->assertStringContainsString('*Total      Rp 11.500*', $text);
        // Perintah yang DIKETIK user tetap dialek `invoice`.
        $this->assertStringContainsString('Kirim: `invoice 123 qris <UID> [Zone_ID]`', $text);
        $this->assertStringContainsString('Contoh: `invoice 123 qris 1234567 1234`', $text);
    }

    public function test_checkout_telegram_locale_en_diterjemahkan(): void
    {
        app()->setLocale('en');

        $text = app(BotMessageFormatter::class)
            ->formatPriceQuote($this->quoteData(), false, 'telegram_gateway')['text'];

        $this->assertStringContainsString('🧾 *Order Summary*', $text);
        $this->assertStringContainsString('Price       Rp 10.000', $text);
        $this->assertStringContainsString('Fee         Rp 1.500', $text);
        $this->assertStringContainsString('*Total      Rp 11.500*', $text);

        // Kritis: perintah yang harus DIKETIK user tidak boleh diterjemahkan.
        // Kalau kata `invoice` ikut berubah, user Inggris tidak akan pernah
        // bisa menghasilkan invoice.
        $this->assertStringContainsString('`invoice 123 qris <UID> [Zone_ID]`', $text);
        $this->assertStringContainsString('Send:', $text);

        // Tidak ada kunci lang yang bocor ke layar user.
        $this->assertStringNotContainsString('bot.', $text);
    }

    public function test_checkout_conversational_tombol_ikut_bahasa(): void
    {
        $fmt = app(BotMessageFormatter::class);

        app()->setLocale('id');
        $id = $fmt->formatPriceQuote($this->quoteData(), true, 'telegram_gateway');

        app()->setLocale('en');
        $en = $fmt->formatPriceQuote($this->quoteData(), true, 'telegram_gateway');
        $wa = $fmt->formatPriceQuote($this->quoteData(), true, 'whatsapp_gateway');

        // Mode conversational tidak menampilkan baris perintah `invoice`;
        // sebagai gantinya muncul baris input tujuan. Label default katalog
        // adalah 'User ID' (bukan email), jadi ikonnya 🎮 dan contohnya angka.
        $this->assertStringContainsString('🎮 *Masukkan User ID*', $id['text']);
        $this->assertStringContainsString('Contoh: `12345`', $id['text']);
        $this->assertStringContainsString('🎮 *Enter User ID*', $en['text']);
        $this->assertStringContainsString('Example: `12345`', $en['text']);

        $this->assertSame('❌ Batal', $id['buttons'][0][0]['text']);
        $this->assertSame('🔙 Kembali', $id['buttons'][0][1]['text']);
        $this->assertSame('❌ Cancel', $en['buttons'][0][0]['text']);
        $this->assertSame('🔙 Back', $en['buttons'][0][1]['text']);

        // Tombol ini callback-driven (`batal`), jadi callback harus sama persis
        // di semua bahasa — kalau berubah, tombolnya mati.
        $this->assertSame('batal', $id['buttons'][0][0]['callback']);
        $this->assertSame('batal', $en['buttons'][0][0]['callback']);

        // WhatsApp tetap Indonesia walau locale en.
        $this->assertSame('❌ Batal', $wa['buttons'][0][0]['text']);
        $this->assertStringContainsString('🎮 *Masukkan User ID*', $wa['text']);
        $this->assertStringNotContainsString('Enter User ID', $wa['text']);
    }

    public function test_zone_id_select_telegram_diterjemahkan(): void
    {
        $overrides = [
            'requires_zone_id' => true,
            'custom_inputs' => [
                'user_id' => ['label' => 'User ID'],
                'zone' => ['label' => 'Server ID', 'is_select' => true, 'options' => [
                    ['label' => 'Indonesia', 'value' => '1001'],
                ]],
            ],
        ];

        $fmt = app(BotMessageFormatter::class);

        app()->setLocale('id');
        $id = $fmt->formatPriceQuote($this->quoteData($overrides), true, 'telegram_gateway')['text'];

        app()->setLocale('en');
        $en = $fmt->formatPriceQuote($this->quoteData($overrides), true, 'telegram_gateway')['text'];

        $this->assertStringContainsString('Format: `UID <Server ID>`', $id);
        $this->assertStringContainsString('Contoh: `12345 6789`', $id);
        $this->assertStringContainsString('Pilihan Server ID:', $id);

        // Label di dalam backtick adalah nama field dari katalog, bukan prosa —
        // jadi 'Server ID' tetap apa adanya di kedua bahasa.
        $this->assertStringContainsString('Format: `UID <Server ID>`', $en);
        $this->assertStringContainsString('Example: `12345 6789`', $en);
        $this->assertStringContainsString('Choose Server ID:', $en);
    }

    public function test_konfirmasi_checkout_telegram_memakai_lang(): void
    {
        [$quote, $payload] = $this->confirmationInput();

        app()->setLocale('id');
        $id = app(BotMessageFormatter::class)
            ->formatCheckoutConfirmation($quote, $payload, 'tok123', 'telegram_gateway');

        app()->setLocale('en');
        $en = app(BotMessageFormatter::class)
            ->formatCheckoutConfirmation($quote, $payload, 'tok123', 'telegram_gateway');

        $this->assertStringContainsString('Cek Pesanan', $id['text']);
        $this->assertStringContainsString('Konfirmasi berlaku 15 menit.', $id['text']);
        $this->assertStringContainsString('*Total      Rp 11.500*', $id['text']);
        // Nickname & label netral tetap seperti semula (identik dua bahasa).
        $this->assertStringContainsString('🏷️ Nickname: Budi', $id['text']);

        $this->assertStringContainsString('Order Summary', $en['text']);
        $this->assertStringContainsString('This confirmation is valid for 15 minutes.', $en['text']);
        $this->assertStringContainsString('*Total      Rp 11.500*', $en['text']);

        // Tombol konfirmasi/batal itu callback-driven (`konfirmasi <token>`),
        // bukan teks yang di-parse — jadi aman diterjemahkan.
        $this->assertSame('✅ Konfirmasi', $id['buttons'][0][0]['text']);
        $this->assertSame('❌ Batal', $id['buttons'][0][1]['text']);
        $this->assertSame('✅ Confirm', $en['buttons'][0][0]['text']);
        $this->assertSame('❌ Cancel', $en['buttons'][0][1]['text']);

        // Callback-nya tidak boleh ikut berubah, apa pun bahasanya.
        $this->assertSame($id['buttons'][0][0]['callback'], $en['buttons'][0][0]['callback']);
        $this->assertSame($id['buttons'][0][1]['callback'], $en['buttons'][0][1]['callback']);
    }

    public function test_checkout_whatsapp_tetap_indonesia_walau_locale_en(): void
    {
        // Keputusan user: hanya Telegram yang ikut switch bahasa. WhatsApp
        // harus lepas tangan total dari fitur ini.
        app()->setLocale('en');

        $text = app(BotMessageFormatter::class)
            ->formatPriceQuote($this->quoteData(), false, 'whatsapp_gateway')['text'];

        $this->assertStringContainsString('🧾 *Cek Pesanan*', $text);
        $this->assertStringContainsString('Harga       Rp 10.000', $text);
        $this->assertStringContainsString('Kirim: `invoice 123 qris <UID> [Zone_ID]`', $text);
        $this->assertStringNotContainsString('Order Summary', $text);

        [$quote, $payload] = $this->confirmationInput();
        $wa = app(BotMessageFormatter::class)
            ->formatCheckoutConfirmation($quote, $payload, 'tok123', 'whatsapp_gateway');

        $this->assertStringContainsString('Konfirmasi berlaku 15 menit.', $wa['text']);
        $this->assertSame('✅ Konfirmasi', $wa['buttons'][0][0]['text']);
    }

    public function test_checkid_membedakan_provider_mati_dari_id_salah(): void
    {
        app()->setLocale('en');
        $fmt = app(BotMessageFormatter::class);

        $unavailable = $fmt->formatCheckId([
            'ok' => false,
            'error_code' => 'CHECK_ID_UNAVAILABLE',
            'message' => 'gateway timeout',
        ], 'telegram_gateway')['text'];

        $invalid = $fmt->formatCheckId([
            'ok' => false,
            'error_code' => 'NOT_FOUND',
            'message' => 'User ID tidak ditemukan',
        ], 'telegram_gateway')['text'];

        // Dua kondisi ini HARUS beda pesannya: yang pertama bukan salah user.
        $this->assertNotSame($unavailable, $invalid);
        $this->assertStringContainsString('unavailable right now', $unavailable);
        $this->assertStringContainsString('Invalid ID', $invalid);

        // Jangan bocorkan detail internal provider ke user.
        $this->assertStringNotContainsString('gateway timeout', $unavailable);

        $valid = $fmt->formatCheckId([
            'ok' => true,
            'data' => ['skip_check' => false, 'nickname' => 'Budi'],
        ], 'telegram_gateway')['text'];
        $this->assertStringContainsString('Valid ID', $valid);
        $this->assertStringContainsString('👤 Nickname: Budi', $valid);
    }

    public function test_checkid_whatsapp_tetap_indonesia_walau_locale_en(): void
    {
        app()->setLocale('en');

        $text = app(BotMessageFormatter::class)->formatCheckId([
            'ok' => false,
            'error_code' => 'CHECK_ID_UNAVAILABLE',
            'message' => 'x',
        ], 'whatsapp_gateway')['text'];

        $this->assertStringContainsString('Validasi ID sedang tidak tersedia', $text);
    }

    public function test_formatter_langsung_tetap_indonesia(): void
    {
        $menu = app(BotMessageFormatter::class)->formatCategories([
            'ok' => true,
            'data' => [['name' => 'Top Up Games', 'slug' => 'top-up-games']],
        ]);

        $this->assertStringContainsString('🏠 *Menu Utama*', $menu['text']);
        $this->assertStringContainsString('Pilih kategori di bawah untuk mulai. 👇', $menu['text']);
    }

    public function test_pesan_kategori_kosong_tetap_indonesia(): void
    {
        $menu = app(BotMessageFormatter::class)->formatCategories(['ok' => false, 'data' => []]);

        $this->assertSame('Maaf, daftar tipe kategori sedang tidak tersedia.', $menu['text']);
        $this->assertSame([], $menu['buttons']);
    }

    public function test_nama_kategori_kosong_dapat_fallback_indonesia(): void
    {
        $menu = app(BotMessageFormatter::class)->formatCategories([
            'ok' => true,
            'data' => [['name' => '', 'slug' => 'top-up-games']],
        ]);

        $labels = collect($menu['buttons'])->flatten(1)->pluck('text')->all();

        $this->assertTrue(
            collect($labels)->contains(fn (string $l): bool => str_contains($l, 'Kategori')),
            'Kategori tanpa nama harus dapat fallback "Kategori". Label: ' . implode(' | ', $labels),
        );
    }
}
