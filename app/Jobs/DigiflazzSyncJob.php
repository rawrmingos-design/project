<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\DigiFlazzController;
use App\Models\ProviderPath;
use Illuminate\Support\Facades\Log;

class DigiflazzSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $digi = new DigiFlazzController();
        $response = $digi->harga(); // Fetch Pricelist

        if (!isset($response['data']) || !is_array($response['data'])) {
            Log::error('DigiflazzSyncJob: Failed to fetch pricelist or invalid response format.', ['response' => $response]);
            return;
        }

        $count = 0;
        foreach ($response['data'] as $item) {
            $sku = $item['buyer_sku_code'];
            $price = $item['price'];
            
            // Map Digiflazz status to our system status
            // Assuming Digiflazz returns: true (available) / false (unavailable)? 
            // Or 'buyer_product_status': true/false, 'seller_product_status': true/false
            
            $isAvailable = $item['buyer_product_status'] && $item['seller_product_status'];
            $status = $isAvailable ? 'available' : 'maintenance'; // Default to maintenance if not available
            
            if ($item['stock'] == 0 && $item['unlimited_stock'] == false) {
                 $status = 'empty';
            }

            // Find matching ProviderPath
            $path = ProviderPath::where('provider_code', 'digiflazz')
                ->where('provider_sku', $sku)
                ->first();

            if ($path) {
                $path->update([
                    'modal_price' => $price,
                    'status' => $status,
                    'last_sync_at' => now(),
                ]);
                $count++;
            }
        }

    }
}
