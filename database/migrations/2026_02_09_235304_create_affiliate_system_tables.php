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
        // 1. Add columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code')->unique()->nullable()->after('username');
            $table->string('uplink')->nullable()->after('referral_code'); // Stores uplink username or ID
        });

        // 2. Add commission setting to setting_webs
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->integer('commission_percent')->default(20)->after('profit_public');
        });

        // 3. Create affiliate history table
        Schema::create('affiliate_histories', function (Blueprint $table) {
            $table->id();
            $table->string('uplink_id'); // Who gets the commission
            $table->string('downlink_id'); // Who made the transaction
            $table->string('order_id')->nullable();
            $table->bigInteger('amount');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_histories');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['referral_code', 'uplink']);
        });

        Schema::table('setting_webs', function (Blueprint $table) {
            $table->dropColumn('commission_percent');
        });
    }
};
