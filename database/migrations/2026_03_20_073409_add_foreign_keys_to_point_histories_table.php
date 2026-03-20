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
        if (! Schema::hasTable('point_histories') || ! Schema::hasTable('users')) {
            return;
        }

        if ($this->foreignKeyExists('point_histories', 'point_histories_user_id_foreign')) {
            return;
        }

        Schema::table('point_histories', function (Blueprint $table) {
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('point_histories') || ! $this->foreignKeyExists('point_histories', 'point_histories_user_id_foreign')) {
            return;
        }

        Schema::table('point_histories', function (Blueprint $table) {
            $table->dropForeign('point_histories_user_id_foreign');
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
