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
            $table->string('duitku_merchant_code', 50)->nullable()->after('tripay_private_key');
            $table->string('duitku_merchant_key', 255)->nullable()->after('duitku_merchant_code');
            $table->string('duitku_callback_url', 255)->nullable()->after('duitku_merchant_key');
            $table->string('duitku_return_url', 255)->nullable()->after('duitku_callback_url');
            $table->enum('duitku_mode', ['sandbox', 'production'])->default('sandbox')->after('duitku_return_url');
            $table->boolean('duitku_enabled')->default(false)->after('duitku_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->dropColumn([
                'duitku_merchant_code',
                'duitku_merchant_key',
                'duitku_callback_url',
                'duitku_return_url',
                'duitku_mode',
                'duitku_enabled'
            ]);
        });
    }
};
