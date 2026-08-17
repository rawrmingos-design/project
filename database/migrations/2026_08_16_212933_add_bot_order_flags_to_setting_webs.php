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
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->boolean('bot_order_wa_enabled')->default(false)->after('wa_number');
            $table->boolean('bot_order_tg_enabled')->default(false)->after('bot_order_wa_enabled');
            $table->string('telegram_bot_token')->nullable()->after('bot_order_tg_enabled');
            $table->string('telegram_webhook_secret')->nullable()->after('telegram_bot_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->dropColumn(['bot_order_wa_enabled', 'bot_order_tg_enabled', 'telegram_bot_token', 'telegram_webhook_secret']);
        });
    }
};
