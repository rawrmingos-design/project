<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Voucher bisa dimiliki tenant, dan bisa diikat ke satu produk.
 *
 * Sebelumnya `vouchers` hanya global milik platform: `Voucher::where('kode',...)`
 * di lima tempat mencari tanpa scope, sehingga kode buatan satu pihak berlaku di
 * toko semua tenant.
 *
 * Kolom baru:
 * - `tenant_id`     NULL = voucher platform (berlaku lintas toko). Terisi = milik
 *                   tenant itu saja. Ini yang menutup kebocoran isolasi.
 * - `layanan_id`    NULL = berlaku untuk semua produk tenant. Terisi = hanya
 *                   produk itu. Perlu karena `layanans` tidak punya `tenant_id`
 *                   (satu katalog dipakai semua tenant) dan margin tiap produk
 *                   berbeda, jadi voucher tanpa batas produk bisa menembus modal
 *                   di produk bermargin tipis.
 * - `max_uses_per_user`  NULL = tanpa batas per pembeli.
 * - `used_count`    penghitung pemakaian nyata; sebelumnya tidak ada yang
 *                   menghitung, jadi "stock" adalah satu-satunya kendali.
 *
 * Keunikan kode: unique `(tenant_id, kode)`.
 *
 * BATASAN YANG DISADARI: MySQL tidak punya `NULLS NOT DISTINCT` (itu fitur
 * PostgreSQL), jadi indeks ini TIDAK menahan dua voucher PLATFORM berkode sama
 * — NULL != NULL di SQL, sehingga dua baris `tenant_id NULL, kode 'X'` sama-sama
 * lolos. Keunikan voucher platform karena itu dijaga di level aplikasi
 * (`VoucherController`, `VoucherForm`, `Tenant\VoucherController`), dan
 * functional index `COALESCE(tenant_id, 0)` sengaja tidak dipakai supaya tidak
 * bergantung pada versi MySQL. Untuk voucher milik tenant, indeks ini berlaku
 * penuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vouchers')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table): void {
            if (! Schema::hasColumn('vouchers', 'tenant_id')) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tenants')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('vouchers', 'layanan_id')) {
                $table->unsignedBigInteger('layanan_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->index();
            }

            if (! Schema::hasColumn('vouchers', 'max_uses_per_user')) {
                $table->unsignedInteger('max_uses_per_user')->nullable()->after('max_potongan');
            }

            if (! Schema::hasColumn('vouchers', 'used_count')) {
                $table->unsignedInteger('used_count')->default(0)->after('max_uses_per_user');
            }
        });

        $this->addUniqueCodeIndex();
    }

    /**
     * Unique `(tenant_id, kode)` yang memperlakukan NULL sebagai nilai sama.
     *
     * Diperiksa dulu apakah data lama sudah punya duplikat: kalau ada, indeks
     * tidak boleh dibuat diam-diam gagal — lebih baik migrasi berhenti dan
     * seseorang memutuskan kode mana yang dipertahankan.
     */
    private function addUniqueCodeIndex(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite tidak punya NULLS NOT DISTINCT; unique index biasa membuat
            // dua baris dengan tenant_id NULL dianggap berbeda. Duplikat voucher
            // platform dijaga di level aplikasi untuk driver ini.
            Schema::table('vouchers', function (Blueprint $table): void {
                $table->unique(['tenant_id', 'kode'], 'vouchers_tenant_kode_unique');
            });

            return;
        }

        $duplikat = DB::table('vouchers')
            ->select('tenant_id', 'kode')
            ->groupBy('tenant_id', 'kode')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplikat->isNotEmpty()) {
            throw new RuntimeException(
                'Migrasi dihentikan: ada ' . $duplikat->count() . ' pasang (tenant_id, kode) duplikat di vouchers. '
                . 'Rapikan dulu, jangan biarkan indeks unik dibuat diam-diam gagal.'
            );
        }

        Schema::table('vouchers', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'kode'], 'vouchers_tenant_kode_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vouchers')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table): void {
            try {
                $table->dropUnique('vouchers_tenant_kode_unique');
            } catch (Throwable) {
                // indeks tidak ada — tidak apa-apa
            }

            if (Schema::hasColumn('vouchers', 'layanan_id')) {
                $table->dropIndex(['layanan_id']);
            }

            foreach (['used_count', 'max_uses_per_user', 'layanan_id', 'tenant_id'] as $kolom) {
                if (Schema::hasColumn('vouchers', $kolom)) {
                    if ($kolom === 'tenant_id') {
                        $table->dropForeign(['tenant_id']);
                    }

                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
