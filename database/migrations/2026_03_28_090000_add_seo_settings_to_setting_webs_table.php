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
            if (! Schema::hasColumn('setting_webs', 'seo_robots_enabled')) {
                $table->boolean('seo_robots_enabled')->default(true)->after('seasonal_background_opacity');
            }
            if (! Schema::hasColumn('setting_webs', 'seo_robots_custom_lines')) {
                $table->text('seo_robots_custom_lines')->nullable()->after('seo_robots_enabled');
            }
            if (! Schema::hasColumn('setting_webs', 'seo_sitemap_enabled')) {
                $table->boolean('seo_sitemap_enabled')->default(true)->after('seo_robots_custom_lines');
            }
            if (! Schema::hasColumn('setting_webs', 'seo_sitemap_include_categories')) {
                $table->boolean('seo_sitemap_include_categories')->default(true)->after('seo_sitemap_enabled');
            }
            if (! Schema::hasColumn('setting_webs', 'seo_sitemap_include_articles')) {
                $table->boolean('seo_sitemap_include_articles')->default(true)->after('seo_sitemap_include_categories');
            }
            if (! Schema::hasColumn('setting_webs', 'seo_sitemap_cache_minutes')) {
                $table->unsignedInteger('seo_sitemap_cache_minutes')->default(30)->after('seo_sitemap_include_articles');
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
                Schema::hasColumn('setting_webs', 'seo_robots_enabled') ? 'seo_robots_enabled' : null,
                Schema::hasColumn('setting_webs', 'seo_robots_custom_lines') ? 'seo_robots_custom_lines' : null,
                Schema::hasColumn('setting_webs', 'seo_sitemap_enabled') ? 'seo_sitemap_enabled' : null,
                Schema::hasColumn('setting_webs', 'seo_sitemap_include_categories') ? 'seo_sitemap_include_categories' : null,
                Schema::hasColumn('setting_webs', 'seo_sitemap_include_articles') ? 'seo_sitemap_include_articles' : null,
                Schema::hasColumn('setting_webs', 'seo_sitemap_cache_minutes') ? 'seo_sitemap_cache_minutes' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};

