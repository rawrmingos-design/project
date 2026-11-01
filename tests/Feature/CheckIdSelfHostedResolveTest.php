<?php

use App\Http\Controllers\ApiCheckController;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Jalur cekid self-hosted: pin koneksi ke IP lokal supaya tidak lewat Cloudflare.
 *
 * Kenapa dijaga dua arah:
 *  - `resolve_ip` kosong   → TIDAK boleh menyuntik CURLOPT_RESOLVE sama sekali.
 *    Menyuntik tanpa diminta bisa membelokkan trafik ke host yang salah.
 *  - `resolve_ip` terisi   → CURLOPT_RESOLVE harus memakai host + port dari
 *    base_url itu sendiri, dan URL/skema TIDAK boleh berubah, supaya TLS tetap
 *    divalidasi terhadap sertifikat nama publik.
 *  - guard HTTPS/localhost TIDAK boleh dilonggarkan oleh fitur ini.
 */
function curlOptionsFor(array $config): array
{
    config([
        'providers.check_id.selfhosted.resolve_port' => 443,
    ]);

    config(array_combine(
        array_map(fn ($k) => 'providers.check_id.selfhosted.' . $k, array_keys($config)),
        array_values($config),
    ));

    $controller = app(ApiCheckController::class);
    $method = new ReflectionMethod($controller, 'selfHostedCurlOptions');
    $method->setAccessible(true);

    return $method->invoke($controller, 'https://cekid.contoh.test');
}

test('tanpa resolve_ip, tidak ada CURLOPT_RESOLVE yang disuntik', function () {
    expect(curlOptionsFor(['resolve_ip' => null]))->toBe([]);
});

test('resolve_ip kosong/spasi diperlakukan sebagai tidak di-set', function () {
    expect(curlOptionsFor(['resolve_ip' => '   ']))->toBe([]);
});

test('resolve_ip terisi, CURLOPT_RESOLVE memakai host dan port yang benar', function () {
    $options = curlOptionsFor(['resolve_ip' => '172.18.0.1']);

    expect($options)->toHaveKey('curl');
    expect($options['curl'])->toHaveKey(CURLOPT_RESOLVE);
    expect($options['curl'][CURLOPT_RESOLVE])->toBe(['cekid.contoh.test:443:172.18.0.1']);
});

test('port mengikuti konfigurasi, bukan selalu 443', function () {
    config(['providers.check_id.selfhosted.resolve_port' => 8443]);
    config(['providers.check_id.selfhosted.resolve_ip' => '10.1.2.3']);

    $controller = app(ApiCheckController::class);
    $method = new ReflectionMethod($controller, 'selfHostedCurlOptions');
    $method->setAccessible(true);

    $options = $method->invoke($controller, 'https://cekid.contoh.test');

    expect($options['curl'][CURLOPT_RESOLVE])->toBe(['cekid.contoh.test:8443:10.1.2.3']);
});

/**
 * Kontrak yang paling mudah rusak diam-diam: resolve_ip dipakai sebagai alasan
 * untuk "sekalian" melonggarkan guard HTTPS. Itu tidak boleh.
 */
test('guard HTTPS tetap menolak base_url http walau resolve_ip terisi', function () {
    config([
        'providers.check_id.selfhosted.enabled' => true,
        'providers.check_id.selfhosted.base_url' => 'http://cekid.contoh.test',
        'providers.check_id.selfhosted.api_key' => 'kunci-uji',
        'providers.check_id.selfhosted.resolve_ip' => '172.18.0.1',
    ]);

    Http::fake();

    $controller = app(ApiCheckController::class);
    $method = new ReflectionMethod($controller, 'connectSelfHosted');
    $method->setAccessible(true);

    $result = $method->invoke($controller, 'fc-mobile', '123', null);

    expect($result['status'])->toBeFalse();
    Http::assertNothingSent();
});

test('guard localhost tetap menolak walau resolve_ip terisi', function () {
    config([
        'providers.check_id.selfhosted.enabled' => true,
        'providers.check_id.selfhosted.base_url' => 'https://localhost',
        'providers.check_id.selfhosted.api_key' => 'kunci-uji',
        'providers.check_id.selfhosted.resolve_ip' => '172.18.0.1',
    ]);

    Http::fake();

    $controller = app(ApiCheckController::class);
    $method = new ReflectionMethod($controller, 'connectSelfHosted');
    $method->setAccessible(true);

    $result = $method->invoke($controller, 'fc-mobile', '123', null);

    expect($result['status'])->toBeFalse();
    Http::assertNothingSent();
});

test('URL request tetap memakai nama publik, bukan IP resolve', function () {
    config([
        'providers.check_id.selfhosted.enabled' => true,
        'providers.check_id.selfhosted.base_url' => 'https://cekid.contoh.test',
        'providers.check_id.selfhosted.api_key' => 'kunci-uji',
        'providers.check_id.selfhosted.resolve_ip' => '172.18.0.1',
    ]);

    Http::fake([
        'cekid.contoh.test/*' => Http::response([
            'code' => 200,
            'status' => true,
            'message' => 'ID berhasil ditemukan',
            'data' => ['username' => 'PEMAIN', 'user_id' => '123'],
            'provider' => 'duniagames',
        ], 200),
    ]);

    $controller = app(ApiCheckController::class);
    $method = new ReflectionMethod($controller, 'connectSelfHosted');
    $method->setAccessible(true);

    $result = $method->invoke($controller, 'fc-mobile', '123', null);

    expect($result['status'])->toBeTrue();
    expect($result['nickname'])->toBe('PEMAIN');

    Http::assertSent(function ($request) {
        $url = (string) $request->url();

        // Skema dan host harus tetap nama publik → sertifikat tetap relevan.
        return str_starts_with($url, 'https://cekid.contoh.test/api/check')
            && ! str_contains($url, '172.18.0.1');
    });
});
