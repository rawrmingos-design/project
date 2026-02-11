<?php

use App\Models\User;
use App\Models\Pembelian;
use App\Models\AffiliateHistory;
use App\Models\SettingWeb;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function log_msg($msg) {
    echo $msg . "\n";
    file_put_contents('test_affiliate_log.txt', $msg . "\n", FILE_APPEND);
}

// Clear log
file_put_contents('test_affiliate_log.txt', "");

log_msg("--- Affiliate System Verification ---");

try {
    // 1. Setup Data
    // Create Uplink User
    $uplink = User::firstOrCreate(
        ['username' => 'test_uplink'],
        [
            'name' => 'Test Uplink',
            'password' => bcrypt('password'),
            'email' => 'uplink@test.com',
            'role' => 'Member',
            'balance' => 0,
            'no_wa' => '081234567890',
            'referral_code' => 'REF-UPLINK'
        ]
    );
    // Reset balance
    $uplink->balance = 0;
    if ($uplink->referral_code !== 'REF-UPLINK') {
        $uplink->referral_code = 'REF-UPLINK';
    }
    $uplink->save();

    log_msg("Uplink User: {$uplink->username} (Balance: {$uplink->balance})");

    // Create Downlink User
    $downlink = User::firstOrCreate(
        ['username' => 'test_downlink'],
        [
            'name' => 'Test Downlink',
            'password' => bcrypt('password'),
            'email' => 'downlink@test.com',
            'role' => 'Member',
            'balance' => 0,
            'no_wa' => '081234567891',
            'uplink' => $uplink->username
        ]
    );
    $downlink->uplink = $uplink->username;
    $downlink->save();

    log_msg("Downlink User: {$downlink->username} (Uplink: {$downlink->uplink})");

    // Ensure Settings
    $setting = SettingWeb::first();
    if (!$setting) {
        $setting = new SettingWeb();
        $setting->save();
    }
    $setting->commission_percent = 20;
    $setting->save();
    log_msg("Commission Rate: {$setting->commission_percent}%");

    // Clear previous history for test
    AffiliateHistory::where('uplink_id', $uplink->id)->where('downlink_id', $downlink->id)->delete();
    // Delete previous test transactions to avoid clutter
    Pembelian::where('username', $downlink->username)->delete();

    // 2. Perform Transaction
    log_msg("Creating Success Transaction...");
    $orderId = 'ORD-' . Str::random(8);
    $profit = 10000;
    $expectedCommission = $profit * 0.20; // 2000

    $pembelian = Pembelian::create([
        'order_id' => $orderId,
        'user_id' => $downlink->id,
        'username' => $downlink->username,
        'status' => 'Success', 
        'profit' => $profit,
        'harga' => 20000,
        'layanan' => 'Test Service',
        // 'no_pembeli' removed
        // 'amount' removed
        'zone' => '1',
        'nickname' => 'Test Nick',
        'provider_order_id' => 'PROV-' . Str::random(5)
    ]);

    log_msg("Transaction Created: Order ID {$pembelian->order_id}");

    // 3. Verify
    $uplink->refresh();
    log_msg("Uplink New Balance: {$uplink->balance}");

    if ($uplink->balance == $expectedCommission) {
        log_msg("PASSED: Balance updated correctly (+{$expectedCommission})");
    } else {
        log_msg("FAILED: Balance mismatch. Expected {$expectedCommission}, got {$uplink->balance}");
    }

    $history = AffiliateHistory::where('order_id', $orderId)->first();
    if ($history) {
        log_msg("PASSED: History record found. Amount: {$history->amount}");
    } else {
        log_msg("FAILED: No history record found.");
    }

} catch (\Exception $e) {
    log_msg("ERROR EXCEPTION: " . $e->getMessage());
    log_msg($e->getTraceAsString());
}

log_msg("--- Verification Complete ---");
