<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('verified_game_accounts')) {
            return;
        }

        Schema::table('verified_game_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('verified_game_accounts', 'country_origin')) {
                // Kode negara akun (ISO-2, mis. "ID", "MY") dari provider cek-ID
                // yang melaporkan region (mis. GoPay). Nullable: provider lain
                // yang tidak melaporkan region tidak mengisi kolom ini.
                $table->string('country_origin', 8)->nullable()->after('source');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('verified_game_accounts')) {
            return;
        }

        Schema::table('verified_game_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('verified_game_accounts', 'country_origin')) {
                $table->dropColumn('country_origin');
            }
        });
    }
};
