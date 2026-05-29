<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pembelians')) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table): void {
            if (! Schema::hasColumn('pembelians', 'environment')) {
                $table->string('environment', 16)->nullable()->after('tipe_transaksi');
            }

            if (! Schema::hasColumn('pembelians', 'is_sandbox')) {
                $table->boolean('is_sandbox')->default(false)->after('environment');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pembelians')) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table): void {
            if (Schema::hasColumn('pembelians', 'is_sandbox')) {
                $table->dropColumn('is_sandbox');
            }

            if (Schema::hasColumn('pembelians', 'environment')) {
                $table->dropColumn('environment');
            }
        });
    }
};
