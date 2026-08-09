<?php

use App\Support\ProviderRetirement;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $retiredCodes = ProviderRetirement::retiredCodes();

        if ($retiredCodes === []) {
            return;
        }

        if (Schema::hasTable('providers') && Schema::hasColumn('providers', 'is_active')) {
            DB::table('providers')
                ->whereIn(DB::raw('LOWER(code)'), $retiredCodes)
                ->update([
                    'is_active' => false,
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('provider_paths')) {
            $hasMetadata = Schema::hasColumn('provider_paths', 'metadata');

            DB::table('provider_paths')
                ->whereIn(DB::raw('LOWER(provider_code)'), $retiredCodes)
                ->orderBy('id')
                ->chunkById(100, function ($paths) use ($hasMetadata): void {
                    foreach ($paths as $path) {
                        $updates = [
                            'status' => 'unavailable',
                            'updated_at' => now(),
                        ];

                        if ($hasMetadata) {
                            $metadata = json_decode((string) ($path->metadata ?? ''), true);
                            $metadata = is_array($metadata) ? $metadata : [];

                            if (! isset($metadata['provider_retirement'])) {
                                $metadata['provider_retirement'] = array_filter([
                                    'retired_at' => now()->toIso8601String(),
                                    'previous_status' => $path->status ?? null,
                                    'reason' => 'Provider integration retired',
                                ], static fn (mixed $value): bool => $value !== null && $value !== '');
                            }

                            $updates['metadata'] = json_encode($metadata);
                        }

                        DB::table('provider_paths')
                            ->where('id', $path->id)
                            ->update($updates);
                    }
                });
        }

        if (Schema::hasTable('layanans') && Schema::hasColumn('layanans', 'status')) {
            DB::table('layanans')
                ->whereIn(DB::raw('LOWER(provider)'), $retiredCodes)
                ->where('status', 'available')
                ->orderBy('id')
                ->chunkById(100, function ($layanans) use ($retiredCodes): void {
                    foreach ($layanans as $layanan) {
                        $hasRetainedPath = Schema::hasTable('provider_paths')
                            && DB::table('provider_paths')
                                ->where('layanan_id', $layanan->id)
                                ->where('status', 'available')
                                ->whereNotIn(DB::raw('LOWER(provider_code)'), $retiredCodes)
                                ->exists();

                        if (! $hasRetainedPath) {
                            DB::table('layanans')
                                ->where('id', $layanan->id)
                                ->update([
                                    'status' => 'unavailable',
                                    'updated_at' => now(),
                                ]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        // Provider retirement is intentionally non-destructive and not auto-reversible.
        // Restore reviewed provider, path, and layanan statuses from the deployment snapshot.
    }
};
