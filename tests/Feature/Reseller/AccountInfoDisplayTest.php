<?php

namespace Tests\Feature\Reseller;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountInfoDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Authenticated users should receive current_user data for account info display.
     */
    public function test_authenticated_user_receives_account_info_section_data(): void
    {
        $user = User::factory()->create([
            'username' => 'reseller_candidate',
            'email' => 'candidate@example.com',
            'no_wa' => '081234567890',
            'role' => 'Member',
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->actingAs($user)->get('/id/reseller/registry');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reseller/Registry', false)
            ->has('current_user')
            ->where('current_user.username', 'reseller_candidate')
            ->where('current_user.email', 'candidate@example.com')
            ->where('current_user.phone', '081234567890')
            ->where('current_user.role', 'Member')
        );
    }

    /**
     * Guest users should receive null current_user so frontend can show guest state.
     */
    public function test_guest_does_not_receive_account_info_section_data(): void
    {
        $response = $this->get('/id/reseller/registry');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reseller/Registry', false)
            ->where('current_user', null)
        );
    }

    /**
     * Account info should use the authenticated user's actual values.
     */
    public function test_account_info_shows_correct_user_data(): void
    {
        $user = User::factory()->create([
            'username' => 'exact_user',
            'email' => 'exact-user@example.test',
            'no_wa' => '089999999999',
            'role' => 'Member',
            'created_at' => now()->subDays(8),
        ]);

        $response = $this->actingAs($user)->get('/id/reseller/registry');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reseller/Registry', false)
            ->where('current_user', [
                'username' => 'exact_user',
                'email' => 'exact-user@example.test',
                'phone' => '089999999999',
                'role' => 'Member',
            ])
        );
    }

    /**
     * Missing phone number should be represented as null, not break the account info payload.
     */
    public function test_account_info_allows_null_phone(): void
    {
        $user = User::factory()->create([
            'username' => 'no_phone_user',
            'email' => 'no-phone@example.test',
            'no_wa' => null,
            'role' => 'Member',
            'created_at' => now()->subDays(8),
        ]);

        $response = $this->actingAs($user)->get('/id/reseller/registry');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reseller/Registry', false)
            ->where('current_user.username', 'no_phone_user')
            ->where('current_user.phone', null)
        );
    }
}
