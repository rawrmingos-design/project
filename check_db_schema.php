<?php
$files = ['topup (2).sql', 'egymarke_production.sql'];
foreach($files as $file) {
    echo "\n--- $file ---\n";
    $content = file_get_contents($file);
    if(preg_match('/CREATE TABLE `?pembelians`?\s*\((.*?)\)\s*ENGINE/si', $content, $m)) {
        echo trim($m[1]) . "\n";
    } else {
        echo "CREATE TABLE statement not found\n";
    }
}
