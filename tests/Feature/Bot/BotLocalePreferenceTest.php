<?php

namespace Tests\Feature\Bot;

use App\Models\BotLocalePreference;
use App\Services\Bot\BotLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Preferensi bahasa bot + rantai resolusi (Fase 0 plan switch bahasa).
 *
 * Yang dikunci di sini bukan sekadar keberadaan tabel, melainkan ATURAN
 * MENGIKAT yang mencegah dua bug mahal:
 *
 *  1. "User pilih Indonesia, pesan berikutnya balik Inggris"
 *     → deteksi TIDAK PERNAH menimpa baris yang sudah ada.
 *  2. "Satu anggota grup menentukan bahasa semua orang"
 *     → seed hanya untuk chat privat; tipe chat tak dikenal = fail closed.
 *
 * Kunci baris memakai `external_user_id` (`telegram:<scope>:<fromId>`) yang
 * sudah stabil sejak pesan pertama — sama dengan kunci state checkout.
 */
class BotLocalePreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['services.telegram-bot-api.default_locale' => 'id']);
    }

    private function ctx(string $externalUserId, array $metadata = [], ?string $chatType = 'private'): array
    {
        return [
            'source' => 'telegram_gateway',
            'external_user_id' => $externalUserId,
            'telegram_chat_type' => $chatType,
            'telegram_metadata' => $metadata,
        ];
    }

    public function test_tabel_preferensi_bahasa_tersedia(): void
    {
        $this->assertTrue(
            Schema::hasTable('bot_locale_preferences'),
            'Tabel bot_locale_preferences belum ada.',
        );
    }

    public function test_preferensi_disimpan_per_external_user_id(): void
    {
        $loc = app(BotLocale::class);
        $loc->setForContext($this->ctx('telegram:default:111'), 'en');

        $row = BotLocalePreference::query()->firstOrFail();

        $this->assertSame('telegram:default:111', $row->external_user_id);
        $this->assertSame('telegram_gateway', $row->source);
        $this->assertSame('en', $row->locale);
        $this->assertSame(BotLocalePreference::SOURCE_EXPLICIT, $row->locale_source);
    }

    public function test_deteksi_tidak_menimpa_pilihan_explicit(): void
    {
        $loc = app(BotLocale::class);
        $loc->setForContext($this->ctx('telegram:default:222'), 'id');

        // Pesan berikutnya: user mengganti UI Telegram ke Inggris.
        $loc->seed($this->ctx('telegram:default:222'), 'en');

        $this->assertSame(
            'id',
            $loc->resolve($this->ctx('telegram:default:222')),
            'Pilihan eksplisit tidak boleh ditimpa auto-deteksi.',
        );
        $this->assertSame(BotLocalePreference::SOURCE_EXPLICIT, BotLocalePreference::query()->firstOrFail()->locale_source);
        $this->assertSame(1, BotLocalePreference::query()->count(), 'Tidak boleh ada baris kedua.');
    }

    public function test_deteksi_hanya_mengisi_kalau_belum_ada_baris(): void
    {
        $loc = app(BotLocale::class);
        $loc->seed($this->ctx('telegram:default:333'), 'en');

        $this->assertSame('en', $loc->resolve($this->ctx('telegram:default:333')));
        $this->assertSame(BotLocalePreference::SOURCE_DETECTED, BotLocalePreference::query()->firstOrFail()->locale_source);
    }

    public function test_preferensi_tetap_ada_setelah_cache_dibersihkan(): void
    {
        $loc = app(BotLocale::class);
        $loc->setForContext($this->ctx('telegram:default:444'), 'en');

        // Bukti bahwa ini data permanen, bukan data sementara seperti state checkout.
        Cache::flush();

        $this->assertSame('en', $loc->resolve($this->ctx('telegram:default:444')));
    }

    public function test_seed_grup_tidak_pernah_disemai(): void
    {
        $loc = app(BotLocale::class);

        // Chat privat → tersemai.
        $loc->seed($this->ctx('telegram:default:555', [], 'private'), 'en');
        $this->assertSame('en', $loc->resolve($this->ctx('telegram:default:555', [], 'private')));

        // Grup → TIDAK tersemai; tetap default panel.
        $loc->seed($this->ctx('telegram:default:666', [], 'supergroup'), 'en');
        $this->assertSame('id', $loc->resolve($this->ctx('telegram:default:666', [], 'supergroup')));
        $this->assertSame(1, BotLocalePreference::query()->count());
    }

    public function test_tipe_chat_tak_dikenal_fail_closed(): void
    {
        $loc = app(BotLocale::class);
        $loc->seed($this->ctx('telegram:default:777', [], null), 'en');

        $this->assertSame('id', $loc->resolve($this->ctx('telegram:default:777', [], null)));
        $this->assertSame(0, BotLocalePreference::query()->count(), 'Konteks tak jelas tidak boleh menyemai apa pun.');
    }

    public function test_seed_kode_tak_didukung_tidak_menulis_baris(): void
    {
        $loc = app(BotLocale::class);

        foreach ([null, '', 'fr', 'ru', 'zh-Hans'] as $code) {
            $loc->seed($this->ctx('telegram:default:888'), $code);
        }

        $this->assertSame(0, BotLocalePreference::query()->count(), 'Tebakan kosong tidak disimpan.');
        $this->assertSame('id', $loc->resolve($this->ctx('telegram:default:888')));
    }

    public function test_normalisasi_kode_bahasa(): void
    {
        $loc = app(BotLocale::class);

        $this->assertSame('en', $loc->normalize('en-US'), 'Region subtag harus dipotong.');
        $this->assertSame('en', $loc->normalize('en_GB'), 'Underscore juga harus dipotong.');
        $this->assertSame('en', $loc->normalize('EN'));
        $this->assertSame('id', $loc->normalize('id-ID'));
        $this->assertSame('id', $loc->normalize(' id '));
        $this->assertNull($loc->normalize('fr'), 'Kode tak didukung harus null, bukan diteruskan.');
        $this->assertNull($loc->normalize('zh-Hans'));
        $this->assertNull($loc->normalize(null));
        $this->assertNull($loc->normalize(''));
    }

    public function test_resolve_selalu_mengembalikan_locale_valid(): void
    {
        $loc = app(BotLocale::class);

        // Tanpa baris & tanpa language_code → default panel.
        $this->assertSame('id', $loc->resolve($this->ctx('telegram:default:999')));

        // language_code tak didukung → tetap locale valid.
        $this->assertSame('id', $loc->resolve($this->ctx('telegram:default:999', ['language_code' => 'fr'])));

        // Default panel diubah ke en → ikut berubah.
        config(['services.telegram-bot-api.default_locale' => 'en']);
        $this->assertSame('en', $loc->resolve($this->ctx('telegram:default:999')));
    }

    public function test_ganti_pilihan_eksplisit_dihormati(): void
    {
        $loc = app(BotLocale::class);
        $loc->setForContext($this->ctx('telegram:default:1010'), 'en');
        $loc->setForContext($this->ctx('telegram:default:1010'), 'id');

        $this->assertSame('id', $loc->resolve($this->ctx('telegram:default:1010')));
        $this->assertSame(1, BotLocalePreference::query()->count());
    }

    public function test_apply_tidak_bocor_setelah_dipulihkan(): void
    {
        $loc = app(BotLocale::class);

        try {
            $loc->apply('en');
            $this->assertSame('en', app()->getLocale());
        } finally {
            app()->setLocale('id');
        }

        $this->assertSame('id', app()->getLocale(), 'Locale harus dipulihkan setelah request selesai.');
    }

    public function test_konteks_tanpa_identitas_tidak_meledak(): void
    {
        $loc = app(BotLocale::class);
        $loc->seed([], 'en');
        $loc->setForContext([], 'en');

        $this->assertSame('id', $loc->resolve([]));
        $this->assertSame(0, BotLocalePreference::query()->count());
    }
}
