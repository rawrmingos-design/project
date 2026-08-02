<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Layanan;
use App\Services\PublicSiteConfigService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SalesPageController extends Controller
{
    /**
     * Display the Elite H2H API reseller sales/marketing landing page.
     */
    public function __invoke(): Response
    {
        $products = $this->getH2HProducts();
        $allProducts = $this->getAllH2HProducts(); // For search functionality
        
        return Inertia::render('Reseller/SalesPage', [
            'products' => $products, // Paginated products for display
            'allProducts' => $allProducts, // All products for search
            'ctaConfig' => [
                'primaryUrl' => route('reseller.registry.form'),
                'docsUrl' => $this->docsUrl(),
            ],
            'seoMeta' => [
                'title' => 'Elite Reseller | H2H API Integration Portal - Top Up Game Tercepat',
                'description' => 'Gateway Host-to-Host (H2H) paling stabil di Indonesia. API Top Up dengan response time <200ms, uptime 99.99%, dan harga wholesale untuk reseller profesional.',
            ],
        ]);
    }

    /**
     * Resolve the canonical API docs URL without rendering duplicate reseller docs content.
     */
    private function docsUrl(): ?string
    {
        return app(PublicSiteConfigService::class)->docsUrl();
    }

    /**
     * Get active H2H products with wholesale pricing and pagination
     * 
     * @return array
     */
    private function getH2HProducts(): array
    {
        try {
            $perPage = 20; // Products per page
            
            $products = Layanan::with([
                    'kategori',
                    'paket' => function($query) {
                        $query->select('pakets.id'); // Only select id to minimize data
                    }
                ])
                ->where('status', 'available')
                ->whereNotNull('harga')
                ->whereNotNull('harga_gold')
                ->where('harga', '>', 0)
                ->where('harga_gold', '>', 0)
                ->orderBy('kategori_id', 'asc')
                ->paginate($perPage);
            
            return [
                'data' => $products->map(function ($product) {
                    return $this->formatProduct($product);
                })->toArray(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
            ];
            
        } catch (\Exception $e) {
            // Log error but don't break the page
            Log::error('Failed to fetch H2H products', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Return empty data with pagination
            return [
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'from' => null,
                    'to' => null,
                ],
            ];
        }
    }

    /**
     * Get ALL H2H products without pagination (for search functionality)
     * 
     * @return array
     */
    private function getAllH2HProducts(): array
    {
        try {
            $products = Layanan::with([
                    'kategori',
                    'paket' => function($query) {
                        $query->select('pakets.id');
                    }
                ])
                ->where('status', 'available')
                ->whereNotNull('harga')
                ->whereNotNull('harga_gold')
                ->where('harga', '>', 0)
                ->where('harga_gold', '>', 0)
                ->orderBy('kategori_id', 'asc')
                ->get(); // Get all, no pagination
            
            return $products->map(function ($product) {
                return $this->formatProduct($product);
            })->toArray();
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch all H2H products for search', [
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    /**
     * Format product data for frontend consumption
     * 
     * @param Layanan $product
     * @return array
     */
    private function formatProduct(Layanan $product): array
    {
        $normalPrice = (float) ($product->harga ?? 0);
        $h2hPrice = (float) ($product->harga_gold ?? 0);
        $gameName = $product->kategori->nama ?? 'Unknown'; // ✅ Fixed: use 'nama' column
        
        // Get product logo from paket pivot (first paket's logo)
        $productLogo = null;
        if ($product->paket && $product->paket->count() > 0) {
            $firstPaket = $product->paket->first();
            $productLogo = $firstPaket->pivot->product_logo ?? null;
        }
        
        return [
            'id' => $product->id,
            'initials' => $this->generateInitials($gameName),
            'logo' => $productLogo, // NEW: Product logo path
            'name' => $product->layanan ?? 'Unknown Product',
            'game' => $gameName,
            'brand' => $product->kategori->sub_nama ?? '', // ✅ Fixed: use 'sub_nama' column
            'sku' => strtoupper($product->provider_id ?? ''),
            'normalPrice' => (int) $normalPrice,
            'h2hPrice' => (int) $h2hPrice,
            'discount' => $this->calculateDiscount($normalPrice, $h2hPrice),
            'formattedNormal' => 'Rp ' . number_format($normalPrice, 0, ',', '.'),
            'formattedH2h' => 'Rp ' . number_format($h2hPrice, 0, ',', '.'),
            'status' => 'instant', // Default status
        ];
    }

    /**
     * Generate 2-letter initials from game name
     * 
     * @param string $gameName
     * @return string
     */
    private function generateInitials(string $gameName): string
    {
        // Remove common words
        $cleanName = preg_replace('/\b(game|mobile|online|top\s*up)\b/i', '', $gameName);
        $cleanName = trim($cleanName);
        
        if (empty($cleanName)) {
            $cleanName = $gameName;
        }
        
        $words = preg_split('/\s+/', $cleanName);
        
        // Single word: Take first 2 characters
        if (count($words) === 1) {
            return strtoupper(substr($cleanName, 0, 2));
        }
        
        // Multiple words: Take first letter of first 2 words
        $first = substr($words[0], 0, 1);
        $second = isset($words[1]) ? substr($words[1], 0, 1) : substr($words[0], 1, 1);
        
        return strtoupper($first . $second);
    }

    /**
     * Calculate discount percentage
     * 
     * @param float $normalPrice
     * @param float $h2hPrice
     * @return int
     */
    private function calculateDiscount(float $normalPrice, float $h2hPrice): int
    {
        if ($normalPrice <= 0) {
            return 0;
        }
        
        $discount = (($normalPrice - $h2hPrice) / $normalPrice) * 100;
        
        return (int) round($discount);
    }
}
