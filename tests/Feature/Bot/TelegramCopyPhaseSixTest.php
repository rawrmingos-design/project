<?php

namespace Tests\Feature\Bot;

use App\Services\Bot\BotGatewayCapabilities;
use App\Services\Bot\BotMessageFormatter;
use Tests\TestCase;

/**
 * Fase 6 — menutup sisa copy Telegram yang masih literal Indonesia.
 *
 * Yang dijaga di sini bukan sekadar "sudah diterjemahkan", tapi dua hal:
 *
 * 1. **Tidak ada layar campur bahasa.** Sebelumnya prosa sudah Inggris tapi
 *    tombolnya masih Indonesia — paling parah di gerbang keanggotaan, yang
 *    justru menghalangi user. Test di sini menolak sisa kata Indonesia di
 *    jalur Telegram locale `en`.
 * 2. **WhatsApp TIDAK ikut berubah.** Scope bahasa masih terkunci ke Telegram.
 *    Locale bisa saja `en` (mis. request web), tapi jalur WhatsApp wajib
 *    menghasilkan teks Indonesia yang sama persis seperti sebelum fase ini.
 */
class TelegramCopyPhaseSixTest extends TestCase
{
    private const TG = BotGatewayCapabilities::SOURCE_TELEGRAM;

    private const WA = BotGatewayCapabilities::SOURCE_WHATSAPP;

    private function fmt(): BotMessageFormatter
    {
        return app(BotMessageFormatter::class);
    }

    /** @return array<int, array<string, string>> */
    private function buttonTexts(array $response): array
    {
        $out = [];
        foreach ($response['buttons'] ?? [] as $row) {
            foreach ((array) $row as $b) {
                $out[] = (string) ($b['text'] ?? '');
            }
        }

        return $out;
    }

    // ---------------------------------------------------------------- gerbang

    public function test_gerbang_tidak_campur_bahasa_dan_hint_menyebut_tombol_yang_ada(): void
    {
        $channels = [['id' => '@gate', 'url' => 'https://t.me/gate', 'label' => 'Gate']];

        app()->setLocale('en');
        $en = $this->fmt()->formatTelegramMembershipRequired($channels);

        $this->assertStringContainsString('🔒 *Limited Access*', $en['text']);
        $this->assertStringNotContainsString('Sudah Bergabung', $en['text']);
        $this->assertStringNotContainsString('Akses Terbatas', $en['text']);

        $verify = $en['buttons'][1][0]['text'];
        $this->assertSame('✅ Joined', $verify);
        $this->assertStringContainsString(
            '*' . $verify . '*',
            $en['text'],
            'Hint harus menyebut tombol dengan nama yang benar-benar dirender.',
        );

        // Locale id tetap persis seperti sebelumnya.
        app()->setLocale('id');
        $id = $this->fmt()->formatTelegramMembershipRequired($channels);
        $this->assertSame('✅ Sudah Bergabung', $id['buttons'][1][0]['text']);
        $this->assertStringContainsString('*✅ Sudah Bergabung*', $id['text']);
    }

    public function test_tombol_coba_lagi_ikut_bahasa(): void
    {
        $fmt = $this->fmt();

        app()->setLocale('id');
        $this->assertSame('Coba Lagi', $fmt->formatTelegramMembershipUnavailable()['buttons'][0][0]['text']);

        app()->setLocale('en');
        $this->assertSame('Try Again', $fmt->formatTelegramMembershipUnavailable()['buttons'][0][0]['text']);
    }

    // ---------------------------------------------------------------- invoice

    public function test_invoice_telegram_ikut_bahasa(): void
    {
        $payload = [
            'ok' => true,
            'data' => [
                'order_id' => 'INV-1',
                'service_name' => 'Diamond 86',
                'category_name' => 'Games',
                'quantity' => 1,
                'payment' => ['amount' => 10000, 'payment_code' => 'VA123'],
            ],
        ];
        $fmt = $this->fmt();

        app()->setLocale('en');
        $en = $fmt->formatInvoice($payload, self::TG);
        $this->assertStringContainsString('⏳ *Awaiting Payment*', $en['text']);
        $this->assertStringContainsString('Payment Code / VA:', $en['text']);
        $this->assertStringContainsString('Type `status` to check the payment.', $en['text']);
        $this->assertStringNotContainsString('Menunggu Pembayaran', $en['text']);
        $this->assertContains('🔎 Check Payment Status', $this->buttonTexts($en));

        // Nominal & order id TIDAK boleh berubah bahasa / format.
        $this->assertStringContainsString('💰 *Rp 10.000*', $en['text']);
        $this->assertStringContainsString('`INV-1`', $en['text']);

        app()->setLocale('id');
        $id = $fmt->formatInvoice($payload, self::TG);
        $this->assertStringContainsString('⏳ *Menunggu Pembayaran*', $id['text']);
        $this->assertStringContainsString('💳 Kode Bayar / VA: `VA123`', $id['text']);
        $this->assertContains('🔎 Cek Status Pembayaran', $this->buttonTexts($id));
    }

    public function test_gagal_buat_invoice_pesan_provider_tidak_diterjemahkan(): void
    {
        $fmt = $this->fmt();

        app()->setLocale('en');
        $en = $fmt->formatInvoice(['ok' => false, 'message' => 'SALDO PROVIDER HABIS'], self::TG);
        $this->assertStringContainsString('Failed to create the invoice:', $en['text']);
        // Alasan mentah dari provider WAJIB tetap apa adanya — itu bukti teknis.
        $this->assertStringContainsString('SALDO PROVIDER HABIS', $en['text']);
        $this->assertStringNotContainsString('Gagal membuat invoice', $en['text']);
    }

    // ---------------------------------------------------------------- katalog

    public function test_katalog_telegram_ikut_bahasa(): void
    {
        $products = [
            'ok' => true,
            'data' => [['code' => 'ML', 'name' => 'Mobile Legends', 'category_type' => ['name' => 'Games', 'slug' => 'games']]],
        ];
        $caps = BotGatewayCapabilities::forSource(self::TG);
        $fmt = $this->fmt();

        app()->setLocale('en');
        $en = $fmt->formatProducts($products, 1, $caps);
        $this->assertStringContainsString('🎮 *Choose a Game* · Games', $en['text']);

        $this->assertStringContainsString(
            'Category not found or it has no products yet.',
            $fmt->formatProducts(['ok' => false, 'data' => []], 1, $caps)['text'],
        );
        $this->assertStringContainsString(
            'Product not found or it has no services yet.',
            $fmt->formatServices(['ok' => false, 'data' => []], 1, $caps)['text'],
        );
        $this->assertStringContainsString(
            'No payment methods are available right now.',
            $fmt->formatPaymentMethods(['ok' => false, 'data' => []], 1, 1, 'layanan ML', $caps)['text'],
        );

        app()->setLocale('id');
        $id = $fmt->formatProducts($products, 1, $caps);
        $this->assertStringContainsString('🎮 *Pilih Game* · Games', $id['text']);
    }

    // ------------------------------------------------------------ leaderboard

    public function test_leaderboard_telegram_ikut_bahasa(): void
    {
        $empty = ['today' => [], 'week' => [], 'month' => []];
        $fmt = $this->fmt();

        app()->setLocale('en');
        $en = $fmt->formatLeaderboard($empty, self::TG);
        $this->assertStringContainsString('*Today*', $en['text']);
        $this->assertStringContainsString('*This Week*', $en['text']);
        $this->assertStringContainsString('*This Month*', $en['text']);
        $this->assertStringContainsString('No successful transactions yet.', $en['text']);
        $this->assertStringNotContainsString('Hari Ini', $en['text']);
        $this->assertContains('🔙 Back to Menu', $this->buttonTexts($en));

        // Baris data tetap apa adanya (username & nominal format Indonesia).
        $rows = ['today' => [['username' => 'budi', 'total_harga' => 50000]], 'week' => [], 'month' => []];
        $this->assertStringContainsString('1. budi — Rp 50.000', $fmt->formatLeaderboard($rows, self::TG)['text']);

        app()->setLocale('id');
        $id = $fmt->formatLeaderboard($empty, self::TG);
        $this->assertStringContainsString('*Hari Ini*', $id['text']);
        $this->assertContains('🔙 Kembali ke Menu', $this->buttonTexts($id));
    }

    // ------------------------------------------------------------- registrasi

    public function test_registrasi_telegram_ikut_bahasa_dan_menyebut_jawaban_yang_dikenal(): void
    {
        $fmt = $this->fmt();

        app()->setLocale('en');
        $prompt = $fmt->formatTgRegisterPrompt();
        $this->assertStringContainsString('*Telegram account not linked yet.*', $prompt['text']);
        // ★ Prompt EN WAJIB menyebut YES/NO — itulah yang dikenali parser.
        // Kalau menyebut YA/TIDAK, user berbahasa Inggris diberi jawaban yang
        // tidak pernah dikenali bot.
        $this->assertStringContainsString('*YES*', $prompt['text']);
        $this->assertStringContainsString('*NO*', $prompt['text']);
        $this->assertStringNotContainsString('*YA*', $prompt['text']);

        $this->assertStringContainsString('📝 *Account Registration*', $fmt->formatTgRegisterUsernamePrompt()['text']);
        $this->assertStringContainsString(
            'That username is already taken.',
            $fmt->formatTgRegisterUsernameRetry(2, 'taken')['text'],
        );
        $this->assertStringContainsString(
            'Invalid email format.',
            $fmt->formatTgRegisterEmailRetry(2, 'invalid')['text'],
        );
        $this->assertStringContainsString(
            'Attempts left: 2',
            $fmt->formatTgRegisterUsernameRetry(2, 'invalid')['text'],
        );
        $this->assertStringContainsString(
            '🎉 *Account created and linked to Telegram!*',
            $fmt->formatTgRegisterSuccess('u', 'p', 'https://x')['text'],
        );

        app()->setLocale('id');
        $idPrompt = $fmt->formatTgRegisterPrompt();
        $this->assertStringContainsString('*YA*', $idPrompt['text']);
        $this->assertStringContainsString('*TIDAK*', $idPrompt['text']);
        $this->assertStringContainsString('Sisa percobaan: 2', $fmt->formatTgRegisterUsernameRetry(2, 'invalid')['text']);
    }

    // ---------------------------------------------------- regresi WhatsApp ★

    /**
     * INI invarian paling penting di fase ini.
     *
     * Locale bisa bernilai `en` (mis. request web yang mengubah locale
     * per-proses), tapi jalur WhatsApp tidak boleh ikut berubah sedikit pun.
     * Kalau suatu hari ada yang menulis `__()` tanpa cek `$source`, test ini
     * yang menangkapnya.
     */
    public function test_whatsapp_tetap_indonesia_meski_locale_en(): void
    {
        app()->setLocale('en');

        $waCaps = BotGatewayCapabilities::forSource(self::WA);
        $fmt = $this->fmt();

        $products = ['ok' => true, 'data' => [['code' => 'ML', 'name' => 'ML', 'category_type' => ['name' => 'Games', 'slug' => 'games']]]];
        $this->assertStringContainsString('🎮 *Pilih Game* · Games', $fmt->formatProducts($products, 1, $waCaps)['text']);
        $this->assertContains('🔙 Kembali', $this->buttonTexts($fmt->formatProducts($products, 1, $waCaps)));

        $this->assertStringContainsString(
            'Kategori tidak ditemukan atau belum ada produk.',
            $fmt->formatProducts(['ok' => false, 'data' => []], 1, $waCaps)['text'],
        );
        $this->assertStringContainsString(
            'Produk tidak ditemukan atau belum ada layanan.',
            $fmt->formatServices(['ok' => false, 'data' => []], 1, $waCaps)['text'],
        );
        $this->assertStringContainsString(
            'Metode pembayaran sedang tidak tersedia.',
            $fmt->formatPaymentMethods(['ok' => false, 'data' => []], 1, 1, null, $waCaps)['text'],
        );

        $empty = ['today' => [], 'week' => [], 'month' => []];
        $waLb = $fmt->formatLeaderboard($empty, self::WA);
        $this->assertStringContainsString('*Hari Ini*', $waLb['text']);
        $this->assertStringContainsString('Belum ada transaksi sukses.', $waLb['text']);
        $this->assertContains('🔙 Kembali ke Menu', $this->buttonTexts($waLb));

        $waInvoice = $fmt->formatInvoice([
            'ok' => true,
            'data' => [
                'order_id' => 'INV-1',
                'service_name' => 'Diamond 86',
                'category_name' => 'Games',
                'quantity' => 1,
                'payment' => ['amount' => 10000, 'payment_code' => 'VA123'],
            ],
        ], self::WA);
        $this->assertStringContainsString('⏳ *Menunggu Pembayaran*', $waInvoice['text']);
        $this->assertStringContainsString('💳 Kode Bayar / VA: `VA123`', $waInvoice['text']);
        $this->assertStringContainsString('Ketik `status` untuk cek pembayaran.', $waInvoice['text']);

        $this->assertStringContainsString(
            'Gagal membuat invoice: boom',
            $fmt->formatInvoice(['ok' => false, 'message' => 'boom'], self::WA)['text'],
        );

        // Prompt registrasi WhatsApp punya method sendiri — tetap Indonesia.
        $this->assertStringContainsString(
            '⚠️ *Nomor WhatsApp kamu belum terdaftar.*',
            $fmt->formatWaRegisterPrompt()['text'],
        );
    }
}
