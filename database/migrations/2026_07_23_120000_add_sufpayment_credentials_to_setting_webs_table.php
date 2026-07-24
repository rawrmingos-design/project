<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('setting_webs')) {
            Schema::table('setting_webs', function (Blueprint $table): void {
                if (! Schema::hasColumn('setting_webs', 'sufpayment_api_id')) {
                    $table->text('sufpayment_api_id')->nullable()->after('apigames_secret');
                }

                if (! Schema::hasColumn('setting_webs', 'sufpayment_api_key')) {
                    $table->text('sufpayment_api_key')->nullable()->after('sufpayment_api_id');
                }

                if (! Schema::hasColumn('setting_webs', 'sufpayment_secret_key')) {
                    $table->text('sufpayment_secret_key')->nullable()->after('sufpayment_api_key');
                }
            });
        }

        if (Schema::hasTable('providers')) {
            $insertData = [
                'name' => 'SUFPAYMENT',
                'api_endpoint' => 'https://sufpayment.com/api/v1',
                'balance' => 0,
                'is_active' => true,
                'last_check_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('providers', 'type')) {
                $column = DB::selectOne("SHOW COLUMNS FROM `providers` LIKE 'type'");
                $columnType = strtolower((string) ($column->Type ?? ''));
                if (preg_match("/^enum\((.+)\)$/", $columnType, $matches) === 1) {
                    preg_match_all("/'([^']+)'/", $matches[1], $enumMatches);
                    $insertData['type'] = $enumMatches[1][0] ?? 'sufpayment';
                } else {
                    $insertData['type'] = 'sufpayment';
                }
            }

            DB::table('providers')->updateOrInsert(
                ['code' => 'sufpayment'],
                $insertData
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('setting_webs')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table): void {
            foreach (['sufpayment_secret_key', 'sufpayment_api_key', 'sufpayment_api_id'] as $column) {
                if (Schema::hasColumn('setting_webs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
