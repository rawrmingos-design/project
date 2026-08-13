<?php

namespace Tests\Feature;

use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionHistoryPageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
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
    }

    #[Test]
    public function bangjeff_history_paginates_beyond_the_previous_120_record_cap(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['username' => 'history-owner']);
        /** @var User $other */
        $other = User::factory()->create(['username' => 'history-other']);

        for ($index = 1; $index <= 126; $index++) {
            $this->createTransaction('INV-OWNER-' . str_pad((string) $index, 3, '0', STR_PAD_LEFT), $owner->username);
        }

        $this->createTransaction('INV-OTHER-001', $other->username);

        $this->actingAs($owner)
            ->get(route('riwayat', ['page' => 6, 'filter' => 'history']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/TransactionHistory')
                ->where('history.pagination.currentPage', 6)
                ->where('history.pagination.lastPage', 6)
                ->where('history.pagination.perPage', 25)
                ->where('history.pagination.total', 126)
                ->where('history.pagination.from', 126)
                ->where('history.pagination.to', 126)
                ->where('history.pagination.nextPageUrl', null)
                ->where('history.pagination.prevPageUrl', fn (?string $url) => str_contains((string) $url, 'page=5'))
                ->has('history.transactions', 1)
                ->where('history.transactions.0.invoiceId', 'INV-OWNER-001')
                ->where('history.transactions.0.invoiceUrl', route('pembelian', ['order' => 'INV-OWNER-001']))
                ->where('history.transactions.0.item', 'Membership')
            );
    }

    #[Test]
    public function history_order_is_deterministic_when_created_at_values_match(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['username' => 'same-time-owner']);
        $createdAt = now()->subDay();

        $olderId = $this->createTransaction('INV-SAME-OLDER', $owner->username, $createdAt);
        $newerId = $this->createTransaction('INV-SAME-NEWER', $owner->username, $createdAt);

        $this->actingAs($owner)
            ->get(route('riwayat'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('history.transactions.0.invoiceId', $newerId->display_order_id)
                ->where('history.transactions.1.invoiceId', $olderId->display_order_id)
            );
    }

    #[Test]
    public function default_theme_history_keeps_the_legacy_view_fallback(): void
    {
        SettingWeb::query()->whereKey(1)->update(['public_theme' => 'default']);
        Cache::flush();
        /** @var User $owner */
        $owner = User::factory()->create(['username' => 'legacy-history-owner']);
        $this->createTransaction('INV-LEGACY-001', $owner->username, 1);

        $this->actingAs($owner)
            ->get(route('riwayat'))
            ->assertOk()
            ->assertViewIs('template.riwayat');
    }

    private function createTransaction(string $orderId, string $username, mixed $createdAt = null): Pembelian
    {
        $transaction = Pembelian::create([
            'order_id' => $orderId,
            'username' => $username,
            'user_id' => '12345678',
            'zone' => '1234',
            'layanan' => 'Membership',
            'harga' => 15000,
            'profit' => 0,
            'status' => 'Sukses',
            'tipe_transaksi' => 'game',
        ]);

        if ($createdAt !== null) {
            $transaction->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->saveQuietly();
        }

        return $transaction;
    }
}
