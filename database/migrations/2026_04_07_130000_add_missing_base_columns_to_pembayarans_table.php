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
            $this->addStringColumn($table, 'order_id');
            $this->addStringColumn($table, 'harga');
            $this->addTextColumn($table, 'no_pembayaran');
            $this->addStringColumn($table, 'no_pembeli', 20);
            $this->addStringColumn($table, 'status');
            $this->addStringColumn($table, 'metode');
            $this->addStringColumn($table, 'reference');
            $this->addStringColumn($table, 'duitku_merchant_order_id');
            $this->addStringColumn($table, 'duitku_reference');

            if (! Schema::hasColumn('pembayarans', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }

            if (! Schema::hasColumn('pembayarans', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('pembayarans', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Compatibility migration for legacy imports. Do not drop columns that may
        // already belong to production schemas.
    }

    private function addStringColumn(Blueprint $table, string $column, int $length = 255): void
    {
        if (! Schema::hasColumn('pembayarans', $column)) {
            $table->string($column, $length)->nullable();
        }
    }

    private function addTextColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn('pembayarans', $column)) {
            $table->text($column)->nullable();
        }
    }
};
