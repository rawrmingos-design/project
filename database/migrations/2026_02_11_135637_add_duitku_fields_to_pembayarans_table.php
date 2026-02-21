<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->string('duitku_merchant_order_id')->nullable()->after('reference');
            $table->string('duitku_reference')->nullable()->after('duitku_merchant_order_id');
            $table->timestamp('paid_at')->nullable()->after('duitku_reference');
            
            // Add index for faster lookups
            $table->index('duitku_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropIndex(['duitku_reference']);
            $table->dropColumn(['duitku_merchant_order_id', 'duitku_reference', 'paid_at']);
        });
    }
};
