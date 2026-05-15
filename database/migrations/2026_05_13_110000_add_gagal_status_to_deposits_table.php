<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('deposits')) {
            return;
        }

        $connection = Schema::getConnection();
        if ($connection->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE `deposits` MODIFY `status` ENUM('Success','Pending','Gagal') NOT NULL DEFAULT 'Pending'"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('deposits')) {
            return;
        }

        $connection = Schema::getConnection();
        if ($connection->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE `deposits` MODIFY `status` ENUM('Success','Pending') NOT NULL DEFAULT 'Pending'"
        );
    }
};
