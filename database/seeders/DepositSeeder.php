<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DepositSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Payment methods available
        $paymentMethods = [
            'BCA', 'BNI', 'BRI', 'Mandiri', 
            'QRIS', 'OVO', 'DANA', 'GoPay', 'ShopeePay'
        ];
        
        // Get all users
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }
        
        $this->command->info('Creating deposits for ' . $users->count() . ' users...');
        
        $depositCount = 0;
        
        // Create deposits for each user
        foreach ($users as $user) {
            // Each user will have 2-5 deposits
            $numberOfDeposits = rand(2, 5);
            
            for ($i = 0; $i < $numberOfDeposits; $i++) {
                $metode = $paymentMethods[array_rand($paymentMethods)];
                
                // Generate payment number based on method
                $no_pembayaran = $this->generatePaymentNumber($metode);
                
                // Random amount between 10,000 and 500,000
                $jumlah = rand(10, 500) * 1000;
                
                // 70% Success, 30% Pending
                $status = (rand(1, 100) <= 70) ? 'Success' : 'Pending';
                
                // Random date within last 30 days
                $createdAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23));
                
                Deposit::create([
                    'order_id' => 'DEP-' . strtoupper(Str::random(10)),
                    'username' => $user->username ?? $user->email,
                    'metode' => $metode,
                    'no_pembayaran' => $no_pembayaran,
                    'jumlah' => $jumlah,
                    'status' => $status,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
                
                $depositCount++;
            }
        }
        
        $this->command->info("✓ Successfully created {$depositCount} deposits!");
    }
    
    /**
     * Generate payment number based on payment method
     */
    private function generatePaymentNumber(string $method): string
    {
        switch ($method) {
            case 'BCA':
            case 'BNI':
            case 'BRI':
            case 'Mandiri':
                // Bank account number (10 digits)
                return '12' . rand(10000000, 99999999);
                
            case 'OVO':
            case 'DANA':
            case 'GoPay':
            case 'ShopeePay':
                // Phone number
                return '08' . rand(100000000, 999999999);
                
            case 'QRIS':
                // QRIS reference number
                return 'QRIS' . rand(100000000000, 999999999999);
                
            default:
                return (string) rand(100000000000, 999999999999);
        }
    }
}
