<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pembelians') || Schema::hasColumn('pembelians', 'gateway_principal')) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table) {
            $table->string('gateway_principal')->nullable()->index()->after('traffic_source');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pembelians') || ! Schema::hasColumn('pembelians', 'gateway_principal')) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table) {
            $table->dropIndex(['gateway_principal']);
            $table->dropColumn('gateway_principal');
        });
    }
};
