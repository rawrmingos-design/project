<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'affiliate_ktp_document_path')) {
                $table->string('affiliate_ktp_document_path')->nullable()->after('affiliate_identity_document_path');
            }

            if (! Schema::hasColumn('users', 'affiliate_selfie_document_path')) {
                $table->string('affiliate_selfie_document_path')->nullable()->after('affiliate_ktp_document_path');
            }

            if (! Schema::hasColumn('users', 'affiliate_family_card_document_path')) {
                $table->string('affiliate_family_card_document_path')->nullable()->after('affiliate_selfie_document_path');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $columns = [
                'affiliate_ktp_document_path',
                'affiliate_selfie_document_path',
                'affiliate_family_card_document_path',
            ];

            $dropColumns = array_values(array_filter($columns, static fn (string $column): bool => Schema::hasColumn('users', $column)));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};

