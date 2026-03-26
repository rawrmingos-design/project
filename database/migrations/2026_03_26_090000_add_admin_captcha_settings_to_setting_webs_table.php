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
            if (! Schema::hasColumn('setting_webs', 'captcha_site_key')) {
                $table->text('captcha_site_key')->nullable()->after('google_tag_manager_id');
            }

            if (! Schema::hasColumn('setting_webs', 'captcha_secret')) {
                $table->text('captcha_secret')->nullable()->after('captcha_site_key');
            }

            if (! Schema::hasColumn('setting_webs', 'captcha_enabled')) {
                $table->boolean('captcha_enabled')->default(true)->after('captcha_secret');
            }

            if (! Schema::hasColumn('setting_webs', 'captcha_bypass')) {
                $table->boolean('captcha_bypass')->default(false)->after('captcha_enabled');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('setting_webs')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table): void {
            $columns = array_filter([
                Schema::hasColumn('setting_webs', 'captcha_bypass') ? 'captcha_bypass' : null,
                Schema::hasColumn('setting_webs', 'captcha_enabled') ? 'captcha_enabled' : null,
                Schema::hasColumn('setting_webs', 'captcha_secret') ? 'captcha_secret' : null,
                Schema::hasColumn('setting_webs', 'captcha_site_key') ? 'captcha_site_key' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

