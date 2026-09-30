<?php

namespace Tests\Feature\Bot;

use App\Services\Bot\AuditLogService;
use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotMessageFormatter;
use App\Services\Bot\BotNumericMenuStore;
use App\Services\Bot\GatewayCatalogService;
use App\Services\Bot\GatewayInvoiceService;
use App\Services\Bot\GatewayPricingService;
use App\Services\Bot\TelegramBotService;
use App\Services\Bot\BotGatewayCapabilities;
use App\Services\Gateway\GatewayCatalogService as RealCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Parser label -> perintah + store peta nomor, tanpa jaringan Telegram.
 *
 * Dipisah dari `TelegramNumericKeyboardTest` supaya dua hal berbeda tidak
 * tercampur: di sini yang diuji adalah PEMILIHAN (angka yang diketuk benar-benar
 * memilih), sedangkan bentuk keyboard diuji di file satunya.
 */
class TelegramNumericSelectionTest extends TestCase
{
    use RefreshDatabase;

    private const MENU_TEXT = "LIST PRODUCT\n\n[1]. Top Up Games\n[2]. App Premium\n\n📆 05:58:50 PM";

    private function store(): BotNumericMenuStore
    {
        return app(BotNumericMenuStore::class);
    }

    private function seedMenu(): void
    {
        $this->store()->put('telegram:default:9876', [
            'menu' => 'categories',
            'parent_menu' => null,
            'page' => 1,
            'entries' => [
                '1' => ['type' => 'content', 'label' => 'Top Up Games', 'command' => 'kategori top-up-games'],
                '2' => ['type' => 'content', 'label' => 'App Premium', 'command' => 'kategori app-premium'],
            ],
        ], self::MENU_TEXT);
    }

    public function test_angka_menghasilkan_perintah_kategori(): void
    {
        $this->seedMenu();

        $this->assertSame(
            'kategori top-up-games',
            $this->store()->resolve('telegram:default:9876', 1)['command'],
        );
    }

    public function test_angka_masih_berlaku_setelah_pindah_layar(): void
    {
        $this->seedMenu();

        // User membuka layar lain dulu (tidak menghapus peta), baru mengetuk
        // angka. Keyboard angka bersifat GLOBAL, jadi ini alur normal.
        app(BotMessageFormatter::class)->formatHelp(
            BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_TELEGRAM),
        );

        $this->assertSame('ok', $this->store()->resolve('telegram:default:9876', 2)['status']);
    }

    public function test_angka_basi_setelah_kedaluwarsa_tidak_mengeksekusi_apa_pun(): void
    {
        $this->seedMenu();

        $this->travel(BotNumericMenuStore::TTL_MINUTES + 1)->minutes();

        $this->assertSame('expired', $this->store()->resolve('telegram:default:9876', 1)['status']);
    }

    public function test_angka_murni_tanpa_daftar_aktif_bukan_pemilihan(): void
    {
        // ID game, nominal deposit, dan ID telegram semuanya ANGKA murni. Kalau
        // angka tanpa daftar aktif dianggap pemilihan menu, pesan-pesan itu
        // hilang dan handler tidak pernah menerimanya — persis regresi yang
        // bikin pengiriman UID game gagal.
        $result = $this->store()->resolve('telegram:default:9876', 1840180550);

        $this->assertSame('expired', $result['status']);
        $this->assertArrayNotHasKey('command', $result);
    }

    public function test_angka_di_luar_daftar_ditolak_dengan_daftar_ulang(): void
    {
        $this->seedMenu();

        $result = $this->store()->resolve('telegram:default:9876', 9);

        $this->assertSame('invalid', $result['status']);
        $this->assertStringContainsString('[1]. Top Up Games', $result['rendered_text']);
    }

    public function test_membuka_menu_lagi_mengembalikan_angka_yang_sudah_kedaluwarsa(): void
    {
        $this->seedMenu();
        $this->travel(BotNumericMenuStore::TTL_MINUTES + 1)->minutes();

        $this->assertSame('expired', $this->store()->resolve('telegram:default:9876', 1)['status']);

        // Melihat menu = state segar. Ini yang mencegah "layar mati": daftar
        // tampil tapi tidak ada yang bisa dipilih.
        $this->seedMenu();

        $this->assertSame('ok', $this->store()->resolve('telegram:default:9876', 1)['status']);
    }

    public function test_nomor_98_dan_99_pindah_halaman(): void
    {
        $this->store()->put('telegram:default:9876', [
            'menu' => 'categories',
            'page' => 1,
            'entries' => [
                '1' => ['type' => 'content', 'label' => 'Top Up Games', 'command' => 'kategori top-up-games'],
                '99' => ['type' => 'navigation_next', 'label' => 'Next ➡️', 'command' => 'menu page:2'],
            ],
        ], self::MENU_TEXT);

        $this->assertSame('menu page:2', $this->store()->resolve('telegram:default:9876', 99)['command']);
    }

    public function test_angka_tidak_dipakai_saat_user_sedang_mengisi_detail_pesanan(): void
    {
        // Regresi paling mahal di fitur ini: ID game atau nominal deposit yang
        // berupa ANGKA murni bisa "ditelan" jadi pemilihan menu, dan pesanan
        // user berubah jadi kategori yang salah.
        $this->assertTrue(
            method_exists(BotCommandHandler::class, 'conversationalStepFor'),
            'Handler harus menyediakan cara membaca step input percakapan.',
        );

        $this->assertTrue(
            BotCommandHandler::isConversationalStep('waiting_game_id'),
            'User yang sedang mengisi ID game tidak boleh terkena pemilihan nomor.',
        );

        $this->assertTrue(BotCommandHandler::isConversationalStep('waiting_deposit_amount'));
        $this->assertTrue(BotCommandHandler::isConversationalStep('waiting_deposit_method'));

        // Di luar step itu, angka tetap berarti pemilihan.
        $this->assertFalse(BotCommandHandler::isConversationalStep(''));
        $this->assertFalse(BotCommandHandler::isConversationalStep('waiting_confirmation'));
    }
}
