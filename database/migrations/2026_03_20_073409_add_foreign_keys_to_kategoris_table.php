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
        if (! Schema::hasTable('kategoris') || ! Schema::hasTable('category_types')) {
            return;
        }

        if ($this->foreignKeyExists('kategoris', 'kategoris_category_type_id_foreign')) {
            return;
        }

        Schema::table('kategoris', function (Blueprint $table) {
            $table->foreign(['category_type_id'])->references(['id'])->on('category_types')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('kategoris') || ! $this->foreignKeyExists('kategoris', 'kategoris_category_type_id_foreign')) {
            return;
        }

        Schema::table('kategoris', function (Blueprint $table) {
            $table->dropForeign('kategoris_category_type_id_foreign');
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
