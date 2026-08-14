<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\Whatsapp\WhatsappUserResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappUserResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_number_resolves_as_linked_without_exposing_unneeded_fields(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);

        $result = app(WhatsappUserResolver::class)->resolve('081234567890');

        $this->assertSame(WhatsappUserResolver::STATUS_LINKED, $result['status']);
        $this->assertSame($user->id, $result['user']->id);
        $this->assertSame('6281234567890', $result['number']);
    }

    public function test_registered_unverified_number_is_not_linked(): void
    {
        User::factory()->create([
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => null,
        ]);

        $result = app(WhatsappUserResolver::class)->resolve('6281234567890');

        $this->assertSame(WhatsappUserResolver::STATUS_REGISTERED_UNVERIFIED, $result['status']);
        $this->assertArrayNotHasKey('user', $result);
    }

    public function test_unregistered_and_invalid_numbers_are_distinguished(): void
    {
        $resolver = app(WhatsappUserResolver::class);

        $this->assertSame(
            WhatsappUserResolver::STATUS_UNREGISTERED,
            $resolver->resolve('081298765432')['status'],
        );
        $this->assertSame(
            WhatsappUserResolver::STATUS_UNAVAILABLE,
            $resolver->resolve('not-a-number')['status'],
        );
    }

    public function test_duplicate_number_fails_closed_as_ambiguous(): void
    {
        User::factory()->count(2)->create([
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);

        $result = app(WhatsappUserResolver::class)->resolve('6281234567890');

        $this->assertSame(WhatsappUserResolver::STATUS_AMBIGUOUS, $result['status']);
        $this->assertArrayNotHasKey('user', $result);
    }

    public function test_cleared_verification_is_not_linked(): void
    {
        User::factory()->create([
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => null,
        ]);

        $result = app(WhatsappUserResolver::class)->resolve('6281234567890');

        $this->assertSame(WhatsappUserResolver::STATUS_REGISTERED_UNVERIFIED, $result['status']);
        $this->assertArrayNotHasKey('user', $result);
    }

    public function test_tenant_context_does_not_resolve_a_number_owned_by_another_tenant(): void
    {
        $ownerA = User::factory()->create(['role' => 'Gold']);
        $tenantA = \App\Models\Tenant::create([
            'name' => 'Store A',
            'subdomain' => 'store-a',
            'status' => \App\Models\Tenant::STATUS_ACTIVE,
            'owner_user_id' => $ownerA->id,
            'tier' => 'starter',
        ]);
        $ownerB = User::factory()->create(['role' => 'Gold']);
        $tenantB = \App\Models\Tenant::create([
            'name' => 'Store B',
            'subdomain' => 'store-b',
            'status' => \App\Models\Tenant::STATUS_ACTIVE,
            'owner_user_id' => $ownerB->id,
            'tier' => 'starter',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenantB->id,
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);
        app(\App\Tenancy\TenantContext::class)->set($tenantA);

        $result = app(WhatsappUserResolver::class)->resolve('6281234567890');

        $this->assertSame(WhatsappUserResolver::STATUS_TENANT_MISMATCH, $result['status']);
        $this->assertArrayNotHasKey('user', $result);
        $this->assertSame($tenantB->id, $user->fresh()->tenant_id);
    }
}
