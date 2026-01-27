<?php
require __DIR__.'/vendor/autoload.php';
$exists1 = class_exists('Filament\Pages\Auth\Login');
$exists2 = class_exists('Filament\Auth\Pages\Login');
echo "Class 1 (Pages\Auth): " . ($exists1 ? "YES" : "NO") . "\n";
echo "Class 2 (Auth\Pages): " . ($exists2 ? "YES" : "NO") . "\n";
