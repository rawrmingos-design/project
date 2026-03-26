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
            if (! Schema::hasColumn('setting_webs', 'wa_provider')) {
                $table->string('wa_provider')->default('fonnte')->after('wa_number');
            }
            if (! Schema::hasColumn('setting_webs', 'easywa_email')) {
                $table->string('easywa_email')->nullable()->after('wa_provider');
            }
            if (! Schema::hasColumn('setting_webs', 'easywa_secret_key')) {
                $table->text('easywa_secret_key')->nullable()->after('easywa_email');
            }
            if (! Schema::hasColumn('setting_webs', 'easywa_send_type')) {
                $table->string('easywa_send_type')->default('sync')->after('easywa_secret_key');
            }
            if (! Schema::hasColumn('setting_webs', 'easywa_send_delay')) {
                $table->unsignedInteger('easywa_send_delay')->default(0)->after('easywa_send_type');
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
                Schema::hasColumn('setting_webs', 'wa_provider') ? 'wa_provider' : null,
                Schema::hasColumn('setting_webs', 'easywa_email') ? 'easywa_email' : null,
                Schema::hasColumn('setting_webs', 'easywa_secret_key') ? 'easywa_secret_key' : null,
                Schema::hasColumn('setting_webs', 'easywa_send_type') ? 'easywa_send_type' : null,
                Schema::hasColumn('setting_webs', 'easywa_send_delay') ? 'easywa_send_delay' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
