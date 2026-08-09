<?php

namespace App\Services;

use App\Models\Layanan;
use App\Models\Method;
use App\Models\Pembelian;
use App\Models\ProviderPath;
use App\Http\Controllers\DigiFlazzController;
use App\Support\PembelianStatus;
use App\Support\ProviderRetirement;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResetDomainService
{
    public function getProviderSwitchCandidates(Pembelian $pembelian): Collection
    {
        $sourceLayanan = $this->resolveSourceLayanan($pembelian);
        [$currentProviderCode, $currentProviderSku] = $this->resolveCurrentProviderContext($pembelian, $sourceLayanan);

        return $sourceLayanan->provider_paths()
            ->orderBy('priority')
            ->orderBy('modal_price')
            ->get()
            ->filter(function (ProviderPath $providerPath) use ($pembelian, $sourceLayanan, $currentProviderCode, $currentProviderSku): bool {
                try {
                    $this->resolveValidatedCandidate(
                        $pembelian,
                        (int) $providerPath->getKey(),
                        $currentProviderCode,
                        $currentProviderSku,
                        $sourceLayanan,
                    );

                    return true;
                } catch (DomainException) {
                    return false;
                }
            })
            ->values();
    }

    public function validateProviderSwitch(Pembelian $pembelian, int|ProviderPath $candidate): ProviderPath
    {
        if (! $pembelian->isResetEligible()) {
            throw new DomainException('This transaction is not eligible for reset.');
        }

        $sourceLayanan = $this->resolveSourceLayanan($pembelian);
        $candidateModel = $candidate instanceof ProviderPath
            ? $candidate->loadMissing('layanan')
            : ProviderPath::query()->with('layanan')->find((int) $candidate);

        if (! $candidateModel) {
            throw new DomainException('Selected provider switch target was not found.');
        }

        [$currentProviderCode, $currentProviderSku] = $this->resolveCurrentProviderContext($pembelian, $sourceLayanan);

        return $this->resolveValidatedCandidate(
            $pembelian,
            (int) $candidateModel->getKey(),
            $currentProviderCode,
            $currentProviderSku,
            $sourceLayanan,
        );
    }

    public function executeReset(Pembelian $pembelian, int|ProviderPath|null $candidate = null, ?int $requestedBy = null, ?string $reason = null): Pembelian
    {
        $pembelianId = (int) $pembelian->getKey();
        $candidateId = $candidate instanceof ProviderPath ? (int) $candidate->getKey() : ($candidate !== null ? (int) $candidate : null);

        return DB::transaction(function () use ($pembelianId, $candidateId, $requestedBy, $reason): Pembelian {
            $lockedPembelian = Pembelian::query()
                ->with(['activeLayanan.provider_paths', 'pembayaran'])
                ->lockForUpdate()
                ->find($pembelianId);

            if (! $lockedPembelian) {
                throw new DomainException('Transaction was not found.');
            }

            if (! $lockedPembelian->isResetEligible()) {
                throw new DomainException('This transaction is not eligible for reset.');
            }

            $sourceLayanan = $this->resolveSourceLayanan($lockedPembelian);
            [$currentProviderCode, $currentProviderSku] = $this->resolveCurrentProviderContext($lockedPembelian, $sourceLayanan);

            // Fix Digiflazz double execution bug
            // Before resetting, check if the current attempt is actually successful on Digiflazz's end
            if ($currentProviderCode === 'digiflazz' && $lockedPembelian->active_attempt_reference) {
                $digiFlazz = new DigiFlazzController();
                $response = $digiFlazz->status(
                    $lockedPembelian->active_attempt_reference,
                    $currentProviderSku,
                    $lockedPembelian->user_id,
                    $lockedPembelian->zone
                );

                $statusData = $response['data'] ?? null;
                if ($statusData && isset($statusData['status'])) {
                    $providerStatus = PembelianStatus::normalize($statusData['status']);

                    if (in_array($providerStatus, [PembelianStatus::SUCCESS, PembelianStatus::PENDING, PembelianStatus::PROCESSING], true)) {
                        // The order is actually successful or still processing on the provider side
                        // Instead of resetting, we should update the order status
                        $lockedPembelian->status = PembelianStatus::preferredDatabaseLabel($providerStatus);

                        if ($providerStatus === PembelianStatus::SUCCESS) {
                            $lockedPembelian->keterangan_sn = $statusData['sn'] ?? $statusData['message'] ?? 'Transaksi Sukses';
                        }

                        $lockedPembelian->save();
                        throw new DomainException("Cannot reset: Order is already {$providerStatus} at provider. Status has been synced.");
                    }
                }
            }

            $validatedCandidate = $candidateId !== null
                ? $this->validateProviderSwitch($lockedPembelian, $candidateId)
                : null;
            $nextInvoiceVersion = $lockedPembelian->invoice_version + 1;
            $nextAttemptReference = $lockedPembelian->deriveDisplayInvoiceId($nextInvoiceVersion);
            $nextProviderCode = $validatedCandidate
                ? $this->normalizeProviderCode($validatedCandidate->provider_code)
                : $currentProviderCode;
            $nextProviderSku = $validatedCandidate
                ? trim((string) $validatedCandidate->provider_sku)
                : $currentProviderSku;
            $nextProfit = $validatedCandidate
                ? $this->calculateProfitFromModalPrice($lockedPembelian, $validatedCandidate)
                : $lockedPembelian->profit;

            $lockedPembelian->fill([
                'invoice_version' => $nextInvoiceVersion,
                'reset_count' => max($lockedPembelian->reset_count, $lockedPembelian->invoice_version) + 1,
                'display_order_id' => $nextAttemptReference,
                'active_layanan_id' => $sourceLayanan->getKey(),
                'active_provider_code' => $nextProviderCode,
                'active_provider_sku' => $nextProviderSku,
                'profit' => $nextProfit,
                'active_attempt_token' => null,
                'active_attempt_reference' => $nextAttemptReference,
                'reset_status' => 'requested',
                'reset_requested_by' => $requestedBy,
                'reset_requested_at' => now(),
                'reset_reason' => $this->normalizeNullableText($reason),
            ]);

            $lockedPembelian->save();

            return $lockedPembelian->fresh(['activeLayanan', 'pembayaran']);
        }, 3);
    }

    public function updateResetDetails(
        Pembelian $pembelian,
        ?int $candidateId = null,
        ?string $userId = null,
        ?string $zone = null,
    ): Pembelian
    {
        $pembelianId = (int) $pembelian->getKey();

        return DB::transaction(function () use ($pembelianId, $candidateId, $userId, $zone): Pembelian {
            $lockedPembelian = Pembelian::query()
                ->with(['activeLayanan.provider_paths', 'pembayaran'])
                ->lockForUpdate()
                ->find($pembelianId);

            if (! $lockedPembelian) {
                throw new DomainException('Transaction was not found.');
            }

            if (! $lockedPembelian->isResetEditable()) {
                throw new DomainException('This transaction is not editable outside reset state.');
            }

            $attributes = [
                'user_id' => $this->normalizeNullableText($userId),
                'zone' => $this->normalizeNullableText($zone),
            ];

            if ($candidateId) {
                $sourceLayanan = $this->resolveSourceLayanan($lockedPembelian);
                $validatedCandidate = $this->validateResetEditableProviderSwitch($lockedPembelian, $candidateId, $sourceLayanan);

                $attributes['active_layanan_id'] = $sourceLayanan->getKey();
                $attributes['active_provider_code'] = $this->normalizeProviderCode($validatedCandidate->provider_code);
                $attributes['active_provider_sku'] = trim((string) $validatedCandidate->provider_sku);
                $attributes['profit'] = $this->calculateProfitFromModalPrice($lockedPembelian, $validatedCandidate);
            }

            $lockedPembelian->fill($attributes);
            $lockedPembelian->save();

            return $lockedPembelian->fresh(['activeLayanan', 'pembayaran']);
        }, 3);
    }

    private function resolveValidatedCandidate(
        Pembelian $pembelian,
        int $candidateId,
        string $currentProviderCode,
        string $currentProviderSku,
        ?Layanan $sourceLayanan = null,
    ): ProviderPath {
        $candidate = ProviderPath::query()
            ->with('layanan')
            ->find($candidateId);

        if (! $candidate) {
            throw new DomainException('Selected provider switch target was not found.');
        }

        $sourceLayanan ??= $this->resolveSourceLayanan($pembelian);

        if ((int) $candidate->layanan_id !== (int) $sourceLayanan->getKey()) {
            throw new DomainException('Selected provider switch target must keep the same layanan.');
        }

        $candidateProviderCode = $this->normalizeProviderCode($candidate->provider_code);
        $candidateProviderSku = trim((string) $candidate->provider_sku);

        if ($candidateProviderCode === '') {
            throw new DomainException('Selected provider switch target must define a provider code.');
        }

        if (ProviderRetirement::isRetired($candidateProviderCode)) {
            throw new DomainException('Selected provider switch target has been retired.');
        }

        if ($candidateProviderSku === '') {
            throw new DomainException('Selected provider switch target must define a provider SKU.');
        }

        if ($candidateProviderCode === $currentProviderCode) {
            throw new DomainException('Selected provider switch target must use a different provider.');
        }

        if (! $this->isSwitchableStatus($candidate->status)) {
            throw new DomainException('Selected provider switch target is not active or available.');
        }

        $ambiguousMatchCount = ProviderPath::query()
            ->where('layanan_id', $candidate->layanan_id)
            ->where('provider_code', $candidate->provider_code)
            ->where('provider_sku', $candidate->provider_sku)
            ->whereKeyNot($candidate->getKey())
            ->get()
            ->filter(fn (ProviderPath $providerPath): bool => $this->isSwitchableStatus($providerPath->status))
            ->count();

        if ($ambiguousMatchCount > 0) {
            throw new DomainException('Selected provider switch target is ambiguous.');
        }

        if ($candidateProviderCode === $currentProviderCode && $candidateProviderSku === $currentProviderSku) {
            throw new DomainException('Selected provider switch target must use a different provider route.');
        }

        return $candidate;
    }

    private function validateResetEditableProviderSwitch(
        Pembelian $pembelian,
        int|ProviderPath $candidate,
        ?Layanan $sourceLayanan = null,
    ): ProviderPath {
        if (! $pembelian->isResetEditable()) {
            throw new DomainException('This transaction is not editable outside reset state.');
        }

        $candidateModel = $candidate instanceof ProviderPath
            ? $candidate->loadMissing('layanan')
            : ProviderPath::query()->with('layanan')->find((int) $candidate);

        if (! $candidateModel) {
            throw new DomainException('Selected provider switch target was not found.');
        }

        $sourceLayanan ??= $this->resolveSourceLayanan($pembelian);
        [$currentProviderCode, $currentProviderSku] = $this->resolveCurrentProviderContext($pembelian, $sourceLayanan);

        return $this->resolveValidatedCandidate(
            $pembelian,
            (int) $candidateModel->getKey(),
            $currentProviderCode,
            $currentProviderSku,
            $sourceLayanan,
        );
    }

    private function resolveSourceLayanan(Pembelian $pembelian): Layanan
    {
        if ($pembelian->relationLoaded('activeLayanan') && $pembelian->activeLayanan) {
            return $pembelian->activeLayanan->loadMissing('provider_paths');
        }

        if ($pembelian->active_layanan_id) {
            $activeLayanan = Layanan::query()
                ->with('provider_paths')
                ->find($pembelian->active_layanan_id);

            if (! $activeLayanan) {
                throw new DomainException('Current layanan context is missing for reset validation.');
            }

            return $activeLayanan;
        }

        $matches = Layanan::query()
            ->with('provider_paths')
            ->where('layanan', $pembelian->layanan)
            ->get()
            ->values();

        if ($matches->isEmpty()) {
            throw new DomainException('Current layanan context was not found for reset validation.');
        }

        if ($matches->count() === 1) {
            return $matches->first();
        }

        $activeProviderCode = $this->normalizeProviderCode($pembelian->active_provider_code);
        $activeProviderSku = trim((string) $pembelian->active_provider_sku);

        if ($activeProviderCode !== '' && $activeProviderSku !== '') {
            $legacyMatches = $matches
                ->filter(function (Layanan $layanan) use ($activeProviderCode, $activeProviderSku): bool {
                    return $this->normalizeProviderCode($layanan->provider) === $activeProviderCode
                        && trim((string) $layanan->provider_id) === $activeProviderSku;
                })
                ->values();

            if ($legacyMatches->count() === 1) {
                return $legacyMatches->first();
            }

            $pathMatches = $matches
                ->filter(function (Layanan $layanan) use ($activeProviderCode, $activeProviderSku): bool {
                    return $layanan->provider_paths->contains(function (ProviderPath $providerPath) use ($activeProviderCode, $activeProviderSku): bool {
                        return $this->normalizeProviderCode($providerPath->provider_code) === $activeProviderCode
                            && trim((string) $providerPath->provider_sku) === $activeProviderSku;
                    });
                })
                ->values();

            if ($pathMatches->count() === 1) {
                return $pathMatches->first();
            }
        }

        throw new DomainException('Current layanan context is ambiguous for reset validation.');
    }

    private function resolveCurrentProviderContext(Pembelian $pembelian, ?Layanan $sourceLayanan = null): array
    {
        $providerCode = $this->normalizeProviderCode($pembelian->active_provider_code);
        $providerSku = trim((string) $pembelian->active_provider_sku);

        if ($providerCode !== '' && $providerSku !== '') {
            return [$providerCode, $providerSku];
        }

        $sourceLayanan ??= $this->resolveSourceLayanan($pembelian);

        $legacyProviderCode = $this->normalizeProviderCode($sourceLayanan->provider);
        $legacyProviderSku = trim((string) $sourceLayanan->provider_id);

        if ($legacyProviderCode !== '' && $legacyProviderSku !== '') {
            return [$legacyProviderCode, $legacyProviderSku];
        }

        $availablePaths = $sourceLayanan->provider_paths
            ->filter(fn (ProviderPath $providerPath): bool => $this->isSwitchableStatus($providerPath->status))
            ->values();

        if ($availablePaths->count() === 1) {
            $providerPath = $availablePaths->first();

            return [
                $this->normalizeProviderCode($providerPath->provider_code),
                trim((string) $providerPath->provider_sku),
            ];
        }

        throw new DomainException('Current provider context is incomplete for reset validation.');
    }

    private function normalizeProviderCode(?string $providerCode): string
    {
        return strtolower(trim((string) $providerCode));
    }

    private function normalizeNullableText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function isSwitchableStatus(?string $status): bool
    {
        return in_array(strtolower(trim((string) $status)), ['active', 'available'], true);
    }

    private function calculateProfitFromModalPrice(Pembelian $pembelian, ProviderPath $providerPath): int
    {
        $hargaJual = max(0, (int) round((float) ($pembelian->harga ?? 0)));
        $gatewayFee = $this->estimateGatewayFeeAmount($pembelian);
        $netRevenue = max(0, $hargaJual - $gatewayFee);
        $modal = max(0, (int) round((float) ($providerPath->modal_price ?? 0)));

        return max(0, $netRevenue - $modal);
    }

    private function estimateGatewayFeeAmount(Pembelian $pembelian): int
    {
        $paymentMethodCode = trim((string) ($pembelian->pembayaran?->metode ?? ''));
        if ($paymentMethodCode === '') {
            return 0;
        }

        $method = Method::query()
            ->select('fee_percent', 'fix_fee')
            ->where('code', $paymentMethodCode)
            ->first();

        if (! $method) {
            return 0;
        }

        $amount = max(0, (float) ($pembelian->harga ?? 0));
        $pointDiscount = max(0, (float) ($pembelian->used_point_amount ?? 0));
        $grossBeforePoint = $amount + $pointDiscount;

        $percent = max(0, (float) ($method->fee_percent ?? 0));
        $fixed = max(0, (float) ($method->fix_fee ?? 0));

        if ($percent <= 0 && $fixed <= 0) {
            return 0;
        }

        $denominator = 1 + ($percent / 100);
        if ($denominator <= 0) {
            return 0;
        }

        $estimatedBase = max(0, ($grossBeforePoint - $fixed) / $denominator);
        $estimatedFee = (int) round($fixed + ($estimatedBase * ($percent / 100)));

        return max(0, min((int) round($amount), $estimatedFee));
    }
}
