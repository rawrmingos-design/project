<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MethodsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('methods')->truncate();

        DB::table('methods')->insert([
        [
            'id' => 68,
            'name' => 'OVO',
            'images' => 'assets/thumbnail/ovo-payment1.webp',
            'code' => 'OVO',
            'keterangan' => 'Dicek Otomatis',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'fee_percent' => '3.00',
            'fix_fee' => '0.00',
            'min_pembelian' => 1000,
            'max_pembelian' => 10000000,
            'statuspayment' => 1,
            'created_at' => '2023-09-07 07:26:22',
            'updated_at' => '2025-05-02 07:29:06'
        ],
        [
            'id' => 104,
            'name' => 'DANA',
            'images' => 'assets/thumbnail/Logo_dana_blue.svg.png',
            'code' => 'DANA',
            'keterangan' => 'Dicek Otomatis',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'fee_percent' => '3.00',
            'fix_fee' => '0.00',
            'min_pembelian' => 1000,
            'max_pembelian' => 10000000,
            'statuspayment' => 1,
            'created_at' => '2024-07-25 13:35:18',
            'updated_at' => '2025-06-12 13:33:01'
        ],
        [
            'id' => 85,
            'name' => 'BRI Virtual Account',
            'images' => 'assets/thumbnail/d839dcdf-1066-4308-a573-f8945f06639b.webp',
            'code' => 'BRIVA',
            'keterangan' => 'Dicek Otomatis',
            'tipe' => 'virtual-account',
            'payment' => 'tripay',
            'fee_percent' => '0.00',
            'fix_fee' => '4250.00',
            'min_pembelian' => 10000,
            'max_pembelian' => 10000000,
            'statuspayment' => 1,
            'created_at' => '2024-05-20 18:46:36',
            'updated_at' => '2025-04-30 13:04:05'
        ],
        [
            'id' => 122,
            'name' => 'Indomaret',
            'images' => 'assets/thumbnail/01KDWWF8CGTEY7T28WQX7DZW8R.webp',
            'code' => 'indomaret',
            'keterangan' => 'Proses Instan & Cepat',
            'tipe' => 'convenience-store',
            'payment' => 'tripay',
            'fee_percent' => '0.00',
            'fix_fee' => '0.00',
            'min_pembelian' => null,
            'max_pembelian' => null,
            'statuspayment' => 1,
            'created_at' => '2026-01-01 14:38:04',
            'updated_at' => '2026-01-01 14:38:04'
        ],
        [
            'id' => 123,
            'name' => 'Saldo',
            'images' => 'assets/thumbnail/saldo.webp',
            'code' => 'SALDO',
            'keterangan' => 'Saldo Akun',
            'tipe' => 'SALDO',
            'payment' => 'manual',
            'fee_percent' => null,
            'fix_fee' => null,
            'min_pembelian' => null,
            'max_pembelian' => null,
            'statuspayment' => 1,
            'created_at' => '2026-01-02 04:41:45',
            'updated_at' => '2026-01-04 05:34:09'
        ],
        [
            'id' => 124,
            'name' => 'Qris Shopepay',
            'images' => 'assets/thumbnail/01KH8ETNTE99B4RNC72BQTZEK9.webp',
            'code' => 'SP',
            'keterangan' => 'Pembayaran Otomatis',
            'tipe' => 'qris',
            'payment' => 'duitku',
            'fee_percent' => '0.70',
            'fix_fee' => '0.00',
            'min_pembelian' => 1000,
            'max_pembelian' => 5000000,
            'statuspayment' => 1,
            'created_at' => '2026-02-12 07:49:24',
            'updated_at' => '2026-02-12 08:18:50'
        ],
        [
            'id' => 107,
            'name' => 'ShopeePay',
            'images' => 'assets/thumbnail/c1c40b81-c303-4119-a83c-34d51b72c1e6.webp',
            'code' => 'SHOPEEPAY',
            'keterangan' => 'Dicek Otomatis',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'fee_percent' => '3.00',
            'fix_fee' => '0.00',
            'min_pembelian' => 100,
            'max_pembelian' => 10000000,
            'statuspayment' => 1,
            'created_at' => '2024-12-29 09:35:50',
            'updated_at' => '2025-04-30 13:12:37'
        ],
        [
            'id' => 121,
            'name' => 'QRIS',
            'images' => 'assets/thumbnail/qris.webp',
            'code' => 'QRIS',
            'keterangan' => 'Dicek Otomatis',
            'tipe' => 'qris',
            'payment' => 'tripay',
            'fee_percent' => '0.70',
            'fix_fee' => '100.00',
            'min_pembelian' => 1000,
            'max_pembelian' => 5000000,
            'statuspayment' => 1,
            'created_at' => '2025-04-30 13:29:35',
            'updated_at' => '2026-02-22 06:58:12'
        ],
        ]);
    }
}