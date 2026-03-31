<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('setting_webs')) {
            return;
        }

        if (Schema::hasColumn('setting_webs', 'home_popup_enabled')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table): void {
            $table->boolean('home_popup_enabled')->default(true)->after('invoice_notify_via_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('setting_webs')) {
            return;
        }

        if (! Schema::hasColumn('setting_webs', 'home_popup_enabled')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table): void {
            $table->dropColumn('home_popup_enabled');
        });
    }
};

