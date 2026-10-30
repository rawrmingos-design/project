<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Kontrak: domain (admin + docs) HARUS dibaca lewat config(), bukan env().
 *
 * Alasan konkret: aplikasi ini menjalankan `php artisan config:cache` pada setiap
 * deploy (lihat docker/scripts/post-deploy-app.sh). Begitu config di-cache,
 * `env()` di luar file config mengembalikan NULL — bukan nilai .env.
 *
 * Akibat nyata yang pernah terjadi (staging, 4 Okt 2026): `ResolveTenant`
 * membaca `env('DOCS_DOMAIN')` => NULL => host docs dianggap subdomain tenant
 * => `abort(404)` => dokumentasi API mustahil dibuka dengan 404, padahal
 * route-nya match sempurna dan TLS-nya sehat.
 *
 * `config('app.docs_domain')` dan `config('app.filament_admin_domain')` adalah
 * satu-satunya jalur yang benar. Test ini menegakkannya secara mekanis supaya
 * regresi yang sama tidak bisa masuk lagi lewat commit berikutnya.
 */
class DomainConfigSourceTest extends TestCase
{
    /**
     * File yang boleh memakai env() untuk domain: hanya definisi config itu sendiri.
     */
    private const ALLOWED_CONFIG_FILES = [
        'config/app.php',
    ];

    public function test_docs_domain_tidak_dibaca_lewat_env_di_luar_config(): void
    {
        $offenders = $this->scanForEnvDomainReads("env('DOCS_DOMAIN'");

        $this->assertSame(
            [],
            $offenders,
            "env('DOCS_DOMAIN') bernilai NULL saat config:cache aktif. "
            ."Pakai config('app.docs_domain'). Pelanggar:\n - ".implode("\n - ", $offenders)
        );
    }

    public function test_admin_domain_tidak_dibaca_lewat_env_di_luar_config(): void
    {
        $offenders = $this->scanForEnvDomainReads("env('FILAMENT_ADMIN_DOMAIN'");

        $this->assertSame(
            [],
            $offenders,
            "env('FILAMENT_ADMIN_DOMAIN') bernilai NULL saat config:cache aktif. "
            ."Pakai config('app.filament_admin_domain'). Pelanggar:\n - ".implode("\n - ", $offenders)
        );
    }

    /**
     * Kunci perilaku untuk host docs: kalau DOCS_DOMAIN diisi, host itu TIDAK
     * boleh dianggap subdomain tenant (yang memicu abort 404 di ResolveTenant).
     */
    public function test_host_docs_di_bypass_oleh_resolve_tenant(): void
    {
        $docsHost = 'docs.test.jasakoding.web.id';

        // getenv()/putenv() dipakai supaya nilai terlihat oleh config() saat
        // repo config dimuat ulang di dalam test (mengikuti pola test existing).
        putenv('DOCS_DOMAIN='.$docsHost);
        $_ENV['DOCS_DOMAIN'] = $docsHost;
        $_SERVER['DOCS_DOMAIN'] = $docsHost;

        $this->refreshApplication();
        $this->app['config']->set('app.docs_domain', $docsHost);

        $middleware = new \App\Http\Middleware\ResolveTenant();

        $reflection = new \ReflectionClass($middleware);
        $bypass = $reflection->getMethod('shouldBypassHost');
        $bypass->setAccessible(true);

        $this->assertTrue(
            $bypass->invoke($middleware, $docsHost),
            'Host docs harus di-bypass oleh ResolveTenant; kalau tidak, request '
            .'ke domain docs akan kena abort(404) sebelum sampai controller.'
        );

        foreach (['DOCS_DOMAIN'] as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }

    /**
     * Penjaga: scanner harus benar-benar MENANGKAP pelanggaran nyata.
     *
     * Tanpa tes ini, memperbaiki bug path dengan cara apa pun (termasuk
     * mengembalikan [] selalu) akan membuat dua tes di atas hijau tanpa
     * membuktikan apa pun. Diuji di root sementara lewat setBasePath supaya
     * tidak bergantung pada nilai base_path() environment tertentu.
     */
    public function test_scanner_menangkap_pelanggaran_nyata_dan_mengabaikan_config_app(): void
    {
        $root = sys_get_temp_dir().'/dcs-'.uniqid('', true);

        foreach (['app', 'routes', 'config'] as $directory) {
            @mkdir($root.'/'.$directory, 0777, true);
        }

        // Diizinkan: definisi config memang harus memakai env().
        file_put_contents($root.'/config/app.php', "<?php\nreturn ['docs_domain' => env('DOCS_DOMAIN')];\n");

        // Pelanggaran: env() dipakai di luar file config.
        file_put_contents($root.'/app/BadDomainReader.php', "<?php\n\$host = env('DOCS_DOMAIN');\n");

        // Komentar tidak boleh dianggap pelanggaran.
        file_put_contents($root.'/routes/web.php', "<?php\n// jangan pakai env('DOCS_DOMAIN') di sini\n");

        $originalBase = base_path();
        $this->app->setBasePath($root);

        try {
            $reflection = new \ReflectionClass($this);
            $method = $reflection->getMethod('scanForEnvDomainReads');
            $method->setAccessible(true);

            $offenders = $method->invoke($this, "env('DOCS_DOMAIN'");
        } finally {
            $this->app->setBasePath($originalBase);
            @unlink($root.'/config/app.php');
            @unlink($root.'/app/BadDomainReader.php');
            @unlink($root.'/routes/web.php');
            @rmdir($root.'/app');
            @rmdir($root.'/routes');
            @rmdir($root.'/config');
            @rmdir($root);
        }

        $this->assertSame(
            ['app/BadDomainReader.php:2'],
            $offenders,
            'Scanner wajib melaporkan env() di app/ dan mengabaikan config/app.php serta baris komentar.'
        );
    }

    /**
     * Cari pemakaian env() untuk domain di seluruh app/config/routes,
     * kecuali file definisi config yang memang seharusnya memakai env().
     *
     * @return list<string>
     */
    private function scanForEnvDomainReads(string $needle): array
    {
        $base = base_path();
        $directories = ['app', 'routes', 'config'];
        $offenders = [];

        foreach ($directories as $directory) {
            $path = $base.'/'.$directory;

            if (! is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                // Potong prefiks base sekali saja dengan substr, JANGAN str_replace.
                // `str_replace($base, '', $path)` mengganti SEMUA kemunculan $base:
                // untuk base `/app` dan file `/app/config/app.php` hasilnya
                // `/config.php` -> `config.php`, sehingga allowlist `config/app.php`
                // tidak pernah cocok dan tes melaporkan pelanggaran palsu.
                $relative = ltrim(substr($file->getPathname(), strlen($base)), '/');

                if (in_array($relative, self::ALLOWED_CONFIG_FILES, true)) {
                    continue;
                }

                $contents = (string) file_get_contents($file->getPathname());

                if (! str_contains($contents, $needle)) {
                    continue;
                }

                // Abaikan baris komentar supaya dokumentasi tidak dianggap pelanggaran.
                foreach (preg_split('/\R/', $contents) as $number => $line) {
                    if (! str_contains($line, $needle)) {
                        continue;
                    }

                    $trimmed = ltrim($line);

                    if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
                        continue;
                    }

                    $offenders[] = $relative.':'.($number + 1);
                }
            }
        }

        sort($offenders);

        return $offenders;
    }
}
