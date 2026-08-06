<?php

namespace Database\Seeders;

use App\Models\InboundSourcePolicy;
use Illuminate\Database\Seeder;

class InboundWhitelistBotWebhookSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Telegram Webhook Policy
        $telegramPolicy = InboundSourcePolicy::updateOrCreate(
            ['source_domain' => 'bot_webhook', 'source_name' => 'telegram'],
            [
                'mode' => 'enforce',
                'is_active' => true,
                'description' => 'Official Telegram Bot API webhook subnets',
            ]
        );

        $telegramSubnets = [
            '149.154.160.0/20' => 'Telegram Bot Subnet 1',
            '91.108.4.0/22'    => 'Telegram Bot Subnet 2',
        ];

        foreach ($telegramSubnets as $cidr => $label) {
            $telegramPolicy->entries()->updateOrCreate(
                ['value' => $cidr],
                [
                    'value_type' => 'cidr_ipv4',
                    'label' => $label,
                    'is_active' => true,
                ]
            );
        }

        // 2. Fonnte Webhook Policy
        $fonntePolicy = InboundSourcePolicy::updateOrCreate(
            ['source_domain' => 'bot_webhook', 'source_name' => 'fonnte'],
            [
                'mode' => 'enforce',
                'is_active' => true,
                'description' => 'Fonnte WhatsApp Gateway IP Addresses',
            ]
        );

        $fonnteIps = [
            '202.162.212.1' => 'Fonnte Primary Server',
        ];

        foreach ($fonnteIps as $ip => $label) {
            $fonntePolicy->entries()->updateOrCreate(
                ['value' => $ip],
                [
                    'value_type' => 'ipv4',
                    'label' => $label,
                    'is_active' => true,
                ]
            );
        }
    }
}
