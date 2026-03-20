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
        Schema::create('setting_webs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('judul_web');
            $table->text('deskripsi_web');
            $table->text('keywords');
            $table->text('logo_header')->nullable();
            $table->text('logo_footer')->nullable();
            $table->text('logo_favicon')->nullable();
            $table->text('url_wa');
            $table->text('url_ig');
            $table->text('url_tiktok');
            $table->text('url_youtube');
            $table->text('url_fb');
            $table->text('topupindo_api');
            $table->text('apikey_bangjeff')->nullable();
            $table->text('apikey_aoshi')->nullable();
            $table->text('api_mobilegamestore')->nullable();
            $table->text('warna1');
            $table->text('warna2');
            $table->text('warna3');
            $table->text('warna4');
            $table->text('paydisini_apikey');
            $table->text('tripay_api')->nullable();
            $table->text('tripay_merchant_code')->nullable();
            $table->text('tripay_private_key')->nullable();
            $table->string('duitku_merchant_code', 50)->nullable();
            $table->string('duitku_merchant_key')->nullable();
            $table->string('duitku_callback_url')->nullable();
            $table->string('duitku_return_url')->nullable();
            $table->enum('duitku_mode', ['sandbox', 'production'])->default('sandbox');
            $table->enum('deposit_jalur', ['duitku', 'tripay', 'tokopay'])->default('duitku');
            $table->boolean('duitku_enabled')->default(false);
            $table->text('tokopay_merchant_id')->nullable();
            $table->text('tokopay_secret_key')->nullable();
            $table->text('username_digi')->nullable();
            $table->text('api_key_digi')->nullable();
            $table->text('apigames_secret')->nullable();
            $table->text('apigames_merchant')->nullable();
            $table->text('vip_apiid')->nullable();
            $table->text('vip_apikey')->nullable();
            $table->text('nomor_admin')->nullable();
            $table->text('wa_key')->nullable();
            $table->text('wa_number')->nullable();
            $table->text('ovo_admin')->nullable();
            $table->text('ovo1_admin')->nullable();
            $table->text('gopay_admin')->nullable();
            $table->text('gopay1_admin')->nullable();
            $table->text('dana_admin')->nullable();
            $table->text('shopeepay_admin')->nullable();
            $table->text('bca_admin')->nullable();
            $table->text('order_prefik');
            $table->integer('commission_percent')->default(20);
            $table->unsignedInteger('point_per_nominal')->default(1);
            $table->unsignedInteger('point_value')->default(100);
            $table->unsignedInteger('max_point_usage_percent')->default(50);
            $table->integer('profit_member')->nullable();
            $table->integer('profit_platinum')->nullable();
            $table->integer('profit_gold')->nullable();
            $table->integer('trx_count_gold')->default(50);
            $table->integer('trx_count_platinum')->default(100);
            $table->timestamps();
            $table->text('google_analytics_id')->nullable();
            $table->text('facebook_pixel_id')->nullable();
            $table->text('google_tag_manager_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_webs');
    }
};
