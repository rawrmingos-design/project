<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->string('openwa_session_id')->nullable()->after('wa_bot_number');
            $table->string('openwa_webhook_secret')->nullable()->after('openwa_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->dropColumn(['openwa_session_id', 'openwa_webhook_secret']);
        });
    }
};
