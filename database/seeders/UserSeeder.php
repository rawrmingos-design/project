<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating users...');
        
        // Create Admin user
        User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@topup.com',
            'password' => Hash::make('password'),
            'no_wa' => '081234567890',
            'balance' => 1000000,
            'role' => 'Admin',
        ]);
        $this->command->info('✓ Admin user created');
        
        // Create Platinum users (2)
        $platinumUsers = [
            [
                'name' => 'Budi Platinum',
                'username' => 'budi_platinum',
                'email' => 'budi.platinum@example.com',
                'no_wa' => '081234567891',
                'balance' => 500000,
            ],
            [
                'name' => 'Siti Platinum',
                'username' => 'siti_platinum',
                'email' => 'siti.platinum@example.com',
                'no_wa' => '081234567892',
                'balance' => 750000,
            ],
        ];
        
        foreach ($platinumUsers as $userData) {
            User::create(array_merge($userData, [
                'password' => Hash::make('password'),
                'role' => 'Platinum',
            ]));
        }
        $this->command->info('✓ 2 Platinum users created');
        
        // Create Gold users (3)
        $goldUsers = [
            [
                'name' => 'Andi Gold',
                'username' => 'andi_gold',
                'email' => 'andi.gold@example.com',
                'no_wa' => '081234567893',
                'balance' => 300000,
            ],
            [
                'name' => 'Dewi Gold',
                'username' => 'dewi_gold',
                'email' => 'dewi.gold@example.com',
                'no_wa' => '081234567894',
                'balance' => 250000,
            ],
            [
                'name' => 'Rizki Gold',
                'username' => 'rizki_gold',
                'email' => 'rizki.gold@example.com',
                'no_wa' => '081234567895',
                'balance' => 400000,
            ],
        ];
        
        foreach ($goldUsers as $userData) {
            User::create(array_merge($userData, [
                'password' => Hash::make('password'),
                'role' => 'Gold',
            ]));
        }
        $this->command->info('✓ 3 Gold users created');
        
        // Create Member users (10)
        $memberNames = [
            'Ahmad Member', 'Rina Member', 'Doni Member', 'Lina Member', 'Yanto Member',
            'Sri Member', 'Bambang Member', 'Wati Member', 'Joko Member', 'Fitri Member'
        ];
        
        foreach ($memberNames as $index => $name) {
            $username = strtolower(str_replace(' ', '_', $name));
            User::create([
                'name' => $name,
                'username' => $username,
                'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
                'password' => Hash::make('password'),
                'no_wa' => '08123456789' . ($index + 6),
                'balance' => rand(50, 200) * 1000, // Random balance between 50k-200k
                'role' => 'Member',
            ]);
        }
        $this->command->info('✓ 10 Member users created');
        
        $totalUsers = User::count();
        $this->command->info("✓ Total {$totalUsers} users created successfully!");
        $this->command->info('  Default password for all users: password');
    }
}
