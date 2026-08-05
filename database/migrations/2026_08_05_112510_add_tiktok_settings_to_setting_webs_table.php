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
            if (! Schema::hasColumn('setting_webs', 'tiktok_tracking_enabled')) {
                $table->boolean('tiktok_tracking_enabled')->nullable();
            }
            if (! Schema::hasColumn('setting_webs', 'tiktok_pixel_id')) {
                $table->string('tiktok_pixel_id')->nullable();
            }
            if (! Schema::hasColumn('setting_webs', 'tiktok_access_token_encrypted')) {
                $table->text('tiktok_access_token_encrypted')->nullable();
            }
            if (! Schema::hasColumn('setting_webs', 'tiktok_test_event_code')) {
                $table->string('tiktok_test_event_code')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('setting_webs')) {
            return;
        }

        $columns = array_values(array_filter([
            'tiktok_tracking_enabled',
            'tiktok_pixel_id',
            'tiktok_access_token_encrypted',
            'tiktok_test_event_code',
        ], static fn (string $column): bool => Schema::hasColumn('setting_webs', $column)));

        if ($columns !== []) {
            Schema::table('setting_webs', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
