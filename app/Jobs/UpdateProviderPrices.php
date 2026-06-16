<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ProviderManager;
use App\Models\Produk;
use Illuminate\Support\Facades\Log;

class UpdateProviderPrices implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public string $providerName;
    public ?string $productId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $providerName, ?string $productId = null)
    {
        $this->providerName = $providerName;
        $this->productId = $productId;
    }

    /**
     * Execute the job.
     */
    public function handle(ProviderManager $providerManager): void
    {
        try {
            $provider = $providerManager->getProvider($this->providerName);
            
            if (!$provider) {
                Log::error("Provider not found: {$this->providerName}");
                return;
            }

            if ($this->productId) {
                // Update specific product
                $this->updateSpecificProduct($provider);
            } else {
                // Update all products for this provider
                $this->updateAllProducts($provider);
            }

        } catch (\Exception $e) {
            Log::error("Price update failed for {$this->providerName}: " . $e->getMessage());
            throw $e;
        }
    }

    private function updateSpecificProduct($provider): void
    {
        $product = Produk::where('provider_id', $this->productId)
            ->where('provider', $this->providerName)
            ->first();

        if (!$product) {
            Log::warning("Product not found: {$this->productId} for provider {$this->providerName}");
            return;
        }

        $newPrice = $provider->getPrice($this->productId);
        
        if ($newPrice && $newPrice !== $product->harga) {
            $product->update([
                'harga' => $newPrice,
                'harga_member' => $newPrice * 0.95, // 5% discount for members
                'harga_platinum' => $newPrice * 0.90, // 10% discount for platinum
                'harga_gold' => $newPrice * 0.92, // 8% discount for gold
                'updated_at' => now(),
            ]);
        }
    }

    private function updateAllProducts($provider): void
    {
        $products = Produk::where('provider', $this->providerName)->get();
        $updatedCount = 0;

        foreach ($products as $product) {
            try {
                $newPrice = $provider->getPrice($product->provider_id);
                
                if ($newPrice && $newPrice !== $product->harga) {
                    $product->update([
                        'harga' => $newPrice,
                        'harga_member' => $newPrice * 0.95,
                        'harga_platinum' => $newPrice * 0.90,
                        'harga_gold' => $newPrice * 0.92,
                        'updated_at' => now(),
                    ]);
                    
                    $updatedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to update price for product {$product->provider_id}: " . $e->getMessage());
            }
        }

    }

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;
}
