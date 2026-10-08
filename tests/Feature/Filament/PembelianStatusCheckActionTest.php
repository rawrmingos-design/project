<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Pembelians\Pages\ListPembelians;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\AdminTestCase;

/**
 * Admin copy for the provider status-check action.
 *
 * The action key stays `retry` (and the dispatch mode stays `retry_status`) because both are
 * internal contracts, but what the admin SEES must describe what actually happens: the job
 * queries the provider for the current status and syncs the result. It does NOT resend the
 * order, so the label must not be "Retry Order" — that wording made admins expect a resend.
 */
class PembelianStatusCheckActionTest extends AdminTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_status_check_action_is_labelled_cek_status(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $pembelian = $this->createRetryablePembelian('INV-CEK-STATUS-001');

        Livewire::test(ListPembelians::class)
            ->assertTableActionHasLabel('retry', 'Cek Status', $pembelian);
    }

    /**
     * Detail-page coverage for the same label lives in ViewPembelianRetryHintTest, which
     * seeds the VIP-without-reference case that makes the retry hint field render at all.
     */

    /**
     * Guards the wording itself: "Retry Order" promised a resend the action never performs.
     */
    public function test_retry_order_wording_is_gone_from_admin_resources(): void
    {
        foreach ([
            app_path('Filament/Admin/Resources/Pembelians/Tables/PembeliansTable.php'),
            app_path('Filament/Admin/Resources/ResellerOrders/Tables/ResellerOrdersTable.php'),
        ] as $path) {
            $this->assertFileExists($path);
            $this->assertStringNotContainsString(
                'Retry Order',
                (string) file_get_contents($path),
                "Label 'Retry Order' masih ada di {$path}",
            );
        }
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'Admin',
        ]);
    }

    private function createRetryablePembelian(string $orderId): Pembelian
    {
        $pembelian = Pembelian::create([
            'order_id' => $orderId,
            'username' => 'status-check-user',
            'user_id' => '123456',
            'zone' => '1234',
            'nickname' => 'Status Check User',
            'layanan' => 'Weekly Pass',
            'harga' => 15000,
            'profit' => 500,
            'status' => 'Failed',
            'tipe_transaksi' => 'game',
        ]);

        Pembayaran::create([
            'order_id' => $pembelian->order_id,
            'harga' => 15000,
            'no_pembayaran' => 'PAY-' . $orderId,
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'QRIS',
            'reference' => 'REF-' . $orderId,
            'expired_at' => now()->addHour(),
        ]);

        return $pembelian->fresh(['pembayaran']);
    }
}
