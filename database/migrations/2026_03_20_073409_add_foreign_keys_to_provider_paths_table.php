<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('provider_paths') || ! Schema::hasTable('layanans')) {
            return;
        }

        if ($this->foreignKeyExists('provider_paths', 'provider_paths_layanan_id_foreign')) {
            return;
        }

        Schema::table('provider_paths', function (Blueprint $table) {
            $table->foreign(['layanan_id'])->references(['id'])->on('layanans')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('provider_paths') || ! $this->foreignKeyExists('provider_paths', 'provider_paths_layanan_id_foreign')) {
            return;
        }

        Schema::table('provider_paths', function (Blueprint $table) {
            $table->dropForeign('provider_paths_layanan_id_foreign');
        });
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
