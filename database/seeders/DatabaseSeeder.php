<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('=====================================');
        $this->command->info('  Starting Database Seeding Process  ');
        $this->command->info('=====================================');
        $this->command->newLine();
        
        // Seed users first
        $this->command->info('Step 1: Seeding Users...');
        $this->call(UserSeeder::class);
        $this->command->newLine();
        
        // Then seed deposits (depends on users)
        $this->command->info('Step 2: Seeding Deposits...');
        $this->call(DepositSeeder::class);
        $this->command->newLine();
        
        $this->command->info('=====================================');
        $this->command->info('  Database Seeding Completed!  ');
        $this->command->info('=====================================');
        $this->command->newLine();
        $this->command->info('You can now login with:');
        $this->command->info('  Email: admin@topup.com');
        $this->command->info('  Password: password');
    }
}
