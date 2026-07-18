<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Data generated dari topup.sql
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('=====================================');
        $this->command->info('  Starting Database Seeding Process  ');
        $this->command->info('=====================================');
        $this->command->newLine();

        Schema::disableForeignKeyConstraints();

        try {
            // Urutan penting: foreign key dependencies
            $this->call([
                // Core config
                SettingWebsSeeder::class,
                UsersSeeder::class,

                // Kategori & Layanan
                CategoryTypesSeeder::class,
                KategorisSeeder::class,
                CustomInputsSeeder::class,
                ProvidersSeeder::class,
                LayanansSeeder::class,
                ProviderPathsSeeder::class,

                // Paket
                PaketsSeeder::class,
                PaketLayanansSeeder::class,

                // Payment
                MethodsSeeder::class,

                // Content
                BeritasSeeder::class,
                EmailTemplatesSeeder::class,
                WhatsappTemplatesSeeder::class,

                // Misc
                VouchersSeeder::class,
            ]);
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->command->newLine();
        $this->command->info('=====================================');
        $this->command->info('  Database Seeding Completed!        ');
        $this->command->info('=====================================');
    }
}
