<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pembayarans')) {
            return;
        }

        Schema::table('pembayarans', function (Blueprint $table): void {
            if (! Schema::hasColumn('pembayarans', 'duitku_api_mode')) {
                $table->string('duitku_api_mode', 16)->nullable()->after('duitku_reference');
            }

            if (! Schema::hasColumn('pembayarans', 'duitku_payment_code')) {
                $table->string('duitku_payment_code', 32)->nullable()->after('duitku_api_mode');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pembayarans')) {
            return;
        }

        $columns = array_values(array_filter([
            'duitku_api_mode',
            'duitku_payment_code',
        ], static fn (string $column): bool => Schema::hasColumn('pembayarans', $column)));

        if ($columns !== []) {
            Schema::table('pembayarans', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};

