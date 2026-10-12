<?php

namespace Tests\Unit\Tenancy;

use App\Tenancy\TenantAddressMasker;
use PHPUnit\Framework\TestCase;

/**
 * D9: nama tenant disensor — huruf pertama + bintang sejumlah huruf yang
 * diganti + huruf terakhir. Jumlah bintang = jumlah karakter yang diganti.
 */
class TenantAddressMaskerTest extends TestCase
{
    private TenantAddressMasker $masker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->masker = new TenantAddressMasker();
    }

    /** @dataProvider labelProvider */
    public function test_masks_label_sesuai_aturan(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->masker->maskLabel($input));
    }

    public static function labelProvider(): array
    {
        return [
            // Contoh nyata dari keputusan Mings.
            'istana' => ['istana', 'i****a'],
            'toko' => ['toko', 't**o'],
            'warung-topup' => ['warung-topup', 'w**********p'],

            // n <= 1: tidak ada yang bisa disensor, tampilkan apa adanya.
            'x' => ['x', 'x'],

            // n = 2: satu karakter tengah diganti.
            'ab' => ['ab', 'a*'],

            // n = 3: bintang tepat satu.
            'abc' => ['abc', 'a*c'],

            // Huruf besar tetap dipertahankan.
            'Istana' => ['Istana', 'I****a'],

            // Nama berangka ikut disensor karakter-per-karakter.
            'toko123' => ['toko123', 't*****3'],
        ];
    }

    public function test_label_kosong_tetap_kosong(): void
    {
        $this->assertSame('', $this->masker->maskLabel(''));
    }

    public function test_masks_host_hanya_pada_label_pertama(): void
    {
        // D9: hanya label pertama (subdomain) yang disensor; basis domain utuh.
        $this->assertSame(
            'w**********p.topupengine.test',
            $this->masker->maskHost('warung-topup.topupengine.test', 'topupengine.test')
        );

        $this->assertSame(
            't**o.topupengine.test',
            $this->masker->maskHost('toko.topupengine.test', 'topupengine.test')
        );
    }

    public function test_host_base_domain_tanpa_subdomain_tidak_disensor(): void
    {
        // Basis domain bukan identitas tenant — menyensor namanya hanya
        // membuat alamat salah tanpa menambah privasi.
        $this->assertSame(
            'topupengine.test',
            $this->masker->maskHost('topupengine.test', 'topupengine.test')
        );
        $this->assertSame('localhost', $this->masker->maskHost('localhost', 'topupengine.test'));
        $this->assertSame('127.0.0.1', $this->masker->maskHost('127.0.0.1', 'topupengine.test'));
        $this->assertSame('', $this->masker->maskHost('', 'topupengine.test'));
    }

    public function test_host_mempertahankan_port(): void
    {
        $this->assertSame(
            't**o.topupengine.test:8000',
            $this->masker->maskHost('toko.topupengine.test:8000', 'topupengine.test')
        );
    }

    public function test_tanpa_basis_domain_tetap_menyensor_label_pertama(): void
    {
        // Gagal-tertutup: tanpa basis domain, label pertama tetap disensor.
        $this->assertSame('t**o.example.com', $this->masker->maskHost('toko.example.com', ''));
    }

    public function test_subdomain_bersarang_tidak_disensor(): void
    {
        // Subdomain bersarang bukan tenant (ResolveTenant menolaknya) — alamat
        // dibiarkan apa adanya supaya tidak menyesatkan.
        $this->assertSame(
            'a.b.topupengine.test',
            $this->masker->maskHost('a.b.topupengine.test', 'topupengine.test')
        );
    }

    public function test_jumlah_bintang_sama_dengan_jumlah_karakter_yang_diganti(): void
    {
        foreach (['istana', 'toko', 'warung-topup', 'abcdefghi'] as $label) {
            $masked = $this->masker->maskLabel($label);

            $this->assertSame(
                strlen($label),
                strlen($masked),
                "Panjang hasil sensor harus sama dengan aslinya untuk '{$label}'."
            );
            $this->assertSame(
                substr_count($masked, '*'),
                strlen($label) - 2,
                "Jumlah bintang untuk '{$label}' harus n-2."
            );
        }
    }
}
