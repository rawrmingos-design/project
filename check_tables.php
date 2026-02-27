<?php
preg_match_all('/CREATE TABLE \`(.*?)\`/i', file_get_contents('topup (2).sql'), $m1);
preg_match_all('/CREATE TABLE \`(.*?)\`/i', file_get_contents('egymarke_production.sql'), $m2);
$tables1 = array_unique($m1[1]);
$tables2 = array_unique($m2[1]);
$missing_in_prod = array_diff($tables1, $tables2);
$missing_in_new = array_diff($tables2, $tables1);

echo "Tables in topup: " . count($tables1) . "\n";
echo "Tables in prod: " . count($tables2) . "\n";
if (empty($missing_in_prod)) {
    echo "NO TABLES MISSING IN PROD.\n";
} else {
    echo "MISSING IN PROD: " . implode(', ', $missing_in_prod) . "\n";
}
