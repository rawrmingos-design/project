<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\AffiliateRequestResource\Pages\ListAffiliateRequests;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\AdminTestCase;

class AffiliateRequestTabsTest extends AdminTestCase
{
    use RefreshDatabase;

    public function test_admin_can_switch_affiliate_request_table_by_status_tabs(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $pendingUser = User::factory()->create([
            'name' => 'Pending Affiliate',
            'affiliate_status' => 'pending',
        ]);
        $activeUser = User::factory()->create([
            'name' => 'Active Affiliate',
            'affiliate_status' => 'active',
        ]);
        $inactiveUser = User::factory()->create([
            'name' => 'Inactive Affiliate',
            'affiliate_status' => 'inactive',
        ]);
        $rejectedUser = User::factory()->create([
            'name' => 'Rejected Affiliate',
            'affiliate_status' => 'rejected',
        ]);

        $component = Livewire::test(ListAffiliateRequests::class)
            ->assertSee('Permintaan')
            ->assertSee('Affiliate Aktif')
            ->assertSee('Belum Affiliate')
            ->assertSee('Ditolak');

        $tabs = $component->instance()->getTabs();

        $this->assertArrayHasKey('pending', $tabs);
        $this->assertArrayHasKey('active', $tabs);
        $this->assertArrayHasKey('inactive', $tabs);
        $this->assertArrayHasKey('rejected', $tabs);

        $component
            ->assertCanSeeTableRecords([$pendingUser])
            ->assertCanNotSeeTableRecords([
                $activeUser,
                $inactiveUser,
                $rejectedUser,
            ])
            ->set('activeTab', 'active')
            ->assertCanSeeTableRecords([$activeUser])
            ->assertCanNotSeeTableRecords([
                $pendingUser,
                $inactiveUser,
                $rejectedUser,
            ])
            ->set('activeTab', 'inactive')
            ->assertCanSeeTableRecords([$inactiveUser])
            ->assertCanNotSeeTableRecords([
                $pendingUser,
                $activeUser,
                $rejectedUser,
            ])
            ->set('activeTab', 'rejected')
            ->assertCanSeeTableRecords([$rejectedUser])
            ->assertCanNotSeeTableRecords([
                $pendingUser,
                $activeUser,
                $inactiveUser,
            ]);
    }

    public function test_review_actions_only_show_for_pending_affiliate_requests(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $pendingUser = User::factory()->create([
            'affiliate_status' => 'pending',
        ]);
        $activeUser = User::factory()->create([
            'affiliate_status' => 'active',
        ]);
        $inactiveUser = User::factory()->create([
            'affiliate_status' => 'inactive',
        ]);
        $rejectedUser = User::factory()->create([
            'affiliate_status' => 'rejected',
        ]);

        Livewire::test(ListAffiliateRequests::class)
            ->assertTableActionVisible('approve', $pendingUser)
            ->assertTableActionVisible('reject', $pendingUser)
            ->set('activeTab', 'active')
            ->assertTableActionHidden('approve', $activeUser)
            ->assertTableActionHidden('reject', $activeUser)
            ->set('activeTab', 'inactive')
            ->assertTableActionHidden('approve', $inactiveUser)
            ->assertTableActionHidden('reject', $inactiveUser)
            ->set('activeTab', 'rejected')
            ->assertTableActionHidden('approve', $rejectedUser)
            ->assertTableActionHidden('reject', $rejectedUser);
    }
}
