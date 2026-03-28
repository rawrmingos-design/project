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
            if (! Schema::hasColumn('setting_webs', 'seo_sitemap_mode')) {
                $table->string('seo_sitemap_mode')->default('dynamic')->after('seo_sitemap_cache_minutes');
            }
            if (! Schema::hasColumn('setting_webs', 'seo_sitemap_index_asset_id')) {
                $table->unsignedBigInteger('seo_sitemap_index_asset_id')->nullable()->after('seo_sitemap_mode');
            }
            if (! Schema::hasColumn('setting_webs', 'seo_sitemap_main_asset_id')) {
                $table->unsignedBigInteger('seo_sitemap_main_asset_id')->nullable()->after('seo_sitemap_index_asset_id');
            }
            if (! Schema::hasColumn('setting_webs', 'seo_sitemap_categories_asset_id')) {
                $table->unsignedBigInteger('seo_sitemap_categories_asset_id')->nullable()->after('seo_sitemap_main_asset_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('setting_webs')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table): void {
            $dropColumns = array_values(array_filter([
                Schema::hasColumn('setting_webs', 'seo_sitemap_mode') ? 'seo_sitemap_mode' : null,
                Schema::hasColumn('setting_webs', 'seo_sitemap_index_asset_id') ? 'seo_sitemap_index_asset_id' : null,
                Schema::hasColumn('setting_webs', 'seo_sitemap_main_asset_id') ? 'seo_sitemap_main_asset_id' : null,
                Schema::hasColumn('setting_webs', 'seo_sitemap_categories_asset_id') ? 'seo_sitemap_categories_asset_id' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};

