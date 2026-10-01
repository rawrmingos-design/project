<?php

namespace Tests\Feature\Bot;

use App\Models\InboundSourcePolicy;
use App\Models\User;
use App\Services\Bot\TelegramChannelMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * BRUTE-FORCE & PENYALAHGUNAAN AKUN LEWAT BOT TELEGRAM.
 *
 * Celah yang mengunci berkas ini: pembuatan akun lewat percakapan bot Telegram
 * TIDAK punya pembatas sama sekali. Satu akun Telegram bisa menjalankan alur
 * `deposit` → `YA` → username → email berulang kali, dan tiap putaran melahirkan
 * satu `users` baru + satu `TelegramIdentity` — tanpa satu pun penghitung.
 *
 * `config('rate_limits.callbacks.telegram_account_per_sender_per_minute')` sudah
 * ada di config sejak lama, tapi TIDAK PERNAH dirujuk kode mana pun: kunci yang
 * berbohong, dan orang yang membaca config akan menyangka registrasi sudah
 * dibatasi padahal tidak. Sekarang ia benar-benar ditegakkan di
 * `createTelegramAccount()`, dan berkas ini menguncinya.
 *
 * Pembatas lain yang juga diuji di sini (sudah ada, tapi belum punya test):
 *  - `telegram_link_per_sender_per_minute` pada percobaan `LINK <token>`;
 *  - token linking 64 karakter acak + `max_attempts` — brute-force token tidak
 *    boleh menembus walaupun percobaannya banyak.
 */
class TelegramRegistrationAbuseTest extends TestCase
{
    use RefreshDatabase;

    private const TG_SECRET = 'uji-rahasia-telegram';

    private const TG_IP = '149.154.160.10';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'services.telegram-bot-api.token' => 'dummy-token',
            'services.telegram-bot-api.webhook_secret' => self::TG_SECRET,
            'services.telegram-bot-api.bot_scope' => 'default',
            'services.telegram-bot-api.order_enabled' => true,
            'services.telegram-bot-api.deposit_enabled' => true,
        ]);

        $this->seedSettingWeb();

        foreach (['telegram', 'fonnte', 'openwa'] as $source) {
            InboundSourcePolicy::query()->updateOrCreate(
                ['source_domain' => 'bot_webhook', 'source_name' => $source],
                ['mode' => 'disabled', 'is_active' => true],
            );
        }

        Cache::flush();
        RateLimiter::clear('bot-invalid:ip:' . self::TG_IP);
    }

    private function seedSettingWeb(): void
    {
        DB::table('setting_webs')->updateOrInsert(['id' => 1], [
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
        ]);
    }

    /**
     * Semua keanggotaan dijawab `member` supaya gate tidak menahan percobaan —
     * yang diuji di sini pembatas laju, bukan gerbang join-channel (itu punya
     * berkasnya sendiri).
     */
    private function fakeMembership(): void
    {
        Http::fake([
            'api.telegram.org/bot*/getChatMember*' => Http::response([
                'ok' => true,
                'result' => ['status' => 'member', 'is_member' => true],
            ]),
            'api.telegram.org/bot*/sendMessage*' => Http::response([
                'ok' => true, 'result' => ['message_id' => 1],
            ]),
            'api.telegram.org/bot*/sendPhoto*' => Http::response([
                'ok' => true, 'result' => ['message_id' => 1],
            ]),
            'api.telegram.org/bot*/answerCallbackQuery*' => Http::response([
                'ok' => true, 'result' => true,
            ]),
        ]);
    }

    private function send(int $updateId, string $text, int $fromId = 555001): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => self::TG_IP])
            ->postJson('/api/webhooks/bot/telegram', [
                'update_id' => $updateId,
                'message' => [
                    'message_id' => 1000 + $updateId,
                    'chat' => ['id' => $fromId, 'type' => 'private'],
                    'from' => ['id' => $fromId, 'first_name' => 'Tester', 'language_code' => 'id'],
                    'text' => $text,
                ],
            ], ['X-Telegram-Bot-Api-Secret-Token' => self::TG_SECRET])
            ->assertStatus(200);
    }

    /** Jalankan alur registrasi lengkap sampai akun tercipta. */
    private function jalankanRegistrasi(int $awal, int $fromId, string $username): void
    {
        $this->send($awal, 'deposit', $fromId);
        $this->send($awal + 1, 'YA', $fromId);
        $this->send($awal + 2, $username, $fromId);
        $this->send($awal + 3, $username . '@contoh.test', $fromId);
    }

    /**
     * Pembatas laju pembuatan akun, di jalur RE-REGISTRASI.
     *
     * Fakta hasil pengukuran (bukan asumsi): alur percakapan NORMAL hanya bisa
     * melahirkan SATU akun per akun Telegram. Setelah akun pertama jadi,
     * `createTelegramAccount()` menulis `TelegramIdentity` dengan `verified_at`
     * terisi, sehingga percobaan `deposit` berikutnya masuk jalur deposit biasa
     * — bukan jalur registrasi lagi.
     *
     * Jalur yang tersisa dan tetap perlu dibatasi adalah RE-REGISTRASI: saat
     * identitas berstatus `revoked` (atau user dihapus, sehingga identitasnya
     * ikut hilang), resolver mengembalikan BUKAN-LINKED dan mesin registrasi
     * hidup lagi. Di situlah seorang pengguna Telegram bisa melahirkan akun
     * berulang kali — dan di situlah `telegram_account_per_sender_per_minute`
     * (yang sebelumnya tidak pernah dipakai kode mana pun) sekarang ditegakkan.
     */
    public function test_pembuatan_akun_ulang_setelah_identitas_dicabut_tetap_dibatasi(): void
    {
        $this->fakeMembership();

        config(['rate_limits.callbacks.telegram_account_per_sender_per_minute' => 3]);

        $fromId = 700123;
        $batas = 3;

        for ($i = 0; $i < $batas; $i++) {
            $this->jalankanRegistrasi(1000 + ($i * 10), $fromId, 'ujicoba' . $i);

            // Cabut identitas supaya jalur registrasi terbuka lagi. Inilah
            // keadaan yang membuat satu akun Telegram bisa mendaftar berulang.
            DB::table('telegram_identities')
                ->where('telegram_user_id', (string) $fromId)
                ->update(['revoked_at' => now()]);
        }

        $tercipta = User::query()->where('username', 'like', 'ujicoba%')->count();
        $this->assertSame($batas, $tercipta, "Kuota {$batas} seharusnya terpakai penuh.");

        // Percobaan berikutnya DITOLAK: kuota habis, tidak boleh ada akun baru.
        DB::table('telegram_identities')
            ->where('telegram_user_id', (string) $fromId)
            ->update(['revoked_at' => now()]);
        $this->jalankanRegistrasi(9000, $fromId, 'ujicobaakhir');

        $this->assertSame(
            $batas,
            User::query()->where('username', 'like', 'ujicoba%')->count(),
            'Setelah kuota habis, pembuatan akun wajib ditolak — bukan terus bertambah.'
        );
        $this->assertDatabaseMissing('users', ['username' => 'ujicobaakhir']);
    }

    /**
     * Di BAWAH kuota, pembuatan akun harus tetap berhasil. Ini penjaga agar
     * pembatas tidak salah menolak pemakaian wajar (regresi "terlalu ketat").
     */
    public function test_di_bawah_kuota_pembuatan_akun_tetap_berhasil(): void
    {
        $this->fakeMembership();

        config(['rate_limits.callbacks.telegram_account_per_sender_per_minute' => 5]);

        $fromId = 750321;

        for ($i = 0; $i < 3; $i++) {
            $this->jalankanRegistrasi(7000 + ($i * 10), $fromId, 'wajar' . $i);
            DB::table('telegram_identities')
                ->where('telegram_user_id', (string) $fromId)
                ->update(['revoked_at' => now()]);
        }

        $this->assertSame(
            3,
            User::query()->where('username', 'like', 'wajar%')->count(),
            'Di bawah kuota, tiap putaran registrasi wajib menghasilkan akun.'
        );
    }

    /**
     * Pembatas dihitung per AKUN Telegram, bukan global. Satu penyalahguna
     * tidak boleh memblokir pengguna lain.
     */
    public function test_akun_telegram_lain_tetap_bisa_daftar(): void
    {
        $this->fakeMembership();

        config(['rate_limits.callbacks.telegram_account_per_sender_per_minute' => 2]);

        $penyalahguna = 800111;

        for ($i = 0; $i < 3; $i++) {
            $this->jalankanRegistrasi(2000 + ($i * 10), $penyalahguna, 'abuser' . $i);
        }

        $sebelum = User::query()->count();

        // Akun Telegram BERBEDA — harus tetap bisa mendaftar.
        $this->jalankanRegistrasi(3000, 800999, 'penggunabaik');

        $this->assertSame(
            $sebelum + 1,
            User::query()->count(),
            'Akun Telegram lain tidak boleh ikut terblokir oleh penyalahguna.'
        );
        $this->assertDatabaseHas('users', ['username' => 'penggunabaik']);
    }

    /**
     * `LINK <token>` juga punya pembatas per pengirim. Sudah ada di kode, tapi
     * belum ada test yang menjaganya.
     */
    public function test_percobaan_linking_dibatasi_per_pengirim(): void
    {
        $this->fakeMembership();

        config(['rate_limits.callbacks.telegram_link_per_sender_per_minute' => 3]);

        $fromId = 900123;

        for ($i = 0; $i < 6; $i++) {
            $this->send(4000 + $i, 'LINK token-palsu-' . $i, $fromId);
        }

        // Setidaknya satu balasan harus berupa pesan pembatas laju.
        $teks = collect(Http::recorded())
            ->map(fn ($pair) => (string) ($pair[0]['text'] ?? $pair[0]->data()['text'] ?? ''))
            ->filter(fn (string $t) => str_contains($t, 'Terlalu banyak percobaan'))
            ->values();

        $this->assertNotEmpty(
            $teks,
            'Setelah melewati batas linking, user harus menerima pesan pembatas laju.'
        );
    }

    /**
     * Token linking dibuat 64 karakter acak dan disimpan sebagai hash. Percobaan
     * token karangan tidak boleh pernah berhasil.
     */
    public function test_token_linking_karangan_tidak_pernah_diterima(): void
    {
        $this->fakeMembership();

        // Daftarkan akun pertama supaya ada user nyata.
        $this->jalankanRegistrasi(5000, 910123, 'punyauser');
        $this->assertDatabaseHas('users', ['username' => 'punyauser']);

        foreach (['', 'abc', 'aaaabbbbccccdddd', str_repeat('a', 64), '0'] as $token) {
            $this->send(6000 + strlen($token), 'LINK ' . $token, 910999);
        }

        // Tidak ada TelegramIdentity baru di luar yang sah dari registrasi.
        $this->assertSame(
            1,
            DB::table('telegram_identities')->count(),
            'Token karangan tidak boleh menautkan identitas Telegram apa pun.'
        );
    }
}
