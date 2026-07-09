<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('email_templates')->truncate();

        DB::table('email_templates')->insert([
        [
            'id' => 1,
            'slug' => 'transaction_pending',
            'name' => 'Transaksi Pending',
            'subject' => 'Menunggu Pembayaran #{order_id}',
            'details' => '_Variables: {order_id}, {nickname}, {product}, {amount}, {status}_',
            'content' => '<p>Halo <strong>{nickname}</strong>,</p><p>Terima kasih telah melakukan pemesanan.</p><p>Berikut adalah detail pesanan Anda:</p><ul><li>No Invoice: <strong>{order_id}</strong></li><li>Produk: {product}</li><li>Nominal: {amount}</li><li>Status: <strong>{status}</strong></li></ul><p>Silakan selesaikan pembayaran agar pesanan dapat diproses otomatis.</p><p>Terima Kasih,<br>TopUpIndo</p>',
            'is_active' => 1,
            'created_at' => '2026-02-08 11:07:39',
            'updated_at' => '2026-02-08 11:07:39'
        ],
        [
            'id' => 2,
            'slug' => 'transaction_success',
            'name' => 'Transaksi Sukses',
            'subject' => 'Pesanan Berhasil #{order_id}',
            'details' => '_Variables: {order_id}, {nickname}, {product}, {amount}, {sn}, {note}_',
            'content' => '<p>Halo <strong>{nickname}</strong>,</p><p>Pesanan Anda telah berhasil diproses.</p><p>Detail Pesanan:</p><ul><li>No Invoice: <strong>{order_id}</strong></li><li>Produk: {product}</li><li>SN/Kode: <strong>{sn}</strong></li><li>Status: <strong>Success</strong></li></ul><p>{note}</p><p>Terima kasih telah berbelanja.</p>',
            'is_active' => 1,
            'created_at' => '2026-02-08 11:07:39',
            'updated_at' => '2026-02-08 11:07:39'
        ],
        [
            'id' => 3,
            'slug' => 'transaction_failed',
            'name' => 'Transaksi Gagal',
            'subject' => 'Pesanan Gagal #{order_id}',
            'details' => '_Variables: {order_id}, {nickname}, {product}, {amount}, {note}_',
            'content' => '<p>Halo <strong>{nickname}</strong>,</p><p>Mohon maaf, pesanan Anda gagal atau dibatalkan.</p><p>Detail Pesanan:</p><ul><li>No Invoice: <strong>{order_id}</strong></li><li>Produk: {product}</li><li>Status: <strong>Failed</strong></li></ul><p>Alasan: {note}</p><p>Silakan hubungi Admin jika ada kendala.</p>',
            'is_active' => 1,
            'created_at' => '2026-02-08 11:07:39',
            'updated_at' => '2026-02-08 11:07:39'
        ],
        [
            'id' => 4,
            'slug' => 'tenant_registration_invoice',
            'name' => 'Invoice Reseller Topup Dibuat',
            'subject' => 'Invoice Reseller Topup kamu sudah dibuat',
            'details' => '_Variables: {owner_name}, {store_name}, {tier}, {amount}, {payment_url}, {due_date}_',
            'content' => '<p>Halo <strong>{owner_name}</strong>,</p><p>Invoice Reseller Topup untuk <strong>{store_name}</strong> sudah dibuat.</p><ul><li>Paket: {tier}</li><li>Nominal: {amount}</li><li>Jatuh tempo: {due_date}</li></ul><p>Bayar di sini: <a href="{payment_url}">{payment_url}</a></p>',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 5,
            'slug' => 'tenant_activated',
            'name' => 'Reseller Topup Aktif',
            'subject' => 'Website Reseller Topup kamu sudah aktif',
            'details' => '_Variables: {owner_name}, {store_name}, {tenant_url}, {dashboard_url}_',
            'content' => '<p>Halo <strong>{owner_name}</strong>,</p><p>Website Reseller Topup <strong>{store_name}</strong> sudah aktif.</p><ul><li>Website: <a href="{tenant_url}">{tenant_url}</a></li><li>Dashboard: <a href="{dashboard_url}">{dashboard_url}</a></li></ul><p>Silakan login dan mulai atur toko kamu.</p>',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 6,
            'slug' => 'tenant_invoice_expired',
            'name' => 'Invoice Reseller Topup Expired',
            'subject' => 'Invoice Reseller Topup kamu expired',
            'details' => '_Variables: {owner_name}, {store_name}, {support_url}_',
            'content' => '<p>Halo <strong>{owner_name}</strong>,</p><p>Invoice Reseller Topup untuk <strong>{store_name}</strong> sudah expired.</p><p>Hubungi support untuk membuat invoice baru: <a href="{support_url}">{support_url}</a></p>',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        ]);
    }
}