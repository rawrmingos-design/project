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
            if (! Schema::hasColumn('setting_webs', 'mail_mailer')) {
                $table->string('mail_mailer')->nullable()->after('wa_number');
            }
            if (! Schema::hasColumn('setting_webs', 'mail_host')) {
                $table->string('mail_host')->nullable()->after('mail_mailer');
            }
            if (! Schema::hasColumn('setting_webs', 'mail_port')) {
                $table->unsignedInteger('mail_port')->nullable()->after('mail_host');
            }
            if (! Schema::hasColumn('setting_webs', 'mail_username')) {
                $table->string('mail_username')->nullable()->after('mail_port');
            }
            if (! Schema::hasColumn('setting_webs', 'mail_password')) {
                $table->text('mail_password')->nullable()->after('mail_username');
            }
            if (! Schema::hasColumn('setting_webs', 'mail_encryption')) {
                $table->string('mail_encryption')->nullable()->after('mail_password');
            }
            if (! Schema::hasColumn('setting_webs', 'mail_from_address')) {
                $table->string('mail_from_address')->nullable()->after('mail_encryption');
            }
            if (! Schema::hasColumn('setting_webs', 'mail_from_name')) {
                $table->string('mail_from_name')->nullable()->after('mail_from_address');
            }
            if (! Schema::hasColumn('setting_webs', 'invoice_notify_via_whatsapp')) {
                $table->boolean('invoice_notify_via_whatsapp')->default(true)->after('mail_from_name');
            }
            if (! Schema::hasColumn('setting_webs', 'invoice_notify_via_email')) {
                $table->boolean('invoice_notify_via_email')->default(true)->after('invoice_notify_via_whatsapp');
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
                Schema::hasColumn('setting_webs', 'mail_mailer') ? 'mail_mailer' : null,
                Schema::hasColumn('setting_webs', 'mail_host') ? 'mail_host' : null,
                Schema::hasColumn('setting_webs', 'mail_port') ? 'mail_port' : null,
                Schema::hasColumn('setting_webs', 'mail_username') ? 'mail_username' : null,
                Schema::hasColumn('setting_webs', 'mail_password') ? 'mail_password' : null,
                Schema::hasColumn('setting_webs', 'mail_encryption') ? 'mail_encryption' : null,
                Schema::hasColumn('setting_webs', 'mail_from_address') ? 'mail_from_address' : null,
                Schema::hasColumn('setting_webs', 'mail_from_name') ? 'mail_from_name' : null,
                Schema::hasColumn('setting_webs', 'invoice_notify_via_whatsapp') ? 'invoice_notify_via_whatsapp' : null,
                Schema::hasColumn('setting_webs', 'invoice_notify_via_email') ? 'invoice_notify_via_email' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
