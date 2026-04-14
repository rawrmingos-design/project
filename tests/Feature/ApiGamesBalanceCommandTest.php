<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiGamesBalanceCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_apigames_balance_command_prints_saldo_summary(): void
    {
        Http::fake([
            'https://v1.apigames.id/merchant/demo-merchant*' => Http::response([
                'status' => 1,
                'message' => 'Sukses !',
                'data' => [
                    'merchant_id' => 'demo-merchant',
                    'nama' => 'Demo Merchant',
                    'saldo' => 245,
                ],
            ]),
        ]);

        $this->artisan('apigames:balance', [
            '--merchant' => 'demo-merchant',
            '--secret' => 'demo-secret',
        ])
            ->expectsTable(
                ['Field', 'Value'],
                [
                    ['Merchant ID', 'demo-merchant'],
                    ['Name', 'Demo Merchant'],
                    ['Saldo', 'Rp 245'],
                ]
            )
            ->assertExitCode(0);
    }
}
