<?php

namespace Tests\Feature\Reseller;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GuestAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test guest can view registry page.
     */
    public function test_guest_can_view_registry_page(): void
    {
        $response = $this->get('/id/reseller/registry');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reseller/Registry', false)
        );
    }

    /**
     * Test guest receives null current_user.
     */
    public function test_guest_receives_null_current_user(): void
    {
        $response = $this->get('/id/reseller/registry');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reseller/Registry', false)
            ->where('current_user', null)
            ->has('captcha')
        );
    }

    /**
     * Test guest cannot submit application without authentication.
     */
    public function test_guest_cannot_submit_application(): void
    {
        $response = $this->post('/id/reseller/registry', [
            'business_name' => 'Test Business',
            'business_url' => 'https://testbusiness.com',
            'estimated_monthly_transactions' => 100,
            'application_reason' => 'Want to sell vouchers',
        ]);

        $response->assertRedirect('/id/sign-in');
    }

    /**
     * Test authenticated user receives current_user data.
     */
    public function test_authenticated_user_receives_current_user_data(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->actingAs($user)->get('/id/reseller/registry');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reseller/Registry', false)
            ->where('current_user.username', 'testuser')
            ->where('current_user.email', 'test@example.com')
            ->where('current_user.role', 'Member')
            ->has('current_user.phone')
        );
    }

    /**
     * Test authenticated user with young account blocked on submit, not view.
     */
    public function test_young_account_blocked_on_submit_not_view(): void
    {
        // Disable captcha for this test. The settings table has several NOT NULL columns,
        // so provide the minimum required payload when the row does not exist yet.
        \DB::table('setting_webs')->updateOrInsert(
            ['id' => 1],
            [
                'judul_web' => 'Test App',
                'deskripsi_web' => 'Test description',
                'keywords' => 'test',
                'url_wa' => 'https://wa.me/6281234567890',
                'url_ig' => 'https://instagram.com/test',
                'url_tiktok' => 'https://tiktok.com/@test',
                'url_youtube' => 'https://youtube.com/@test',
                'url_fb' => 'https://facebook.com/test',
                'topupindo_api' => 'test',
                'warna1' => '#000000',
                'warna2' => '#111111',
                'warna3' => '#222222',
                'warna4' => '#333333',
                'paydisini_apikey' => 'test',
                'order_prefik' => 'TST',
                'captcha_bypass' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $user = User::factory()->create([
            'role' => 'Member',
            'created_at' => now()->subDays(3), // Too young
        ]);

        // Can VIEW the page
        $getResponse = $this->actingAs($user)->get('/id/reseller/registry');
        $getResponse->assertStatus(200);

        // Cannot SUBMIT due to eligibility (account age < 7 days)
        $postResponse = $this->actingAs($user)->post('/id/reseller/registry', [
            'business_name' => 'Test Business',
            'business_url' => 'https://test.com',
            'estimated_monthly_transactions' => 100,
            'application_reason' => 'Test reason',
            'identity' => \Illuminate\Http\UploadedFile::fake()->image('ktp.jpg')->size(500),
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg')->size(500),
            'business_proof' => \Illuminate\Http\UploadedFile::fake()->image('proof.jpg')->size(500),
        ]);

        $postResponse->assertSessionHasErrors('eligibility');
    }

    /**
     * Test Gold user redirected on submit (already has reseller access).
     */
    public function test_gold_user_redirected_on_submit(): void
    {
        $user = User::factory()->create([
            'role' => 'Gold',
            'created_at' => now()->subDays(10),
        ]);

        // Can VIEW the page
        $getResponse = $this->actingAs($user)->get('/id/reseller/registry');
        $getResponse->assertStatus(200);

        // Redirected on SUBMIT
        $postResponse = $this->actingAs($user)->post('/id/reseller/registry', [
            'business_name' => 'Test Business',
        ]);

        $postResponse->assertRedirect();
    }
}
