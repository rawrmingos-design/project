<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // e.g., transaction_success
            $table->string('name'); // Human readable name
            $table->text('details')->nullable(); // Description of variables
            $table->text('content'); // The message template
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default templates
        DB::table('whatsapp_templates')->insert([
            [
                'slug' => 'transaction_pending',
                'name' => 'Transaksi Pending',
                'details' => '_Variables: {order_id}, {nickname}, {product}, {amount}, {status}_',
                'content' => "*Konfirmasi Pesanan*\n\nTerima kasih telah melakukan pemesanan di *TopUpIndo*.\n\nNo Invoice: *{order_id}*\nProduk: *{product}*\nNominal: *{amount}*\nStatus: *{status}*\n\nSilakan selesaikan pembayaran agar pesanan dapat diproses otomatis.\n\nTerima Kasih,\nTopUpIndo",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'transaction_success',
                'name' => 'Transaksi Sukses',
                'details' => '_Variables: {order_id}, {nickname}, {product}, {amount}, {sn}_',
                'content' => "*Pesanan Sukses!*\n\nHalo *{nickname}*,\nPesanan kamu telah berhasil diproses:\n\nNo Invoice: *{order_id}*\nProduk: *{product}*\nSN/Kode: *{sn}*\n\nTerima kasih telah berbelanja di *TopUpIndo*.\nJangan lupa simpan bukti transaksi ini.",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'transaction_failed',
                'name' => 'Transaksi Gagal / Batal',
                'details' => '_Variables: {order_id}, {nickname}, {product}, {reason}_',
                'content' => "*Pesanan Dibatalkan*\n\nMohon maaf, pesanan dengan No Invoice: *{order_id}* telah dibatalkan.\n\nAlasan: {reason}\n\nSilakan hubungi Admin jika saldo terpotong namun transaksi gagal.\n\nTerima Kasih,\nTopUpIndo",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
