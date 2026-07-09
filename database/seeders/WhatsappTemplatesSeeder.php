<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WhatsappTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('whatsapp_templates')->truncate();

        DB::table('whatsapp_templates')->insert([
        [
            'id' => 1,
            'slug' => 'transaction_pending',
            'name' => 'Transaksi Pending',
            'details' => '_Variables: {order_id}, {nickname}, {product}, {amount}, {status}_',
            'content' => '*Konfirmasi Pesanan*\n\nTerima kasih telah melakukan pemesanan di *IstanaTopup*.\n\nNo Invoice: *{order_id}*\nProduk: *{product}*\nNominal: *{amount}*\nStatus: *{status}*\n\nSilakan selesaikan pembayaran agar pesanan dapat diproses otomatis.\n\nTerima Kasih,\nIstanaTopup',
            'is_active' => 1,
            'created_at' => '2026-02-07 11:54:59',
            'updated_at' => '2026-02-07 13:37:35'
        ],
        [
            'id' => 2,
            'slug' => 'transaction_success',
            'name' => 'Transaksi Sukses',
            'details' => '_Variables: {order_id}, {nickname}, {product}, {amount}, {sn}_',
            'content' => '*Pesanan Sukses!*\n\nHalo *{nickname}*,\nPesanan kamu telah berhasil diproses:\n\nNo Invoice: *{order_id}*\nProduk: *{product}*\nSN/Kode: *{sn}*\n\nTerima kasih telah berbelanja di *TopUpIndo*.\nJangan lupa simpan bukti transaksi ini.',
            'is_active' => 1,
            'created_at' => '2026-02-07 11:54:59',
            'updated_at' => '2026-02-07 11:54:59'
        ],
        [
            'id' => 3,
            'slug' => 'transaction_failed',
            'name' => 'Transaksi Gagal / Batal',
            'details' => '_Variables: {order_id}, {nickname}, {product}, {reason}_',
            'content' => '*Pesanan Dibatalkan*\n\nMohon maaf, pesanan dengan No Invoice: *{order_id}* telah dibatalkan.\n\nAlasan: {reason}\n\nSilakan hubungi Admin jika saldo terpotong namun transaksi gagal.\n\nTerima Kasih,\nTopUpIndo',
            'is_active' => 1,
            'created_at' => '2026-02-07 11:54:59',
            'updated_at' => '2026-02-07 11:54:59'
        ],
        [
            'id' => 4,
            'slug' => 'tenant_registration_invoice',
            'name' => 'Invoice Reseller Topup Dibuat',
            'details' => '_Variables: {owner_name}, {store_name}, {tier}, {amount}, {payment_url}, {due_date}_',
            'content' => "🧾 *Invoice Reseller Topup Dibuat*\n\nHalo {owner_name}, invoice untuk *{store_name}* sudah dibuat.\nPaket: {tier}\nNominal: {amount}\nJatuh tempo: {due_date}\nBayar: {payment_url}",
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 5,
            'slug' => 'tenant_activated',
            'name' => 'Reseller Topup Aktif',
            'details' => '_Variables: {owner_name}, {store_name}, {tenant_url}, {dashboard_url}_',
            'content' => "✅ *Reseller Topup Aktif*\n\nHalo {owner_name}, website *{store_name}* sudah aktif.\n\nWebsite: {tenant_url}\nDashboard: {dashboard_url}",
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 6,
            'slug' => 'tenant_invoice_expired',
            'name' => 'Invoice Reseller Topup Expired',
            'details' => '_Variables: {owner_name}, {store_name}, {support_url}_',
            'content' => "⚠️ *Invoice Reseller Topup Expired*\n\nHalo {owner_name}, invoice untuk *{store_name}* sudah expired.\nHubungi support: {support_url}",
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        ]);
    }
}