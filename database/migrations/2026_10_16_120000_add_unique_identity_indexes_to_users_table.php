<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci identitas di level database: satu username dan satu email = satu akun.
 *
 * Sebelum ini `users.email` dan `users.username` sama sekali tidak punya unique
 * index — keunikannya hanya dijaga validasi aplikasi, jalur per jalur. Begitu ada
 * satu jalur yang lupa mengecek (jalur Google pernah begitu), duplikat langsung
 * masuk tanpa ada yang menahannya. Index ini yang membuat aturan "satu orang satu
 * akun" tidak bisa dilanggar lagi, bukan sekadar diharapkan.
 *
 * Pemasangannya dipisah karena kondisi datanya berbeda per kolom:
 *
 *   - `username` — bersih di staging & prod (0 duplikat). WAJIB berhasil. Kalau
 *     ada duplikat, migrasi memang harus gagal: `username` adalah kunci login
 *     (LoginController memakai `Auth::attempt(['username', ...])`), jadi dua baris
 *     dengan username sama berarti satu orang bisa masuk ke akun orang lain.
 *
 *   - `email` — bersih di prod, tapi di staging masih ada 515 grup email duplikat
 *     dari data seed lama (dibuat 14 Jul dalam rentang 44 detik). Menghapus atau
 *     menulis ulang 500+ baris data bukan tugas migrasi, jadi index-nya dipasang
 *     HANYA kalau datanya sudah bersih; kalau belum, migrasi mencatat peringatan
 *     berisi jumlahnya lalu lanjut — bukan menggagalkan deploy. Selama index email
 *     belum terpasang, jalur Google tetap dijaga di lapisan aplikasi
 *     (`UserIdentityService`).
 *
 * Catatan teknis:
 * - Kolom memakai collation `utf8mb4_unicode_ci` → unique-nya case-insensitive:
 *   "Budi@mail.com" dan "budi@mail.com" dianggap sama. Ini yang diinginkan.
 * - MySQL 8 + DYNAMIC row format → batas index 3072 byte, `varchar(255)` utf8mb4
 *   (maks 1020 byte) aman, tidak perlu prefix.
 * - `no_wa` tidak diikutkan: ada baris `no_wa` NULL dan beberapa nomor duplikat;
 *   mengunci kolom ini perlu keputusan produk dulu.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicateUsernames = $this->duplicateGroups('username');

        if ($duplicateUsernames > 0) {
            throw new RuntimeException(
                "Tidak bisa memasang unique index `users.username`: masih ada "
                . "{$duplicateUsernames} username duplikat. Bereskan datanya dulu "
                . '(gabungkan atau ganti salah satunya), baru jalankan migrasi ini lagi.'
            );
        }

        if (! Schema::hasIndex('users', 'users_username_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('username', 'users_username_unique');
            });
        }

        $duplicateEmails = $this->duplicateGroups('email');

        if ($duplicateEmails > 0) {
            Log::warning('Unique index `users.email` belum dipasang: masih ada email duplikat.', [
                'duplicate_email_groups' => $duplicateEmails,
            ]);

            return;
        }

        if (! Schema::hasIndex('users', 'users_email_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('email', 'users_email_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasIndex('users', 'users_email_unique')) {
                $table->dropUnique('users_email_unique');
            }

            if (Schema::hasIndex('users', 'users_username_unique')) {
                $table->dropUnique('users_username_unique');
            }
        });
    }

    /**
     * Jumlah nilai yang dipakai lebih dari satu baris.
     *
     * Case-insensitive: `Budi@mail.com` dan `budi@mail.com` dihitung sebagai
     * duplikat, karena index unique-nya nanti juga akan menganggap begitu — dan
     * begitu pula `UserIdentityService` yang memakai `LOWER()`.
     * Baris NULL dikecualikan: unique index MySQL mengizinkan banyak NULL.
     */
    private function duplicateGroups(string $column): int
    {
        return (int) DB::table('users')
            ->whereNotNull($column)
            ->selectRaw("LOWER({$column}) AS nilai")
            ->groupByRaw("LOWER({$column})")
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();
    }
};
