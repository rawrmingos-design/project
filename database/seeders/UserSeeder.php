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

        // `users.email` dan `users.username` sekarang punya unique index, dan
        // `UsersSeeder` (dump legacy yang memakai username yang sama) bisa jalan
        // di database yang sama — lihat SeederSchemaCompatibilityTest. Karena itu
        // identitas di sini WAJIB berbeda dari dump itu; email dev dipisah ke
        // domain `@dev.local` supaya tidak pernah bertabrakan.
        $existingAdmin = User::query()->where('username', 'admin')->first();

        if ($existingAdmin === null) {
            // Create Admin user
            User::create([
                'name' => 'Administrator',
                'username' => 'admin',
                'email' => 'admin@dev.local',
                'password' => Hash::make('password'),
                'no_wa' => '081234567890',
                'balance' => 1000000,
                'role' => 'Admin',
            ]);
            $this->command->info('✓ Admin user created');
        } else {
            $this->command->info('→ Admin user sudah ada, dilewati');
        }
        
        // Create Platinum users (2)
        $platinumUsers = [
            [
                'name' => 'Budi Platinum',
                'username' => 'budi_platinum',
                'email' => 'budi.platinum@dev.local',
                'no_wa' => '081234567891',
                'balance' => 500000,
            ],
            [
                'name' => 'Siti Platinum',
                'username' => 'siti_platinum',
                'email' => 'siti.platinum@dev.local',
                'no_wa' => '081234567892',
                'balance' => 750000,
            ],
        ];
        
        foreach ($platinumUsers as $userData) {
            if (User::query()->where('username', $userData['username'])->exists()) {
                continue;
            }

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
                'email' => 'andi.gold@dev.local',
                'no_wa' => '081234567893',
                'balance' => 300000,
            ],
            [
                'name' => 'Dewi Gold',
                'username' => 'dewi_gold',
                'email' => 'dewi.gold@dev.local',
                'no_wa' => '081234567894',
                'balance' => 250000,
            ],
            [
                'name' => 'Rizki Gold',
                'username' => 'rizki_gold',
                'email' => 'rizki.gold@dev.local',
                'no_wa' => '081234567895',
                'balance' => 400000,
            ],
        ];
        
        foreach ($goldUsers as $userData) {
            if (User::query()->where('username', $userData['username'])->exists()) {
                continue;
            }

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

            // Idempoten: `UsersSeeder` (dump legacy) memakai username yang sama,
            // dan unique index di kolom `username` akan menolak baris kedua.
            // Urutan seeding tidak boleh menentukan hasil.
            if (User::query()->where('username', $username)->exists()) {
                continue;
            }

            User::create([
                'name' => $name,
                'username' => $username,
                'email' => strtolower(str_replace(' ', '.', $name)) . '@dev.local',
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
