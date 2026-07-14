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

        if (! is_array($response)) {
            Log::warning('DigiflazzSyncJob: invalid pricelist response.', [
                'response_type' => get_debug_type($response),
            ]);

            return;
        }

        $products = $response['data'] ?? null;

        if (! is_array($products) || ! array_is_list($products)) {
            Log::warning('DigiflazzSyncJob: pricelist response did not contain a product list.', [
                'status' => is_array($products) ? ($products['status'] ?? null) : null,
                'message' => is_array($products) ? ($products['message'] ?? null) : null,
                'data_type' => get_debug_type($products),
            ]);

            return;
        }

        $count = 0;
        $skippedInvalid = 0;

        foreach ($products as $item) {
            if (! is_array($item)) {
                $skippedInvalid++;
                continue;
            }

            $sku = trim((string) ($item['buyer_sku_code'] ?? ''));
            $price = $item['price'] ?? null;

            if ($sku === '' || ! is_numeric($price)) {
                $skippedInvalid++;
                continue;
            }

            // Map Digiflazz status to our system status.
            $isAvailable = (bool) ($item['buyer_product_status'] ?? false)
                && (bool) ($item['seller_product_status'] ?? false);
            $status = $isAvailable ? 'available' : 'maintenance';
            $stock = $item['stock'] ?? null;
            $unlimitedStock = (bool) ($item['unlimited_stock'] ?? true);

            if (is_numeric($stock) && (int) $stock <= 0 && ! $unlimitedStock) {
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

        if ($skippedInvalid > 0) {
            Log::warning('DigiflazzSyncJob: skipped invalid pricelist entries.', [
                'skipped_invalid' => $skippedInvalid,
                'updated' => $count,
            ]);
        }

    }
}
