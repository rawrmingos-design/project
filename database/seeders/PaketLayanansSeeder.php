<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaketLayanansSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('paket_layanans')->truncate();

        DB::table('paket_layanans')->insert([
        [
            'id' => 1,
            'paket_id' => 2,
            'layanan_id' => 158,
            'product_logo' => '/assets/product_logo/EsIMtDqXyTGn9tO.webp',
            'created_at' => '2025-04-22 00:58:24',
            'updated_at' => '2025-04-22 00:58:24'
        ],
        [
            'id' => 2,
            'paket_id' => 2,
            'layanan_id' => 160,
            'product_logo' => '/assets/product_logo/EsIMtDqXyTGn9tO.webp',
            'created_at' => '2025-04-22 00:58:24',
            'updated_at' => '2025-04-22 00:58:24'
        ],
        [
            'id' => 3,
            'paket_id' => 2,
            'layanan_id' => 161,
            'product_logo' => '/assets/product_logo/EsIMtDqXyTGn9tO.webp',
            'created_at' => '2025-04-22 00:58:24',
            'updated_at' => '2025-04-22 00:58:24'
        ],
        [
            'id' => 216,
            'paket_id' => 3,
            'layanan_id' => 12,
            'product_logo' => '/assets/product_logo/0M1k7PUwgUTttn9.webp',
            'created_at' => '2025-05-05 12:50:54',
            'updated_at' => '2025-05-05 12:50:54'
        ],
        [
            'id' => 233,
            'paket_id' => 2,
            'layanan_id' => 370,
            'product_logo' => '/assets/product_logo/B7luLO8qLDIP2Eq.png',
            'created_at' => '2025-05-06 16:59:40',
            'updated_at' => '2025-05-06 16:59:40'
        ],
        [
            'id' => 6,
            'paket_id' => 3,
            'layanan_id' => 62,
            'product_logo' => '/assets/product_logo/j7dKVsA5RnNPWPJ.webp',
            'created_at' => '2025-04-22 01:00:15',
            'updated_at' => '2025-04-22 01:00:15'
        ],
        [
            'id' => 7,
            'paket_id' => 3,
            'layanan_id' => 21,
            'product_logo' => '/assets/product_logo/j7dKVsA5RnNPWPJ.webp',
            'created_at' => '2025-04-22 01:00:15',
            'updated_at' => '2025-04-22 01:00:15'
        ],
        [
            'id' => 8,
            'paket_id' => 3,
            'layanan_id' => 35,
            'product_logo' => '/assets/product_logo/j7dKVsA5RnNPWPJ.webp',
            'created_at' => '2025-04-22 01:00:15',
            'updated_at' => '2025-04-22 01:00:15'
        ],
        [
            'id' => 9,
            'paket_id' => 3,
            'layanan_id' => 48,
            'product_logo' => '/assets/product_logo/j7dKVsA5RnNPWPJ.webp',
            'created_at' => '2025-04-22 01:00:15',
            'updated_at' => '2025-04-22 01:00:15'
        ],
        [
            'id' => 10,
            'paket_id' => 3,
            'layanan_id' => 60,
            'product_logo' => '/assets/product_logo/j7dKVsA5RnNPWPJ.webp',
            'created_at' => '2025-04-22 01:00:15',
            'updated_at' => '2025-04-22 01:00:15'
        ],
        [
            'id' => 11,
            'paket_id' => 3,
            'layanan_id' => 67,
            'product_logo' => '/assets/product_logo/j7dKVsA5RnNPWPJ.webp',
            'created_at' => '2025-04-22 01:00:15',
            'updated_at' => '2025-04-22 01:00:15'
        ],
        [
            'id' => 12,
            'paket_id' => 3,
            'layanan_id' => 73,
            'product_logo' => '/assets/product_logo/j7dKVsA5RnNPWPJ.webp',
            'created_at' => '2025-04-22 01:00:15',
            'updated_at' => '2025-04-22 01:00:15'
        ],
        [
            'id' => 13,
            'paket_id' => 3,
            'layanan_id' => 79,
            'product_logo' => '/assets/product_logo/j7dKVsA5RnNPWPJ.webp',
            'created_at' => '2025-04-22 01:00:15',
            'updated_at' => '2025-04-22 01:00:15'
        ],
        [
            'id' => 14,
            'paket_id' => 3,
            'layanan_id' => 88,
            'product_logo' => '/assets/product_logo/j7dKVsA5RnNPWPJ.webp',
            'created_at' => '2025-04-22 01:00:15',
            'updated_at' => '2025-04-22 01:00:15'
        ],
        [
            'id' => 15,
            'paket_id' => 3,
            'layanan_id' => 93,
            'product_logo' => '/assets/product_logo/j7dKVsA5RnNPWPJ.webp',
            'created_at' => '2025-04-22 01:00:15',
            'updated_at' => '2025-04-22 01:00:15'
        ],
        [
            'id' => 16,
            'paket_id' => 3,
            'layanan_id' => 11,
            'product_logo' => '/assets/product_logo/3y3SEjRuYmFYVMK.webp',
            'created_at' => '2025-04-22 01:01:57',
            'updated_at' => '2025-04-22 01:01:57'
        ],
        [
            'id' => 17,
            'paket_id' => 3,
            'layanan_id' => 27,
            'product_logo' => '/assets/product_logo/3y3SEjRuYmFYVMK.webp',
            'created_at' => '2025-04-22 01:01:57',
            'updated_at' => '2025-04-22 01:01:57'
        ],
        [
            'id' => 18,
            'paket_id' => 3,
            'layanan_id' => 41,
            'product_logo' => '/assets/product_logo/3y3SEjRuYmFYVMK.webp',
            'created_at' => '2025-04-22 01:01:57',
            'updated_at' => '2025-04-22 01:01:57'
        ],
        [
            'id' => 19,
            'paket_id' => 3,
            'layanan_id' => 43,
            'product_logo' => '/assets/product_logo/3y3SEjRuYmFYVMK.webp',
            'created_at' => '2025-04-22 01:01:57',
            'updated_at' => '2025-04-22 01:01:57'
        ],
        [
            'id' => 20,
            'paket_id' => 3,
            'layanan_id' => 54,
            'product_logo' => '/assets/product_logo/3y3SEjRuYmFYVMK.webp',
            'created_at' => '2025-04-22 01:01:57',
            'updated_at' => '2025-04-22 01:01:57'
        ],
        [
            'id' => 21,
            'paket_id' => 3,
            'layanan_id' => 56,
            'product_logo' => '/assets/product_logo/3y3SEjRuYmFYVMK.webp',
            'created_at' => '2025-04-22 01:01:57',
            'updated_at' => '2025-04-22 01:01:57'
        ],
        [
            'id' => 22,
            'paket_id' => 3,
            'layanan_id' => 57,
            'product_logo' => '/assets/product_logo/3y3SEjRuYmFYVMK.webp',
            'created_at' => '2025-04-22 01:01:57',
            'updated_at' => '2025-04-22 01:01:57'
        ],
        [
            'id' => 23,
            'paket_id' => 3,
            'layanan_id' => 64,
            'product_logo' => '/assets/product_logo/3y3SEjRuYmFYVMK.webp',
            'created_at' => '2025-04-22 01:01:57',
            'updated_at' => '2025-04-22 01:01:57'
        ],
        [
            'id' => 24,
            'paket_id' => 3,
            'layanan_id' => 70,
            'product_logo' => '/assets/product_logo/3y3SEjRuYmFYVMK.webp',
            'created_at' => '2025-04-22 01:01:57',
            'updated_at' => '2025-04-22 01:01:57'
        ],
        [
            'id' => 25,
            'paket_id' => 3,
            'layanan_id' => 78,
            'product_logo' => '/assets/product_logo/3y3SEjRuYmFYVMK.webp',
            'created_at' => '2025-04-22 01:01:57',
            'updated_at' => '2025-04-22 01:01:57'
        ],
        [
            'id' => 26,
            'paket_id' => 3,
            'layanan_id' => 81,
            'product_logo' => '/assets/product_logo/3y3SEjRuYmFYVMK.webp',
            'created_at' => '2025-04-22 01:01:57',
            'updated_at' => '2025-04-22 01:01:57'
        ],
        [
            'id' => 27,
            'paket_id' => 3,
            'layanan_id' => 83,
            'product_logo' => '/assets/product_logo/3y3SEjRuYmFYVMK.webp',
            'created_at' => '2025-04-22 01:01:57',
            'updated_at' => '2025-04-22 01:01:57'
        ],
        [
            'id' => 28,
            'paket_id' => 3,
            'layanan_id' => 87,
            'product_logo' => '/assets/product_logo/3y3SEjRuYmFYVMK.webp',
            'created_at' => '2025-04-22 01:01:57',
            'updated_at' => '2025-04-22 01:01:57'
        ],
        [
            'id' => 29,
            'paket_id' => 3,
            'layanan_id' => 91,
            'product_logo' => '/assets/product_logo/3y3SEjRuYmFYVMK.webp',
            'created_at' => '2025-04-22 01:01:57',
            'updated_at' => '2025-04-22 01:01:57'
        ],
        [
            'id' => 30,
            'paket_id' => 3,
            'layanan_id' => 6,
            'product_logo' => '/assets/product_logo/SGOZHrlzMEAuM8X.webp',
            'created_at' => '2025-04-22 01:03:26',
            'updated_at' => '2025-04-22 01:03:26'
        ],
        [
            'id' => 31,
            'paket_id' => 3,
            'layanan_id' => 10,
            'product_logo' => '/assets/product_logo/SGOZHrlzMEAuM8X.webp',
            'created_at' => '2025-04-22 01:03:26',
            'updated_at' => '2025-04-22 01:03:26'
        ],
        [
            'id' => 32,
            'paket_id' => 3,
            'layanan_id' => 18,
            'product_logo' => '/assets/product_logo/SGOZHrlzMEAuM8X.webp',
            'created_at' => '2025-04-22 01:03:26',
            'updated_at' => '2025-04-22 01:03:26'
        ],
        [
            'id' => 33,
            'paket_id' => 3,
            'layanan_id' => 32,
            'product_logo' => '/assets/product_logo/SGOZHrlzMEAuM8X.webp',
            'created_at' => '2025-04-22 01:03:26',
            'updated_at' => '2025-04-22 01:03:26'
        ],
        [
            'id' => 34,
            'paket_id' => 3,
            'layanan_id' => 34,
            'product_logo' => '/assets/product_logo/SGOZHrlzMEAuM8X.webp',
            'created_at' => '2025-04-22 01:03:26',
            'updated_at' => '2025-04-22 01:03:26'
        ],
        [
            'id' => 35,
            'paket_id' => 3,
            'layanan_id' => 37,
            'product_logo' => '/assets/product_logo/SGOZHrlzMEAuM8X.webp',
            'created_at' => '2025-04-22 01:03:26',
            'updated_at' => '2025-04-22 01:03:26'
        ],
        [
            'id' => 36,
            'paket_id' => 3,
            'layanan_id' => 40,
            'product_logo' => '/assets/product_logo/xQgUXTCLcHMe1XM.webp',
            'created_at' => '2025-04-22 01:06:09',
            'updated_at' => '2025-04-22 01:06:09'
        ],
        [
            'id' => 37,
            'paket_id' => 3,
            'layanan_id' => 44,
            'product_logo' => '/assets/product_logo/xQgUXTCLcHMe1XM.webp',
            'created_at' => '2025-04-22 01:06:09',
            'updated_at' => '2025-04-22 01:06:09'
        ],
        [
            'id' => 38,
            'paket_id' => 3,
            'layanan_id' => 50,
            'product_logo' => '/assets/product_logo/xQgUXTCLcHMe1XM.webp',
            'created_at' => '2025-04-22 01:06:09',
            'updated_at' => '2025-04-22 01:06:09'
        ],
        [
            'id' => 39,
            'paket_id' => 3,
            'layanan_id' => 53,
            'product_logo' => '/assets/product_logo/xQgUXTCLcHMe1XM.webp',
            'created_at' => '2025-04-22 01:06:09',
            'updated_at' => '2025-04-22 01:06:09'
        ],
        [
            'id' => 40,
            'paket_id' => 3,
            'layanan_id' => 55,
            'product_logo' => '/assets/product_logo/xQgUXTCLcHMe1XM.webp',
            'created_at' => '2025-04-22 01:06:09',
            'updated_at' => '2025-04-22 01:06:09'
        ],
        [
            'id' => 41,
            'paket_id' => 3,
            'layanan_id' => 66,
            'product_logo' => '/assets/product_logo/xQgUXTCLcHMe1XM.webp',
            'created_at' => '2025-04-22 01:06:09',
            'updated_at' => '2025-04-22 01:06:09'
        ],
        [
            'id' => 172,
            'paket_id' => 3,
            'layanan_id' => 364,
            'product_logo' => '/assets/product_logo/pHpPyzXA3RO9gYl.png',
            'created_at' => '2025-05-02 11:22:17',
            'updated_at' => '2025-05-02 11:22:17'
        ],
        [
            'id' => 176,
            'paket_id' => 2,
            'layanan_id' => 252,
            'product_logo' => '/assets/product_logo/l2DrjmJgo4EQykj.jpg',
            'created_at' => '2025-05-02 18:26:40',
            'updated_at' => '2025-05-02 18:26:40'
        ],
        [
            'id' => 209,
            'paket_id' => 3,
            'layanan_id' => 214,
            'product_logo' => '/assets/product_logo/nXMId6ZRkvW10kk.webp',
            'created_at' => '2025-05-05 07:33:45',
            'updated_at' => '2025-05-05 07:33:45'
        ],
        [
            'id' => 177,
            'paket_id' => 3,
            'layanan_id' => 167,
            'product_logo' => '/assets/product_logo/BPHJcuqIzrMnisZ.webp',
            'created_at' => '2025-05-02 18:42:49',
            'updated_at' => '2025-05-02 18:42:49'
        ],
        [
            'id' => 46,
            'paket_id' => 3,
            'layanan_id' => 188,
            'product_logo' => '/assets/product_logo/IFR13Ovonvb5Slf.webp',
            'created_at' => '2025-04-22 01:09:49',
            'updated_at' => '2025-04-22 01:09:49'
        ],
        [
            'id' => 47,
            'paket_id' => 3,
            'layanan_id' => 215,
            'product_logo' => '/assets/product_logo/IFR13Ovonvb5Slf.webp',
            'created_at' => '2025-04-22 01:09:49',
            'updated_at' => '2025-04-22 01:09:49'
        ],
        [
            'id' => 48,
            'paket_id' => 3,
            'layanan_id' => 228,
            'product_logo' => '/assets/product_logo/IFR13Ovonvb5Slf.webp',
            'created_at' => '2025-04-22 01:09:49',
            'updated_at' => '2025-04-22 01:09:49'
        ],
        [
            'id' => 49,
            'paket_id' => 3,
            'layanan_id' => 164,
            'product_logo' => '/assets/product_logo/IFR13Ovonvb5Slf.webp',
            'created_at' => '2025-04-22 01:09:49',
            'updated_at' => '2025-04-22 01:09:49'
        ],
        [
            'id' => 50,
            'paket_id' => 3,
            'layanan_id' => 173,
            'product_logo' => '/assets/product_logo/IFR13Ovonvb5Slf.webp',
            'created_at' => '2025-04-22 01:09:49',
            'updated_at' => '2025-04-22 01:09:49'
        ],
        [
            'id' => 51,
            'paket_id' => 3,
            'layanan_id' => 179,
            'product_logo' => '/assets/product_logo/IFR13Ovonvb5Slf.webp',
            'created_at' => '2025-04-22 01:09:49',
            'updated_at' => '2025-04-22 01:09:49'
        ],
        [
            'id' => 52,
            'paket_id' => 3,
            'layanan_id' => 185,
            'product_logo' => '/assets/product_logo/cGz3eNMbr7lvSAm.webp',
            'created_at' => '2025-04-22 01:11:08',
            'updated_at' => '2025-04-22 01:11:08'
        ],
        [
            'id' => 53,
            'paket_id' => 3,
            'layanan_id' => 191,
            'product_logo' => '/assets/product_logo/cGz3eNMbr7lvSAm.webp',
            'created_at' => '2025-04-22 01:11:08',
            'updated_at' => '2025-04-22 01:11:08'
        ],
        [
            'id' => 54,
            'paket_id' => 3,
            'layanan_id' => 193,
            'product_logo' => '/assets/product_logo/cGz3eNMbr7lvSAm.webp',
            'created_at' => '2025-04-22 01:11:08',
            'updated_at' => '2025-04-22 01:11:08'
        ],
        [
            'id' => 55,
            'paket_id' => 3,
            'layanan_id' => 195,
            'product_logo' => '/assets/product_logo/cGz3eNMbr7lvSAm.webp',
            'created_at' => '2025-04-22 01:11:08',
            'updated_at' => '2025-04-22 01:11:08'
        ],
        [
            'id' => 56,
            'paket_id' => 3,
            'layanan_id' => 207,
            'product_logo' => '/assets/product_logo/cGz3eNMbr7lvSAm.webp',
            'created_at' => '2025-04-22 01:11:08',
            'updated_at' => '2025-04-22 01:11:08'
        ],
        [
            'id' => 57,
            'paket_id' => 3,
            'layanan_id' => 216,
            'product_logo' => '/assets/product_logo/cGz3eNMbr7lvSAm.webp',
            'created_at' => '2025-04-22 01:11:09',
            'updated_at' => '2025-04-22 01:11:09'
        ],
        [
            'id' => 58,
            'paket_id' => 3,
            'layanan_id' => 224,
            'product_logo' => '/assets/product_logo/cGz3eNMbr7lvSAm.webp',
            'created_at' => '2025-04-22 01:11:09',
            'updated_at' => '2025-04-22 01:11:09'
        ],
        [
            'id' => 59,
            'paket_id' => 3,
            'layanan_id' => 229,
            'product_logo' => '/assets/product_logo/cGz3eNMbr7lvSAm.webp',
            'created_at' => '2025-04-22 01:11:09',
            'updated_at' => '2025-04-22 01:11:09'
        ],
        [
            'id' => 197,
            'paket_id' => 3,
            'layanan_id' => 240,
            'product_logo' => '/assets/product_logo/AnkhtQAq6uarDSB.webp',
            'created_at' => '2025-05-02 19:02:59',
            'updated_at' => '2025-05-02 19:02:59'
        ],
        [
            'id' => 61,
            'paket_id' => 3,
            'layanan_id' => 245,
            'product_logo' => '/assets/product_logo/cGz3eNMbr7lvSAm.webp',
            'created_at' => '2025-04-22 01:11:09',
            'updated_at' => '2025-04-22 01:11:09'
        ],
        [
            'id' => 199,
            'paket_id' => 3,
            'layanan_id' => 249,
            'product_logo' => '/assets/product_logo/o8aS3HJ75PwxmvT.webp',
            'created_at' => '2025-05-02 19:04:47',
            'updated_at' => '2025-05-02 19:04:47'
        ],
        [
            'id' => 63,
            'paket_id' => 3,
            'layanan_id' => 165,
            'product_logo' => '/assets/product_logo/JCpPMRLyimlw7yi.webp',
            'created_at' => '2025-04-22 01:12:07',
            'updated_at' => '2025-04-22 01:12:07'
        ],
        [
            'id' => 64,
            'paket_id' => 3,
            'layanan_id' => 166,
            'product_logo' => '/assets/product_logo/JCpPMRLyimlw7yi.webp',
            'created_at' => '2025-04-22 01:12:07',
            'updated_at' => '2025-04-22 01:12:07'
        ],
        [
            'id' => 200,
            'paket_id' => 3,
            'layanan_id' => 186,
            'product_logo' => '/assets/product_logo/eqEm5d1h2d8ZH7b.webp',
            'created_at' => '2025-05-02 19:07:20',
            'updated_at' => '2025-05-02 19:07:20'
        ],
        [
            'id' => 66,
            'paket_id' => 3,
            'layanan_id' => 176,
            'product_logo' => '/assets/product_logo/JCpPMRLyimlw7yi.webp',
            'created_at' => '2025-04-22 01:12:07',
            'updated_at' => '2025-04-22 01:12:07'
        ],
        [
            'id' => 67,
            'paket_id' => 3,
            'layanan_id' => 184,
            'product_logo' => '/assets/product_logo/JCpPMRLyimlw7yi.webp',
            'created_at' => '2025-04-22 01:12:07',
            'updated_at' => '2025-04-22 01:12:07'
        ],
        [
            'id' => 201,
            'paket_id' => 3,
            'layanan_id' => 365,
            'product_logo' => '/assets/product_logo/19pqBsRgSlUKh2W.webp',
            'created_at' => '2025-05-02 19:22:07',
            'updated_at' => '2025-05-02 19:22:07'
        ],
        [
            'id' => 69,
            'paket_id' => 3,
            'layanan_id' => 190,
            'product_logo' => '/assets/product_logo/JCpPMRLyimlw7yi.webp',
            'created_at' => '2025-04-22 01:12:07',
            'updated_at' => '2025-04-22 01:12:07'
        ],
        [
            'id' => 70,
            'paket_id' => 3,
            'layanan_id' => 197,
            'product_logo' => '/assets/product_logo/JCpPMRLyimlw7yi.webp',
            'created_at' => '2025-04-22 01:12:07',
            'updated_at' => '2025-04-22 01:12:07'
        ],
        [
            'id' => 71,
            'paket_id' => 3,
            'layanan_id' => 205,
            'product_logo' => '/assets/product_logo/JCpPMRLyimlw7yi.webp',
            'created_at' => '2025-04-22 01:12:07',
            'updated_at' => '2025-04-22 01:12:07'
        ],
        [
            'id' => 72,
            'paket_id' => 3,
            'layanan_id' => 209,
            'product_logo' => '/assets/product_logo/aV0gZ3EkVecGSvr.webp',
            'created_at' => '2025-04-22 01:12:36',
            'updated_at' => '2025-04-22 01:12:36'
        ],
        [
            'id' => 73,
            'paket_id' => 3,
            'layanan_id' => 213,
            'product_logo' => '/assets/product_logo/aV0gZ3EkVecGSvr.webp',
            'created_at' => '2025-04-22 01:12:36',
            'updated_at' => '2025-04-22 01:12:36'
        ],
        [
            'id' => 74,
            'paket_id' => 3,
            'layanan_id' => 220,
            'product_logo' => '/assets/product_logo/aV0gZ3EkVecGSvr.webp',
            'created_at' => '2025-04-22 01:12:36',
            'updated_at' => '2025-04-22 01:12:36'
        ],
        [
            'id' => 75,
            'paket_id' => 3,
            'layanan_id' => 223,
            'product_logo' => '/assets/product_logo/aV0gZ3EkVecGSvr.webp',
            'created_at' => '2025-04-22 01:12:36',
            'updated_at' => '2025-04-22 01:12:36'
        ],
        [
            'id' => 76,
            'paket_id' => 3,
            'layanan_id' => 226,
            'product_logo' => '/assets/product_logo/aV0gZ3EkVecGSvr.webp',
            'created_at' => '2025-04-22 01:12:36',
            'updated_at' => '2025-04-22 01:12:36'
        ],
        [
            'id' => 77,
            'paket_id' => 3,
            'layanan_id' => 232,
            'product_logo' => '/assets/product_logo/aV0gZ3EkVecGSvr.webp',
            'created_at' => '2025-04-22 01:12:36',
            'updated_at' => '2025-04-22 01:12:36'
        ],
        [
            'id' => 78,
            'paket_id' => 4,
            'layanan_id' => 260,
            'product_logo' => '/assets/product_logo/dILukHUdWWETQzY.webp',
            'created_at' => '2025-04-22 01:14:42',
            'updated_at' => '2025-04-22 01:14:42'
        ],
        [
            'id' => 79,
            'paket_id' => 4,
            'layanan_id' => 261,
            'product_logo' => '/assets/product_logo/dILukHUdWWETQzY.webp',
            'created_at' => '2025-04-22 01:14:42',
            'updated_at' => '2025-04-22 01:14:42'
        ],
        [
            'id' => 80,
            'paket_id' => 4,
            'layanan_id' => 262,
            'product_logo' => '/assets/product_logo/dILukHUdWWETQzY.webp',
            'created_at' => '2025-04-22 01:14:42',
            'updated_at' => '2025-04-22 01:14:42'
        ],
        [
            'id' => 81,
            'paket_id' => 4,
            'layanan_id' => 263,
            'product_logo' => '/assets/product_logo/dILukHUdWWETQzY.webp',
            'created_at' => '2025-04-22 01:14:42',
            'updated_at' => '2025-04-22 01:14:42'
        ],
        [
            'id' => 82,
            'paket_id' => 4,
            'layanan_id' => 264,
            'product_logo' => '/assets/product_logo/dILukHUdWWETQzY.webp',
            'created_at' => '2025-04-22 01:14:42',
            'updated_at' => '2025-04-22 01:14:42'
        ],
        [
            'id' => 83,
            'paket_id' => 5,
            'layanan_id' => 258,
            'product_logo' => '/assets/product_logo/51xEVszGNUVfC6s.webp',
            'created_at' => '2025-04-22 01:15:27',
            'updated_at' => '2025-04-22 01:15:27'
        ],
        [
            'id' => 84,
            'paket_id' => 5,
            'layanan_id' => 256,
            'product_logo' => '/assets/product_logo/51xEVszGNUVfC6s.webp',
            'created_at' => '2025-04-22 01:15:27',
            'updated_at' => '2025-04-22 01:15:27'
        ],
        [
            'id' => 85,
            'paket_id' => 5,
            'layanan_id' => 254,
            'product_logo' => '/assets/product_logo/51xEVszGNUVfC6s.webp',
            'created_at' => '2025-04-22 01:15:27',
            'updated_at' => '2025-04-22 01:15:27'
        ],
        [
            'id' => 86,
            'paket_id' => 5,
            'layanan_id' => 255,
            'product_logo' => '/assets/product_logo/51xEVszGNUVfC6s.webp',
            'created_at' => '2025-04-22 01:15:27',
            'updated_at' => '2025-04-22 01:15:27'
        ],
        [
            'id' => 87,
            'paket_id' => 5,
            'layanan_id' => 257,
            'product_logo' => '/assets/product_logo/51xEVszGNUVfC6s.webp',
            'created_at' => '2025-04-22 01:15:27',
            'updated_at' => '2025-04-22 01:15:27'
        ],
        [
            'id' => 88,
            'paket_id' => 5,
            'layanan_id' => 259,
            'product_logo' => '/assets/product_logo/51xEVszGNUVfC6s.webp',
            'created_at' => '2025-04-22 01:15:27',
            'updated_at' => '2025-04-22 01:15:27'
        ],
        [
            'id' => 273,
            'paket_id' => 3,
            'layanan_id' => 398,
            'product_logo' => '/assets/product_logo/sSFm9WEWlsA0SRs.webp',
            'created_at' => '2025-05-12 03:53:03',
            'updated_at' => '2025-05-12 03:53:03'
        ],
        [
            'id' => 274,
            'paket_id' => 3,
            'layanan_id' => 399,
            'product_logo' => '/assets/product_logo/xJaZjboPgnqtBvo.webp',
            'created_at' => '2025-05-12 03:55:50',
            'updated_at' => '2025-05-12 03:55:50'
        ],
        [
            'id' => 275,
            'paket_id' => 3,
            'layanan_id' => 400,
            'product_logo' => '/assets/product_logo/jmg6qjViNZkTjwu.webp',
            'created_at' => '2025-05-12 04:01:20',
            'updated_at' => '2025-05-12 04:01:20'
        ],
        [
            'id' => 307,
            'paket_id' => 3,
            'layanan_id' => 432,
            'product_logo' => '/assets/product_logo/FfCLU9WotmWRP5g.webp',
            'created_at' => '2025-06-23 06:59:14',
            'updated_at' => '2025-06-23 06:59:14'
        ],
        [
            'id' => 304,
            'paket_id' => 3,
            'layanan_id' => 429,
            'product_logo' => '/assets/product_logo/FfCLU9WotmWRP5g.webp',
            'created_at' => '2025-06-23 06:59:14',
            'updated_at' => '2025-06-23 06:59:14'
        ],
        [
            'id' => 301,
            'paket_id' => 3,
            'layanan_id' => 426,
            'product_logo' => '/assets/product_logo/FfCLU9WotmWRP5g.webp',
            'created_at' => '2025-06-23 06:59:14',
            'updated_at' => '2025-06-23 06:59:14'
        ],
        [
            'id' => 277,
            'paket_id' => 3,
            'layanan_id' => 402,
            'product_logo' => '/assets/product_logo/nUKK5BMafKf6D3H.webp',
            'created_at' => '2025-05-12 04:13:50',
            'updated_at' => '2025-05-12 04:13:50'
        ],
        [
            'id' => 268,
            'paket_id' => 3,
            'layanan_id' => 277,
            'product_logo' => '/assets/product_logo/bmkuO4ERDjyP4Km.jpg',
            'created_at' => '2025-05-08 15:18:46',
            'updated_at' => '2025-05-08 15:18:46'
        ],
        [
            'id' => 269,
            'paket_id' => 3,
            'layanan_id' => 281,
            'product_logo' => '/assets/product_logo/bmkuO4ERDjyP4Km.jpg',
            'created_at' => '2025-05-08 15:18:46',
            'updated_at' => '2025-05-08 15:18:46'
        ],
        [
            'id' => 270,
            'paket_id' => 3,
            'layanan_id' => 286,
            'product_logo' => '/assets/product_logo/bmkuO4ERDjyP4Km.jpg',
            'created_at' => '2025-05-08 15:18:46',
            'updated_at' => '2025-05-08 15:18:46'
        ],
        [
            'id' => 271,
            'paket_id' => 2,
            'layanan_id' => 272,
            'product_logo' => '/assets/product_logo/SZ3OsauryVsEs39.webp',
            'created_at' => '2025-05-08 15:20:51',
            'updated_at' => '2025-05-08 15:20:51'
        ],
        [
            'id' => 259,
            'paket_id' => 3,
            'layanan_id' => 275,
            'product_logo' => '/assets/product_logo/bmkuO4ERDjyP4Km.jpg',
            'created_at' => '2025-05-08 15:18:46',
            'updated_at' => '2025-05-08 15:18:46'
        ],
        [
            'id' => 104,
            'paket_id' => 7,
            'layanan_id' => 271,
            'product_logo' => '/assets/product_logo/LvYPF5tfiDmeeNj.webp',
            'created_at' => '2025-04-22 01:19:21',
            'updated_at' => '2025-04-22 01:19:21'
        ],
        [
            'id' => 296,
            'paket_id' => 3,
            'layanan_id' => 421,
            'product_logo' => '/assets/product_logo/FfCLU9WotmWRP5g.webp',
            'created_at' => '2025-06-23 06:59:13',
            'updated_at' => '2025-06-23 06:59:13'
        ],
        [
            'id' => 297,
            'paket_id' => 3,
            'layanan_id' => 422,
            'product_logo' => '/assets/product_logo/FfCLU9WotmWRP5g.webp',
            'created_at' => '2025-06-23 06:59:13',
            'updated_at' => '2025-06-23 06:59:13'
        ],
        [
            'id' => 298,
            'paket_id' => 3,
            'layanan_id' => 423,
            'product_logo' => '/assets/product_logo/FfCLU9WotmWRP5g.webp',
            'created_at' => '2025-06-23 06:59:13',
            'updated_at' => '2025-06-23 06:59:13'
        ],
        [
            'id' => 306,
            'paket_id' => 3,
            'layanan_id' => 431,
            'product_logo' => '/assets/product_logo/FfCLU9WotmWRP5g.webp',
            'created_at' => '2025-06-23 06:59:14',
            'updated_at' => '2025-06-23 06:59:14'
        ],
        [
            'id' => 303,
            'paket_id' => 3,
            'layanan_id' => 428,
            'product_logo' => '/assets/product_logo/FfCLU9WotmWRP5g.webp',
            'created_at' => '2025-06-23 06:59:14',
            'updated_at' => '2025-06-23 06:59:14'
        ],
        [
            'id' => 300,
            'paket_id' => 3,
            'layanan_id' => 425,
            'product_logo' => '/assets/product_logo/FfCLU9WotmWRP5g.webp',
            'created_at' => '2025-06-23 06:59:14',
            'updated_at' => '2025-06-23 06:59:14'
        ],
        [
            'id' => 276,
            'paket_id' => 3,
            'layanan_id' => 401,
            'product_logo' => '/assets/product_logo/O3l5BOG2DttKij0.webp',
            'created_at' => '2025-05-12 04:10:00',
            'updated_at' => '2025-05-12 04:10:00'
        ],
        [
            'id' => 264,
            'paket_id' => 3,
            'layanan_id' => 284,
            'product_logo' => '/assets/product_logo/bmkuO4ERDjyP4Km.jpg',
            'created_at' => '2025-05-08 15:18:46',
            'updated_at' => '2025-05-08 15:18:46'
        ],
        [
            'id' => 265,
            'paket_id' => 3,
            'layanan_id' => 285,
            'product_logo' => '/assets/product_logo/bmkuO4ERDjyP4Km.jpg',
            'created_at' => '2025-05-08 15:18:46',
            'updated_at' => '2025-05-08 15:18:46'
        ],
        [
            'id' => 266,
            'paket_id' => 3,
            'layanan_id' => 274,
            'product_logo' => '/assets/product_logo/bmkuO4ERDjyP4Km.jpg',
            'created_at' => '2025-05-08 15:18:46',
            'updated_at' => '2025-05-08 15:18:46'
        ],
        [
            'id' => 267,
            'paket_id' => 3,
            'layanan_id' => 276,
            'product_logo' => '/assets/product_logo/bmkuO4ERDjyP4Km.jpg',
            'created_at' => '2025-05-08 15:18:46',
            'updated_at' => '2025-05-08 15:18:46'
        ],
        [
            'id' => 258,
            'paket_id' => 3,
            'layanan_id' => 273,
            'product_logo' => '/assets/product_logo/bmkuO4ERDjyP4Km.jpg',
            'created_at' => '2025-05-08 15:18:46',
            'updated_at' => '2025-05-08 15:18:46'
        ],
        [
            'id' => 119,
            'paket_id' => 9,
            'layanan_id' => 268,
            'product_logo' => '/assets/product_logo/6LD02an0DVhdulX.webp',
            'created_at' => '2025-04-22 01:23:21',
            'updated_at' => '2025-04-22 01:23:21'
        ],
        [
            'id' => 120,
            'paket_id' => 9,
            'layanan_id' => 266,
            'product_logo' => '/assets/product_logo/6LD02an0DVhdulX.webp',
            'created_at' => '2025-04-22 01:23:21',
            'updated_at' => '2025-04-22 01:23:21'
        ],
        [
            'id' => 121,
            'paket_id' => 9,
            'layanan_id' => 269,
            'product_logo' => '/assets/product_logo/6LD02an0DVhdulX.webp',
            'created_at' => '2025-04-22 01:23:21',
            'updated_at' => '2025-04-22 01:23:21'
        ],
        [
            'id' => 122,
            'paket_id' => 9,
            'layanan_id' => 265,
            'product_logo' => '/assets/product_logo/6LD02an0DVhdulX.webp',
            'created_at' => '2025-04-22 01:23:21',
            'updated_at' => '2025-04-22 01:23:21'
        ],
        [
            'id' => 123,
            'paket_id' => 9,
            'layanan_id' => 267,
            'product_logo' => '/assets/product_logo/6LD02an0DVhdulX.webp',
            'created_at' => '2025-04-22 01:23:21',
            'updated_at' => '2025-04-22 01:23:21'
        ],
        [
            'id' => 124,
            'paket_id' => 9,
            'layanan_id' => 270,
            'product_logo' => '/assets/product_logo/6LD02an0DVhdulX.webp',
            'created_at' => '2025-04-22 01:23:21',
            'updated_at' => '2025-04-22 01:23:21'
        ],
        [
            'id' => 125,
            'paket_id' => 10,
            'layanan_id' => 287,
            'product_logo' => '/assets/product_logo/173BeH8tyj86Iu4.webp',
            'created_at' => '2025-04-22 01:26:39',
            'updated_at' => '2025-04-22 01:26:39'
        ],
        [
            'id' => 126,
            'paket_id' => 10,
            'layanan_id' => 290,
            'product_logo' => '/assets/product_logo/173BeH8tyj86Iu4.webp',
            'created_at' => '2025-04-22 01:26:39',
            'updated_at' => '2025-04-22 01:26:39'
        ],
        [
            'id' => 127,
            'paket_id' => 10,
            'layanan_id' => 295,
            'product_logo' => '/assets/product_logo/173BeH8tyj86Iu4.webp',
            'created_at' => '2025-04-22 01:26:39',
            'updated_at' => '2025-04-22 01:26:39'
        ],
        [
            'id' => 128,
            'paket_id' => 10,
            'layanan_id' => 288,
            'product_logo' => '/assets/product_logo/173BeH8tyj86Iu4.webp',
            'created_at' => '2025-04-22 01:26:39',
            'updated_at' => '2025-04-22 01:26:39'
        ],
        [
            'id' => 129,
            'paket_id' => 10,
            'layanan_id' => 291,
            'product_logo' => '/assets/product_logo/173BeH8tyj86Iu4.webp',
            'created_at' => '2025-04-22 01:26:39',
            'updated_at' => '2025-04-22 01:26:39'
        ],
        [
            'id' => 130,
            'paket_id' => 10,
            'layanan_id' => 292,
            'product_logo' => '/assets/product_logo/173BeH8tyj86Iu4.webp',
            'created_at' => '2025-04-22 01:26:39',
            'updated_at' => '2025-04-22 01:26:39'
        ],
        [
            'id' => 131,
            'paket_id' => 10,
            'layanan_id' => 293,
            'product_logo' => '/assets/product_logo/173BeH8tyj86Iu4.webp',
            'created_at' => '2025-04-22 01:26:39',
            'updated_at' => '2025-04-22 01:26:39'
        ],
        [
            'id' => 132,
            'paket_id' => 10,
            'layanan_id' => 294,
            'product_logo' => '/assets/product_logo/173BeH8tyj86Iu4.webp',
            'created_at' => '2025-04-22 01:26:39',
            'updated_at' => '2025-04-22 01:26:39'
        ],
        [
            'id' => 133,
            'paket_id' => 10,
            'layanan_id' => 296,
            'product_logo' => '/assets/product_logo/173BeH8tyj86Iu4.webp',
            'created_at' => '2025-04-22 01:26:39',
            'updated_at' => '2025-04-22 01:26:39'
        ],
        [
            'id' => 134,
            'paket_id' => 10,
            'layanan_id' => 298,
            'product_logo' => '/assets/product_logo/173BeH8tyj86Iu4.webp',
            'created_at' => '2025-04-22 01:26:39',
            'updated_at' => '2025-04-22 01:26:39'
        ],
        [
            'id' => 135,
            'paket_id' => 3,
            'layanan_id' => 303,
            'product_logo' => '/assets/product_logo/4D0nliDPFmM7PNa.webp',
            'created_at' => '2025-04-22 01:27:18',
            'updated_at' => '2025-04-22 01:27:18'
        ],
        [
            'id' => 136,
            'paket_id' => 3,
            'layanan_id' => 300,
            'product_logo' => '/assets/product_logo/4D0nliDPFmM7PNa.webp',
            'created_at' => '2025-04-22 01:27:18',
            'updated_at' => '2025-04-22 01:27:18'
        ],
        [
            'id' => 137,
            'paket_id' => 3,
            'layanan_id' => 301,
            'product_logo' => '/assets/product_logo/4D0nliDPFmM7PNa.webp',
            'created_at' => '2025-04-22 01:27:18',
            'updated_at' => '2025-04-22 01:27:18'
        ],
        [
            'id' => 138,
            'paket_id' => 3,
            'layanan_id' => 302,
            'product_logo' => '/assets/product_logo/4D0nliDPFmM7PNa.webp',
            'created_at' => '2025-04-22 01:27:18',
            'updated_at' => '2025-04-22 01:27:18'
        ],
        [
            'id' => 139,
            'paket_id' => 3,
            'layanan_id' => 304,
            'product_logo' => '/assets/product_logo/4D0nliDPFmM7PNa.webp',
            'created_at' => '2025-04-22 01:27:18',
            'updated_at' => '2025-04-22 01:27:18'
        ],
        [
            'id' => 140,
            'paket_id' => 3,
            'layanan_id' => 299,
            'product_logo' => '/assets/product_logo/4D0nliDPFmM7PNa.webp',
            'created_at' => '2025-04-22 01:27:18',
            'updated_at' => '2025-04-22 01:27:18'
        ],
        [
            'id' => 141,
            'paket_id' => 3,
            'layanan_id' => 323,
            'product_logo' => '/assets/product_logo/xRghUDgSu1m6Rik.webp',
            'created_at' => '2025-04-22 01:29:55',
            'updated_at' => '2025-04-22 01:29:55'
        ],
        [
            'id' => 142,
            'paket_id' => 3,
            'layanan_id' => 319,
            'product_logo' => '/assets/product_logo/xRghUDgSu1m6Rik.webp',
            'created_at' => '2025-04-22 01:29:55',
            'updated_at' => '2025-04-22 01:29:55'
        ],
        [
            'id' => 143,
            'paket_id' => 3,
            'layanan_id' => 324,
            'product_logo' => '/assets/product_logo/xRghUDgSu1m6Rik.webp',
            'created_at' => '2025-04-22 01:29:55',
            'updated_at' => '2025-04-22 01:29:55'
        ],
        [
            'id' => 144,
            'paket_id' => 3,
            'layanan_id' => 320,
            'product_logo' => '/assets/product_logo/xRghUDgSu1m6Rik.webp',
            'created_at' => '2025-04-22 01:29:55',
            'updated_at' => '2025-04-22 01:29:55'
        ],
        [
            'id' => 145,
            'paket_id' => 3,
            'layanan_id' => 322,
            'product_logo' => '/assets/product_logo/xRghUDgSu1m6Rik.webp',
            'created_at' => '2025-04-22 01:29:55',
            'updated_at' => '2025-04-22 01:29:55'
        ],
        [
            'id' => 146,
            'paket_id' => 3,
            'layanan_id' => 325,
            'product_logo' => '/assets/product_logo/xRghUDgSu1m6Rik.webp',
            'created_at' => '2025-04-22 01:29:55',
            'updated_at' => '2025-04-22 01:29:55'
        ],
        [
            'id' => 147,
            'paket_id' => 3,
            'layanan_id' => 321,
            'product_logo' => '/assets/product_logo/xRghUDgSu1m6Rik.webp',
            'created_at' => '2025-04-22 01:29:55',
            'updated_at' => '2025-04-22 01:29:55'
        ],
        [
            'id' => 148,
            'paket_id' => 11,
            'layanan_id' => 1,
            'product_logo' => '/assets/product_logo/YaMMPIG4mR1TNRK.webp',
            'created_at' => '2025-04-22 01:33:18',
            'updated_at' => '2025-04-22 01:33:18'
        ],
        [
            'id' => 149,
            'paket_id' => 11,
            'layanan_id' => 343,
            'product_logo' => '/assets/product_logo/YaMMPIG4mR1TNRK.webp',
            'created_at' => '2025-04-22 01:33:18',
            'updated_at' => '2025-04-22 01:33:18'
        ],
        [
            'id' => 150,
            'paket_id' => 11,
            'layanan_id' => 344,
            'product_logo' => '/assets/product_logo/wKmjZ5cqQ15d4VM.webp',
            'created_at' => '2025-04-22 01:33:41',
            'updated_at' => '2025-04-22 01:33:41'
        ],
        [
            'id' => 151,
            'paket_id' => 11,
            'layanan_id' => 347,
            'product_logo' => '/assets/product_logo/wKmjZ5cqQ15d4VM.webp',
            'created_at' => '2025-04-22 01:33:41',
            'updated_at' => '2025-04-22 01:33:41'
        ],
        [
            'id' => 152,
            'paket_id' => 11,
            'layanan_id' => 348,
            'product_logo' => '/assets/product_logo/wKmjZ5cqQ15d4VM.webp',
            'created_at' => '2025-04-22 01:33:41',
            'updated_at' => '2025-04-22 01:33:41'
        ],
        [
            'id' => 153,
            'paket_id' => 11,
            'layanan_id' => 345,
            'product_logo' => '/assets/product_logo/wKmjZ5cqQ15d4VM.webp',
            'created_at' => '2025-04-22 01:33:41',
            'updated_at' => '2025-04-22 01:33:41'
        ],
        [
            'id' => 154,
            'paket_id' => 11,
            'layanan_id' => 346,
            'product_logo' => '/assets/product_logo/wKmjZ5cqQ15d4VM.webp',
            'created_at' => '2025-04-22 01:33:41',
            'updated_at' => '2025-04-22 01:33:41'
        ],
        [
            'id' => 155,
            'paket_id' => 11,
            'layanan_id' => 349,
            'product_logo' => '/assets/product_logo/a16o8uktswQKQO9.webp',
            'created_at' => '2025-04-22 01:33:58',
            'updated_at' => '2025-04-22 01:33:58'
        ],
        [
            'id' => 156,
            'paket_id' => 11,
            'layanan_id' => 351,
            'product_logo' => '/assets/product_logo/a16o8uktswQKQO9.webp',
            'created_at' => '2025-04-22 01:33:58',
            'updated_at' => '2025-04-22 01:33:58'
        ],
        [
            'id' => 157,
            'paket_id' => 11,
            'layanan_id' => 350,
            'product_logo' => '/assets/product_logo/a16o8uktswQKQO9.webp',
            'created_at' => '2025-04-22 01:33:58',
            'updated_at' => '2025-04-22 01:33:58'
        ],
        [
            'id' => 158,
            'paket_id' => 11,
            'layanan_id' => 352,
            'product_logo' => '/assets/product_logo/PG4EbUe7FvHo869.png',
            'created_at' => '2025-04-22 01:34:20',
            'updated_at' => '2025-04-22 01:34:20'
        ],
        [
            'id' => 159,
            'paket_id' => 11,
            'layanan_id' => 353,
            'product_logo' => '/assets/product_logo/ZmbijYU9bJ2wCj8.webp',
            'created_at' => '2025-04-22 01:34:35',
            'updated_at' => '2025-04-22 01:34:35'
        ],
        [
            'id' => 160,
            'paket_id' => 11,
            'layanan_id' => 354,
            'product_logo' => '/assets/product_logo/ZmbijYU9bJ2wCj8.webp',
            'created_at' => '2025-04-22 01:34:35',
            'updated_at' => '2025-04-22 01:34:35'
        ],
        [
            'id' => 161,
            'paket_id' => 11,
            'layanan_id' => 355,
            'product_logo' => '/assets/product_logo/zBrjsZRVdJjQeta.jpg',
            'created_at' => '2025-04-22 01:34:55',
            'updated_at' => '2025-04-22 01:34:55'
        ],
        [
            'id' => 162,
            'paket_id' => 11,
            'layanan_id' => 356,
            'product_logo' => '/assets/product_logo/bAPlYsIdTkJZQsR.jpg',
            'created_at' => '2025-04-22 01:35:11',
            'updated_at' => '2025-04-22 01:35:11'
        ],
        [
            'id' => 163,
            'paket_id' => 11,
            'layanan_id' => 357,
            'product_logo' => '/assets/product_logo/bAPlYsIdTkJZQsR.jpg',
            'created_at' => '2025-04-22 01:35:11',
            'updated_at' => '2025-04-22 01:35:11'
        ],
        [
            'id' => 164,
            'paket_id' => 11,
            'layanan_id' => 358,
            'product_logo' => '/assets/product_logo/bAPlYsIdTkJZQsR.jpg',
            'created_at' => '2025-04-22 01:35:11',
            'updated_at' => '2025-04-22 01:35:11'
        ],
        [
            'id' => 165,
            'paket_id' => 11,
            'layanan_id' => 361,
            'product_logo' => '/assets/product_logo/G16L9iuh9jLXcnd.png',
            'created_at' => '2025-04-22 01:35:31',
            'updated_at' => '2025-04-22 01:35:31'
        ],
        [
            'id' => 166,
            'paket_id' => 11,
            'layanan_id' => 362,
            'product_logo' => '/assets/product_logo/G16L9iuh9jLXcnd.png',
            'created_at' => '2025-04-22 01:35:31',
            'updated_at' => '2025-04-22 01:35:31'
        ],
        [
            'id' => 167,
            'paket_id' => 11,
            'layanan_id' => 359,
            'product_logo' => '/assets/product_logo/G16L9iuh9jLXcnd.png',
            'created_at' => '2025-04-22 01:35:31',
            'updated_at' => '2025-04-22 01:35:31'
        ],
        [
            'id' => 168,
            'paket_id' => 11,
            'layanan_id' => 363,
            'product_logo' => '/assets/product_logo/G16L9iuh9jLXcnd.png',
            'created_at' => '2025-04-22 01:35:31',
            'updated_at' => '2025-04-22 01:35:31'
        ],
        [
            'id' => 169,
            'paket_id' => 11,
            'layanan_id' => 360,
            'product_logo' => '/assets/product_logo/G16L9iuh9jLXcnd.png',
            'created_at' => '2025-04-22 01:35:31',
            'updated_at' => '2025-04-22 01:35:31'
        ],
        [
            'id' => 175,
            'paket_id' => 2,
            'layanan_id' => 253,
            'product_logo' => '/assets/product_logo/mPvU9cNmhX1dYsL.jpg',
            'created_at' => '2025-05-02 18:25:07',
            'updated_at' => '2025-05-02 18:25:07'
        ],
        [
            'id' => 178,
            'paket_id' => 3,
            'layanan_id' => 182,
            'product_logo' => '/assets/product_logo/SAgAX8djehsv30q.webp',
            'created_at' => '2025-05-02 18:43:27',
            'updated_at' => '2025-05-02 18:43:27'
        ],
        [
            'id' => 179,
            'paket_id' => 3,
            'layanan_id' => 192,
            'product_logo' => '/assets/product_logo/nZv7QwGmNWE0yNX.webp',
            'created_at' => '2025-05-02 18:44:09',
            'updated_at' => '2025-05-02 18:44:09'
        ],
        [
            'id' => 180,
            'paket_id' => 3,
            'layanan_id' => 201,
            'product_logo' => '/assets/product_logo/v1fhTYh7vSV1qQk.webp',
            'created_at' => '2025-05-02 18:45:27',
            'updated_at' => '2025-05-02 18:45:27'
        ],
        [
            'id' => 181,
            'paket_id' => 3,
            'layanan_id' => 219,
            'product_logo' => '/assets/product_logo/9DtpCEqMIFqObef.webp',
            'created_at' => '2025-05-02 18:45:52',
            'updated_at' => '2025-05-02 18:45:52'
        ],
        [
            'id' => 182,
            'paket_id' => 3,
            'layanan_id' => 237,
            'product_logo' => '/assets/product_logo/Cr1a8JX9E2uFpvz.webp',
            'created_at' => '2025-05-02 18:47:21',
            'updated_at' => '2025-05-02 18:47:21'
        ],
        [
            'id' => 183,
            'paket_id' => 3,
            'layanan_id' => 242,
            'product_logo' => '/assets/product_logo/wfHqkhoHqF7Cx2d.webp',
            'created_at' => '2025-05-02 18:47:50',
            'updated_at' => '2025-05-02 18:47:50'
        ],
        [
            'id' => 184,
            'paket_id' => 3,
            'layanan_id' => 168,
            'product_logo' => '/assets/product_logo/Ku1SLgtR3uaMFPr.webp',
            'created_at' => '2025-05-02 18:50:52',
            'updated_at' => '2025-05-02 18:50:52'
        ],
        [
            'id' => 185,
            'paket_id' => 3,
            'layanan_id' => 175,
            'product_logo' => '/assets/product_logo/5ksWC0E93ZnLCx9.webp',
            'created_at' => '2025-05-02 18:52:51',
            'updated_at' => '2025-05-02 18:52:51'
        ],
        [
            'id' => 192,
            'paket_id' => 3,
            'layanan_id' => 204,
            'product_logo' => '/assets/product_logo/iM0cg5MO6ERztVI.webp',
            'created_at' => '2025-05-02 18:58:06',
            'updated_at' => '2025-05-02 18:58:06'
        ],
        [
            'id' => 190,
            'paket_id' => 3,
            'layanan_id' => 196,
            'product_logo' => '/assets/product_logo/iM0cg5MO6ERztVI.webp',
            'created_at' => '2025-05-02 18:58:06',
            'updated_at' => '2025-05-02 18:58:06'
        ],
        [
            'id' => 191,
            'paket_id' => 3,
            'layanan_id' => 200,
            'product_logo' => '/assets/product_logo/iM0cg5MO6ERztVI.webp',
            'created_at' => '2025-05-02 18:58:06',
            'updated_at' => '2025-05-02 18:58:06'
        ],
        [
            'id' => 193,
            'paket_id' => 3,
            'layanan_id' => 202,
            'product_logo' => '/assets/product_logo/iM0cg5MO6ERztVI.webp',
            'created_at' => '2025-05-02 18:58:06',
            'updated_at' => '2025-05-02 18:58:06'
        ],
        [
            'id' => 194,
            'paket_id' => 3,
            'layanan_id' => 217,
            'product_logo' => '/assets/product_logo/0kj8IU5mSuiQsAi.webp',
            'created_at' => '2025-05-02 18:59:57',
            'updated_at' => '2025-05-02 18:59:57'
        ],
        [
            'id' => 195,
            'paket_id' => 3,
            'layanan_id' => 222,
            'product_logo' => '/assets/product_logo/0kj8IU5mSuiQsAi.webp',
            'created_at' => '2025-05-02 18:59:57',
            'updated_at' => '2025-05-02 18:59:57'
        ],
        [
            'id' => 196,
            'paket_id' => 3,
            'layanan_id' => 218,
            'product_logo' => '/assets/product_logo/0kj8IU5mSuiQsAi.webp',
            'created_at' => '2025-05-02 18:59:57',
            'updated_at' => '2025-05-02 18:59:57'
        ],
        [
            'id' => 198,
            'paket_id' => 3,
            'layanan_id' => 241,
            'product_logo' => '/assets/product_logo/AnkhtQAq6uarDSB.webp',
            'created_at' => '2025-05-02 19:02:59',
            'updated_at' => '2025-05-02 19:02:59'
        ],
        [
            'id' => 202,
            'paket_id' => 3,
            'layanan_id' => 198,
            'product_logo' => '/assets/product_logo/qpte3gqKDsGq16b.webp',
            'created_at' => '2025-05-02 20:01:08',
            'updated_at' => '2025-05-02 20:01:08'
        ],
        [
            'id' => 203,
            'paket_id' => 3,
            'layanan_id' => 233,
            'product_logo' => '/assets/product_logo/qpte3gqKDsGq16b.webp',
            'created_at' => '2025-05-02 20:01:08',
            'updated_at' => '2025-05-02 20:01:08'
        ],
        [
            'id' => 204,
            'paket_id' => 2,
            'layanan_id' => 367,
            'product_logo' => '/assets/product_logo/cXTC5a4SChpjJHu.webp',
            'created_at' => '2025-05-02 21:45:18',
            'updated_at' => '2025-05-02 21:45:18'
        ],
        [
            'id' => 283,
            'paket_id' => 3,
            'layanan_id' => 408,
            'product_logo' => '/assets/product_logo/WeswhbwGJIzxyBh.webp',
            'created_at' => '2025-06-23 02:08:07',
            'updated_at' => '2025-06-23 02:08:07'
        ],
        [
            'id' => 208,
            'paket_id' => 3,
            'layanan_id' => 178,
            'product_logo' => '/assets/product_logo/2eYgERw2zKuV5LA.webp',
            'created_at' => '2025-05-05 05:53:24',
            'updated_at' => '2025-05-05 05:53:24'
        ],
        [
            'id' => 284,
            'paket_id' => 3,
            'layanan_id' => 409,
            'product_logo' => '/assets/product_logo/WeswhbwGJIzxyBh.webp',
            'created_at' => '2025-06-23 02:08:07',
            'updated_at' => '2025-06-23 02:08:07'
        ],
        [
            'id' => 285,
            'paket_id' => 3,
            'layanan_id' => 410,
            'product_logo' => '/assets/product_logo/WeswhbwGJIzxyBh.webp',
            'created_at' => '2025-06-23 02:08:07',
            'updated_at' => '2025-06-23 02:08:07'
        ],
        [
            'id' => 234,
            'paket_id' => 2,
            'layanan_id' => 366,
            'product_logo' => '/assets/product_logo/lfK9EQ2oI0BIUJc.jpg',
            'created_at' => '2025-05-08 06:31:47',
            'updated_at' => '2025-05-08 06:31:47'
        ],
        [
            'id' => 217,
            'paket_id' => 3,
            'layanan_id' => 377,
            'product_logo' => '/assets/product_logo/SEkfwL4ntnFKIMG.webp',
            'created_at' => '2025-05-05 13:24:59',
            'updated_at' => '2025-05-05 13:24:59'
        ],
        [
            'id' => 336,
            'paket_id' => 3,
            'layanan_id' => 449,
            'product_logo' => '/assets/product_logo/5ypvZptT38wENsU.webp',
            'created_at' => '2025-09-04 03:58:16',
            'updated_at' => '2025-09-04 03:58:16'
        ],
        [
            'id' => 293,
            'paket_id' => 3,
            'layanan_id' => 418,
            'product_logo' => '/assets/product_logo/WeswhbwGJIzxyBh.webp',
            'created_at' => '2025-06-23 02:08:07',
            'updated_at' => '2025-06-23 02:08:07'
        ],
        [
            'id' => 292,
            'paket_id' => 3,
            'layanan_id' => 417,
            'product_logo' => '/assets/product_logo/WeswhbwGJIzxyBh.webp',
            'created_at' => '2025-06-23 02:08:07',
            'updated_at' => '2025-06-23 02:08:07'
        ],
        [
            'id' => 291,
            'paket_id' => 3,
            'layanan_id' => 416,
            'product_logo' => '/assets/product_logo/WeswhbwGJIzxyBh.webp',
            'created_at' => '2025-06-23 02:08:07',
            'updated_at' => '2025-06-23 02:08:07'
        ],
        [
            'id' => 290,
            'paket_id' => 3,
            'layanan_id' => 415,
            'product_logo' => '/assets/product_logo/WeswhbwGJIzxyBh.webp',
            'created_at' => '2025-06-23 02:08:07',
            'updated_at' => '2025-06-23 02:08:07'
        ],
        [
            'id' => 289,
            'paket_id' => 3,
            'layanan_id' => 414,
            'product_logo' => '/assets/product_logo/WeswhbwGJIzxyBh.webp',
            'created_at' => '2025-06-23 02:08:07',
            'updated_at' => '2025-06-23 02:08:07'
        ],
        [
            'id' => 288,
            'paket_id' => 3,
            'layanan_id' => 413,
            'product_logo' => '/assets/product_logo/WeswhbwGJIzxyBh.webp',
            'created_at' => '2025-06-23 02:08:07',
            'updated_at' => '2025-06-23 02:08:07'
        ],
        [
            'id' => 287,
            'paket_id' => 3,
            'layanan_id' => 412,
            'product_logo' => '/assets/product_logo/WeswhbwGJIzxyBh.webp',
            'created_at' => '2025-06-23 02:08:07',
            'updated_at' => '2025-06-23 02:08:07'
        ],
        [
            'id' => 286,
            'paket_id' => 3,
            'layanan_id' => 411,
            'product_logo' => '/assets/product_logo/WeswhbwGJIzxyBh.webp',
            'created_at' => '2025-06-23 02:08:07',
            'updated_at' => '2025-06-23 02:08:07'
        ],
        [
            'id' => 227,
            'paket_id' => 2,
            'layanan_id' => 386,
            'product_logo' => '/assets/product_logo/Phm8PxFylf1WQxA.png',
            'created_at' => '2025-05-05 22:30:14',
            'updated_at' => '2025-05-05 22:30:14'
        ],
        [
            'id' => 228,
            'paket_id' => 2,
            'layanan_id' => 387,
            'product_logo' => '/assets/product_logo/Phm8PxFylf1WQxA.png',
            'created_at' => '2025-05-05 22:30:14',
            'updated_at' => '2025-05-05 22:30:14'
        ],
        [
            'id' => 229,
            'paket_id' => 2,
            'layanan_id' => 388,
            'product_logo' => '/assets/product_logo/Phm8PxFylf1WQxA.png',
            'created_at' => '2025-05-05 22:30:14',
            'updated_at' => '2025-05-05 22:30:14'
        ],
        [
            'id' => 230,
            'paket_id' => 2,
            'layanan_id' => 389,
            'product_logo' => '/assets/product_logo/Phm8PxFylf1WQxA.png',
            'created_at' => '2025-05-05 22:30:14',
            'updated_at' => '2025-05-05 22:30:14'
        ],
        [
            'id' => 231,
            'paket_id' => 3,
            'layanan_id' => 2,
            'product_logo' => '/assets/product_logo/3qUIVdumfH1s5Ql.webp',
            'created_at' => '2025-05-05 22:34:19',
            'updated_at' => '2025-05-05 22:34:19'
        ],
        [
            'id' => 282,
            'paket_id' => 3,
            'layanan_id' => 407,
            'product_logo' => '/assets/product_logo/WeswhbwGJIzxyBh.webp',
            'created_at' => '2025-06-23 02:08:07',
            'updated_at' => '2025-06-23 02:08:07'
        ],
        [
            'id' => 281,
            'paket_id' => 3,
            'layanan_id' => 406,
            'product_logo' => '/assets/product_logo/WeswhbwGJIzxyBh.webp',
            'created_at' => '2025-06-23 02:08:07',
            'updated_at' => '2025-06-23 02:08:07'
        ],
        [
            'id' => 280,
            'paket_id' => 3,
            'layanan_id' => 405,
            'product_logo' => '/assets/product_logo/WeswhbwGJIzxyBh.webp',
            'created_at' => '2025-06-23 02:08:07',
            'updated_at' => '2025-06-23 02:08:07'
        ],
        [
            'id' => 279,
            'paket_id' => 3,
            'layanan_id' => 404,
            'product_logo' => '/assets/product_logo/WeswhbwGJIzxyBh.webp',
            'created_at' => '2025-06-23 02:08:07',
            'updated_at' => '2025-06-23 02:08:07'
        ],
        [
            'id' => 278,
            'paket_id' => 3,
            'layanan_id' => 403,
            'product_logo' => '/assets/product_logo/KhONjAeDmgJVtgf.webp',
            'created_at' => '2025-05-12 04:17:56',
            'updated_at' => '2025-05-12 04:17:56'
        ],
        [
            'id' => 294,
            'paket_id' => 3,
            'layanan_id' => 419,
            'product_logo' => '/assets/product_logo/FfCLU9WotmWRP5g.webp',
            'created_at' => '2025-06-23 06:59:13',
            'updated_at' => '2025-06-23 06:59:13'
        ],
        [
            'id' => 295,
            'paket_id' => 3,
            'layanan_id' => 420,
            'product_logo' => '/assets/product_logo/FfCLU9WotmWRP5g.webp',
            'created_at' => '2025-06-23 06:59:13',
            'updated_at' => '2025-06-23 06:59:13'
        ],
        [
            'id' => 305,
            'paket_id' => 3,
            'layanan_id' => 430,
            'product_logo' => '/assets/product_logo/FfCLU9WotmWRP5g.webp',
            'created_at' => '2025-06-23 06:59:14',
            'updated_at' => '2025-06-23 06:59:14'
        ],
        [
            'id' => 302,
            'paket_id' => 3,
            'layanan_id' => 427,
            'product_logo' => '/assets/product_logo/FfCLU9WotmWRP5g.webp',
            'created_at' => '2025-06-23 06:59:14',
            'updated_at' => '2025-06-23 06:59:14'
        ],
        [
            'id' => 299,
            'paket_id' => 3,
            'layanan_id' => 424,
            'product_logo' => '/assets/product_logo/FfCLU9WotmWRP5g.webp',
            'created_at' => '2025-06-23 06:59:13',
            'updated_at' => '2025-06-23 06:59:13'
        ],
        [
            'id' => 272,
            'paket_id' => 2,
            'layanan_id' => 271,
            'product_logo' => '/assets/product_logo/8Bxj1TMYNvcBUkK.webp',
            'created_at' => '2025-05-08 15:21:24',
            'updated_at' => '2025-05-08 15:21:24'
        ],
        [
            'id' => 260,
            'paket_id' => 3,
            'layanan_id' => 278,
            'product_logo' => '/assets/product_logo/bmkuO4ERDjyP4Km.jpg',
            'created_at' => '2025-05-08 15:18:46',
            'updated_at' => '2025-05-08 15:18:46'
        ],
        [
            'id' => 261,
            'paket_id' => 3,
            'layanan_id' => 279,
            'product_logo' => '/assets/product_logo/bmkuO4ERDjyP4Km.jpg',
            'created_at' => '2025-05-08 15:18:46',
            'updated_at' => '2025-05-08 15:18:46'
        ],
        [
            'id' => 262,
            'paket_id' => 3,
            'layanan_id' => 280,
            'product_logo' => '/assets/product_logo/bmkuO4ERDjyP4Km.jpg',
            'created_at' => '2025-05-08 15:18:46',
            'updated_at' => '2025-05-08 15:18:46'
        ],
        [
            'id' => 263,
            'paket_id' => 3,
            'layanan_id' => 282,
            'product_logo' => '/assets/product_logo/bmkuO4ERDjyP4Km.jpg',
            'created_at' => '2025-05-08 15:18:46',
            'updated_at' => '2025-05-08 15:18:46'
        ],
        [
            'id' => 257,
            'paket_id' => 3,
            'layanan_id' => 283,
            'product_logo' => '/assets/product_logo/bmkuO4ERDjyP4Km.jpg',
            'created_at' => '2025-05-08 15:18:46',
            'updated_at' => '2025-05-08 15:18:46'
        ],
        [
            'id' => 331,
            'paket_id' => 3,
            'layanan_id' => 446,
            'product_logo' => '/assets/product_logo/tvqEeHLoJetCXCi.webp',
            'created_at' => '2025-06-26 01:22:32',
            'updated_at' => '2025-06-26 01:22:32'
        ],
        [
            'id' => 330,
            'paket_id' => 3,
            'layanan_id' => 445,
            'product_logo' => '/assets/product_logo/tvqEeHLoJetCXCi.webp',
            'created_at' => '2025-06-26 01:22:32',
            'updated_at' => '2025-06-26 01:22:32'
        ],
        [
            'id' => 329,
            'paket_id' => 3,
            'layanan_id' => 444,
            'product_logo' => '/assets/product_logo/tvqEeHLoJetCXCi.webp',
            'created_at' => '2025-06-26 01:22:32',
            'updated_at' => '2025-06-26 01:22:32'
        ],
        [
            'id' => 328,
            'paket_id' => 3,
            'layanan_id' => 443,
            'product_logo' => '/assets/product_logo/tvqEeHLoJetCXCi.webp',
            'created_at' => '2025-06-26 01:22:32',
            'updated_at' => '2025-06-26 01:22:32'
        ],
        [
            'id' => 327,
            'paket_id' => 3,
            'layanan_id' => 442,
            'product_logo' => '/assets/product_logo/tvqEeHLoJetCXCi.webp',
            'created_at' => '2025-06-26 01:22:32',
            'updated_at' => '2025-06-26 01:22:32'
        ],
        [
            'id' => 326,
            'paket_id' => 3,
            'layanan_id' => 441,
            'product_logo' => '/assets/product_logo/tvqEeHLoJetCXCi.webp',
            'created_at' => '2025-06-26 01:22:32',
            'updated_at' => '2025-06-26 01:22:32'
        ],
        [
            'id' => 325,
            'paket_id' => 3,
            'layanan_id' => 440,
            'product_logo' => '/assets/product_logo/tvqEeHLoJetCXCi.webp',
            'created_at' => '2025-06-26 01:22:32',
            'updated_at' => '2025-06-26 01:22:32'
        ],
        [
            'id' => 324,
            'paket_id' => 3,
            'layanan_id' => 439,
            'product_logo' => '/assets/product_logo/tvqEeHLoJetCXCi.webp',
            'created_at' => '2025-06-26 01:22:32',
            'updated_at' => '2025-06-26 01:22:32'
        ],
        [
            'id' => 323,
            'paket_id' => 3,
            'layanan_id' => 438,
            'product_logo' => '/assets/product_logo/tvqEeHLoJetCXCi.webp',
            'created_at' => '2025-06-26 01:22:32',
            'updated_at' => '2025-06-26 01:22:32'
        ],
        [
            'id' => 322,
            'paket_id' => 3,
            'layanan_id' => 437,
            'product_logo' => '/assets/product_logo/tvqEeHLoJetCXCi.webp',
            'created_at' => '2025-06-26 01:22:32',
            'updated_at' => '2025-06-26 01:22:32'
        ],
        [
            'id' => 321,
            'paket_id' => 3,
            'layanan_id' => 436,
            'product_logo' => '/assets/product_logo/tvqEeHLoJetCXCi.webp',
            'created_at' => '2025-06-26 01:22:32',
            'updated_at' => '2025-06-26 01:22:32'
        ],
        [
            'id' => 320,
            'paket_id' => 3,
            'layanan_id' => 435,
            'product_logo' => '/assets/product_logo/tvqEeHLoJetCXCi.webp',
            'created_at' => '2025-06-26 01:22:32',
            'updated_at' => '2025-06-26 01:22:32'
        ],
        [
            'id' => 332,
            'paket_id' => 2,
            'layanan_id' => 433,
            'product_logo' => '/assets/product_logo/k12emQZ3UQc8pfh.webp',
            'created_at' => '2025-06-26 01:23:49',
            'updated_at' => '2025-06-26 01:23:49'
        ],
        [
            'id' => 333,
            'paket_id' => 2,
            'layanan_id' => 434,
            'product_logo' => '/assets/product_logo/kO8dxbek2Qxe9cE.webp',
            'created_at' => '2025-06-26 01:24:54',
            'updated_at' => '2025-06-26 01:24:54'
        ],
        [
            'id' => 334,
            'paket_id' => 2,
            'layanan_id' => 447,
            'product_logo' => '/assets/product_logo/ZAS8qx5IaR5h5rj.jpg',
            'created_at' => '2025-07-03 23:04:45',
            'updated_at' => '2025-07-03 23:04:45'
        ],
        [
            'id' => 335,
            'paket_id' => 2,
            'layanan_id' => 448,
            'product_logo' => '/assets/product_logo/cpdsMauaj9tcLfb.jpg',
            'created_at' => '2025-07-04 04:34:34',
            'updated_at' => '2025-07-04 04:34:34'
        ],
        [
            'id' => 337,
            'paket_id' => 3,
            'layanan_id' => 450,
            'product_logo' => '/assets/product_logo/5ypvZptT38wENsU.webp',
            'created_at' => '2025-09-04 03:58:16',
            'updated_at' => '2025-09-04 03:58:16'
        ],
        [
            'id' => 338,
            'paket_id' => 3,
            'layanan_id' => 451,
            'product_logo' => '/assets/product_logo/5ypvZptT38wENsU.webp',
            'created_at' => '2025-09-04 03:58:16',
            'updated_at' => '2025-09-04 03:58:16'
        ],
        [
            'id' => 339,
            'paket_id' => 3,
            'layanan_id' => 452,
            'product_logo' => '/assets/product_logo/5ypvZptT38wENsU.webp',
            'created_at' => '2025-09-04 03:58:16',
            'updated_at' => '2025-09-04 03:58:16'
        ],
        [
            'id' => 340,
            'paket_id' => 3,
            'layanan_id' => 453,
            'product_logo' => '/assets/product_logo/5ypvZptT38wENsU.webp',
            'created_at' => '2025-09-04 03:58:16',
            'updated_at' => '2025-09-04 03:58:16'
        ],
        [
            'id' => 341,
            'paket_id' => 3,
            'layanan_id' => 454,
            'product_logo' => '/assets/product_logo/5ypvZptT38wENsU.webp',
            'created_at' => '2025-09-04 03:58:16',
            'updated_at' => '2025-09-04 03:58:16'
        ],
        ]);
    }
}