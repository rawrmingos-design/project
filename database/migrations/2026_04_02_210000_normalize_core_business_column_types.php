<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('layanans') || ! Schema::hasTable('pembayarans')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            // Keep migration portable for sqlite-based tests.
            return;
        }

        $this->normalizeLayanansKategoriId();
        $this->normalizePembayaransHarga();
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('layanans') && Schema::hasColumn('layanans', 'kategori_id')) {
            DB::statement('ALTER TABLE `layanans` MODIFY `kategori_id` VARCHAR(255) NOT NULL');
        }

        if (Schema::hasTable('pembayarans') && Schema::hasColumn('pembayarans', 'harga')) {
            DB::statement('ALTER TABLE `pembayarans` MODIFY `harga` VARCHAR(255) NOT NULL');
        }
    }

    private function normalizeLayanansKategoriId(): void
    {
        if (! Schema::hasColumn('layanans', 'kategori_id')) {
            return;
        }

        if ($this->isColumnNumeric('layanans', 'kategori_id')) {
            return;
        }

        $invalidRows = DB::table('layanans')
            ->select('id', 'kategori_id')
            ->whereRaw("`kategori_id` IS NULL OR `kategori_id` = '' OR `kategori_id` REGEXP '[^0-9]'")
            ->limit(10)
            ->get();

        if ($invalidRows->isNotEmpty()) {
            $sample = $invalidRows
                ->map(fn ($row): string => "#{$row->id}:{$row->kategori_id}")
                ->implode(', ');

            throw new RuntimeException(
                "Cannot normalize layanans.kategori_id to BIGINT. Invalid rows found: {$sample}"
            );
        }

        DB::statement('ALTER TABLE `layanans` MODIFY `kategori_id` BIGINT UNSIGNED NOT NULL');
    }

    private function normalizePembayaransHarga(): void
    {
        if (! Schema::hasColumn('pembayarans', 'harga')) {
            return;
        }

        if ($this->isColumnNumeric('pembayarans', 'harga')) {
            return;
        }

        $invalidRows = DB::table('pembayarans')
            ->select('id', 'harga')
            ->whereRaw("`harga` IS NULL OR `harga` = '' OR `harga` REGEXP '[^0-9]'")
            ->limit(10)
            ->get();

        if ($invalidRows->isNotEmpty()) {
            $sample = $invalidRows
                ->map(fn ($row): string => "#{$row->id}:{$row->harga}")
                ->implode(', ');

            throw new RuntimeException(
                "Cannot normalize pembayarans.harga to BIGINT. Invalid rows found: {$sample}"
            );
        }

        DB::statement('ALTER TABLE `pembayarans` MODIFY `harga` BIGINT UNSIGNED NOT NULL');
    }

    private function isColumnNumeric(string $table, string $column): bool
    {
        $columnMeta = DB::selectOne(
            'SELECT DATA_TYPE AS data_type
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
             LIMIT 1',
            [$table, $column]
        );

        $dataType = strtolower((string) ($columnMeta->data_type ?? ''));

        return in_array($dataType, ['tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'decimal', 'float', 'double'], true);
    }
};

