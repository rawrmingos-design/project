<?php

/**
 * Generate SQLite schema dump for testing.
 * 
 * UPDATED STRATEGY:
 * 1. Restore old migrations from git temporarily
 * 2. Run ALL migrations on SQLite
 * 3. Generate schema dump
 * 4. Clean up
 * 
 * Usage: php database/schema/generate-sqlite-schema.php
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tempDbPath = __DIR__ . '/temp-sqlite-dump.db';

// Clean up any existing temp database
if (file_exists($tempDbPath)) {
    @unlink($tempDbPath);
}

echo "=================================================\n";
echo "SQLite Schema Generation for Testing\n";
echo "=================================================\n\n";

echo "Step 1: Checking for squashed schema...\n";

// Check if we have the mysql schema dump (from squashing)
$mysqlSchemaPath = __DIR__ . '/mysql-schema.sql';
if (file_exists($mysqlSchemaPath)) {
    echo "✓ Found mysql-schema.sql (squashed schema)\n";
    echo "  We need to restore old migrations temporarily...\n\n";
    
    echo "Step 2: Restoring old migrations from git...\n";
    $migrationsDir = __DIR__ . '/../migrations';
    
    // Run git checkout to restore deleted migrations temporarily
    $output = [];
    $returnCode = 0;
    exec('cd ' . escapeshellarg(dirname(dirname(__DIR__))) . ' && git checkout database/migrations/*.php 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "✓ Old migrations restored from git\n";
    } else {
        echo "⚠ Could not restore migrations from git\n";
        echo "  This might be okay if migrations are already present\n";
    }
}

echo "\nStep 3: Creating temporary SQLite database...\n";

// Configure SQLite connection for schema generation
config(['database.connections.sqlite_schema_gen' => [
    'driver' => 'sqlite',
    'database' => $tempDbPath,
    'prefix' => '',
    'foreign_key_constraints' => true,
]]);

DB::purge('sqlite_schema_gen');
DB::reconnect('sqlite_schema_gen');
DB::setDefaultConnection('sqlite_schema_gen');

echo "✓ SQLite connection configured\n";

echo "\nStep 4: Running ALL migrations...\n";

// Run all migrations
Artisan::call('migrate', [
    '--database' => 'sqlite_schema_gen',
    '--force' => true,
]);

echo Artisan::output();

echo "\nStep 5: Extracting schema...\n";

// Get SQLite schema
$pdo = DB::connection('sqlite_schema_gen')->getPdo();
$stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get indexes
$stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='index' AND sql IS NOT NULL ORDER BY name");
$indexes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get migration records (critical for Laravel to know which migrations have run)
$stmt = $pdo->query("SELECT * FROM migrations ORDER BY id");
$migrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build schema dump
$schema = "-- SQLite Schema Dump for Testing\n";
$schema .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$schema .= "-- Laravel Migration Squashing - SQLite Version\n";
$schema .= "-- This schema is equivalent to mysql-schema.sql but adapted for SQLite\n\n";
$schema .= "PRAGMA foreign_keys=OFF;\n\n";

foreach ($tables as $sql) {
    if (!empty($sql)) {
        $schema .= $sql . ";\n\n";
    }
}

foreach ($indexes as $sql) {
    if (!empty($sql)) {
        $schema .= $sql . ";\n\n";
    }
}

// Add migration records so Laravel knows these migrations have already been run
if (!empty($migrations)) {
    $schema .= "-- Migration records\n";
    foreach ($migrations as $migration) {
        $id = $migration['id'];
        $name = str_replace("'", "''", $migration['migration']); // Escape single quotes
        $batch = $migration['batch'];
        $schema .= "INSERT INTO \"migrations\" VALUES ({$id}, '{$name}', {$batch});\n";
    }
    $schema .= "\n";
}

$schema .= "PRAGMA foreign_keys=ON;\n";

// Write to file
$outputPath = __DIR__ . '/sqlite-schema.sql';
file_put_contents($outputPath, $schema);

$tableCount = count($tables);
$indexCount = count($indexes);
$fileSize = filesize($outputPath);

echo "✓ Schema extracted successfully\n";
echo "  Output: {$outputPath}\n";
echo "  Size: " . number_format($fileSize) . " bytes\n";
echo "  Tables: {$tableCount}\n";
echo "  Indexes: {$indexCount}\n";

// Validation check
if ($tableCount < 10) {
    echo "\n⚠ WARNING: Only {$tableCount} tables exported!\n";
    echo "  Expected 20+ tables. Something might be wrong.\n";
} else {
    echo "\n✓ Schema looks good ({$tableCount} tables)\n";
}

echo "\nStep 6: Cleaning up...\n";

// Disconnect database
DB::disconnect('sqlite_schema_gen');

// Wait a bit before cleanup
sleep(1);

// Clean up temp database
if (file_exists($tempDbPath)) {
    $attempts = 0;
    while ($attempts < 3) {
        try {
            @unlink($tempDbPath);
            if (!file_exists($tempDbPath)) {
                echo "✓ Temporary database removed\n";
                break;
            }
        } catch (\Exception $e) {
            // File might be locked, wait and retry
        }
        $attempts++;
        sleep(1);
    }
    
    if (file_exists($tempDbPath)) {
        echo "⚠ Could not remove temp database (file locked)\n";
        echo "  You can manually delete: {$tempDbPath}\n";
    }
}

// Restore git state (undo the migration restore)
echo "\nStep 7: Restoring git state...\n";
exec('cd ' . escapeshellarg(dirname(dirname(__DIR__))) . ' && git checkout database/migrations/ 2>&1', $output, $returnCode);
if ($returnCode === 0) {
    echo "✓ Git state restored (migrations re-squashed)\n";
}

echo "\n=================================================\n";
echo "✓ SQLite Schema Generation Complete!\n";
echo "=================================================\n\n";

if ($tableCount >= 10) {
    echo "Next steps:\n";
    echo "  1. Review schema: head -n 100 database/schema/sqlite-schema.sql\n";
    echo "  2. Run tests: php artisan test --filter=ApiOrderRegressionTest\n";
    echo "  3. Commit: git add database/schema/sqlite-schema.sql\n";
} else {
    echo "⚠ Schema generation may have failed. Please check the output above.\n";
}
