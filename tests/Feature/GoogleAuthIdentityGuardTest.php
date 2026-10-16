<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use App\Models\User;
use App\Services\UserIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Jalur Google tidak boleh pernah membuat akun kedua untuk identitas yang sama.
 *
 * `GoogleAuthController` dulu membuat baris baru lewat `User::query()->create()`
 * sebagai jalan terakhir tanpa gerbang apa pun. Test di sini mengunci gerbang itu:
 * setiap kombinasi identitas yang sudah dipakai (google_id, email) harus berakhir
 * dengan "pakai akun yang ada" atau "tolak", tidak pernah "bikin akun baru".
 */
class GoogleAuthIdentityGuardTest extends TestCase
{
    use RefreshDatabase;

    private const GOOGLE_CLIENT_ID = 'test-google-client-id.apps.googleusercontent.com';

    private const SUB = '117584989561986429719';

    private const EMAIL = 'guard-test@gmail.com';

    protected function setUp(): void
    {
        parent::setUp();

        SettingWeb::query()->create([
            'id' => 1,
            'judul_web' => 'Test Store',
            'deskripsi_web' => 'Test store description',
            'keywords' => 'test',
            'url_wa' => 'https://wa.me/620000000000',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'test-topupindo-key',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => 'test-paydisini-key',
            'order_prefik' => 'INV',
            'google_client_id' => self::GOOGLE_CLIENT_ID,
        ]);
    }

    private function fakeGoogleToken(array $claims = []): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(array_merge([
                'aud' => self::GOOGLE_CLIENT_ID,
                'iss' => 'https://accounts.google.com',
                'sub' => self::SUB,
                'email' => self::EMAIL,
                'email_verified' => 'true',
                'name' => 'Guard Test',
                'picture' => 'https://lh3.googleusercontent.com/a/avatar=s96-c',
            ], $claims), 200),
        ]);
    }

    /**
     * Mensimulasikan data warisan: kolom `email` dulu TIDAK punya unique index,
     * jadi satu email bisa menempel di beberapa akun (di staging nyata: 515 email),
     * dan `findAllByEmail()` bisa mengembalikan >1 baris.
     *
     * Kenapa tidak benar-benar men-drop index-nya: `DROP INDEX` adalah DDL, dan
     * di SQLite (database test) DDL di tengah transaksi `RefreshDatabase`
     * membatalkan transaksi itu — seluruh kelas test ikut gagal. Jadi cabang
     * "count > 1" disuntik lewat peran service, bukan lewat skema.
     */
    private function simulateLegacyDuplicateEmails(array $users): void
    {
        $fakes = collect($users);

        $this->partialMock(UserIdentityService::class, function ($mock) use ($fakes): void {
            $mock->shouldReceive('findAllByEmail')->andReturn($fakes);
        });
    }

    /** Bikin user lewat cara biasa (bukan Google), lengkap dengan no_wa. */
    private function makeUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    // -----------------------------------------------------------------
    // 1. Email sudah ada → tautkan, jangan bikin akun kedua
    // -----------------------------------------------------------------

    public function test_existing_account_email_is_reused_instead_of_creating_a_second_one(): void
    {
        $existing = $this->makeUser(['email' => self::EMAIL, 'google_id' => null]);

        $this->fakeGoogleToken();
        $this->post('/id/auth/google', ['credential' => 'fake-token']);

        // Akun ke-2 TIDAK boleh ada: total tetap 1, dan visitor masuk sebagai
        // akun yang sudah ada (bukan akun Google baru).
        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($existing->fresh());
        $this->assertSame(self::SUB, $existing->fresh()->google_id, 'google_id harus tertaut ke akun lama.');
    }

    public function test_email_match_ignores_letter_case(): void
    {
        // Email tersimpan huruf besar, Google mengirim huruf kecil. Dulu jalur
        // tenant memakai `where('email', ...)` persis, jadi beda huruf = akun baru.
        $existing = $this->makeUser(['email' => 'Guard-Test@Gmail.com', 'google_id' => null]);

        $this->fakeGoogleToken();
        $this->post('/id/auth/google', ['credential' => 'fake-token']);

        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($existing->fresh());
    }

    // -----------------------------------------------------------------
    // 2. Google selesai dua langkah → langkah kedua tidak boleh bikin duplikat
    // -----------------------------------------------------------------

    public function test_completion_reuses_the_account_created_while_the_visitor_was_filling_the_form(): void
    {
        $this->fakeGoogleToken();
        $this->post('/id/auth/google', ['credential' => 'fake-token']);

        // Di sela-sela: pendaftaran lain sudah memakai email yang sama.
        $raced = $this->makeUser(['email' => self::EMAIL, 'google_id' => null]);

        $response = $this->post(route('auth.google.complete.post'), ['no_wa' => '081234567890']);

        $response->assertRedirect('/id/dashboard');
        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($raced->fresh());
        $this->assertSame(self::SUB, $raced->fresh()->google_id);
    }

    public function test_completion_refuses_when_two_accounts_already_share_the_google_email(): void
    {
        // Langkah 1: visitor lewat Google ketika emailnya BELUM dipakai siapa pun.
        // Profil ditahan di session (pending), belum ada baris user dibuat.
        $this->fakeGoogleToken();
        $this->post('/id/auth/google', ['credential' => 'fake-token']);

        // Langkah 2: di sela itu keadaan berubah — email yang sama kini menempel
        // di lebih dari satu akun (kondisi warisan yang di staging nyata ada 515).
        $existing = $this->makeUser(['email' => self::EMAIL, 'google_id' => null]);

        $this->simulateLegacyDuplicateEmails([
            $existing,
            User::factory()->make(['id' => 9002, 'email' => self::EMAIL, 'google_id' => null]),
        ]);

        $otherQuadrant = $this->makeUser(['email' => 'orang-lain@example.com', 'google_id' => null]);

        // Langkah 3: menyelesaikan pendaftaran mustahil memilih akun yang benar,
        // jadi harus ditolak — bukan diam-diam memilih salah satu.
        $response = $this->post(route('auth.google.complete.post'), ['no_wa' => '081234567890']);

        $response->assertSessionHasErrors('no_wa');
        $this->assertGuest();
        $this->assertNull($existing->fresh()->google_id, 'Akun warisan tidak boleh ditautkan saat ambigu.');
        $this->assertNull($otherQuadrant->fresh()->google_id);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_store_refuses_google_login_when_the_email_maps_to_two_accounts(): void
    {
        $first = $this->makeUser(['email' => self::EMAIL, 'google_id' => null]);

        $this->simulateLegacyDuplicateEmails([
            $first,
            User::factory()->make(['id' => 9003, 'email' => self::EMAIL, 'google_id' => null]),
        ]);

        $this->fakeGoogleToken();

        $response = $this->post('/id/auth/google', ['credential' => 'fake-token']);

        $response->assertSessionHasErrors('error');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
        $this->assertNull($first->fresh()->google_id, 'Login ambigu tidak boleh menautkan google_id.');
    }

    // -----------------------------------------------------------------
    // 3. google_id sudah tertaut → masuk ke akun itu, apa pun emailnya
    // -----------------------------------------------------------------

    public function test_google_id_match_wins_and_creates_nothing_new(): void
    {
        $linked = $this->makeUser(['email' => 'lain@gmail.com', 'google_id' => self::SUB]);

        $this->fakeGoogleToken();
        $this->post('/id/auth/google', ['credential' => 'fake-token']);

        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($linked->fresh());
        $this->assertSame('lain@gmail.com', $linked->fresh()->email, 'Email akun lama tidak boleh ditimpa.');
    }

    // -----------------------------------------------------------------
    // 4. Jalur normal tetap membuat akun baru (fix tidak boleh over-block)
    // -----------------------------------------------------------------

    public function test_a_genuinely_new_identity_still_creates_one_account(): void
    {
        $this->fakeGoogleToken();
        $this->post('/id/auth/google', ['credential' => 'fake-token']);
        $this->post(route('auth.google.complete.post'), ['no_wa' => '081234567890']);

        $this->assertDatabaseCount('users', 1);

        $created = User::query()->where('email', self::EMAIL)->firstOrFail();
        $this->assertSame(self::SUB, $created->google_id);
        $this->assertSame('Member', $created->role);
    }

    public function test_unique_username_is_still_generated_when_the_plain_one_is_taken(): void
    {
        // "guardtest" (hasil sanitasi "Guard Test") sudah dipakai orang lain.
        $this->makeUser(['username' => 'guardtest', 'email' => 'someone@example.com']);

        $this->fakeGoogleToken();
        $this->post('/id/auth/google', ['credential' => 'fake-token']);
        $this->post(route('auth.google.complete.post'), ['no_wa' => '081234567890']);

        $created = User::query()->where('email', self::EMAIL)->firstOrFail();
        $this->assertNotSame('guardtest', $created->username);
        $this->assertDatabaseCount('users', 2);
    }

    // -----------------------------------------------------------------
    // 5. Index unik di database = penjaga terakhir
    // -----------------------------------------------------------------

    public function test_users_table_has_unique_indexes_on_identity_columns(): void
    {
        $this->assertTrue(
            Schema::hasIndex('users', 'users_username_unique'),
            'users.username harus punya unique index.'
        );
        $this->assertTrue(
            Schema::hasIndex('users', 'users_email_unique'),
            'users.email harus punya unique index.'
        );
    }

    public function test_database_rejects_a_duplicate_email_even_if_application_checks_are_bypassed(): void
    {
        $this->makeUser(['email' => self::EMAIL]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        // Sengaja menembus UserIdentityService: ini membuktikan penjaganya ada di
        // database juga, bukan hanya di lapisan aplikasi.
        User::query()->create([
            'name' => 'Duplikat',
            'username' => 'duplikat-email-01',
            'password' => 'x',
            'email' => self::EMAIL,
            'balance' => 0,
            'no_wa' => '6281999999999',
            'role' => 'Member',
        ]);
    }

    public function test_database_rejects_a_duplicate_username_with_the_same_spelling(): void
    {
        $this->makeUser(['username' => 'Uniquecase']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::query()->create([
            'name' => 'Duplikat',
            'username' => 'Uniquecase',
            'password' => 'x',
            'email' => 'unique-case-other@example.com',
            'balance' => 0,
            'no_wa' => '6281999999998',
            'role' => 'Member',
        ]);
    }

    /**
     * Keunikan username harus tidak peduli huruf besar/kecil.
     *
     * Di MySQL ini dijaga dua lapis: collation `utf8mb4_unicode_ci` pada kolomnya
     * (jadi index unique ikut case-insensitive) DAN `LOWER()` di UserIdentityService.
     * Di SQLite (database test) kolomnya case-sensitive, jadi lapisan aplikasi
     * itulah yang harus diuji — dan itulah yang berlaku di produksi juga, karena
     * kolom `users.email`/`username` TIDAK punya unique index sebelum migrasi ini.
     */
    public function test_identity_service_treats_usernames_case_insensitively(): void
    {
        $existing = $this->makeUser(['username' => 'BudiSaja', 'email' => 'budi.saja@example.com']);

        $identity = app(\App\Services\UserIdentityService::class);

        $this->assertSame(
            $existing->id,
            $identity->findByUsername('budisaja')?->id,
            'Pencarian username harus mengabaikan huruf besar/kecil.'
        );
        $this->assertTrue($identity->usernameTaken('BUDISAJA'), 'Username yang beda huruf besar/kecil harus dianggap terpakai.');
        $this->assertTrue($identity->usernameTaken('budisaja'));
        $this->assertFalse($identity->usernameTaken('budisaja-lain'));
    }

    /** Membuat akun baru tidak boleh mengambil username yang cuma beda huruf besar/kecil. */
    public function test_unique_username_suffixes_around_a_case_only_collision(): void
    {
        $this->makeUser(['username' => 'guardtest', 'email' => 'pemakai-lama@example.com']);

        $this->fakeGoogleToken();
        $this->post('/id/auth/google', ['credential' => 'fake-token']);
        $this->post(route('auth.google.complete.post'), ['no_wa' => '081234567890']);

        $created = User::query()->where('email', self::EMAIL)->firstOrFail();
        $this->assertNotSame('GUARDTEST', strtoupper($created->username));
        $this->assertDatabaseCount('users', 2);
    }
}
