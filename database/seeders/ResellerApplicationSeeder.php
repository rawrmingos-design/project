<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResellerApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find or create admin user for reviewed_by references
        $admin = User::where('role', 'Admin')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin',
                'username' => 'admin_test',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'no_wa' => '6281234567899',
                'role' => 'Admin',
            ]);
        }

        // Scenario 1: Pending Application (just submitted)
        $pendingUser = User::create([
            'name' => 'John Doe',
            'username' => 'johndoe_reseller',
            'email' => 'john.reseller@example.com',
            'password' => Hash::make('password'),
            'no_wa' => '6281234567890',
            'role' => 'Member',
        ]);

        DB::table('reseller_applications')->insert([
            'user_id' => $pendingUser->id,
            'status' => 'pending',
            'applied_at' => now()->subHours(2),
            'business_meta' => json_encode([
                'business_name' => 'Toko Game Jaya',
                'business_url' => 'https://instagram.com/tokogamejaya',
                'estimated_monthly_transactions' => 150,
                'application_reason' => 'Saya memiliki konter game yang sudah berjalan 2 tahun dan ingin expand dengan API H2H untuk meningkatkan efisiensi operasional.',
            ]),
            'submitted_from_ip' => '203.0.113.50',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createDocuments($pendingUser->id, 'pending');
        $this->createReviewLog($pendingUser->id, 'submitted');

        // Scenario 2: Approved Application (already Gold user)
        $approvedUser = User::create([
            'name' => 'Jane Smith',
            'username' => 'janesmith_reseller',
            'email' => 'jane.reseller@example.com',
            'password' => Hash::make('password'),
            'no_wa' => '6281234567891',
            'role' => 'Gold',
        ]);

        DB::table('reseller_applications')->insert([
            'user_id' => $approvedUser->id,
            'status' => 'approved',
            'applied_at' => now()->subDays(5),
            'approved_at' => now()->subDays(4),
            'reviewed_by' => $admin->id,
            'business_meta' => json_encode([
                'business_name' => 'Store Game Pro',
                'business_url' => 'https://shopee.co.id/storegamepro',
                'estimated_monthly_transactions' => 300,
                'application_reason' => 'Saya punya toko di Shopee dengan 5000+ sales dan rating 4.9. Ingin expand dengan sistem H2H.',
            ]),
            'submitted_from_ip' => '203.0.113.51',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(4),
        ]);

        $this->createDocuments($approvedUser->id, 'approved');
        $this->createReviewLog($approvedUser->id, 'submitted', null, null, now()->subDays(5));
        $this->createReviewLog($approvedUser->id, 'approved', $admin->id, 'Verified store, documents clear. Approved.', now()->subDays(4));

        // Scenario 3: Rejected Application
        $rejectedUser = User::create([
            'name' => 'Bob Wilson',
            'username' => 'bobwilson_reseller',
            'email' => 'bob.reseller@example.com',
            'password' => Hash::make('password'),
            'no_wa' => '6281234567892',
            'role' => 'Member',
        ]);

        DB::table('reseller_applications')->insert([
            'user_id' => $rejectedUser->id,
            'status' => 'rejected',
            'applied_at' => now()->subDays(3),
            'rejected_at' => now()->subDays(2),
            'reviewed_by' => $admin->id,
            'rejection_reason' => 'Business proof tidak jelas. Instagram account tidak ditemukan. Mohon submit ulang dengan bukti yang lebih jelas.',
            'business_meta' => json_encode([
                'business_name' => 'Game Shop',
                'business_url' => 'https://instagram.com/fakeshop123',
                'estimated_monthly_transactions' => 50,
                'application_reason' => 'Mau jualan game online.',
            ]),
            'submitted_from_ip' => '203.0.113.52',
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(2),
        ]);

        $this->createDocuments($rejectedUser->id, 'rejected');
        $this->createReviewLog($rejectedUser->id, 'submitted', null, null, now()->subDays(3));
        $this->createReviewLog($rejectedUser->id, 'rejected', $admin->id, 'Business proof tidak jelas, Instagram tidak valid.', now()->subDays(2));

        $this->command->info('✓ Created 3 test users with reseller applications');
        $this->command->info('✓ Created 9 documents (3 per user)');
        $this->command->info('✓ Created 6 review log entries');
    }

    private function createDocuments(int $userId, string $status): void
    {
        $types = ['identity', 'selfie', 'business_proof'];

        foreach ($types as $type) {
            DB::table('reseller_documents')->insert([
                'user_id' => $userId,
                'document_type' => $type,
                'file_path' => "reseller_documents/{$userId}/{$type}_" . Str::random(10) . ".jpg",
                'file_name' => "{$type}.jpg",
                'file_size' => rand(100000, 2000000),
                'mime_type' => 'image/jpeg',
                'status' => $status,
                'notes' => $status === 'rejected' ? 'Image quality insufficient' : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function createReviewLog(int $userId, string $action, ?int $reviewedBy = null, ?string $notes = null, $createdAt = null): void
    {
        DB::table('reseller_application_reviews')->insert([
            'user_id' => $userId,
            'action' => $action,
            'reviewed_by' => $reviewedBy,
            'notes' => $notes,
            'created_at' => $createdAt ?? now(),
        ]);
    }
}
