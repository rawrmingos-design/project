<?php

namespace Tests\Feature;

use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\ProviderPath;
use App\Services\ResetDomainService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderSwitchEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_accepts_a_candidate_with_same_layanan_same_sku_different_provider_and_active_status(): void
    {
        $service = app(ResetDomainService::class);
        $currentLayanan = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $candidate = $this->createProviderPath($currentLayanan, 'vip', 'VIP-WP', status: 'available');
        $pembelian = $this->createEligiblePembelian($currentLayanan);

        $validated = $service->validateProviderSwitch($pembelian, $candidate->id);

        $this->assertTrue($validated->is($candidate));
    }

    public function test_legacy_transaction_without_active_provider_fields_can_still_validate_switch_when_current_context_is_deterministic(): void
    {
        $service = app(ResetDomainService::class);
        $sourceLayanan = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $candidate = $this->createProviderPath($sourceLayanan, 'vip', 'VIP-WP', status: 'available');
        $pembelian = $this->createEligiblePembelian(activeLayanan: null, overrides: [
            'order_id' => 'INV-LEGACY-VALIDATE-001',
        ]);

        $validated = $service->validateProviderSwitch($pembelian, $candidate->id);

        $this->assertTrue($validated->is($candidate));
    }

    public function test_it_rejects_same_provider_mismatched_name_mismatched_sku_and_inactive_candidates(): void
    {
        $service = app(ResetDomainService::class);
        $currentLayanan = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $pembelian = $this->createEligiblePembelian($currentLayanan);

        $sameProvider = $this->createProviderPath($currentLayanan, 'digiflazz', 'SKU-WP');
        $wrongNameLayanan = $this->createLayanan('Monthly Pass', 'SKU-WP', 'vip');
        $wrongName = $this->createProviderPath($wrongNameLayanan, 'vip', 'VIP-MONTHLY');
        $inactive = $this->createProviderPath($currentLayanan, 'bangjeff', 'BJ-WP', status: 'inactive');

        foreach ([
            [$sameProvider->id, 'different provider'],
            [$wrongName->id, 'same layanan'],
            [$inactive->id, 'active or available'],
        ] as [$candidateId, $messageFragment]) {
            try {
                $service->validateProviderSwitch($pembelian, $candidateId);
                $this->fail('Expected provider switch validation to fail.');
            } catch (DomainException $exception) {
                $this->assertStringContainsString($messageFragment, $exception->getMessage());
            }
        }
    }

    public function test_it_rejects_ambiguous_matches_and_reset_ineligible_transactions(): void
    {
        $service = app(ResetDomainService::class);
        $currentLayanan = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $ambiguous = $this->createProviderPath($currentLayanan, 'vip', 'VIP-WP');
        $this->createProviderPath($currentLayanan, 'vip', 'VIP-WP');

        $eligiblePembelian = $this->createEligiblePembelian($currentLayanan, orderId: 'INV-SWITCH-ELIGIBLE-001');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('ambiguous');

        $service->validateProviderSwitch($eligiblePembelian, $ambiguous->id);
    }

    public function test_it_rejects_transactions_that_fail_existing_reset_eligibility_rules(): void
    {
        $service = app(ResetDomainService::class);
        $currentLayanan = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $candidate = $this->createProviderPath($currentLayanan, 'vip', 'VIP-WP');
        $pembelian = $this->createEligiblePembelian($currentLayanan, overrides: [
            'reset_status' => 'processing',
            'order_id' => 'INV-SWITCH-INFLIGHT-001',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('not eligible for reset');

        $service->validateProviderSwitch($pembelian, $candidate->id);
    }

    public function test_it_rejects_ambiguous_legacy_current_provider_resolution_cleanly(): void
    {
        $service = app(ResetDomainService::class);
        $firstLayanan = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $candidate = $this->createProviderPath($firstLayanan, 'vip', 'VIP-WP');
        $this->createLayanan('Weekly Pass', 'SKU-WP', 'manual');
        $pembelian = $this->createEligiblePembelian(activeLayanan: null, overrides: [
            'order_id' => 'INV-LEGACY-AMBIGUOUS-001',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('ambiguous for reset validation');

        $service->validateProviderSwitch($pembelian, $candidate->id);
    }

    private function createEligiblePembelian(?Layanan $activeLayanan = null, array $overrides = [], ?string $orderId = null): Pembelian
    {
        $orderId ??= 'INV-RESET-' . substr(str_replace('.', '', (string) microtime(true)), -8);

        $pembelian = Pembelian::create(array_merge([
            'order_id' => $orderId,
            'username' => 'reset-user',
            'user_id' => '10001',
            'zone' => '2001',
            'nickname' => 'Reset User',
            'layanan' => $activeLayanan?->layanan ?? 'Weekly Pass',
            'active_layanan_id' => $activeLayanan?->id,
            'active_provider_code' => $activeLayanan?->provider,
            'active_provider_sku' => $activeLayanan?->provider_id,
            'harga' => 15000,
            'profit' => 1000,
            'status' => 'Failed',
            'tipe_transaksi' => 'game',
        ], $overrides));

        Pembayaran::create([
            'order_id' => $pembelian->order_id,
            'harga' => '15000',
            'no_pembayaran' => '08123456789',
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'QRIS',
            'reference' => 'REF-' . $pembelian->order_id,
        ]);

        return $pembelian->fresh(['activeLayanan', 'pembayaran']);
    }

    private function createLayanan(string $layanan, string $providerId, string $provider, string $status = 'active'): Layanan
    {
        return Layanan::create([
            'kategori_id' => '1',
            'layanan' => $layanan,
            'provider_id' => $providerId,
            'harga' => 15000,
            'harga_member' => 14500,
            'harga_platinum' => 14000,
            'harga_gold' => 13500,
            'profit_member' => 500,
            'profit_platinum' => 400,
            'profit_gold' => 300,
            'status' => $status,
            'provider' => $provider,
            'catatan' => 'Test service',
            'is_flash_sale' => 0,
        ]);
    }

    private function createProviderPath(Layanan $layanan, string $providerCode, string $providerSku, string $status = 'available'): ProviderPath
    {
        return ProviderPath::create([
            'layanan_id' => $layanan->id,
            'provider_code' => $providerCode,
            'provider_sku' => $providerSku,
            'modal_price' => 10000,
            'priority' => 1,
            'status' => $status,
        ]);
    }
}
