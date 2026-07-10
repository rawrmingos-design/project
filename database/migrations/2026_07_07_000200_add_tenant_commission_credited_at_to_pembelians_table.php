<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pembelians') || Schema::hasColumn('pembelians', 'tenant_commission_credited_at')) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table) {
            $table->timestamp('tenant_commission_credited_at')->nullable()->after('profit');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pembelians') || ! Schema::hasColumn('pembelians', 'tenant_commission_credited_at')) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table) {
            $table->dropColumn('tenant_commission_credited_at');
        });
    }
};
