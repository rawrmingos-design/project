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
                $table->text('captcha_site_key')->nullable();
            }

            if (! Schema::hasColumn('setting_webs', 'captcha_secret')) {
                $table->text('captcha_secret')->nullable();
            }

            if (! Schema::hasColumn('setting_webs', 'captcha_enabled')) {
                $table->boolean('captcha_enabled')->default(true);
            }

            if (! Schema::hasColumn('setting_webs', 'captcha_bypass')) {
                $table->boolean('captcha_bypass')->default(false);
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
