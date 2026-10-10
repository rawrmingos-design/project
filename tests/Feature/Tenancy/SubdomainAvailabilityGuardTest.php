<?php

use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\Contracts\CloudflareDnsClientInterface;
use App\Tenancy\SubdomainAvailabilityGuard;
use App\Tenancy\SubdomainReservationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Fake Cloudflare client: tidak menyentuh jaringan sama sekali.
 */
function fakeCloudflare(array $hostnames = [], ?string $throw = null): CloudflareDnsClientInterface
{
    return new class($hostnames, $throw) implements CloudflareDnsClientInterface
    {
        public function __construct(
            private array $hostnames,
            private ?string $throw,
        ) {}

        public function hostnamesUnder(string $zoneId, string $suffix): array
        {
            if ($this->throw !== null) {
                throw new RuntimeException($this->throw);
            }

            return $this->hostnames;
        }
    };
}

function bindCloudflare(CloudflareDnsClientInterface $client): void
{
    app()->instance(CloudflareDnsClientInterface::class, $client);
}

/** Setel konfigurasi agar lapisan DNS benar-benar aktif. */
function withDnsLayer(string $base = 'test.jasakoding.web.id'): void
{
    config([
        'app.url' => 'https://' . $base,
        'tenancy.cloudflare.zone_id' => 'zone-uji-123',
        'tenancy.cloudflare.api_token' => 'token-uji',
    ]);
}

function guard(): SubdomainAvailabilityGuard
{
    return app(SubdomainAvailabilityGuard::class);
}

// ---------------------------------------------------------------------------
// 1. Daftar reserved: SETIAP nama harus ditolak (Q1 = B, konservatif)
// ---------------------------------------------------------------------------

test('menolak seluruh nama di daftar reserved', function (string $nama) {
    bindCloudflare(fakeCloudflare());

    $hasil = guard()->check($nama);

    expect($hasil['available'])->toBeFalse()
        ->and($hasil['reason'])->not->toBeNull();
})->with(SubdomainReservationPolicy::reserved());

test('daftar reserved mencakup infrastruktur yang WAJIB diblokir', function (string $nama) {
    expect(SubdomainReservationPolicy::isReserved($nama))->toBeTrue();
})->with([
    // platform
    'admin', 'api', 'app', 'assets', 'cdn', 'docs', 'mail', 'static', 'support', 'www',
    // email & DNS infra
    'smtp', 'imap', 'pop', 'pop3', 'mx', 'ns1', 'ns2', 'webmail', 'autodiscover', 'autoconfig',
    'ftp', 'cpanel', 'whm',
    // host environment (nyata dipakai di zona jasakoding)
    'test', 'dev', 'demo', 'ws', 'n8n', 'kiro', 'notion', 'notion-ai',
    // layanan nyata di zona
    'store-a', 'store-b', 'cekid', 'mt5', 'vorspad', 'wagateway', 'multindoku',
    // klien
    'imhaf',
]);

// ---------------------------------------------------------------------------
// 2. Nama wajar harus DITERIMA (Q5: egymarket bebas)
// ---------------------------------------------------------------------------

test('menerima nama yang wajar', function (string $nama) {
    bindCloudflare(fakeCloudflare());

    $hasil = guard()->check($nama);

    expect($hasil['available'])->toBeTrue()
        ->and($hasil['reason'])->toBeNull();
})->with(['egymarket', 'tokoku', 'tokosaya', 'jualgame', 'topupcepat']);

// ---------------------------------------------------------------------------
// 3. Fail-open saat Cloudflare error (Q3 = B)
// ---------------------------------------------------------------------------

test('fail-open ketika klien Cloudflare melempar error', function () {
    bindCloudflare(fakeCloudflare(throw: 'cloudflare down'));

    $hasil = guard()->check('tokoku');

    expect($hasil['available'])->toBeTrue()
        ->and($hasil['reason'])->toBeNull();
});

test('fail-open ketika kredensial Cloudflare tidak dikonfigurasi', function () {
    config(['tenancy.cloudflare.zone_id' => null, 'tenancy.cloudflare.api_token' => null]);

    // Klien nyata akan melempar karena kredensial kosong; guard harus meloloskannya.
    $hasil = guard()->check('tokoku');

    expect($hasil['available'])->toBeTrue();
});

// ---------------------------------------------------------------------------
// 4. Menangkap nama di LUAR daftar reserved via Cloudflare (Q6 = B)
// ---------------------------------------------------------------------------

test('menolak nama yang sudah punya record DNS walau tidak ada di daftar reserved', function () {
    withDnsLayer();
    bindCloudflare(fakeCloudflare(['nama-aneh-xyz.test.jasakoding.web.id']));

    $hasil = guard()->check('nama-aneh-xyz');

    expect($hasil['available'])->toBeFalse()
        ->and($hasil['reason'])->not->toBeNull();
});

test('wildcard tidak dianggap sebagai nama terpakai', function () {
    withDnsLayer();
    // Klien harus menyaring wildcard; guard tidak boleh menolak karena ada "*"
    bindCloudflare(fakeCloudflare(['*.test.jasakoding.web.id']));

    expect(guard()->check('tokoku')['available'])->toBeTrue();
});

// ---------------------------------------------------------------------------
// 5. Saklar guard (Q4)
// ---------------------------------------------------------------------------

test('guard bisa dimatikan lewat saklar tanpa mengubah daftar reserved', function () {
    config(['tenancy.subdomain_guard_enabled' => false]);
    bindCloudflare(fakeCloudflare());

    // 'webmail' ada di reserved, tapi guard mati -> lolos
    expect(guard()->check('webmail')['available'])->toBeTrue();
});

test('saklar guard aktif secara default', function () {
    expect((bool) config('tenancy.subdomain_guard_enabled'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// 6. Normalisasi & format
//    Catatan: normalizer proyek ini LENIENT — huruf besar di-lowercase, karakter
//    tak sah jadi dash, dash berulang dirapikan, dash di tepi dibuang.
//    Jadi yang "ditolak" hanya yang TETAP tak sah setelah normalisasi.
// ---------------------------------------------------------------------------

test('menolak nama yang tetap tak sah setelah normalisasi', function (string $nama) {
    bindCloudflare(fakeCloudflare());

    expect(guard()->check($nama)['available'])->toBeFalse();
})->with([
    'kosong'   => '',
    'satu'     => 'a',
    'dua'      => 'ab',
    'semua-dash' => '---',
    'terlalu-panjang' => str_repeat('a', 64),
]);

test('menormalkan huruf besar & karakter tak sah menjadi dash', function (string $input, string $expected) {
    bindCloudflare(fakeCloudflare());

    expect(guard()->check($input)['subdomain'])->toBe($expected);
})->with([
    ['TokoKu', 'tokoku'],
    ['  Toko-Ku  ', 'toko-ku'],
    ['toko ku', 'toko-ku'],
    ['toko_ku', 'toko-ku'],
    ['toko--ku', 'toko-ku'],
    ['-toko-', 'toko'],
]);


test('menolak nama yang sudah dipakai tenant di database', function () {
    bindCloudflare(fakeCloudflare());

    $owner = User::factory()->create(['role' => 'Gold']);
    Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Sudah Ada',
        'subdomain' => 'sudahada',
        'tier' => 'starter',
        'status' => Tenant::STATUS_PENDING_PAYMENT,
    ]);

    $hasil = guard()->check('sudahada');

    expect($hasil['available'])->toBeFalse();
});

test('mengembalikan subdomain yang sudah dinormalisasi', function () {
    bindCloudflare(fakeCloudflare());

    $hasil = guard()->check('  Toko-Ku  ');

    expect($hasil['subdomain'])->toBe('toko-ku');
});

// ---------------------------------------------------------------------------
// 7. Cache: klien tidak boleh dipanggil berkali-kali untuk hasil yang sama? 
//    (guard sendiri tidak cache; klien yang cache. Di sini cukup pastikan
//     guard memanggil klien SEKALI per check.)
// ---------------------------------------------------------------------------

test('memanggil klien Cloudflare sekali per pemeriksaan', function () {
    withDnsLayer();
    $spy = new class implements CloudflareDnsClientInterface
    {
        public int $calls = 0;

        public function hostnamesUnder(string $zoneId, string $suffix): array
        {
            $this->calls++;

            return [];
        }
    };
    bindCloudflare($spy);

    guard()->check('tokoku');
    guard()->check('toko-lain');

    expect($spy->calls)->toBe(2);
});
