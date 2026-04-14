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
            if (! Schema::hasColumn('setting_webs', 'gtm_custom_head_script')) {
                $table->longText('gtm_custom_head_script')->nullable()->after('google_tag_manager_id');
            }

            if (! Schema::hasColumn('setting_webs', 'gtm_custom_body_noscript')) {
                $table->longText('gtm_custom_body_noscript')->nullable()->after('gtm_custom_head_script');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('setting_webs')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table): void {
            $dropColumns = [];

            if (Schema::hasColumn('setting_webs', 'gtm_custom_head_script')) {
                $dropColumns[] = 'gtm_custom_head_script';
            }

            if (Schema::hasColumn('setting_webs', 'gtm_custom_body_noscript')) {
                $dropColumns[] = 'gtm_custom_body_noscript';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
