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

        if (Schema::hasColumn('providers', 'api_sign')) {
            return;
        }

        Schema::table('providers', function (Blueprint $table) {
            $table->string('api_sign')->nullable()->after('api_key');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('providers')) {
            return;
        }

        if (! Schema::hasColumn('providers', 'api_sign')) {
            return;
        }

        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('api_sign');
        });
    }
};

