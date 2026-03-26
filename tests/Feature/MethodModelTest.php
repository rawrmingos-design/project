<?php

namespace Tests\Feature;

use App\Models\Method;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MethodModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_method_creation_normalizes_tipe_and_image_path_and_flushes_caches(): void
    {
        Cache::put('payment_methods', ['stale' => true], 600);
        Cache::put('payment_methods_all', ['stale' => true], 600);
        Cache::put('payment_methods_price_calc', ['stale' => true], 600);
        Cache::put('payment_methods_all_api', ['stale' => true], 600);

        $method = Method::query()->create([
            'name' => 'OVO',
            'images' => 'assets/thumbnail/ovo.webp',
            'code' => 'OVO',
            'keterangan' => 'OVO test',
            'tipe' => 'ewallet',
            'payment' => 'tripay',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'min_pembelian' => 1000,
            'max_pembelian' => 1000000,
            'statuspayment' => true,
        ]);

        $method->refresh();

        $this->assertSame('e-walet', $method->tipe);
        $this->assertSame('/assets/thumbnail/ovo.webp', $method->images);
        $this->assertNull(Cache::get('payment_methods'));
        $this->assertNull(Cache::get('payment_methods_all'));
        $this->assertNull(Cache::get('payment_methods_price_calc'));
        $this->assertNull(Cache::get('payment_methods_all_api'));
    }
}
