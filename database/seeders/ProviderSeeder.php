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
                'api_username' => $settings->username_digi,
                'api_key' => $settings->api_key_digi,
                'api_endpoint' => 'https://api.digiflazz.com',
            ],
            [
                'code' => 'bangjeff',
                'name' => 'BANGJEFF',
                'api_username' => null, // Bangjeff usually only key
                'api_key' => $settings->apikey_bangjeff,
                'api_endpoint' => 'https://client.bangjeff.com/api/v2',
            ],
            [
                'code' => 'vip',
                'name' => 'VIP RESELLER',
                'api_username' => $settings->vip_apiid,
                'api_key' => $settings->vip_apikey,
                'api_endpoint' => 'https://vip-reseller.co.id/api',
            ],
            [
                'code' => 'apigames',
                'name' => 'APIGAMES',
                'api_username' => $settings->apigames_merchant,
                'api_key' => $settings->apigames_secret,
                'api_endpoint' => 'https://v1.apigames.id',
            ],
            [
                'code' => 'manual',
                'name' => 'MANUAL',
                'api_username' => null,
                'api_key' => null,
                'api_endpoint' => null,
            ]
        ];

        foreach ($providers as $data) {
            Provider::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'api_username' => $data['api_username'],
                    'api_key' => $data['api_key'],
                    'api_endpoint' => $data['api_endpoint'],
                    'is_active' => true,
                ]
            );
        }
    }
}
