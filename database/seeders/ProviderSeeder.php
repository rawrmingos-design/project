<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Provider;
use App\Models\SettingWeb;

class ProviderSeeder extends Seeder
{
    public function run()
    {
        $settings = SettingWeb::first();

        if (!$settings) {
            return;
        }

        $providers = [
            [
                'code' => 'digiflazz',
                'name' => 'DIGIFLAZZ',
                'api_endpoint' => 'https://api.digiflazz.com',
            ],
            [
                'code' => 'bangjeff',
                'name' => 'BANGJEFF',
                'api_endpoint' => 'https://client.bangjeff.com/api/v2',
            ],
            [
                'code' => 'vip',
                'name' => 'VIP RESELLER',
                'api_endpoint' => 'https://vip-reseller.co.id/api',
            ],
            [
                'code' => 'apigames',
                'name' => 'APIGAMES',
                'api_endpoint' => 'https://v1.apigames.id',
            ],
            [
                'code' => 'sufpayment',
                'name' => 'SUFPAYMENT',
                'api_endpoint' => 'https://sufpayment.com/api/v1',
            ],
            [
                'code' => 'manual',
                'name' => 'MANUAL',
                'api_endpoint' => null,
            ]
        ];

        foreach ($providers as $data) {
            Provider::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'api_endpoint' => $data['api_endpoint'],
                    'is_active' => true,
                ]
            );
        }
    }
}
