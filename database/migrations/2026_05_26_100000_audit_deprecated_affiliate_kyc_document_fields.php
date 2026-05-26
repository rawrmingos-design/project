<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FIELDS = [
        'affiliate_identity_document_path',
        'affiliate_support_document_path',
        'affiliate_ktp_document_path',
        'affiliate_selfie_document_path',
        'affiliate_family_card_document_path',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $counts = [];

        foreach (self::FIELDS as $field) {
            if (! Schema::hasColumn('users', $field)) {
                $counts[$field] = 'missing';

                continue;
            }

            $counts[$field] = DB::table('users')
                ->whereNotNull($field)
                ->where($field, '<>', '')
                ->count();
        }

        Log::info('Deprecated affiliate KYC document fields audit.', [
            'counts' => $counts,
        ]);
    }

    public function down(): void
    {
        // Non-breaking audit marker only. The deprecated columns are dropped in a future batch.
    }
};
