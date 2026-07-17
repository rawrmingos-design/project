<?php

use App\Models\PaymentDisplayCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_display_categories')) {
            return;
        }

        if (! Schema::hasColumn('payment_display_categories', 'code')) {
            Schema::table('payment_display_categories', function (Blueprint $table): void {
                $table->string('code', 100)->nullable()->after('label');
            });
        }

        $this->backfillCodes();

        Schema::table('payment_display_categories', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'code'], 'payment_display_categories_tenant_code_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_display_categories') || ! Schema::hasColumn('payment_display_categories', 'code')) {
            return;
        }

        Schema::table('payment_display_categories', function (Blueprint $table): void {
            $table->dropUnique('payment_display_categories_tenant_code_unique');
            $table->dropColumn('code');
        });
    }

    private function backfillCodes(): void
    {
        $seenByTenant = [];

        DB::table('payment_display_categories')
            ->select(['id', 'tenant_id', 'label', 'code'])
            ->orderBy('tenant_id')
            ->orderBy('id')
            ->get()
            ->each(function (object $category) use (&$seenByTenant): void {
                $tenantKey = $category->tenant_id === null ? 'global' : (string) $category->tenant_id;
                $baseCode = PaymentDisplayCategory::normalizeCode($category->code, $category->label);
                $code = $baseCode;
                $suffix = 2;

                while (isset($seenByTenant[$tenantKey][$code])) {
                    $code = substr($baseCode, 0, 95) . '-' . $suffix;
                    $suffix++;
                }

                $seenByTenant[$tenantKey][$code] = true;

                if ($category->code !== $code) {
                    DB::table('payment_display_categories')
                        ->where('id', $category->id)
                        ->update(['code' => $code]);
                }
            });
    }
};
