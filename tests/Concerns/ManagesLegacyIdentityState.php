<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membangun keadaan warisan: baris yang dulu bisa ada karena `users.email` dan
 * `users.username` TIDAK punya unique index.
 *
 * Beberapa test (password recovery) perilakunya memang ditentukan oleh keadaan
 * itu — "dua akun memakai email yang sama" harus ditolak. Setelah unique index
 * dipasang, keadaan itu tidak bisa dibuat lewat `User::factory()` lagi, jadi test
 * perlu menyingkap index-nya sebentar.
 *
 * Kenapa `DB::statement` dan bukan `Schema::table(...)->dropUnique(...)`:
 * SQLite (database test) mengimplementasikan `dropUnique` lewat pembuatan ulang
 * tabel, dan DDL seperti itu tidak bisa jalan di tengah transaksi `RefreshDatabase`
 * — SQLite melempar "cannot start a transaction within a transaction" dan SELURUH
 * kelas test ikut gagal. `DROP INDEX` biasa jalan tanpa masalah.
 *
 * Kenapa index-nya harus dipasang kembali manual di `tearDown`: `DROP INDEX`
 * adalah DDL, dan di SQLite perubahan itu tidak ikut ter-rollback bersama
 * transaksi test. Tanpa pemulihan, index yang hilang akan terbawa ke test
 * berikutnya di seluruh run — termasuk test yang justru memverifikasi index itu
 * ada. Pemulihan dilakukan setelah baris duplikat dibersihkan, karena
 * `CREATE UNIQUE INDEX` akan gagal kalau masih ada nilai kembar.
 */
trait ManagesLegacyIdentityState
{
    private bool $droppedUniqueEmailIndex = false;

    private bool $droppedUniqueUsernameIndex = false;

    /** Buka kunci kolom email supaya email kembar bisa dibuat. */
    protected function withoutUniqueEmailIndex(): void
    {
        if (Schema::hasIndex('users', 'users_email_unique')) {
            DB::statement('DROP INDEX users_email_unique');
        }

        $this->droppedUniqueEmailIndex = true;
    }

    /** Buka kunci kolom username supaya username kembar bisa dibuat. */
    protected function withoutUniqueUsernameIndex(): void
    {
        if (Schema::hasIndex('users', 'users_username_unique')) {
            DB::statement('DROP INDEX users_username_unique');
        }

        $this->droppedUniqueUsernameIndex = true;
    }

    /** Buka dua-duanya sekaligus. */
    protected function withoutUniqueIdentityIndexes(): void
    {
        $this->withoutUniqueEmailIndex();
        $this->withoutUniqueUsernameIndex();
    }

    protected function tearDown(): void
    {
        $this->restoreIdentityIndexes();

        parent::tearDown();
    }

    private function restoreIdentityIndexes(): void
    {
        if (! $this->droppedUniqueEmailIndex && ! $this->droppedUniqueUsernameIndex) {
            return;
        }

        // Unique index tidak bisa dipasang selama masih ada nilai kembar, dan
        // baris kembar itu belum tentu sudah ter-rollback saat ini.
        $this->deleteDuplicateIdentityRows();

        if ($this->droppedUniqueUsernameIndex && ! Schema::hasIndex('users', 'users_username_unique')) {
            DB::statement('CREATE UNIQUE INDEX users_username_unique ON users (username)');
        }

        if ($this->droppedUniqueEmailIndex && ! Schema::hasIndex('users', 'users_email_unique')) {
            DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');
        }

        $this->droppedUniqueEmailIndex = false;
        $this->droppedUniqueUsernameIndex = false;
    }

    /**
     * Sisakan satu baris per nilai identitas; hapus sisanya.
     *
     * Hanya menghapus baris yang dibuat di dalam transaksi test — jadi tidak
     * menyentuh data nyata, dan seluruh penghapusan ikut ter-rollback nanti.
     */
    private function deleteDuplicateIdentityRows(): void
    {
        foreach (['email', 'username'] as $column) {
            $duplicates = DB::table('users')
                ->selectRaw("LOWER({$column}) AS nilai")
                ->whereNotNull($column)
                ->groupByRaw("LOWER({$column})")
                ->havingRaw('COUNT(*) > 1')
                ->pluck('nilai');

            foreach ($duplicates as $value) {
                $surplusIds = DB::table('users')
                    ->whereRaw("LOWER({$column}) = ?", [$value])
                    ->orderBy('id')
                    ->pluck('id')
                    ->slice(1);

                DB::table('users')->whereIn('id', $surplusIds->all())->delete();
            }
        }
    }
}
