<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('setting_webs')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table): void {
            $this->addTextColumn($table, 'judul_web');
            $this->addTextColumn($table, 'deskripsi_web');
            $this->addTextColumn($table, 'keywords');
            $this->addTextColumn($table, 'logo_header');
            $this->addTextColumn($table, 'logo_footer');
            $this->addTextColumn($table, 'logo_favicon');
            $this->addTextColumn($table, 'url_wa');
            $this->addTextColumn($table, 'url_ig');
            $this->addTextColumn($table, 'url_tiktok');
            $this->addTextColumn($table, 'url_youtube');
            $this->addTextColumn($table, 'url_fb');
            $this->addTextColumn($table, 'topupindo_api');
            $this->addTextColumn($table, 'apikey_bangjeff');
            $this->addTextColumn($table, 'apikey_aoshi');
            $this->addTextColumn($table, 'api_mobilegamestore');
            $this->addTextColumn($table, 'warna1');
            $this->addTextColumn($table, 'warna2');
            $this->addTextColumn($table, 'warna3');
            $this->addTextColumn($table, 'warna4');
            $this->addTextColumn($table, 'paydisini_apikey');
            $this->addTextColumn($table, 'tripay_api');
            $this->addTextColumn($table, 'tripay_merchant_code');
            $this->addTextColumn($table, 'tripay_private_key');
            $this->addStringColumn($table, 'duitku_merchant_code', 50);
            $this->addStringColumn($table, 'duitku_merchant_key');
            $this->addStringColumn($table, 'duitku_callback_url');
            $this->addStringColumn($table, 'duitku_return_url');
            $this->addStringColumn($table, 'duitku_mode', default: 'sandbox');
            $this->addStringColumn($table, 'deposit_jalur', default: 'duitku');
            $this->addBooleanColumn($table, 'duitku_enabled', false);
            $this->addTextColumn($table, 'tokopay_merchant_id');
            $this->addTextColumn($table, 'tokopay_secret_key');
            $this->addTextColumn($table, 'username_digi');
            $this->addTextColumn($table, 'api_key_digi');
            $this->addTextColumn($table, 'apigames_secret');
            $this->addTextColumn($table, 'apigames_merchant');
            $this->addTextColumn($table, 'vip_apiid');
            $this->addTextColumn($table, 'vip_apikey');
            $this->addTextColumn($table, 'nomor_admin');
            $this->addTextColumn($table, 'wa_key');
            $this->addTextColumn($table, 'wa_number');
            $this->addTextColumn($table, 'ovo_admin');
            $this->addTextColumn($table, 'ovo1_admin');
            $this->addTextColumn($table, 'gopay_admin');
            $this->addTextColumn($table, 'gopay1_admin');
            $this->addTextColumn($table, 'dana_admin');
            $this->addTextColumn($table, 'shopeepay_admin');
            $this->addTextColumn($table, 'bca_admin');
            $this->addTextColumn($table, 'order_prefik');
            $this->addIntegerColumn($table, 'commission_percent', 20);
            $this->addUnsignedIntegerColumn($table, 'point_per_nominal', 1);
            $this->addUnsignedIntegerColumn($table, 'point_value', 100);
            $this->addUnsignedIntegerColumn($table, 'max_point_usage_percent', 50);
            $this->addIntegerColumn($table, 'profit_member');
            $this->addIntegerColumn($table, 'profit_platinum');
            $this->addIntegerColumn($table, 'profit_gold');
            $this->addIntegerColumn($table, 'trx_count_gold', 50);
            $this->addIntegerColumn($table, 'trx_count_platinum', 100);
            $this->addTextColumn($table, 'google_analytics_id');
            $this->addTextColumn($table, 'facebook_pixel_id');
            $this->addTextColumn($table, 'google_tag_manager_id');

            if (! Schema::hasColumn('setting_webs', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('setting_webs', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Compatibility migration for legacy imports. Do not drop columns that may
        // already belong to production schemas.
    }

    private function addTextColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn('setting_webs', $column)) {
            $table->text($column)->nullable();
        }
    }

    private function addStringColumn(
        Blueprint $table,
        string $column,
        int $length = 255,
        ?string $default = null
    ): void {
        if (Schema::hasColumn('setting_webs', $column)) {
            return;
        }

        $definition = $table->string($column, $length)->nullable();

        if ($default !== null) {
            $definition->default($default);
        }
    }

    private function addBooleanColumn(Blueprint $table, string $column, bool $default): void
    {
        if (! Schema::hasColumn('setting_webs', $column)) {
            $table->boolean($column)->default($default);
        }
    }

    private function addIntegerColumn(Blueprint $table, string $column, ?int $default = null): void
    {
        if (Schema::hasColumn('setting_webs', $column)) {
            return;
        }

        $definition = $table->integer($column)->nullable();

        if ($default !== null) {
            $definition->default($default);
        }
    }

    private function addUnsignedIntegerColumn(Blueprint $table, string $column, int $default): void
    {
        if (! Schema::hasColumn('setting_webs', $column)) {
            $table->unsignedInteger($column)->default($default);
        }
    }
};
