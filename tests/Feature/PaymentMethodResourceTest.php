<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Methods\Pages\CreateMethod;
use App\Models\MediaAsset;
use App\Models\Method;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\AdminTestCase;

class PaymentMethodResourceTest extends AdminTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_create_method_with_media_library_populates_images_before_insert(): void
    {
        $admin = $this->createAdminUser();
        $asset = MediaAsset::create([
            'name' => 'Demo Payment Logo',
            'folder' => 'produk',
            'path' => '/assets/payment/demo-logo.png',
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateMethod::class)
            ->fillForm([
                'name' => 'Demo QRIS',
                'code' => 'DEMO-QRIS',
                'keterangan' => 'Metode pembayaran demo untuk pengujian.',
                'tipe' => 'e-walet',
                'payment' => 'duitku',
                'fee_percent' => 1.5,
                'fix_fee' => 1000,
                'min_pembelian' => 10000,
                'max_pembelian' => 500000,
                'statuspayment' => true,
                'images_input_mode' => 'library',
                'images_media_asset_id' => $asset->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $method = Method::query()->where('code', 'DEMO-QRIS')->first();

        $this->assertNotNull($method);
        $this->assertSame('/assets/payment/demo-logo.png', $method->getRawOriginal('images'));
        $this->assertSame('Metode pembayaran demo untuk pengujian.', $method->keterangan);
        $this->assertSame('duitku', $method->payment);
    }

    public function test_create_method_requires_keterangan(): void
    {
        $admin = $this->createAdminUser();
        $asset = MediaAsset::create([
            'name' => 'Manual Payment Logo',
            'folder' => 'produk',
            'path' => '/assets/payment/manual-va.png',
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateMethod::class)
            ->fillForm([
                'name' => 'Demo Virtual Account',
                'code' => 'DEMO-VA',
                'keterangan' => '',
                'tipe' => 'virtual-account',
                'payment' => 'manual',
                'fee_percent' => 0,
                'fix_fee' => 0,
                'min_pembelian' => 10000,
                'max_pembelian' => 500000,
                'statuspayment' => true,
                'images_input_mode' => 'library',
                'images_media_asset_id' => $asset->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['keterangan' => 'required']);

        $this->assertDatabaseMissing('methods', [
            'code' => 'DEMO-VA',
        ]);
    }

    public function test_methods_index_page_loads_for_admin_after_table_changes(): void
    {
        $admin = $this->createAdminUser();

        Method::create([
            'name' => 'Saldo Internal',
            'images' => '/assets/payment/saldo.png',
            'code' => 'SALDO',
            'keterangan' => 'Dipakai untuk pembayaran dengan saldo internal.',
            'tipe' => 'SALDO',
            'payment' => 'manual',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'min_pembelian' => 1000,
            'max_pembelian' => 1000000,
            'statuspayment' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('filament.admin.resources.methods.index'))
            ->assertSuccessful()
            ->assertSee('Payment Methods')
            ->assertSee('Saldo Internal');
    }

    private function createAdminUser(): User
    {
        return User::create([
            'name' => 'Admin Payment Method',
            'username' => 'admin-payment-method',
            'email' => 'admin-payment-method@example.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'balance' => 0,
            'point_balance' => 0,
            'email_verified_at' => now(),
        ]);
    }
}
