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
            if (! Schema::hasColumn('setting_webs', 'google_analytics_id')) {
                $table->text('google_analytics_id')->nullable();
            }

            if (! Schema::hasColumn('setting_webs', 'facebook_pixel_id')) {
                $table->text('facebook_pixel_id')->nullable();
            }

            if (! Schema::hasColumn('setting_webs', 'google_tag_manager_id')) {
                $table->text('google_tag_manager_id')->nullable();
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

            if (Schema::hasColumn('setting_webs', 'google_analytics_id')) {
                $dropColumns[] = 'google_analytics_id';
            }

            if (Schema::hasColumn('setting_webs', 'facebook_pixel_id')) {
                $dropColumns[] = 'facebook_pixel_id';
            }

            if (Schema::hasColumn('setting_webs', 'google_tag_manager_id')) {
                $dropColumns[] = 'google_tag_manager_id';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
