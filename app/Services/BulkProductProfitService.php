<?php

namespace App\Services;

use App\Models\ProductProfitBulkUpdate;
use App\Models\Produk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use InvalidArgumentException;

class BulkProductProfitService
{
    public function buildTargetQuery(array $scope): Builder
    {
        $scopeType = $scope['scope_type'] ?? 'all';
        $query = Produk::query();

        if ($scopeType === 'selected') {
            $ids = array_values(array_filter(array_map('intval', $scope['selected_ids'] ?? [])));
            if ($ids === []) {
                throw new InvalidArgumentException('Minimal satu produk harus dipilih.');
            }
            $query->whereIn('id', $ids);
        } elseif ($scopeType === 'category') {
            if (blank($scope['kategori_id'] ?? null)) {
                throw new InvalidArgumentException('Kategori wajib dipilih.');
            }
            $query->where('kategori_id', $scope['kategori_id']);
        } elseif ($scopeType !== 'all') {
            throw new InvalidArgumentException('Scope produk tidak valid.');
        }

        return $query
            ->when(filled($scope['provider'] ?? null), fn (Builder $q) => $q->where('provider', $scope['provider']))
            ->when(filled($scope['status'] ?? null), fn (Builder $q) => $q->where('status', $scope['status']))
            ->orderBy('id');
    }

    public function preview(Builder $query, array $percentages, int $limit = 10): array
    {
        $this->validatePercentages($percentages);
        $matchedCount = (clone $query)->count();
        $examples = [];

        foreach ((clone $query)->limit($limit)->get() as $product) {
            $before = $this->pricingSnapshot($product);
            $draft = $product->replicate();
            app(ProductPricingService::class)->applyTierProfitPercentages($draft, $percentages);
            $examples[] = [
                'id' => $product->getKey(),
                'layanan' => $product->layanan,
                'before' => $before,
                'after' => $this->pricingSnapshot($draft),
            ];
        }

        return ['matched_count' => $matchedCount, 'examples' => $examples];
    }

    public function apply(Builder $query, array $percentages, ?int $adminId = null, array $scope = []): ProductProfitBulkUpdate
    {
        $this->validatePercentages($percentages);
        $bulkUpdate = DB::transaction(function () use ($query, $percentages, $adminId, $scope): ProductProfitBulkUpdate {
            $bulk = ProductProfitBulkUpdate::query()->create([
                'admin_id' => $adminId,
                'scope_type' => $scope['scope_type'] ?? 'all',
                'kategori_id' => $scope['kategori_id'] ?? null,
                'filters' => $scope,
                'requested_profits' => $percentages,
                'matched_count' => (clone $query)->count(),
                'updated_count' => 0,
            ]);
            $updated = 0;
            $query->chunkById(100, function ($products) use ($bulk, $percentages, &$updated): void {
                foreach ($products as $product) {
                    $before = $this->pricingSnapshot($product);
                    app(ProductPricingService::class)->applyTierProfitPercentages($product, $percentages);
                    $product->save();
                    $bulk->items()->create([
                        'layanan_id' => $product->getKey(),
                        'before_values' => $before,
                        'after_values' => $this->pricingSnapshot($product),
                    ]);
                    $updated++;
                }
            });
            $bulk->update(['updated_count' => $updated]);
            return $bulk->fresh();
        });
        return $bulkUpdate;
    }

    private function validatePercentages(array $percentages): void
    {
        $resolved = [];
        foreach (['member', 'platinum', 'gold'] as $tier) {
            $value = $percentages[$tier] ?? null;
            if ($value === null || $value === '') {
                $resolved[$tier] = null;
                continue;
            }
            if (! is_numeric($value) || (float) $value < 0 || (float) $value > 100) {
                throw new InvalidArgumentException("Profit {$tier} harus berada di antara 0 dan 100 persen.");
            }
            $resolved[$tier] = (float) $value;
        }
        $filled = array_values(array_filter($resolved, fn ($value) => $value !== null));
        if ($filled === []) {
            throw new InvalidArgumentException('Minimal satu tier profit harus diisi.');
        }
        $previous = null;
        foreach ($resolved as $value) {
            if ($value !== null && $previous !== null && $value < $previous) {
                throw new InvalidArgumentException('Profit tier harus berurutan: Member/Public <= Platinum <= Gold.');
            }
            if ($value !== null) {
                $previous = $value;
            }
        }
    }

    private function pricingSnapshot(Model $product): array
    {
        return collect(['harga', 'harga_member', 'harga_platinum', 'harga_gold', 'profit_member', 'profit_platinum', 'profit_gold'])
            ->mapWithKeys(fn (string $field): array => [$field => $product->{$field}])
            ->all();
    }
}
