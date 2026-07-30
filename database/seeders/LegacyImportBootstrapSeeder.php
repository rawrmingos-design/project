<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LegacyImportBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $this->bootstrapProviders($now);
        $this->bootstrapCategoryTypes($now);
        $this->bootstrapEmailTemplates($now);
        $this->bootstrapWhatsappTemplates($now);
    }

    private function bootstrapProviders(Carbon $now): void
    {
        if (! DB::getSchemaBuilder()->hasTable('providers')) {
            return;
        }

        $typeDefault = $this->resolveProvidersTypeDefault();

        $rows = [
            [
                'code' => 'digiflazz',
                'name' => 'DIGIFLAZZ',
                'api_endpoint' => 'https://api.digiflazz.com',
                'balance' => 0,
                'is_active' => true,
                'last_check_at' => null,
            ],
            [
                'code' => 'bangjeff',
                'name' => 'BANGJEFF',
                'api_endpoint' => 'https://distribution-api.bangjeff.com/api/v4',
                'balance' => 0,
                'is_active' => true,
                'last_check_at' => null,
            ],
            [
                'code' => 'vip',
                'name' => 'VIP RESELLER',
                'api_endpoint' => 'https://vip-reseller.co.id/api',
                'balance' => 0,
                'is_active' => true,
                'last_check_at' => null,
            ],
            [
                'code' => 'apigames',
                'name' => 'APIGAMES',
                'api_endpoint' => 'https://v1.apigames.id',
                'balance' => 0,
                'is_active' => true,
                'last_check_at' => null,
            ],
            [
                'code' => 'sufpayment',
                'name' => 'SUFPAYMENT',
                'api_endpoint' => 'https://sufpayment.com/api/v1',
                'balance' => 0,
                'is_active' => true,
                'last_check_at' => null,
            ],
            [
                'code' => 'manual',
                'name' => 'MANUAL',
                'api_endpoint' => null,
                'balance' => 0,
                'is_active' => true,
                'last_check_at' => null,
            ],
        ];

        foreach ($rows as $row) {
            if ($typeDefault !== null) {
                $row['type'] = $typeDefault === '__use_code__'
                    ? $row['code']
                    : $typeDefault;
            }

            DB::table('providers')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, [
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }
    }

    private function resolveProvidersTypeDefault(): ?string
    {
        if (! DB::getSchemaBuilder()->hasColumn('providers', 'type')) {
            return null;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `providers` LIKE 'type'");
        $columnType = strtolower((string) ($column->Type ?? ''));

        if (preg_match("/^enum\\((.+)\\)$/", $columnType, $matches) === 1) {
            preg_match_all("/'([^']+)'/", $matches[1], $enumMatches);

            return $enumMatches[1][0] ?? null;
        }

        return '__use_code__';
    }

    private function bootstrapCategoryTypes(Carbon $now): void
    {
        if (! DB::getSchemaBuilder()->hasTable('category_types')) {
            return;
        }

        $rows = [
            ['slug' => 'top-up-games', 'name' => 'Top Up Games', 'sort' => 1, 'icon' => null],
            ['slug' => 'specialist-mobile-legends', 'name' => 'Specialist Mobile Legends', 'sort' => 2, 'icon' => null],
            ['slug' => 'app-premium', 'name' => 'App Premium', 'sort' => 3, 'icon' => null],
            ['slug' => 'pulsa-data', 'name' => 'Pulsa & Data', 'sort' => 4, 'icon' => null],
            ['slug' => 'voucher', 'name' => 'Voucher', 'sort' => 5, 'icon' => null],
        ];

        foreach ($rows as $row) {
            DB::table('category_types')->updateOrInsert(
                ['slug' => $row['slug']],
                array_merge($row, [
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }

        $this->mapLegacyKategorisToCategoryTypes();
    }

    private function mapLegacyKategorisToCategoryTypes(): void
    {
        if (
            ! DB::getSchemaBuilder()->hasTable('kategoris')
            || ! DB::getSchemaBuilder()->hasColumn('kategoris', 'category_type_id')
        ) {
            return;
        }

        $categoryTypes = DB::table('category_types')
            ->whereIn('slug', [
                'top-up-games',
                'specialist-mobile-legends',
                'app-premium',
                'pulsa-data',
                'voucher',
            ])
            ->pluck('id', 'slug');

        $mapping = [
            'top-up-games' => ['game', 'populer'],
            'specialist-mobile-legends' => ['giftskin', 'joki', 'jokigendong', 'vilogml'],
            'app-premium' => ['app', 'apps', 'premium', 'app-premium'],
            'pulsa-data' => ['pulsa', 'data', 'ppob'],
            'voucher' => ['voucher'],
        ];

        foreach ($mapping as $slug => $legacyTypes) {
            $categoryTypeId = $categoryTypes[$slug] ?? null;

            if (! $categoryTypeId) {
                continue;
            }

            DB::table('kategoris')
                ->whereIn(DB::raw('LOWER(tipe)'), $legacyTypes)
                ->whereNull('category_type_id')
                ->update(['category_type_id' => $categoryTypeId]);
        }
    }

    private function bootstrapEmailTemplates(Carbon $now): void
    {
        if (! DB::getSchemaBuilder()->hasTable('email_templates')) {
            return;
        }

        $rows = [
            [
                'slug' => 'transaction_pending',
                'name' => 'Transaksi Pending',
                'subject' => 'Menunggu Pembayaran #{order_id}',
                'details' => '_Variables: {order_id}, {nickname}, {product}, {amount}, {status}_',
                'content' => '<p>Halo <strong>{nickname}</strong>,</p><p>Terima kasih telah melakukan pemesanan.</p><p>Berikut adalah detail pesanan Anda:</p><ul><li>No Invoice: <strong>{order_id}</strong></li><li>Produk: {product}</li><li>Nominal: {amount}</li><li>Status: <strong>{status}</strong></li></ul><p>Silakan selesaikan pembayaran agar pesanan dapat diproses otomatis.</p><p>Terima kasih.</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'transaction_success',
                'name' => 'Transaksi Sukses',
                'subject' => 'Pesanan Berhasil #{order_id}',
                'details' => '_Variables: {order_id}, {nickname}, {product}, {amount}, {sn}, {note}_',
                'content' => '<p>Halo <strong>{nickname}</strong>,</p><p>Pesanan Anda telah berhasil diproses.</p><p>Detail Pesanan:</p><ul><li>No Invoice: <strong>{order_id}</strong></li><li>Produk: {product}</li><li>SN/Kode: <strong>{sn}</strong></li><li>Status: <strong>Success</strong></li></ul><p>{note}</p><p>Terima kasih telah berbelanja.</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'transaction_failed',
                'name' => 'Transaksi Gagal',
                'subject' => 'Pesanan Gagal #{order_id}',
                'details' => '_Variables: {order_id}, {nickname}, {product}, {amount}, {note}_',
                'content' => '<p>Halo <strong>{nickname}</strong>,</p><p>Mohon maaf, pesanan Anda gagal atau dibatalkan.</p><p>Detail Pesanan:</p><ul><li>No Invoice: <strong>{order_id}</strong></li><li>Produk: {product}</li><li>Status: <strong>Failed</strong></li></ul><p>Alasan: {note}</p><p>Silakan hubungi admin jika ada kendala.</p>',
                'is_active' => true,
            ],
        ];

        foreach ($rows as $row) {
            DB::table('email_templates')->updateOrInsert(
                ['slug' => $row['slug']],
                array_merge($row, [
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }
    }

    private function bootstrapWhatsappTemplates(Carbon $now): void
    {
        if (! DB::getSchemaBuilder()->hasTable('whatsapp_templates')) {
            return;
        }

        $rows = [
            [
                'slug' => 'transaction_pending',
                'name' => 'Transaksi Pending',
                'details' => '_Variables: {order_id}, {nickname}, {product}, {amount}, {status}_',
                'content' => "*Konfirmasi Pesanan*\n\nTerima kasih telah melakukan pemesanan.\n\nNo Invoice: *{order_id}*\nProduk: *{product}*\nNominal: *{amount}*\nStatus: *{status}*\n\nSilakan selesaikan pembayaran agar pesanan dapat diproses otomatis.",
                'is_active' => true,
            ],
            [
                'slug' => 'transaction_success',
                'name' => 'Transaksi Sukses',
                'details' => '_Variables: {order_id}, {product}_',
                'content' => "✅ *PEMBAYARAN BERHASIL DIVERIFIKASI!*\n\nTerima kasih telah berbelanja di Z-Vault Store.\n\n🧾 *RINCIAN TRANSAKSI*\n├ Nomor Invoice: *{order_id}*\n└ Produk: *{product}*\n\n🔐 Jika ada kendala hubungi admin utama:\nchat admin @mings dan kirimkan id pesanan nya",
                'is_active' => true,
            ],
            [
                'slug' => 'transaction_failed',
                'name' => 'Transaksi Gagal / Batal',
                'details' => '_Variables: {order_id}, {nickname}, {product}, {reason}_',
                'content' => "*Pesanan Dibatalkan*\n\nMohon maaf, pesanan dengan No Invoice: *{order_id}* telah dibatalkan.\n\nAlasan: {reason}\n\nSilakan hubungi admin jika ada kendala.",
                'is_active' => true,
            ],
        ];

        foreach ($rows as $row) {
            DB::table('whatsapp_templates')->updateOrInsert(
                ['slug' => $row['slug']],
                array_merge($row, [
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }
    }
}
