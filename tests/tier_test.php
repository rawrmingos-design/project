<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Pembelian;
use App\Models\SettingWeb;

echo "--- Tier System Verification ---\n";

// 1. Setup
$settings = SettingWeb::first();
if (!$settings) {
    echo "Error: SettingWeb not found.\n";
    exit(1);
}

$gold = $settings->trx_count_gold;
$platinum = $settings->trx_count_platinum;

echo "Thresholds: Gold=$gold, Platinum=$platinum\n";

$user = User::where('username', 'test_tier_user')->first();
if (!$user) {
    $user = User::forceCreate([
        'name' => 'Test Tier',
        'username' => 'test_tier_user',
        'email' => 'test_tier@example.com',
        'password' => bcrypt('password'),
        'role' => 'Member',
        'balance' => 0,
        'no_wa' => '08123456789'
    ]);
} else {
    // Reset
    $user->update(['role' => 'Member']);
    echo "Reset user role to Member.\n";
    Pembelian::where('username', 'test_tier_user')->delete();
    echo "Deleted old test transactions.\n";
}

function log_msg($msg) {
    echo $msg;
    file_put_contents('test_log.txt', $msg, FILE_APPEND);
    // flush(); // Not needed for file_put_contents but maybe script is stuck
}

// Clear log
file_put_contents('test_log.txt', '');

// Debug Schema
$columns = \Illuminate\Support\Facades\Schema::getColumnListing('pembelians');
log_msg("Pembelians Columns: " . implode(', ', $columns) . "\n");

// 2. Create Gold Threshold transactions
log_msg("Creating $gold transactions to reach Gold...\n");
for ($i = 1; $i <= $gold; $i++) {
    try {
        Pembelian::create([
            'username' => $user->username,
            'order_id' => 'TEST-' . uniqid() . "-$i",
            'user_id' => '12345',
            'status' => 'Success',
            'harga' => 1000,
            'profit' => 100,
            'layanan' => 'Test Layer'
        ]);
    } catch (\Exception $e) {
        log_msg("ERROR Creating Transaction: " . $e->getMessage() . "\n");
        exit(1);
    }
    
    // Check intermediate
    if ($i == $gold - 1) {
        $user->refresh();
        if ($user->role !== 'Member') echo "WARNING: Upgraded too early at $i transactions!\n";
    }
}

$user->refresh();
echo "Role after $gold tx: " . $user->role . "\n";

if ($user->role !== 'Gold') {
    echo "FAILED: Expected Gold, got " . $user->role . "\n";
} else {
    echo "PASSED: Upgraded to Gold.\n";
}

// 3. Upgrade to Platinum
$remaining = $platinum - $gold;
echo "Creating $remaining more transactions to reach Platinum...\n";
for ($i = 1; $i <= $remaining; $i++) {
    Pembelian::create([
        'username' => $user->username,
        'order_id' => 'TEST-PLAT-' . uniqid() . "-$i",
        'user_id' => '12345',
        'status' => 'Success',
        'harga' => 1000,
        'profit' => 100,
        'layanan' => 'Test Layer',
        // 'kategori_id' => 1,
    ]);
}

$user->refresh();
echo "Role after $platinum tx: " . $user->role . "\n";

if ($user->role !== 'Platinum') {
    echo "FAILED: Expected Platinum, got " . $user->role . "\n";
} else {
    echo "PASSED: Upgraded to Platinum.\n";
}

echo "--- Verification Complete ---\n";
