<?php

namespace Tests\Feature;

use App\Console\Commands\LogTriageCommand;
use App\Jobs\CheckProviderBalanceJob;
use App\Models\Provider;
use App\Services\ProviderBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Mockery;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

/**
 * Mengunci dua kegagalan SENYAP yang membuat alarm pemantauan tidak berguna:
 *
 * 1. `log:triage` membaca `storage/logs/laravel.log`, padahal dengan
 *    `LOG_CHANNEL=daily` berkas itu TIDAK PERNAH ditulis. Hasilnya perintah itu
 *    selalu melaporkan 0 di semua kategori dan tidak pernah mengirim alert —
 *    terbukti saat insiden 2026-10-01: log berisi 3092 warning/error (termasuk 7
 *    ERROR asli) sementara triage melaporkan "Total Errors: 0, All within
 *    thresholds".
 * 2. Baris INFO turut dikategorikan, sehingga frasa seperti "credentials are
 *    missing" pada pesan rutin menyalakan alarm palsu.
 */
class LogTriageReadsRealDailyLogTest extends TestCase
{
    use RefreshDatabase;

    private function command(): LogTriageCommand
    {
        $command = new class extends LogTriageCommand
        {
            public function bindOptions(): void
            {
                $this->input = new ArrayInput(['--hours' => 24], $this->getDefinition());
                $this->output = new NullOutput();
            }

            public function paths(): array
            {
                return $this->resolveLogPaths();
            }

            public function parse(string $path, int $hours): array
            {
                return $this->parseLogFile($path, $hours);
            }
        };

        $command->bindOptions();

        return $command;
    }

    public function test_berkas_log_harian_ikut_dianalisis_bukan_hanya_laravel_log(): void
    {
        $path = storage_path('logs/laravel-2999-12-31.log');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, "[2999-12-31 10:00:00] local.WARNING: baris uji\n");

        try {
            $paths = $this->command()->paths();

            $this->assertContains(
                $path,
                $paths,
                'Berkas daily (LOG_CHANNEL=daily) wajib ikut dianalisis; kalau tidak, triage selalu melaporkan 0.'
            );
        } finally {
            File::delete($path);
        }
    }

    public function test_baris_info_tidak_dikategorikan_sebagai_kegagalan(): void
    {
        $path = storage_path('logs/laravel-triage-fixture.log');
        $now = now()->format('Y-m-d H:i:s');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, implode("\n", [
            // INFO: memuat frasa yang cocok pola "credentials ... missing".
            "[{$now}] local.INFO: CheckProviderBalanceJob: ApiGames credentials are missing.",
            "[{$now}] local.WARNING: ApiCheckController:check - All providers failed.",
            '',
        ]));

        try {
            $stats = $this->command()->parse($path, 24);
        } finally {
            File::delete($path);
        }

        $this->assertSame(0, $stats['missing_config'], 'INFO tidak boleh masuk kategori kegagalan.');
        $this->assertSame(1, $stats['api_check_controller'], 'WARNING tetap harus dikategorikan.');
        $this->assertSame(1, $stats['total_warnings']);
        $this->assertSame(0, $stats['total_errors']);
    }

    public function test_provider_belum_dikonfigurasi_dicatat_info_bukan_warning(): void
    {
        Log::spy();

        $provider = Provider::query()->updateOrCreate(
            ['code' => 'apigames'],
            ['name' => 'API Games', 'is_active' => true, 'balance' => 1000]
        );

        $service = Mockery::mock(ProviderBalanceService::class);
        $service->shouldReceive('sync')->once()->andReturn([
            'success' => false,
            'balance' => 1000.0,
            'message' => 'Konfigurasi API Games belum lengkap.',
        ]);

        (new CheckProviderBalanceJob($provider->id))->handle($service);

        Log::shouldHaveReceived('info')->once();
        Log::shouldNotHaveReceived('warning');
    }

    public function test_kegagalan_nyata_tetap_dicatat_warning(): void
    {
        Log::spy();

        $provider = Provider::query()->updateOrCreate(
            ['code' => 'sufpayment'],
            ['name' => 'SufPayment', 'is_active' => true, 'balance' => 1000]
        );

        $service = Mockery::mock(ProviderBalanceService::class);
        $service->shouldReceive('sync')->once()->andReturn([
            'success' => false,
            'balance' => 1000.0,
            'message' => 'SufPayment HTTP error: 503',
        ]);

        (new CheckProviderBalanceJob($provider->id))->handle($service);

        Log::shouldHaveReceived('warning')->once();
        Log::shouldNotHaveReceived('info');
    }
}
