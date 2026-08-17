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
            $table->boolean('use_separate_bot_wa')->default(false)->after('bot_order_wa_enabled');
            $table->string('wa_bot_key')->nullable()->after('use_separate_bot_wa');
            $table->string('wa_bot_number')->nullable()->after('wa_bot_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->dropColumn([
                'use_separate_bot_wa',
                'wa_bot_key',
                'wa_bot_number',
            ]);
        });
    }
};
