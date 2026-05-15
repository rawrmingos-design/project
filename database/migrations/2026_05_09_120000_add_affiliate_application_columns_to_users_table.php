<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'affiliate_requested_at')) {
                $table->timestamp('affiliate_requested_at')->nullable()->after('affiliate_status');
            }

            if (!Schema::hasColumn('users', 'affiliate_requirement_acknowledged_at')) {
                $table->timestamp('affiliate_requirement_acknowledged_at')->nullable()->after('affiliate_requested_at');
            }

            if (!Schema::hasColumn('users', 'affiliate_identity_document_path')) {
                $table->string('affiliate_identity_document_path')->nullable()->after('affiliate_requirement_acknowledged_at');
            }

            if (!Schema::hasColumn('users', 'affiliate_support_document_path')) {
                $table->string('affiliate_support_document_path')->nullable()->after('affiliate_identity_document_path');
            }

            if (!Schema::hasColumn('users', 'affiliate_application_note')) {
                $table->text('affiliate_application_note')->nullable()->after('affiliate_support_document_path');
            }

            if (!Schema::hasColumn('users', 'affiliate_application_meta')) {
                $table->json('affiliate_application_meta')->nullable()->after('affiliate_application_note');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $columns = [
                'affiliate_requested_at',
                'affiliate_requirement_acknowledged_at',
                'affiliate_identity_document_path',
                'affiliate_support_document_path',
                'affiliate_application_note',
                'affiliate_application_meta',
            ];

            $dropColumns = array_values(array_filter($columns, static fn (string $column): bool => Schema::hasColumn('users', $column)));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};

