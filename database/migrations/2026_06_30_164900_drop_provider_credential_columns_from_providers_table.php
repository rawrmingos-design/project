<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('providers')) {
            return;
        }

        Schema::table('providers', function (Blueprint $table): void {
            foreach (['api_username', 'api_key', 'api_sign'] as $column) {
                if (Schema::hasColumn('providers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('providers')) {
            return;
        }

        Schema::table('providers', function (Blueprint $table): void {
            if (! Schema::hasColumn('providers', 'api_username')) {
                $table->string('api_username')->nullable()->after('name');
            }

            if (! Schema::hasColumn('providers', 'api_key')) {
                $table->string('api_key')->nullable()->after('api_username');
            }

            if (! Schema::hasColumn('providers', 'api_sign')) {
                $table->string('api_sign')->nullable()->after('api_key');
            }
        });
    }
};
