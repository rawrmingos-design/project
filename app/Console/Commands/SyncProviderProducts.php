<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProviderManager;
use App\Models\Produk;
use App\Models\Kategori;
use Illuminate\Support\Facades\DB;

class SyncProviderProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'provider:sync-products 
                            {--provider= : Specific provider to sync (optional)}
                            {--update-existing : Update existing products}
                            {--dry-run : Show what would be synced without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync products from external providers (Digiflazz, BangJeff, etc.)';

    private ProviderManager $providerManager;

    public function __construct(ProviderManager $providerManager)
    {
        parent::__construct();
        $this->providerManager = $providerManager;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Provider Product Sync...');
        
        $provider = $this->option('provider');
        $updateExisting = $this->option('update-existing');
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        try {
            if ($provider) {
                $this->syncSpecificProvider($provider, $updateExisting, $dryRun);
            } else {
                $this->syncAllProviders($updateExisting, $dryRun);
            }
            
            $this->info('✅ Product sync completed successfully!');
        } catch (\Exception $e) {
            $this->error('❌ Product sync failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function syncSpecificProvider(string $providerName, bool $updateExisting, bool $dryRun): void
    {
        $this->info("📡 Syncing products from: {$providerName}");
        
        $result = $this->providerManager->syncProviderProducts($providerName);
        
        if (!$result['success']) {
            throw new \Exception("Failed to sync from {$providerName}: " . $result['error']);
        }

        $this->processProducts($result['products'], $providerName, $updateExisting, $dryRun);
    }

    private function syncAllProviders(bool $updateExisting, bool $dryRun): void
    {
        $providers = $this->providerManager->getProviderNames();
        
        $this->info('📡 Syncing products from all providers: ' . implode(', ', $providers));
        
        $results = $this->providerManager->syncAllProducts();
        
        foreach ($results as $providerName => $result) {
            if ($result['success']) {
                $this->info("✅ {$providerName}: {$result['products_count']} products");
                $this->processProducts($result['products'], $providerName, $updateExisting, $dryRun);
            } else {
                $this->error("❌ {$providerName}: {$result['error']}");
            }
        }
    }

    private function processProducts(array $products, string $providerName, bool $updateExisting, bool $dryRun): void
    {
        $bar = $this->output->createProgressBar(count($products));
        $bar->start();

        $stats = [
            'new' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0
        ];

        foreach ($products as $productData) {
            try {
                $result = $this->processProduct($productData, $providerName, $updateExisting, $dryRun);
                $stats[$result]++;
            } catch (\Exception $e) {
                $stats['errors']++;
                $this->error("\n❌ Error processing product {$productData['name']}: " . $e->getMessage());
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->table(['Status', 'Count'], [
            ['New Products', $stats['new']],
            ['Updated Products', $stats['updated']],
            ['Skipped Products', $stats['skipped']],
            ['Errors', $stats['errors']],
        ]);
    }

    private function processProduct(array $productData, string $providerName, bool $updateExisting, bool $dryRun): string
    {
        // Find or create category
        $category = $this->findOrCreateCategory($productData['category'] ?? 'Uncategorized', $dryRun);
        
        // Check if product exists
        $existingProduct = Produk::where('provider_id', $productData['provider_id'])
            ->where('provider', $providerName)
            ->first();

        if ($existingProduct) {
            if ($updateExisting && !$dryRun) {
                $existingProduct->update([
                    'layanan' => $productData['name'],
                    'kategori_id' => $category?->id,
                    'harga' => $productData['price'],
                    'status' => $productData['status'] === 'available' ? 'active' : 'inactive',
                    'updated_at' => now(),
                ]);
                return 'updated';
            }
            return 'skipped';
        }

        // Create new product
        if (!$dryRun) {
            Produk::create([
                'layanan' => $productData['name'],
                'kategori_id' => $category?->id,
                'provider_id' => $productData['provider_id'],
                'provider' => $providerName,
                'harga' => $productData['price'],
                'harga_member' => $productData['price'] * 0.95, // 5% discount for members
                'harga_platinum' => $productData['price'] * 0.90, // 10% discount for platinum
                'harga_gold' => $productData['price'] * 0.92, // 8% discount for gold
                'profit' => 10, // Default 10% profit
                'status' => $productData['status'] === 'available' ? 'active' : 'inactive',
                'deskripsi' => $productData['description'] ?? '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return 'new';
    }

    private function findOrCreateCategory(string $categoryName, bool $dryRun): ?Kategori
    {
        $category = Kategori::where('nama', $categoryName)->first();
        
        if (!$category && !$dryRun) {
            $category = Kategori::create([
                'nama' => $categoryName,
                'deskripsi' => "Auto-created category for {$categoryName}",
                'gambar' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $category;
    }
}
