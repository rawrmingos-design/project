<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pembelians')) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table): void {
            $this->addStringColumn($table, 'order_id');
            $this->addStringColumn($table, 'username');
            $this->addStringColumn($table, 'user_id');
            $this->addStringColumn($table, 'zone');
            $this->addStringColumn($table, 'nickname');
            $this->addStringColumn($table, 'layanan');
            $this->addIntegerColumn($table, 'harga', 0);
            $this->addIntegerColumn($table, 'profit', 0);
            $this->addStringColumn($table, 'provider_order_id');
            $this->addStringColumn($table, 'status');
            $this->addStringColumn($table, 'log', 1000);
            $this->addStringColumn($table, 'traffic_source');
            $this->addStringColumn($table, 'voucher');
            $this->addTextColumn($table, 'keterangan_sn');
            $this->addStringColumn($table, 'tipe_transaksi', default: 'game');
            $this->addStringColumn($table, 'email_pembeli');
            $this->addStringColumn($table, 'ip_address', 2225);
            $this->addUnsignedBigIntegerColumn($table, 'used_points', 0);
            $this->addUnsignedBigIntegerColumn($table, 'used_point_amount', 0);

            if (! Schema::hasColumn('pembelians', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('pembelians', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Compatibility migration for legacy imports. Do not drop columns that may
        // already belong to production schemas.
    }

    private function addStringColumn(
        Blueprint $table,
        string $column,
        int $length = 255,
        ?string $default = null
    ): void {
        if (Schema::hasColumn('pembelians', $column)) {
            return;
        }

        $definition = $table->string($column, $length)->nullable();

        if ($default !== null) {
            $definition->default($default);
        }
    }

    private function addTextColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn('pembelians', $column)) {
            $table->text($column)->nullable();
        }
    }

    private function addIntegerColumn(Blueprint $table, string $column, ?int $default = null): void
    {
        if (Schema::hasColumn('pembelians', $column)) {
            return;
        }

        $definition = $table->integer($column)->nullable();

        if ($default !== null) {
            $definition->default($default);
        }
    }

    private function addUnsignedBigIntegerColumn(Blueprint $table, string $column, int $default): void
    {
        if (! Schema::hasColumn('pembelians', $column)) {
            $table->unsignedBigInteger($column)->default($default);
        }
    }
};
