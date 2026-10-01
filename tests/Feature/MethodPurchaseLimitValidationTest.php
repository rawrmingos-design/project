<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Methods\Pages\EditMethod;
use App\Models\Method;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\AdminTestCase;

/**
 * Mengunci perbaikan "1264 Out of range value for column 'max_pembelian'".
 *
 * Kolomnya dulu INT (maks 2.147.483.647). Admin mengisi Maximum Pembelian
 * 100000000000 (100 miliar) dan penyimpanan gagal di MySQL STRICT_TRANS_TABLES.
 *
 * Migrasi melebarkan kolom ke BIGINT, tetapi migrasi itu di-skip pada sqlite
 * (driver test), jadi yang diuji di sini adalah PENJAGA FORM-nya: batas nilai
 * yang ditegakkan LINTAS DRIVER, tanpa bergantung pada mesin MySQL mana pun.
 */
class MethodPurchaseLimitValidationTest extends AdminTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function method(): Method
    {
        return Method::create([
            'name' => 'QRIS',
            'images' => '/assets/payment/qris-lama.webp',
            'code' => 'QRIS',
            'keterangan' => 'Metode pembayaran QRIS.',
            'tipe' => 'QRIS',
            'payment' => 'tripay',
            'fee_percent' => 0.7,
            'fix_fee' => 100,
            'min_pembelian' => 1000,
            'max_pembelian' => 15000000,
            'statuspayment' => true,
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Limit Uji',
            'username' => 'admin-limit-uji',
            'email' => 'admin-limit-uji@example.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'balance' => 0,
            'point_balance' => 0,
            'email_verified_at' => now(),
        ]);
    }

    public function test_nilai_yang_dulu_meledak_kini_tersimpan(): void
    {
        $this->actingAs($this->admin());

        $method = $this->method();

        Livewire::test(EditMethod::class, ['record' => $method->getRouteKey()])
            ->fillForm(['max_pembelian' => 100000000000])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            100000000000,
            (int) Method::query()->whereKey($method->id)->value('max_pembelian'),
            'Nilai 100 miliar harus tersimpan setelah kolom dilebarkan ke BIGINT.'
        );
    }

    public function test_di_atas_batas_form_ditolak_dengan_pesan_form(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(EditMethod::class, ['record' => $this->method()->getRouteKey()])
            ->fillForm(['max_pembelian' => 1000000000000])
            ->call('save')
            ->assertHasFormErrors(['max_pembelian']);
    }

    public function test_batas_atas_yang_panjang_tetap_bisa_disimpan(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(EditMethod::class, ['record' => $this->method()->getRouteKey()])
            ->fillForm(['max_pembelian' => 999999999999])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            999999999999,
            (int) Method::query()->where('code', 'QRIS')->value('max_pembelian')
        );
    }

    public function test_kosong_berarti_tanpa_batas(): void
    {
        $this->actingAs($this->admin());

        $method = $this->method();

        Livewire::test(EditMethod::class, ['record' => $method->getRouteKey()])
            ->fillForm(['min_pembelian' => null, 'max_pembelian' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = Method::query()->whereKey($method->id)->firstOrFail();

        $this->assertNull($fresh->min_pembelian);
        $this->assertNull($fresh->max_pembelian);
    }

    public function test_nol_berarti_tanpa_batas(): void
    {
        $this->actingAs($this->admin());

        // SATU instance saja: `methods.code` divalidasi unik di form, jadi
        // membuat dua metode ber-kode sama dalam satu test menggagalkan form.
        $method = $this->method();

        Livewire::test(EditMethod::class, ['record' => $method->getRouteKey()])
            ->fillForm(['min_pembelian' => 0, 'max_pembelian' => 0])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = Method::query()->whereKey($method->id)->firstOrFail();

        $this->assertSame(0, (int) $fresh->min_pembelian);
        $this->assertSame(0, (int) $fresh->max_pembelian);
    }

    public function test_nilai_negatif_ditolak(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(EditMethod::class, ['record' => $this->method()->getRouteKey()])
            ->fillForm(['max_pembelian' => -1])
            ->call('save')
            ->assertHasFormErrors(['max_pembelian']);
    }

    public function test_pecahan_ditolak(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(EditMethod::class, ['record' => $this->method()->getRouteKey()])
            ->fillForm(['max_pembelian' => 1500.75])
            ->call('save')
            ->assertHasFormErrors(['max_pembelian']);
    }

    public function test_simpan_tanpa_upload_logo_tidak_menghapus_logo_lama(): void
    {
        $this->actingAs($this->admin());

        $method = $this->method();

        // Foto lama hanya ada sebagai path di kolom `images` (tidak terdaftar
        // sebagai Media Asset) — kondisi nyata 4 dari 5 metode di staging.
        Livewire::test(EditMethod::class, ['record' => $method->getRouteKey()])
            ->fillForm([
                'name' => 'QRIS Diubah',
                'images' => null,
                'images_input_mode' => 'upload',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = Method::query()->where('code', 'QRIS')->firstOrFail();

        $this->assertSame('QRIS Diubah', $fresh->name, 'Perubahan lain harus tersimpan.');
        $this->assertSame(
            '/assets/payment/qris-lama.webp',
            $fresh->getRawOriginal('images'),
            'Logo lama tidak boleh terhapus saat admin tidak mengunggah apa pun.'
        );
    }
}
