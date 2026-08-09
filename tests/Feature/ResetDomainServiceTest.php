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

class ResetDomainServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_executes_a_reset_transaction_without_mutating_canonical_identity(): void
    {
        $service = app(ResetDomainService::class);
        $currentLayanan = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $candidate = $this->createProviderPath($currentLayanan, 'vip', 'VIP-WP');
        $pembelian = $this->createEligiblePembelian($currentLayanan);

        $resetPembelian = $service->executeReset($pembelian, $candidate->id, requestedBy: 99, reason: 'Switch provider after failed attempt');

        $this->assertSame('INV-RESET-EXEC-001', $resetPembelian->order_id);
        $this->assertSame('Weekly Pass', $resetPembelian->layanan);
        $this->assertSame('INV-RESET-EXEC-001', $resetPembelian->base_order_id);
        $this->assertSame(1, $resetPembelian->invoice_version);
        $this->assertSame(1, $resetPembelian->reset_count);
        $this->assertSame('INV-RESET-EXEC-001_001', $resetPembelian->display_order_id);
        $this->assertSame('INV-RESET-EXEC-001_001', $resetPembelian->active_attempt_reference);
        $this->assertSame($currentLayanan->id, $resetPembelian->active_layanan_id);
        $this->assertSame('vip', $resetPembelian->active_provider_code);
        $this->assertSame('VIP-WP', $resetPembelian->active_provider_sku);
        $this->assertSame('requested', $resetPembelian->reset_status);
        $this->assertSame(99, $resetPembelian->reset_requested_by);
        $this->assertSame('Switch provider after failed attempt', $resetPembelian->reset_reason);
        $this->assertNotNull($resetPembelian->reset_requested_at);
    }

    public function test_legacy_transaction_without_active_provider_fields_can_execute_reset_when_current_context_is_deterministic(): void
    {
        $service = app(ResetDomainService::class);
        $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $sourceLayanan = Layanan::query()->where('layanan', 'Weekly Pass')->firstOrFail();
        $candidate = $this->createProviderPath($sourceLayanan, 'vip', 'VIP-WP');
        $pembelian = $this->createEligiblePembelian(activeLayanan: null, overrides: [
            'order_id' => 'INV-RESET-LEGACY-001',
        ]);

        $resetPembelian = $service->executeReset($pembelian, $candidate->id, reason: 'Legacy fallback reset');

        $this->assertSame('INV-RESET-LEGACY-001', $resetPembelian->order_id);
        $this->assertSame('INV-RESET-LEGACY-001_001', $resetPembelian->active_attempt_reference);
        $this->assertSame($sourceLayanan->id, $resetPembelian->active_layanan_id);
        $this->assertSame('vip', $resetPembelian->active_provider_code);
        $this->assertSame('VIP-WP', $resetPembelian->active_provider_sku);
        $this->assertSame('Legacy fallback reset', $resetPembelian->reset_reason);
    }

    public function test_it_executes_a_reset_with_current_provider_when_no_candidate_is_supplied(): void
    {
        $service = app(ResetDomainService::class);
        $currentLayanan = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $pembelian = $this->createEligiblePembelian($currentLayanan, overrides: [
            'order_id' => 'INV-RESET-SAME-PROVIDER-001',
            'profit' => 1250,
        ]);

        $resetPembelian = $service->executeReset($pembelian, null, requestedBy: 77, reason: 'Retry current provider after paid failure');

        $this->assertSame('INV-RESET-SAME-PROVIDER-001', $resetPembelian->order_id);
        $this->assertSame('INV-RESET-SAME-PROVIDER-001', $resetPembelian->base_order_id);
        $this->assertSame(1, $resetPembelian->invoice_version);
        $this->assertSame(1, $resetPembelian->reset_count);
        $this->assertSame('INV-RESET-SAME-PROVIDER-001_001', $resetPembelian->display_order_id);
        $this->assertSame('INV-RESET-SAME-PROVIDER-001_001', $resetPembelian->active_attempt_reference);
        $this->assertSame($currentLayanan->id, $resetPembelian->active_layanan_id);
        $this->assertSame('digiflazz', $resetPembelian->active_provider_code);
        $this->assertSame('SKU-WP', $resetPembelian->active_provider_sku);
        $this->assertSame(1250, $resetPembelian->profit);
        $this->assertNull($resetPembelian->active_attempt_token);
        $this->assertSame('requested', $resetPembelian->reset_status);
        $this->assertSame(77, $resetPembelian->reset_requested_by);
        $this->assertSame('Retry current provider after paid failure', $resetPembelian->reset_reason);
    }

    public function test_it_rejects_invalid_provider_switch_candidates_during_reset_execution(): void
    {
        $service = app(ResetDomainService::class);
        $currentLayanan = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $invalidCandidate = $this->createProviderPath($currentLayanan, 'digiflazz', 'SKU-WP');
        $pembelian = $this->createEligiblePembelian($currentLayanan);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('different provider');

        $service->executeReset($pembelian, $invalidCandidate->id);
    }

    public function test_it_rejects_a_retired_provider_switch_candidate(): void
    {
        $service = app(ResetDomainService::class);
        $currentLayanan = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $retiredCandidate = $this->createProviderPath($currentLayanan, 'topupedia', 'OLD-WP', 'unavailable');
        $pembelian = $this->createEligiblePembelian($currentLayanan);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('has been retired');

        $service->validateProviderSwitch($pembelian, $retiredCandidate);
    }

    public function test_it_rechecks_reset_eligibility_before_persisting_changes(): void
    {
        $service = app(ResetDomainService::class);
        $currentLayanan = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $candidate = $this->createProviderPath($currentLayanan, 'vip', 'VIP-WP');
        $pembelian = $this->createEligiblePembelian($currentLayanan, overrides: [
            'reset_status' => 'processing',
            'order_id' => 'INV-RESET-EXEC-LOCKED-001',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('not eligible for reset');

        $service->executeReset($pembelian, $candidate->id);
    }

    private function createEligiblePembelian(?Layanan $activeLayanan = null, array $overrides = []): Pembelian
    {
        $pembelian = Pembelian::create(array_merge([
            'order_id' => 'INV-RESET-EXEC-001',
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
