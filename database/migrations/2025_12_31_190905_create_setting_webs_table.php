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
            $table->integer('profit_public')->nullable();
            $table->integer('profit_member')->nullable();
            $table->integer('profit_platinum')->nullable();
            $table->integer('profit_gold')->nullable();
            $table->timestamps();
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
