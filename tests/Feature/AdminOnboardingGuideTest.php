<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AdminTestCase;

class AdminOnboardingGuideTest extends AdminTestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_admin_onboarding_guide_for_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('filament.admin.pages.dashboard'));

        $response->assertOk();
        $response->assertSee('Panduan Admin Panel');
        $response->assertSee('Lihat Panduan Lagi');
        $response->assertSee('data-onboarding-guide', false);
        $response->assertSee('assets/admin/onboarding-guide.js', false);
        $response->assertSee('Kenali area penting dashboard admin');
    }

    public function test_order_management_page_renders_its_own_onboarding_guide(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('filament.admin.resources.pembelians.index'));

        $response->assertOk();
        $response->assertSee('Panduan Order Management');
        $response->assertSee('Kelola transaksi dari satu halaman');
        $response->assertSee('data-onboarding-guide', false);
        $response->assertSee('Lihat Panduan Lagi');
    }

    public function test_create_produk_page_renders_its_own_onboarding_guide(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('filament.admin.resources.produks.create'));

        $response->assertOk();
        $response->assertSee('Panduan Buat Produk');
        $response->assertSee('Susun produk baru dengan alur yang jelas');
        $response->assertSee('data-onboarding-guide', false);
    }

    public function test_payment_methods_page_renders_its_own_onboarding_guide(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('filament.admin.resources.methods.index'));

        $response->assertOk();
        $response->assertSee('Panduan Payment Methods');
        $response->assertSee('Kelola metode pembayaran yang tampil di checkout');
        $response->assertSee('data-onboarding-guide', false);
    }

    public function test_settings_page_renders_its_own_onboarding_guide(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('filament.admin.pages.settings'));

        $response->assertOk();
        $response->assertSee('Panduan Settings Hub');
        $response->assertSee('Pilih submenu settings sesuai domain kerja');
        $response->assertSee('Settings Hub');
        $response->assertSee('General');
        $response->assertSee('Membership & Rewards');
        $response->assertSee('data-onboarding-guide', false);
    }

    public function test_settings_general_sub_page_renders_its_own_onboarding_guide(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('filament.admin.pages.settings.general'));

        $response->assertOk();
        $response->assertSee('Settings: General');
        $response->assertSee('Kelola konfigurasi umum website');
        $response->assertSee('data-onboarding-guide', false);
    }

    public function test_other_non_target_admin_page_does_not_render_onboarding_guide(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('filament.admin.resources.produks.index'));

        $response->assertOk();
        $response->assertDontSee('data-onboarding-guide', false);
    }

    public function test_non_admin_cannot_access_dashboard_onboarding_page(): void
    {
        $member = User::factory()->create([
            'role' => 'Member',
        ]);

        $response = $this
            ->actingAs($member)
            ->get(route('filament.admin.pages.dashboard'));

        $response->assertForbidden();
    }
}
