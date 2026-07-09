<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenants', 'custom_domain_status')) {
                $table->string('custom_domain_status')->nullable()->default('pending')->after('custom_domain')->index();
            }

            if (! Schema::hasColumn('tenants', 'custom_domain_verification_token')) {
                $table->string('custom_domain_verification_token')->nullable()->after('custom_domain_status');
            }

            if (! Schema::hasColumn('tenants', 'custom_domain_verified_at')) {
                $table->timestamp('custom_domain_verified_at')->nullable()->after('custom_domain_verification_token');
            }

            if (! Schema::hasColumn('tenants', 'custom_domain_last_error')) {
                $table->text('custom_domain_last_error')->nullable()->after('custom_domain_verified_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table): void {
            $columns = ['custom_domain_status', 'custom_domain_verification_token', 'custom_domain_verified_at', 'custom_domain_last_error'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
