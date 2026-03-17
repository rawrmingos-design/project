<?php

namespace App\Console\Commands;

use App\Models\Kategori;
use App\Support\CustomInputDefaults;
use Illuminate\Console\Command;

class BackfillCustomInputs extends Command
{
    protected $signature = 'custom-inputs:backfill {--dry-run : Tampilkan data yang akan dibuat tanpa menyimpan}';

    protected $description = 'Buat row custom_inputs default untuk kategori yang belum memilikinya.';

    public function handle(CustomInputDefaults $customInputDefaults): int
    {
        $missingCategories = Kategori::query()
            ->whereDoesntHave('customInput')
            ->orderBy('id')
            ->get();

        $this->info('Kategori tanpa custom_inputs: ' . $missingCategories->count());

        if ($missingCategories->isEmpty()) {
            $this->line('Tidak ada data yang perlu dibackfill.');

            return self::SUCCESS;
        }

        $created = 0;

        foreach ($missingCategories as $kategori) {
            if ($this->option('dry-run')) {
                $this->line(sprintf('[DRY RUN] #%d %s (%s)', $kategori->id, $kategori->nama, $kategori->tipe));
                continue;
            }

            if ($customInputDefaults->ensureExists($kategori)) {
                $created++;
                $this->line(sprintf('[CREATED] #%d %s (%s)', $kategori->id, $kategori->nama, $kategori->tipe));
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Summary => ditemukan: %d, dibuat: %d, skipped: %d',
            $missingCategories->count(),
            $created,
            $missingCategories->count() - $created
        ));

        return self::SUCCESS;
    }
}
