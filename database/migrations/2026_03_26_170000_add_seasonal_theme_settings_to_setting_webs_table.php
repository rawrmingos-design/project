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
            if (! Schema::hasColumn('setting_webs', 'seasonal_enabled')) {
                $table->boolean('seasonal_enabled')->default(false)->after('captcha_bypass');
            }

            if (! Schema::hasColumn('setting_webs', 'seasonal_mode')) {
                $table->string('seasonal_mode', 20)->default('manual')->after('seasonal_enabled');
            }

            if (! Schema::hasColumn('setting_webs', 'seasonal_theme')) {
                $table->string('seasonal_theme', 30)->nullable()->after('seasonal_mode');
            }

            if (! Schema::hasColumn('setting_webs', 'seasonal_starts_at')) {
                $table->timestamp('seasonal_starts_at')->nullable()->after('seasonal_theme');
            }

            if (! Schema::hasColumn('setting_webs', 'seasonal_ends_at')) {
                $table->timestamp('seasonal_ends_at')->nullable()->after('seasonal_starts_at');
            }

            if (! Schema::hasColumn('setting_webs', 'seasonal_effect_intensity')) {
                $table->string('seasonal_effect_intensity', 20)->default('subtle')->after('seasonal_ends_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('setting_webs')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('setting_webs', 'seasonal_effect_intensity') ? 'seasonal_effect_intensity' : null,
                Schema::hasColumn('setting_webs', 'seasonal_ends_at') ? 'seasonal_ends_at' : null,
                Schema::hasColumn('setting_webs', 'seasonal_starts_at') ? 'seasonal_starts_at' : null,
                Schema::hasColumn('setting_webs', 'seasonal_theme') ? 'seasonal_theme' : null,
                Schema::hasColumn('setting_webs', 'seasonal_mode') ? 'seasonal_mode' : null,
                Schema::hasColumn('setting_webs', 'seasonal_enabled') ? 'seasonal_enabled' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
