<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $tables = DB::select('SHOW TABLES');
    $tableNames = array_map(function($table) {
        return array_values((array)$table)[0];
    }, $tables);

    echo "Tables found: " . implode(", ", $tableNames) . "\n";
    
    // Check key tables
    if (in_array('vouchers', $tableNames)) {
        $count = DB::table('vouchers')->count();
        echo "Vouchers count: $count\n";
    } else {
        echo "CRITICAL: 'vouchers' table missing!\n";
    }
    
    if (in_array('pembelians', $tableNames)) {
        $count = DB::table('pembelians')->count();
        echo "Pembelians count: $count\n";
    } else {
        echo "CRITICAL: 'pembelians' table missing!\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
