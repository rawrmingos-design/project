<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\Rating;
use App\Models\SettingWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceRatingParityTest extends TestCase
{
    use RefreshDatabase;

    private Kategori $category;

    protected function setUp(): void
    {
        parent::setUp();

        SettingWeb::create([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Demo storefront',
            'keywords' => 'top up game',
            'logo_header' => 'assets/logo/logo.webp',
            'logo_footer' => 'assets/logo/footer.webp',
            'logo_favicon' => 'assets/logo/favicon.webp',
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/testweb',
            'url_tiktok' => 'https://tiktok.com/@testweb',
            'url_youtube' => 'https://youtube.com/@testweb',
            'url_fb' => 'https://facebook.com/testweb',
            'topupindo_api' => 'demo-topupindo-key',
            'paydisini_apikey' => 'demo-paydisini-key',
            'order_prefik' => 'TST',
            'warna1' => '#0f172a',
            'warna2' => '#ea580c',
            'warna3' => '#f59e0b',
            'warna4' => '#fb923c',
            'public_theme' => 'bangjeff',
        ]);

        $this->category = Kategori::factory()->create([
            'nama' => 'Mobile Legends',
            'tipe' => 'game',
            'status' => 'active',
        ]);
    }

    public function test_json_rating_submission_creates_one_rating(): void
    {
        $order = $this->createInvoice('INV-RATING-001');

        $response = $this->postJson(route('rating.pembelian', ['order' => $order->order_id]), [
            'bintang' => 5,
            'comment' => 'Pelayanan cepat',
            'kategori_nama' => $this->category->nama,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Terima kasih telah memberikan testimoni!')
            ->assertJsonPath('rating.rating_id', $order->order_id)
            ->assertJsonPath('rating.bintang', '5');

        $this->assertDatabaseHas('ratings', [
            'rating_id' => $order->order_id,
            'kategori_id' => (string) $this->category->id,
            'comment' => 'Pelayanan cepat',
            'username' => $order->username,
        ]);
        $this->assertDatabaseCount('ratings', 1);
    }

    public function test_json_rating_submission_rejects_invalid_payload(): void
    {
        $order = $this->createInvoice('INV-RATING-INVALID');

        $this->postJson(route('rating.pembelian', ['order' => $order->order_id]), [
            'bintang' => 6,
            'comment' => '',
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['bintang', 'comment', 'kategori_nama']]);

        $this->assertDatabaseCount('ratings', 0);
    }

    public function test_duplicate_json_rating_is_rejected_without_creating_another_row(): void
    {
        $order = $this->createInvoice('INV-RATING-DUPLICATE');
        Rating::create([
            'rating_id' => $order->order_id,
            'kategori_id' => (string) $this->category->id,
            'bintang' => '5',
            'comment' => 'Sudah dikirim',
            'username' => $order->username,
            'layanan' => $order->layanan,
            'no_pembeli' => '08123456789',
        ]);

        $this->postJson(route('rating.pembelian', ['order' => $order->order_id]), [
            'bintang' => 4,
            'comment' => 'Kirim ulang',
            'kategori_nama' => $this->category->nama,
        ])->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('already_reviewed', true);

        $this->assertDatabaseCount('ratings', 1);
    }

    public function test_rating_returns_not_found_when_invoice_data_is_incomplete(): void
    {
        $this->postJson(route('rating.pembelian', ['order' => 'INV-RATING-MISSING']), [
            'bintang' => 5,
            'comment' => 'Tidak ada invoice',
            'kategori_nama' => $this->category->nama,
        ])->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_rating_returns_not_found_for_unknown_category(): void
    {
        $order = $this->createInvoice('INV-RATING-CATEGORY');

        $this->postJson(route('rating.pembelian', ['order' => $order->order_id]), [
            'bintang' => 5,
            'comment' => 'Kategori salah',
            'kategori_nama' => 'Unknown category',
        ])->assertStatus(404)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('ratings', 0);
    }

    private function createInvoice(string $orderId): Pembelian
    {
        $order = Pembelian::factory()->create([
            'order_id' => $orderId,
            'username' => 'rating-owner',
            'layanan' => '86 Diamond',
            'harga' => 20000,
            'status' => 'Sukses',
            'tipe_transaksi' => 'game',
        ]);

        Pembayaran::create([
            'order_id' => $orderId,
            'harga' => 20000,
            'no_pembayaran' => 'PAY-' . $orderId,
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'QRIS',
        ]);

        return $order;
    }
}
