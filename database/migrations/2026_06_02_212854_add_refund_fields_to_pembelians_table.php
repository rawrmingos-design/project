<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            // Idempotency guard: set when a H2H refund has been processed.
            // If refunded_at IS NOT NULL, refund already happened — do NOT refund again.
            $table->timestamp('refunded_at')->nullable()->after('status');

            // Amount actually refunded (usually equals harga, stored for audit trail).
            $table->bigInteger('refund_amount')->unsigned()->nullable()->after('refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            $table->dropColumn(['refunded_at', 'refund_amount']);
        });
    }
};
