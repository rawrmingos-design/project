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
            if (! Schema::hasColumn('setting_webs', 'seasonal_background_image')) {
                $table->text('seasonal_background_image')->nullable()->after('seasonal_effect_intensity');
            }

            if (! Schema::hasColumn('setting_webs', 'seasonal_background_opacity')) {
                $table->unsignedTinyInteger('seasonal_background_opacity')->default(38)->after('seasonal_background_image');
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
                Schema::hasColumn('setting_webs', 'seasonal_background_opacity') ? 'seasonal_background_opacity' : null,
                Schema::hasColumn('setting_webs', 'seasonal_background_image') ? 'seasonal_background_image' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
