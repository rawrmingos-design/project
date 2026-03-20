<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            $table->string('base_order_id')->nullable()->after('order_id');
            $table->unsignedInteger('invoice_version')->default(0)->after('base_order_id');
            $table->string('display_order_id')->nullable()->after('invoice_version');
            $table->unsignedBigInteger('active_layanan_id')->nullable()->after('layanan');
            $table->string('active_provider_code')->nullable()->after('active_layanan_id');
            $table->string('active_provider_sku')->nullable()->after('active_provider_code');
            $table->string('active_attempt_token')->nullable()->after('active_provider_sku');
            $table->string('active_attempt_reference')->nullable()->after('active_attempt_token');
            $table->string('reset_status')->default('none')->after('active_attempt_reference');
            $table->unsignedInteger('reset_count')->default(0)->after('reset_status');
            $table->unsignedBigInteger('reset_requested_by')->nullable()->after('reset_count');
            $table->timestamp('reset_requested_at')->nullable()->after('reset_requested_by');
            $table->text('reset_reason')->nullable()->after('reset_requested_at');

            $table->index('base_order_id');
            $table->index(['base_order_id', 'invoice_version']);
            $table->index('display_order_id');
            $table->index('active_layanan_id');
            $table->index('active_attempt_token');
            $table->index('active_attempt_reference');
        });

        DB::table('pembelians')->update([
            'base_order_id' => DB::raw('order_id'),
            'invoice_version' => 0,
            'display_order_id' => DB::raw('order_id'),
            'active_attempt_reference' => DB::raw('order_id'),
            'reset_status' => 'none',
            'reset_count' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            $table->dropIndex(['base_order_id']);
            $table->dropIndex(['base_order_id', 'invoice_version']);
            $table->dropIndex(['display_order_id']);
            $table->dropIndex(['active_layanan_id']);
            $table->dropIndex(['active_attempt_token']);
            $table->dropIndex(['active_attempt_reference']);

            $table->dropColumn([
                'base_order_id',
                'invoice_version',
                'display_order_id',
                'active_layanan_id',
                'active_provider_code',
                'active_provider_sku',
                'active_attempt_token',
                'active_attempt_reference',
                'reset_status',
                'reset_count',
                'reset_requested_by',
                'reset_requested_at',
                'reset_reason',
            ]);
        });
    }
};
