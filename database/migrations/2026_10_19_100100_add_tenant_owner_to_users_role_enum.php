<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah role `tenant_owner` ke kolom users.role.
     *
     * Kenapa perlu: pemilik toko tenant BUKAN reseller H2H. Sebelumnya
     * TenantProvisioningService memaksa role 'Gold' saat invoice lunas, sehingga
     * pemilik tenant mendapat harga Gold di website platform DAN kena
     * EnsureNotReseller (ditendang dari halaman deposit-nya sendiri).
     *
     * Catatan skema: di schema legacy (create_users_table) kolom ini ENUM,
     * sedangkan migrasi kompatibilitas 2026_04_07 membuatnya string. Karena itu
     * alter-nya dilakukan kondisional per driver, dan aman dijalankan dua kali.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            // SQLite/Postgres: kolom string tidak punya batasan nilai.
            return;
        }

        $columnType = (string) (DB::selectOne(
            "SELECT COLUMN_TYPE AS column_type FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'"
        )->column_type ?? '');

        if (! str_contains(strtolower($columnType), 'enum')) {
            return; // sudah string — tidak ada yang perlu diubah
        }

        if (str_contains(strtolower($columnType), "'tenant_owner'")) {
            return; // idempoten
        }

        DB::statement(
            "ALTER TABLE `users` MODIFY `role` ENUM('Admin','Member','Gold','Platinum','tenant_owner')
             NOT NULL DEFAULT 'Member'"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || DB::getDriverName() !== 'mysql') {
            return;
        }

        // Jangan hapus role saat rollback kalau masih dipakai user — biarkan.
        $used = DB::table('users')->where('role', 'tenant_owner')->exists();

        if ($used) {
            return;
        }

        DB::statement(
            "ALTER TABLE `users` MODIFY `role` ENUM('Admin','Member','Gold','Platinum')
             NOT NULL DEFAULT 'Member'"
        );
    }
};
