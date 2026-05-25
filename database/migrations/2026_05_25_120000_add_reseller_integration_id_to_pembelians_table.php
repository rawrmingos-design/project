<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pembelians') || Schema::hasColumn('pembelians', 'reseller_integration_id')) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table): void {
            $table->foreignId('reseller_integration_id')->nullable();
        });

        Schema::table('pembelians', function (Blueprint $table): void {
            $table->foreign('reseller_integration_id', 'pembelians_reseller_integration_fk')
                ->references('id')
                ->on('reseller_integrations')
                ->nullOnDelete();
            $table->index('reseller_integration_id', 'pembelians_reseller_integration_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pembelians') || ! Schema::hasColumn('pembelians', 'reseller_integration_id')) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table): void {
            $table->dropIndex('pembelians_reseller_integration_idx');
            $table->dropForeign(['reseller_integration_id']);
            $table->dropColumn('reseller_integration_id');
        });
    }
};
