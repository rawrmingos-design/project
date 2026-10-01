<?php

namespace Tests\Feature\Bot;

use App\Models\BotLocalePreference;
use App\Models\InboundSourcePolicy;
use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotCommandParser;
use App\Services\Bot\BotGatewayCapabilities;
use App\Services\Bot\BotLocale;
use App\Services\Bot\BotMessageFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Fase 3 — perintah `/bahasa`, tombol pemilih bahasa, dan komponen bahasa di
 * keyboard tetap.
 *
 * Ini fase pertama yang KELIHATAN USER, jadi yang diuji bukan "perintahnya ada",
 * melainkan tiga hal yang bisa merusak pengalaman nyata:
 *
 *  1. **Tidak ada tombol mati.** Tiap label bahasa yang dirender (panel, panduan,
 *     menu, keyboard tetap) harus dikenali `BotCommandParser` — karena tap-nya
 *     mengirim TEKS, bukan callback. Label yang tidak dikenali = tombol yang
 *     diam saat ditekan.
 *  2. **Panel yang dibalas sudah dalam bahasa baru.** Kalau user menekan
 *     "English" lalu tetap menerima panel Indonesia, dia wajar mengira tombolnya
 *     rusak dan berhenti mencoba.
 *  3. **WhatsApp tidak ikut berubah.** Scope fase ini Telegram saja.
 */
class TelegramLanguageCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        // Seed setting_webs LENGKAP: banyak kolom NOT NULL, dan
        // AppServiceProvider membaca bot_order_tg_enabled dari sini saat request
        // HTTP — tanpa seed, nilai DB menimpa config() di bawah.
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

        // Pola sama dengan TelegramCopyPhaseOneTest: mode 'disabled' = tidak
        // memblokir request lokal.
        InboundSourcePolicy::query()->create([
            'source_domain' => 'bot_webhook',
            'source_name' => 'telegram',
            'mode' => 'disabled',
            'is_active' => true,
        ]);

        config([
            'services.telegram-bot-api.bot_scope' => 'default',
            'services.telegram-bot-api.default_locale' => 'id',
            // Gate keanggotaan dimatikan: yang diuji di sini perilaku perintah,
            // bukan gerbangnya (`TelegramMembershipGateTest` yang mengurus itu).
            'services.telegram-bot-api.required_channel.enabled' => false,
            'bot.order_enabled' => true,
        ]);
    }

    private function context(string $fromId = '6252007210', ?string $firstName = 'Mings'): array
    {
        return [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:default:' . $fromId,
            'telegram_user_id' => $fromId,
            'telegram_chat_id' => $fromId,
            'telegram_bot_scope' => 'default',
            'telegram_chat_type' => 'private',
            'telegram_metadata' => ['first_name' => $firstName, 'language_code' => 'id'],
            'email' => $fromId . '@telegram.user',
        ];
    }

    private function handler(): BotCommandHandler
    {
        return app(BotCommandHandler::class);
    }

    private function telegram(): BotGatewayCapabilities
    {
        return BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_TELEGRAM);
    }

    /** Semua label tombol dalam sebuah respons, apa pun bentuknya. */
    /** Apakah label ini tombol BAHASA (bukan navigasi generik)? */
    private function isLanguageLabel(string $label): bool
    {
        return str_contains($label, 'Bahasa') || str_contains($label, 'English') || str_contains($label, 'Indonesian');
    }

    /** @param array<int, string> $labels */
    private function hasLabelFor(array $labels, string $locale): bool
    {
        $needle = $locale === 'en' ? 'English' : 'Bahasa';

        foreach ($labels as $label) {
            if (str_contains($label, $needle) || ($locale === 'id' && str_contains($label, 'Indonesian'))) {
                return true;
            }
        }

        return false;
    }

    private function labelsIn(array $response): array
    {
        $labels = [];

        foreach ((array) ($response['buttons'] ?? []) as $row) {
            foreach ((array) $row as $button) {
                if (is_array($button) && isset($button['text'])) {
                    $labels[] = (string) $button['text'];
                }
            }
        }

        return $labels;
    }

    // ---------------------------------------------------------------------
    // 1. TIDAK ADA TOMBOL MATI
    // ---------------------------------------------------------------------

    /**
     * Setiap label bahasa yang dirender panel `/bahasa` harus dikenal parser.
     *
     * Ini jaring utama: label yang dirender tapi tidak dikenal parser adalah
     * tombol yang diam saat ditekan — dan itu terjadi di layar pertama yang
     * dilihat user, jadi paling merusak.
     */
    public function test_setiap_label_panel_bahasa_dikenali_parser(): void
    {
        $formatter = app(BotMessageFormatter::class);

        foreach (['id', 'en'] as $current) {
            $panel = $formatter->languagePanel($current);
            $labels = $this->labelsIn($panel);

            $this->assertNotEmpty($labels, "Panel bahasa ({$current}) tidak punya tombol.");

            // Yang wajib dikenali parser = tombol yang mengirim TEKS kembali.
            // Tombol navigasi generik (`🔙 ...`, callback `menu`) dikirim sebagai
            // CALLBACK, jadi labelnya tidak pernah kembali sebagai pesan dan
            // tidak perlu — juga tidak boleh — ada di peta label parser.
            foreach ($labels as $label) {
                if ($this->isLanguageLabel($label)) {
                    $this->assertTrue(
                        BotCommandParser::anyLabel($label),
                        "Tombol bahasa '{$label}' dirender tapi TIDAK dikenali parser — tap-nya akan diam.",
                    );
                }
            }

            // Panel memang benar-benar menawarkan kedua bahasa.
            $this->assertTrue($this->hasLabelFor($labels, 'id'), "Panel ({$current}) tidak menawarkan Bahasa Indonesia.");
            $this->assertTrue($this->hasLabelFor($labels, 'en'), "Panel ({$current}) tidak menawarkan English.");
        }
    }

    /**
     * Label bahasa di keyboard tetap harus sama persis dengan label di panel.
     *
     * Kalau menyimpang, salah satu dari keduanya menjadi tombol mati — dan yang
     * paling berbahaya adalah keyboard tetap, karena tombolnya MENETAP di layar
     * dan akan ditekan berkali-kali.
     */
    public function test_label_bahasa_keyboard_sama_dengan_label_panel(): void
    {
        $formatter = app(BotMessageFormatter::class);
        $keyboard = $formatter->defaultReplyKeyboard($this->telegram());

        $keyboardLabels = [];
        foreach ($keyboard['keyboard'] as $row) {
            foreach ($row as $button) {
                $keyboardLabels[] = (string) $button['text'];
            }
        }

        // Kedua label bahasa harus ada di keyboard tetap.
        $this->assertContains('🇮🇩 Bahasa', $keyboardLabels);
        $this->assertContains('🇬🇧 English', $keyboardLabels);

        // Dan keduanya dikenali parser.
        foreach (['🇮🇩 Bahasa', '🇬🇧 English'] as $label) {
            $this->assertTrue(BotCommandParser::anyLabel($label), "Label keyboard '{$label}' tidak dikenali parser.");
        }

        // Tombol panduan harus merujuk nama yang benar-benar ada di keyboard.
        $help = $formatter->formatHelp($this->telegram());
        foreach ($this->labelsIn($help) as $label) {
            $this->assertContains($label, $keyboardLabels, "Label panduan '{$label}' tidak ada di keyboard tetap.");
        }
    }

    /**
     * Label yang dirender saat locale `en` harus tetap dikenali parser.
     *
     * Nama tombol "pindah ke Indonesia" BERUBAH mengikuti bahasa aktif
     * (`🇮🇩 Bahasa` → `🇮🇩 Indonesian`). Keduanya mengirim TEKS, jadi keduanya
     * wajib dikenali — ini yang paling mudah terlewat saat menambah bahasa.
     */
    public function test_label_bahasa_saat_locale_en_tetap_dikenali(): void
    {
        app()->setLocale('en');

        $formatter = app(BotMessageFormatter::class);
        $panel = $formatter->languagePanel('en');

        foreach ($this->labelsIn($panel) as $label) {
            if (! $this->isLanguageLabel($label)) {
                continue;
            }

            $this->assertTrue(
                BotCommandParser::anyLabel($label),
                "Tombol bahasa EN '{$label}' tidak dikenali parser.",
            );
        }

        // Label menunya memang versi Inggris, dan tetap memetakan ke 'id'.
        $this->assertContains('🇮🇩 Indonesian', $this->labelsIn($panel));

        app()->setLocale('id');
    }

    // ---------------------------------------------------------------------
    // 2. PERILAKU PERINTAH
    // ---------------------------------------------------------------------

    public function test_perintah_bahasa_menampilkan_panel_pemilih(): void
    {
        $result = $this->handler()->handle('bahasa', [], $this->context());

        $this->assertStringContainsString('Pengaturan Bahasa', (string) $result['text']);
        $this->assertNotEmpty($result['buttons']);
    }

    /** `/language` adalah alias, harus identik. */
    public function test_alias_language_menampilkan_panel_yang_sama(): void
    {
        $a = $this->handler()->handle('bahasa', [], $this->context());
        $b = $this->handler()->handle('language', [], $this->context());

        $this->assertSame($a['text'], $b['text']);
    }

    /**
     * Inti fase ini: menekan pilihan bahasa menyimpan preferensi DAN membalas
     * dalam bahasa baru dalam satu langkah.
     */
    public function test_memilih_english_menyimpan_preferensi_dan_membalas_english(): void
    {
        $context = $this->context();

        $result = $this->handler()->handle('bahasa_en', [], $context);

        // Preferensi tersimpan sebagai pilihan EKSPLISIT (mengikat permanen).
        $row = BotLocalePreference::query()->firstOrFail();
        $this->assertSame('en', $row->locale);
        $this->assertSame(BotLocalePreference::SOURCE_EXPLICIT, $row->locale_source);

        // Balasannya sudah bahasa Inggris.
        $this->assertStringContainsString('Language Settings', (string) $result['text']);
        $this->assertStringContainsString('Language switched', (string) $result['text']);

        // Handler memang mengubah locale request ini — sama seperti request
        // nyata. Yang mengikat: adapter yang memulihkannya di `finally`
        // (dibuktikan terpisah di test adapter), jadi nilai ini tidak boleh
        // dibiarkan sebagai jaminan di sini.
        app()->setLocale('id');
        $this->assertSame('id', app()->getLocale());
    }

    public function test_memilih_indonesia_dari_locale_en(): void
    {
        $context = $this->context();

        $this->handler()->handle('bahasa_en', [], $context);
        $result = $this->handler()->handle('bahasa_id', [], $context);

        $this->assertSame('id', BotLocalePreference::query()->firstOrFail()->locale);
        $this->assertStringContainsString('Pengaturan Bahasa', (string) $result['text']);
        $this->assertStringContainsString('Bahasa diganti', (string) $result['text']);
    }

    /**
     * Jalur TAP tombol picker: `bahasa en` (command + argumen), bukan `bahasa_en`.
     *
     * Tombol inline membawa `callback` = `bahasa en`. Kalau argumen itu diabaikan,
     * tap-nya hanya membuka panel lagi tanpa mengubah bahasa — terlihat persis
     * seperti tombol rusak, padahal tidak ada error.
     */
    public function test_jalur_callback_memilih_bahasa_menyimpan_dan_mengganti(): void
    {
        $context = $this->context();

        $result = $this->handler()->handle('bahasa', ['en'], $context);

        $this->assertSame('en', BotLocalePreference::query()->firstOrFail()->locale);
        $this->assertStringContainsString('Language Settings', (string) $result['text']);
        $this->assertStringContainsString('Language switched', (string) $result['text']);
    }

    /** Argumen tak dikenal tidak boleh dianggap pilihan (fallback ke panel). */
    public function test_argumen_bahasa_tak_dikenal_diabaikan_aman(): void
    {
        $result = $this->handler()->handle('bahasa', ['klingon'], $this->context());

        $this->assertSame(0, BotLocalePreference::query()->count(), 'Argumen tak sah tidak boleh tersimpan.');
        $this->assertStringContainsString('Pengaturan Bahasa', (string) $result['text']);
    }

    /** Memilih bahasa yang sudah aktif tidak boleh berbohong "diganti". */
    public function test_memilih_bahasa_yang_sama_diberitahu_sudah_aktif(): void
    {
        $context = $this->context();

        $result = $this->handler()->handle('bahasa_id', [], $context);

        $this->assertStringContainsString('sudah', (string) $result['text']);
        $this->assertStringNotContainsString('diganti ke', (string) $result['text']);
    }

    /** Panel `/bahasa` TIDAK boleh mengubah preferensi apa pun. */
    public function test_panel_bahasa_tidak_mengubah_preferensi(): void
    {
        $this->handler()->handle('bahasa', [], $this->context());

        $this->assertSame(0, BotLocalePreference::query()->count(), 'Membuka panel tidak boleh menyimpan preferensi.');
    }

    /** Pilihan bahasa mengikat: auto-deteksi tidak boleh menimpanya. */
    public function test_pilihan_bahasa_tidak_ditimpa_deteksi_berikutnya(): void
    {
        $context = $this->context();

        $this->handler()->handle('bahasa_en', [], $context);

        // Pesan berikutnya: user mengganti UI Telegram ke Indonesia.
        app(BotLocale::class)->seed($context, 'id');

        $this->assertSame('en', app(BotLocale::class)->resolve($context));
    }

    // ---------------------------------------------------------------------
    // 3. GATE TIDAK BOLEH MENGUNCI JALAN KELUAR
    // ---------------------------------------------------------------------

    /**
     * `/bahasa` dan tombolnya harus tetap bisa dipakai walau user BELUM lolos
     * gerbang keanggotaan.
     *
     * Kalau ini rusak, user yang salah-deteksi bahasa tapi belum bergabung
     * channel terjebak: satu-satunya jalan ganti bahasa ada di balik gerbang.
     */
    public function test_perintah_bahasa_lolos_gate_keanggotaan(): void
    {
        config([
            'services.telegram-bot-api.required_channel.enabled' => true,
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '@wajib', 'url' => 'https://t.me/wajib'],
            ],
        ]);

        // getChatMember bilang user BUKAN anggota.
        Http::fake([
            'https://api.telegram.org/bot*/getChatMember' => Http::response([
                'ok' => true,
                'result' => ['status' => 'left'],
            ]),
        ]);

        $context = $this->context('6252007210');

        foreach (['bahasa', 'bahasa_id', 'bahasa_en'] as $command) {
            $result = $this->handler()->handle($command, [], $context);

            $this->assertStringNotContainsString(
                'Akses Terbatas',
                (string) $result['text'],
                "Perintah '{$command}' terkunci di belakang gerbang keanggotaan.",
            );
        }
    }

    // ---------------------------------------------------------------------
    // 4. SCOPE TELEGRAM SAJA
    // ---------------------------------------------------------------------

    /**
     * WhatsApp tidak boleh ikut switch bahasa.
     *
     * Diuji lewat FORM+METODE yang sama supaya berbeda dari sekadar memeriksa
     * satu string: `/bahasa` di WhatsApp jatuh ke input tak dikenal seperti
     * sebelumnya, dan TIDAK ada tombol bahasa di keyboard/panduannya.
     */
    public function test_whatsapp_tidak_ikut_switch_bahasa(): void
    {
        $capabilities = BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_WHATSAPP);
        $formatter = app(BotMessageFormatter::class);

        $help = $formatter->formatHelp($capabilities);
        foreach ($this->labelsIn($help) as $label) {
            $this->assertStringNotContainsString('English', $label, 'Tombol bahasa bocor ke WhatsApp.');
            $this->assertStringNotContainsString('Bahasa', $label, 'Tombol bahasa bocor ke WhatsApp.');
        }

        // Keyboard bawaannya (WhatsApp) tidak boleh memuat label bahasa.
        $whatsappKeyboard = $formatter->defaultReplyKeyboard($capabilities)['keyboard'];
        $labels = [];
        foreach ($whatsappKeyboard as $row) {
            foreach ($row as $button) {
                $labels[] = (string) $button['text'];
            }
        }

        $this->assertNotContains('🇬🇧 English', $labels);
        $this->assertNotContains('🇮🇩 Bahasa', $labels);
    }

    /** Perintah `/bahasa` di WhatsApp tidak membuka panel bahasa. */
    public function test_perintah_bahasa_di_whatsapp_tidak_membuka_panel(): void
    {
        $context = [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:628123456789',
            'whatsapp' => '628123456789',
        ];

        $result = $this->handler()->handle('bahasa', [], $context);
        $text = (string) ($result['text'] ?? '');

        $this->assertStringNotContainsString('Pengaturan Bahasa', $text);
        $this->assertStringNotContainsString('Language Settings', $text);
    }

    // ---------------------------------------------------------------------
    // 4b. KEYBOARD IKUT BAHASA AKTIF
    // ---------------------------------------------------------------------

    /** @return array<int, string> */
    private function keyboardLabels(): array
    {
        $keyboard = app(BotMessageFormatter::class)->defaultReplyKeyboard($this->telegram());

        $labels = [];
        foreach ($keyboard['keyboard'] as $row) {
            foreach ($row as $button) {
                $labels[] = (string) $button['text'];
            }
        }

        return $labels;
    }

    /**
     * Inti Fase 5: keyboard tetap IKUT bahasa aktif.
     *
     * Sebelumnya label keyboard literal Indonesia di semua bahasa, jadi user
     * berbahasa Inggris melihat teks Inggris dengan tombol Indonesia.
     */
    public function test_keyboard_ikut_bahasa_aktif(): void
    {
        $formatter = app(BotMessageFormatter::class);

        app()->setLocale('en');
        $en = $this->keyboardLabels();

        $this->assertContains('🛍️ Open Menu', $en);
        $this->assertContains('❓ Help', $en);
        $this->assertContains('📦 Check Status', $en);
        $this->assertContains('🔍 Check Game ID', $en);
        // Tombol batal DIHAPUS dari keyboard (keputusan user), jadi ia tidak
        // boleh muncul di bahasa mana pun. Dinyatakan di kedua locale supaya
        // penghapusannya tidak bisa lolos hanya karena labelnya berbeda:
        // penyaringan berbasis literal Indonesia pernah membuat tombol EN
        // `❌ Cancel Order` tetap terkirim.
        $this->assertNotContains('❌ Cancel Order', $en);
        $this->assertNotContains('❌ Batal Transaksi', $en);
        $this->assertNotContains('🛍️ Buka Menu', $en, 'Locale en tidak boleh merender label Indonesia.');
        $this->assertNotContains('❓ Bantuan', $en);
        $this->assertSame('Choose an action...', $formatter->defaultReplyKeyboard($this->telegram())['input_field_placeholder']);

        app()->setLocale('id');
        $id = $this->keyboardLabels();

        $this->assertContains('🛍️ Buka Menu', $id);
        $this->assertContains('❓ Bantuan', $id);
        $this->assertContains('📦 Cek Status', $id);
        $this->assertContains('🔍 Cek ID Game', $id);
        $this->assertNotContains('❌ Batal Transaksi', $id);
        $this->assertNotContains('❌ Cancel Order', $id);
        $this->assertNotContains('🛍️ Open Menu', $id, 'Locale id tidak boleh merender label Inggris.');
        $this->assertSame('Pilih aksi...', $formatter->defaultReplyKeyboard($this->telegram())['input_field_placeholder']);
    }

    /**
     * SETIAP label keyboard yang dirender di KEDUA locale harus dikenal parser.
     *
     * Ini jaring yang paling penting untuk fase ini: keyboard sekarang berubah
     * bahasa, jadi himpunan label yang dikirim ke user berlipat. Satu label baru
     * yang lupa didaftarkan = tombol yang diam saat ditekan, dan itu di keyboard
     * yang MENETAP di layar.
     */
    public function test_semua_label_keyboard_kedua_locale_dikenali_parser(): void
    {
        $formatter = app(BotMessageFormatter::class);

        foreach (['id', 'en'] as $locale) {
            app()->setLocale($locale);
            $labels = $this->keyboardLabels();

            $this->assertNotEmpty($labels, "Keyboard locale {$locale} kosong.");

            foreach ($labels as $label) {
                $this->assertTrue(
                    BotCommandParser::anyLabel($label),
                    "Label keyboard '{$label}' (locale {$locale}) tidak dikenali parser — tombol mati.",
                );
            }

            // Locale default harus tetap terpakai setelah render.
            app()->setLocale($locale);
        }

        app()->setLocale('id');
    }

    /**
     * Copy yang MENTION nama tombol harus memakai nama itu — bukan nama versi
     * bahasa lain, dan bukan perintah mentah.
     *
     * Ini yang menangkap copy basi: dulu prosa EN menulis `*🛍️ Buka Menu*`
     * sementara keyboard (kini) menulis `*🛍️ Open Menu*`.
     */
    public function test_copy_menyebut_nama_tombol_yang_benar(): void
    {
        $formatter = app(BotMessageFormatter::class);

        app()->setLocale('en');
        $help = (string) $formatter->formatHelp($this->telegram())['text'];

        foreach ($this->keyboardLabels() as $label) {
            // Setiap tombol yang disebut di panduan harus ada di keyboard.
            if (str_contains($help, "*{$label}*")) {
                $this->assertContains($label, $this->keyboardLabels());
            }
        }

        $this->assertStringContainsString('*🛍️ Open Menu*', $help);
        $this->assertStringContainsString('*📦 Check Status*', $help);
        $this->assertStringNotContainsString('*🛍️ Buka Menu*', $help, 'Panduan EN menyebut tombol berbahasa Indonesia.');
        $this->assertStringNotContainsString('*❌ Batal Transaksi*', $help);
        // Petunjuk bahasa menyebut tombol yang memang ada di keyboard.
        $this->assertStringContainsString('*🇬🇧 English*', $help);
        $this->assertStringContainsString('*🇮🇩 Indonesian*', $help);

        app()->setLocale('id');
        $helpId = (string) $formatter->formatHelp($this->telegram())['text'];

        $this->assertStringContainsString('*🛍️ Buka Menu*', $helpId);
        // Panduan TIDAK menyebut batal di bahasa mana pun: tombolnya sudah
        // dicabut dari keyboard, jadi menyebutkannya mengarahkan user ke tombol
        // yang tidak ada. Dinyatakan di kedua locale supaya pencabutan barisnya
        // tidak bisa lolos hanya karena labelnya berbeda bahasa.
        $this->assertStringNotContainsString('*❌ Batal Transaksi*', $helpId);
        $this->assertStringNotContainsString('*❌ Cancel Order*', $helpId);
        $this->assertStringNotContainsString('*🛍️ Open Menu*', $helpId);
    }

    /**
     * Tombol INLINE di panduan dan keyboard tetap harus memakai label yang sama.
     *
     * Tombol yang sama tampil dua kali (inline di pesan, tetap di bawah layar) —
     * dua nama berbeda untuk satu tombol itu membingungkan.
     */
    public function test_tombol_panduan_sama_dengan_keyboard_di_kedua_locale(): void
    {
        $formatter = app(BotMessageFormatter::class);

        foreach (['id', 'en'] as $locale) {
            app()->setLocale($locale);

            $keyboard = $this->keyboardLabels();
            $help = $formatter->formatHelp($this->telegram());

            foreach ($this->labelsIn($help) as $label) {
                // Label bahasa & navigasi callback juga harus ada di keyboard.
                $this->assertContains(
                    $label,
                    $keyboard,
                    "Tombol panduan '{$label}' (locale {$locale}) tidak ada di keyboard tetap.",
                );
            }
        }

        app()->setLocale('id');
    }

    /** WhatsApp tetap literal Indonesia walau locale en (scope terkunci). */
    public function test_keyboard_whatsapp_tetap_indonesia_walau_locale_en(): void
    {
        app()->setLocale('en');

        $capabilities = BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_WHATSAPP);
        $keyboard = app(BotMessageFormatter::class)->defaultReplyKeyboard($capabilities)['keyboard'];

        $labels = [];
        foreach ($keyboard as $row) {
            foreach ($row as $button) {
                $labels[] = (string) $button['text'];
            }
        }

        $this->assertContains('🛍️ Buka Menu', $labels);
        $this->assertContains('📦 Cek Status', $labels);
        $this->assertNotContains('🛍️ Open Menu', $labels);
        $this->assertNotContains('📦 Check Status', $labels);

        app()->setLocale('id');
    }

    // ---------------------------------------------------------------------
    // 5. PANDUAN MENYEBUTKAN JALAN KELUARNYA
    // ---------------------------------------------------------------------

    /**
     * Panduan Telegram harus menyebut bahasa sebagai salah satu hal yang bisa
     * diatur.
     *
     * Ini kompensasi wajib dari auto-deteksi: kalau tebakan bahasa perangkat
     * salah, user harus bisa MENEMUKAN penggantinya tanpa menebak nama perintah.
     */
    public function test_panduan_telegram_menyebut_pengaturan_bahasa(): void
    {
        $help = app(BotMessageFormatter::class)->formatHelp($this->telegram());

        $this->assertStringContainsString('Bahasa', (string) $help['text']);
    }

    /** Panduan WhatsApp tidak boleh ikut menyebut bahasa (scope terkunci). */
    public function test_panduan_whatsapp_tidak_menyebut_bahasa(): void
    {
        $help = app(BotMessageFormatter::class)->formatHelp(
            BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_WHATSAPP),
        );

        $this->assertStringNotContainsString('🌐 Bahasa', (string) $help['text']);
        $this->assertStringNotContainsString('Language', (string) $help['text']);
    }

    // ---------------------------------------------------------------------
    // 6. ADAPTER: SEED + TRY/FINALLY + SCOPE
    // ---------------------------------------------------------------------

    private function postTelegram(array $data, array $headers = [])
    {
        return $this->postJson('/api/webhooks/bot/telegram', $data, array_merge([
            'X-Telegram-Bot-Api-Secret-Token' => config('services.telegram-bot-api.webhook_secret', ''),
        ], $headers));
    }

    /**
     * Chat privat: `language_code` Telegram menjadi benih bahasa.
     *
     * Ini jalur auto-deteksi yang diminta user — tapi perhatikan: benih hanya
     * ditulis kalau BELUM ada preferensi, jadi ini tidak bisa menimpa pilihan.
     */
    public function test_chat_privat_dengan_language_code_en_dapat_balasan_inggris(): void
    {
        // Token WAJIB: tanpa ini `sendReply()` keluar lebih awal dan tidak ada
        // request yang bisa di-assert — test akan gagal karena alasan yang
        // menyesatkan (terlihat seperti bug locale, padahal token kosong).
        config([
            'services.telegram-bot-api.token' => 'dummy-token',
            'services.telegram-bot-api.webhook_secret' => 'dummy-secret',
        ]);

        Http::fake(['https://api.telegram.org/*/sendMessage' => Http::response(['ok' => true])]);

        $this->postTelegram([
            'message' => [
                'chat' => ['id' => 6252007210, 'type' => 'private'],
                'from' => ['id' => 6252007210, 'language_code' => 'en'],
                'text' => '/bahasa',
                'message_id' => 11,
            ],
        ])->assertOk();

        // Benih tersimpan sebagai DETECTED (bukan explicit — user belum memilih).
        $row = BotLocalePreference::query()->firstOrFail();
        $this->assertSame('en', $row->locale);
        $this->assertSame(BotLocalePreference::SOURCE_DETECTED, $row->locale_source);

        // Dan balasannya benar-benar Inggris.
        Http::assertSent(function ($request): bool {
            return str_contains((string) ($request['text'] ?? ''), 'Language Settings');
        });
    }

    /**
     * Grup TIDAK boleh disemai: `language_code` di grup milik PENGIRIM, bukan
     * audiens. Kalau ini bocor, satu anggota menentukan bahasa semua orang.
     */
    public function test_grup_tidak_disemai_walau_pengirim_berbahasa_inggris(): void
    {
        config(['services.telegram-bot-api.webhook_secret' => 'dummy-secret']);

        Http::fake(['https://api.telegram.org/*/sendMessage' => Http::response(['ok' => true])]);

        $this->postTelegram([
            'message' => [
                'chat' => ['id' => -1004406592692, 'type' => 'supergroup'],
                'from' => ['id' => 6252007210, 'language_code' => 'en'],
                'text' => '/bahasa',
                'message_id' => 12,
            ],
        ])->assertOk();

        $this->assertSame(0, BotLocalePreference::query()->count(), 'Grup tidak boleh menyemai bahasa.');
    }

    /**
     * Keyboard yang terkirim harus memakai label bahasa yang AKTIF.
     *
     * Reply keyboard dibangun di lapisan paling akhir (saat mengirim balasan),
     * bukan saat handler menyusun teks. Kalau urutannya salah, user berbahasa
     * Inggris menerima teks Inggris tapi keyboard berlabel Indonesia — dan itu
     * terlihat seperti "bahasanya tidak berganti".
     */
    public function test_keyboard_yang_terkirim_memakai_label_bahasa_aktif(): void
    {
        config([
            'services.telegram-bot-api.token' => 'dummy-token',
            'services.telegram-bot-api.webhook_secret' => 'dummy-secret',
        ]);

        Http::fake(['https://api.telegram.org/*/sendMessage' => Http::response(['ok' => true])]);

        // Gelombang 1: `/start` — satu-satunya jalur yang memasang reply
        // keyboard (dan karena itu satu-satunya tempat labelnya bisa salah).
        $this->postTelegram([
            'message' => [
                'chat' => ['id' => 6252007210, 'type' => 'private'],
                'from' => ['id' => 6252007210, 'language_code' => 'id'],
                'text' => '/start',
                'message_id' => 21,
            ],
        ])->assertOk();

        // Gelombang 2: user menekan tombol English (callback, bukan teks).
        $this->postTelegram([
            'callback_query' => [
                'id' => 'cb-1',
                'from' => ['id' => 6252007210, 'language_code' => 'id'],
                'data' => 'bahasa en',
                'message' => [
                    'message_id' => 22,
                    'chat' => ['id' => 6252007210, 'type' => 'private'],
                ],
            ],
        ])->assertOk();

        // Gelombang 3: `/start` lagi — keyboard dibangun ulang, sekarang harus
        // memakai penamaan bahasa Inggris.
        $this->postTelegram([
            'message' => [
                'chat' => ['id' => 6252007210, 'type' => 'private'],
                'from' => ['id' => 6252007210, 'language_code' => 'id'],
                'text' => '/start',
                'message_id' => 23,
            ],
        ])->assertOk();

        $labels = [];
        foreach (Http::recorded() as [$request]) {
            $markup = $request['reply_markup'] ?? null;
            if (is_array($markup) && isset($markup['keyboard'])) {
                foreach ($markup['keyboard'] as $row) {
                    foreach ($row as $button) {
                        $labels[] = (string) ($button['text'] ?? '');
                    }
                }
            }
        }

        $this->assertNotEmpty($labels, 'Tidak ada reply_markup.keyboard yang terkirim.');

        // Penamaan ID harus muncul lebih dulu (gelombang 1), lalu penamaan EN
        // (gelombang 3). Kalau yang kedua tidak pernah muncul, artinya label
        // dibangun setelah locale dipulihkan.
        $this->assertContains('🇮🇩 Bahasa', $labels, 'Keyboard awal tidak memakai penamaan Indonesia.');
        $this->assertContains(
            '🇮🇩 Indonesian',
            $labels,
            'Keyboard setelah pindah bahasa tidak memakai penamaan Inggris — label dibangun setelah locale dipulihkan.',
        );
    }

    /**
     * Locale TIDAK BOLEH bocor ke request berikutnya.
     *
     * `App::setLocale()` global per-proses; di worker antrean nilainya bisa
     * menempel ke job berikutnya. Adapter memulihkannya di `finally`.
     */
    public function test_locale_dipulihkan_setelah_webhook_selesai(): void
    {
        config([
            'services.telegram-bot-api.webhook_secret' => 'dummy-secret',
            'services.telegram-bot-api.default_locale' => 'id',
        ]);

        Http::fake(['https://api.telegram.org/*/sendMessage' => Http::response(['ok' => true])]);

        $this->postTelegram([
            'message' => [
                'chat' => ['id' => 6252007210, 'type' => 'private'],
                'from' => ['id' => 6252007210, 'language_code' => 'en'],
                'text' => '/bahasa',
                'message_id' => 13,
            ],
        ])->assertOk();

        $this->assertSame('id', app()->getLocale(), 'Locale request berikutnya tercemar bahasa user sebelumnya.');
    }

    /**
     * WhatsApp tidak boleh ikut tersentuh mesin locale.
     *
     * Adapter WhatsApp dipanggil LANGSUNG, bukan lewat rute webhook: rute itu
     * dijaga middleware daftar-IP yang tidak ada hubungannya dengan klaim yang
     * diuji di sini (dan 403-nya akan menyamarkan hasil aslinya).
     */
    public function test_adapter_whatsapp_tidak_menyentuh_preferensi_bahasa(): void
    {
        config([
            'services.fonnte.device_token' => 'dummy-device-token',
            'bot.order_wa_enabled' => true,
        ]);

        // Kirim balasan WhatsApp di-stub: yang diuji efek samping locale-nya,
        // bukan pengiriman pesannya.
        $waService = \Mockery::mock(\App\Services\WhatsappNotificationService::class);
        $waService->shouldReceive('sendMessage')->andReturn(['status' => true]);
        $this->app->instance(\App\Services\WhatsappNotificationService::class, $waService);

        $request = \Illuminate\Http\Request::create('/api/webhooks/bot/fonnte', 'POST', [
            'sender' => '628123456789',
            'message' => 'menu',
        ]);
        $request->attributes->set('bot_correlation_id', 'test-correlation');

        app(\App\Services\Bot\Adapters\FonnteAdapter::class)->handle($request);

        $this->assertSame(
            0,
            BotLocalePreference::query()->count(),
            'WhatsApp tidak boleh menulis preferensi bahasa (scope Telegram saja).',
        );

        // Dan locale request tidak berubah karena pesan WhatsApp.
        $this->assertSame('id', app()->getLocale());
    }
}
