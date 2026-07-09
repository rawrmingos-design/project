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
            if (! Schema::hasColumn('setting_webs', 'tenant_notify_via_whatsapp')) {
                $table->boolean('tenant_notify_via_whatsapp')
                    ->default(true)
                    ->after('affiliate_notify_via_email');
            }

            if (! Schema::hasColumn('setting_webs', 'tenant_notify_via_email')) {
                $table->boolean('tenant_notify_via_email')
                    ->default(true)
                    ->after('tenant_notify_via_whatsapp');
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
                Schema::hasColumn('setting_webs', 'tenant_notify_via_whatsapp') ? 'tenant_notify_via_whatsapp' : null,
                Schema::hasColumn('setting_webs', 'tenant_notify_via_email') ? 'tenant_notify_via_email' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
