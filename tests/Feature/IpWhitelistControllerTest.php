<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ResellerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IpWhitelistControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createResellerUser()
    {
        $user = User::factory()->create(['role' => 'Member']);
        
        ResellerIntegration::create([
            'user_id' => $user->id,
            'integration_code' => 'TEST-INT-01',
            'is_active' => true,
            'mode' => 'live',
            'allowed_ips' => [],
        ]);

        return $user;
    }

    public function test_reseller_can_add_valid_ip_to_whitelist()
    {
        $user = $this->createResellerUser();

        $response = $this->actingAs($user)->postJson('/id/reseller/ip-whitelist', [
            'ip' => '192.168.1.1'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'IP address berhasil ditambahkan.')
                 ->assertJsonCount(1, 'allowed_ips');

        $this->assertContains('192.168.1.1', $response->json('allowed_ips'));
        
        $integration = ResellerIntegration::where('user_id', $user->id)->first();
        $this->assertContains('192.168.1.1', $integration->allowed_ips);
    }

    public function test_system_rejects_invalid_ip_format()
    {
        $user = $this->createResellerUser();

        $response = $this->actingAs($user)->postJson('/id/reseller/ip-whitelist', [
            'ip' => 'bukan-ip-address'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('ip');

        $this->assertStringContainsString('Format IP tidak valid', $response->json('errors.ip.0'));
    }

    public function test_system_prevents_duplicate_ip_entries()
    {
        $user = $this->createResellerUser();

        $this->actingAs($user)->postJson('/id/reseller/ip-whitelist', [
            'ip' => '10.0.0.1'
        ]);

        $response = $this->actingAs($user)->postJson('/id/reseller/ip-whitelist', [
            'ip' => '10.0.0.1'
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('message', 'IP address sudah ada di whitelist.');
                 
        $integration = ResellerIntegration::where('user_id', $user->id)->first();
        $this->assertCount(1, $integration->allowed_ips);
    }

    public function test_system_enforces_maximum_of_20_whitelisted_ips()
    {
        $user = clone $this->createResellerUser();
        $integration = ResellerIntegration::where('user_id', $user->id)->first();
        
        $ips = [];
        for ($i = 1; $i <= 20; $i++) {
            $ips[] = "10.0.0.$i";
        }
        $integration->allowed_ips = $ips;
        $integration->save();

        $response = $this->actingAs($user)->postJson('/id/reseller/ip-whitelist', [
            'ip' => '10.0.0.21' // The 21st IP
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('message', 'Maksimal 20 IP address yang diizinkan.');
    }

    public function test_reseller_can_delete_whitelisted_ip()
    {
        $user = $this->createResellerUser();
        
        // Add IP first
        $this->actingAs($user)->postJson('/id/reseller/ip-whitelist', [
            'ip' => '192.168.1.1'
        ]);
        
        // Delete IP
        $response = $this->actingAs($user)->deleteJson('/id/reseller/ip-whitelist/192.168.1.1');

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'IP address berhasil dihapus.')
                 ->assertJsonCount(0, 'allowed_ips');

        $integration = ResellerIntegration::where('user_id', $user->id)->first();
        $this->assertEmpty($integration->allowed_ips);
    }
    
    public function test_reseller_can_delete_cidr_format_ip()
    {
        $user = $this->createResellerUser();
        
        // Add CIDR
        $this->actingAs($user)->postJson('/id/reseller/ip-whitelist', [
            'ip' => '192.168.1.0/24'
        ]);
        
        // Delete CIDR, the URL will be encoded
        $encodedIp = urlencode('192.168.1.0/24');
        $response = $this->actingAs($user)->deleteJson("/id/reseller/ip-whitelist/{$encodedIp}");

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'allowed_ips');
    }

    public function test_non_reseller_cannot_access_ip_whitelist_endpoints()
    {
        $nonReseller = User::factory()->create(['role' => 'Member']);

        $response = $this->actingAs($nonReseller)->postJson('/id/reseller/ip-whitelist', [
            'ip' => '192.168.1.1'
        ]);

        $response->assertRedirect(route('dashboard'));
    }
}
