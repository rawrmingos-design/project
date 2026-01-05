<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tables = ['pembelians', 'vouchers', 'users'];
$results = Illuminate\Support\Facades\DB::select("SHOW TABLE STATUS WHERE Name IN ('" . implode("','", $tables) . "')");

foreach ($results as $row) {
    echo "Table: {$row->Name}, Engine: {$row->Engine}\n";
}
