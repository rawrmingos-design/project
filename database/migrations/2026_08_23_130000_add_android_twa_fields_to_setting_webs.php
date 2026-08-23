<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->string('android_package_id')->nullable()->default(null);
            $table->text('android_cert_fingerprints')->nullable()->default(null);
        });
    }

    public function down(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->dropColumn(['android_package_id', 'android_cert_fingerprints']);
        });
    }
};
