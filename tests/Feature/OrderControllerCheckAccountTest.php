<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Services\CheckId\CheckIdResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderControllerCheckAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
        Http::preventStrayRequests();
    }

    public function test_resolver_skips_non_game_categories_for_all_entrypoints(): void
    {
        foreach (['voucher', 'joki', 'jokigendong', 'vilogml'] as $type) {
            $kategori = Kategori::factory()->create([
                'kode' => $type . '-category',
                'tipe' => $type,
            ]);

            $result = app(CheckIdResolver::class)->resolveForCategory($kategori, 'CUSTOM_UID', null);

            $this->assertSame(204, $result['status']['code']);
            $this->assertTrue($result['skip_check']);
        }

        Http::assertNothingSent();
    }

    public function test_check_account_skips_voucher_and_complex_categories(): void
    {
        foreach (['voucher', 'joki', 'jokigendong', 'vilogml'] as $type) {
            $category = Kategori::factory()->create([
                'kode' => $type . '-endpoint',
                'tipe' => $type,
            ]);

            $this->postJson('/ajax/check-account', [
                'uid' => 'CUSTOM_UID',
                'kategori_kode' => $category->kode,
            ])
                ->assertOk()
                ->assertJsonPath('status.code', 204)
                ->assertJsonPath('skip_check', true);
        }

        Http::assertNothingSent();
    }

    public function test_check_account_keeps_game_and_populer_as_validation_candidates(): void
    {
        foreach (['game', 'populer'] as $type) {
            $category = Kategori::factory()->create([
                'kode' => $type . '-endpoint',
                'tipe' => $type,
            ]);

            $this->postJson('/ajax/check-account', [
                'uid' => 'CUSTOM_UID',
                'kategori_kode' => $category->kode,
            ])
                ->assertStatus(200)
                ->assertJsonMissingPath('skip_check');
        }

        Http::assertNothingSent();
    }

    public function test_check_account_rejects_unknown_category(): void
    {
        $this->postJson('/ajax/check-account', [
            'uid' => 'CUSTOM_UID',
            'kategori_kode' => 'missing-category',
        ])->assertStatus(404);

        Http::assertNothingSent();
    }

    public function test_check_account_rejects_layanan_from_different_category(): void
    {
        $requestedKategori = Kategori::factory()->create([
            'kode' => 'custom-game',
            'tipe' => 'game',
        ]);
        $otherKategori = Kategori::factory()->create([
            'kode' => 'other-game',
            'tipe' => 'game',
        ]);

        $layanan = Layanan::factory()->create([
            'kategori_id' => $otherKategori->id,
        ]);

        $response = $this->postJson('/ajax/check-account', [
            'uid' => 'CUSTOM_UID',
            'kategori_kode' => $requestedKategori->kode,
            'service' => $layanan->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status.code', 422);

        Http::assertNothingSent();
    }
}
