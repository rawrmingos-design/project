<?php

/**
 * Create storage symbolic link for Windows
 * Run this file directly: php create-storage-link.php
 */

$link = __DIR__ . '/public/storage';
$target = __DIR__ . '/storage/app/public';

// Remove existing link/directory if exists
if (file_exists($link)) {
    if (is_link($link)) {
        unlink($link);
        echo "Removed existing symbolic link.\n";
    } else {
        echo "Warning: 'public/storage' exists but is not a symbolic link.\n";
        echo "Please remove it manually first.\n";
        exit(1);
    }
}

// Create symbolic link
if (symlink($target, $link)) {
    echo "✓ Symbolic link created successfully!\n";
    echo "  Link: {$link}\n";
    echo "  Target: {$target}\n";
} else {
    echo "✗ Failed to create symbolic link.\n";
    echo "  On Windows, you may need to run as Administrator or enable Developer Mode.\n";
    echo "\n";
    echo "Alternative: Run PowerShell as Administrator and execute:\n";
    echo "  cd " . __DIR__ . "\n";
    echo "  cmd /c mklink /D public\\storage storage\\app\\public\n";
}
