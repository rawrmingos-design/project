<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Layanan;
use App\Models\ProviderPath;
use Illuminate\Support\Facades\DB;

class MigrateToDigiflazz extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:providers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate all legacy products to use Digiflazz as default provider path';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration to Digiflazz...');

        $layanans = Layanan::all();
        $count = 0;
        
        DB::beginTransaction();
        try {
            foreach ($layanans as $layanan) {
                // Skip if doesn't have a SKU (provider_id)
                if (empty($layanan->provider_id)) {
                    continue;
                }

                // Check if path already exists
                $exists = ProviderPath::where('layanan_id', $layanan->id)
                    ->where('provider_code', 'digiflazz')
                    ->exists();

                if (!$exists) {
                    ProviderPath::create([
                        'layanan_id' => $layanan->id,
                        'provider_code' => 'digiflazz', // User requested to 'all in' on Digiflazz
                        'provider_sku' => $layanan->provider_id,
                        'priority' => 1,
                        'status' => 'available',
                        'modal_price' => $layanan->modal ?? 0,
                        'last_sync_at' => now(),
                    ]);
                    $count++;
                    $this->line("Migrated: {$layanan->layanan} -> Digiflazz ({$layanan->provider_id})");
                }
            }
            
            DB::commit();
            $this->info("Migration completed. {$count} services updated.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Migration failed: " . $e->getMessage());
        }
    }
}
