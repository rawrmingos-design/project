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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // e.g., transaction_success
            $table->string('name'); // Human readable name
            $table->string('subject'); // Email Subject
            $table->text('details')->nullable(); // Description of variables
            $table->text('content'); // The message template (HTML)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default templates
        DB::table('email_templates')->insert([
            [
                'slug' => 'transaction_pending',
                'name' => 'Transaksi Pending',
                'subject' => 'Menunggu Pembayaran #{order_id}',
                'details' => '_Variables: {order_id}, {nickname}, {product}, {amount}, {status}_',
                'content' => "<p>Halo <strong>{nickname}</strong>,</p><p>Terima kasih telah melakukan pemesanan.</p><p>Berikut adalah detail pesanan Anda:</p><ul><li>No Invoice: <strong>{order_id}</strong></li><li>Produk: {product}</li><li>Nominal: {amount}</li><li>Status: <strong>{status}</strong></li></ul><p>Silakan selesaikan pembayaran agar pesanan dapat diproses otomatis.</p><p>Terima Kasih,<br>TopUpIndo</p>",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'transaction_success',
                'name' => 'Transaksi Sukses',
                'subject' => 'Pesanan Berhasil #{order_id}',
                'details' => '_Variables: {order_id}, {nickname}, {product}, {amount}, {sn}, {note}_',
                'content' => "<p>Halo <strong>{nickname}</strong>,</p><p>Pesanan Anda telah berhasil diproses.</p><p>Detail Pesanan:</p><ul><li>No Invoice: <strong>{order_id}</strong></li><li>Produk: {product}</li><li>SN/Kode: <strong>{sn}</strong></li><li>Status: <strong>Success</strong></li></ul><p>{note}</p><p>Terima kasih telah berbelanja.</p>",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'transaction_failed',
                'name' => 'Transaksi Gagal',
                'subject' => 'Pesanan Gagal #{order_id}',
                'details' => '_Variables: {order_id}, {nickname}, {product}, {amount}, {note}_',
                'content' => "<p>Halo <strong>{nickname}</strong>,</p><p>Mohon maaf, pesanan Anda gagal atau dibatalkan.</p><p>Detail Pesanan:</p><ul><li>No Invoice: <strong>{order_id}</strong></li><li>Produk: {product}</li><li>Status: <strong>Failed</strong></li></ul><p>Alasan: {note}</p><p>Silakan hubungi Admin jika ada kendala.</p>",
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
        Schema::dropIfExists('email_templates');
    }
};
