<?php

namespace App\Jobs;

use App\Models\Provider;
use App\Services\ProviderBalanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckProviderBalanceJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $backoff = 20;

    public int $uniqueFor = 30;

    public function __construct(public int $providerId)
    {
    }

    public function uniqueId(): string
    {
        return 'check-provider-balance:' . $this->providerId;
    }

    /**
     * Apakah pesan kegagalan ini sebenarnya hanya kredensial yang belum diisi?
     *
     * Sengaja memakai daftar frasa yang SEMPIT dan spesifik, bukan pencocokan
     * longgar: pesan yang tidak dikenali tetap dicatat WARNING, sehingga
     * kegagalan nyata tidak ikut tersembunyi.
     */
    private function isConfigurationGap(string $message): bool
    {
        $needles = [
            'belum lengkap',
            'tidak didukung untuk check balance',
            'credentials are missing',
            'credential is missing',
            'is not set',
            'not configured',
        ];

        $message = strtolower($message);

        foreach ($needles as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function handle(ProviderBalanceService $providerBalanceService): void
    {
        $provider = Provider::query()->find($this->providerId);

        if (! $provider) {
            Log::warning('CheckProviderBalanceJob: provider not found.', [
                'provider_id' => $this->providerId,
            ]);

            return;
        }

        try {
            $result = $providerBalanceService->sync($provider);

            if (!($result['success'] ?? false)) {
                $message = (string) ($result['message'] ?? 'Unknown provider balance issue.');

                // Kredensial yang belum diisi dianggap CELAH KONFIGURASI, bukan
                // kegagalan. Tanpa pemisahan ini, provider yang memang belum
                // dipakai mengisi log tiap menit (job ini berjalan everyMinute;
                // terukur ~1020 baris/hari PER provider) dan menenggelamkan
                // kegagalan yang nyata. Sudah kejadian: 3037 dari 3092
                // WARNING/ERROR sehari berasal dari tiga provider yang belum
                // dikonfigurasi.
                //
                // Kegagalan yang NYATA (mis. "HTTP error: 503" pada provider
                // yang kredensialnya ADA) tetap WARNING supaya tetap terlihat.
                if ($this->isConfigurationGap($message)) {
                    Log::info('CheckProviderBalanceJob: provider belum dikonfigurasi, pemeriksaan saldo dilewati.', [
                        'provider_id' => $provider->id,
                        'provider_code' => $provider->code,
                        'message' => $message,
                    ]);

                    return;
                }

                Log::warning('CheckProviderBalanceJob skipped/failed gracefully.', [
                    'provider_id' => $provider->id,
                    'provider_code' => $provider->code,
                    'message' => $message,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('CheckProviderBalanceJob failed.', [
                'provider_id' => $provider->id,
                'provider_code' => $provider->code,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
