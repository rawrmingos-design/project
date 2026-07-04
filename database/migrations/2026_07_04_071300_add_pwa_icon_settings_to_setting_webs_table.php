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
            if (! Schema::hasColumn('setting_webs', 'pwa_icon_source')) {
                $table->text('pwa_icon_source')->nullable()->after('logo_favicon');
            }

            if (! Schema::hasColumn('setting_webs', 'pwa_icon_generated_at')) {
                $table->timestamp('pwa_icon_generated_at')->nullable()->after('pwa_icon_source');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('setting_webs')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table): void {
            foreach (['pwa_icon_generated_at', 'pwa_icon_source'] as $column) {
                if (Schema::hasColumn('setting_webs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
