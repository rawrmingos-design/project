<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('verified_game_accounts')) {
            DB::table('verified_game_accounts')
                ->where(function ($query): void {
                    $query->whereRaw('LOWER(source) = ?', ['digiflazz'])
                        ->orWhereRaw("LOWER(source) LIKE 'digiflazz:%'");
                })
                ->delete();
        }

        if (! Schema::hasTable('layanans')) {
            return;
        }

        $columns = collect([
            'check_id_provider_sku',
            'check_id_provider',
            'check_id_enabled',
        ])->filter(fn (string $column): bool => Schema::hasColumn('layanans', $column));

        if ($columns->isNotEmpty()) {
            Schema::table('layanans', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns->all());
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('layanans')) {
            return;
        }

        Schema::table('layanans', function (Blueprint $table): void {
            if (! Schema::hasColumn('layanans', 'check_id_enabled')) {
                $table->boolean('check_id_enabled')->default(false)->after('product_logo');
            }

            if (! Schema::hasColumn('layanans', 'check_id_provider')) {
                $table->string('check_id_provider')->nullable()->after('check_id_enabled');
            }

            if (! Schema::hasColumn('layanans', 'check_id_provider_sku')) {
                $table->string('check_id_provider_sku')->nullable()->after('check_id_provider');
            }
        });
    }
};
