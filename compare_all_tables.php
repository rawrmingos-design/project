<?php

function getSchemas($file) {
    $content = file_get_contents($file);
    preg_match_all('/CREATE TABLE \`(.*?)\` \((.*?)\) ENGINE=/si', $content, $matches);
    $schemas = [];
    foreach($matches[1] as $k => $table) {
        $columns = [];
        $lines = explode("\n", trim($matches[2][$k]));
        foreach($lines as $line) {
            $line = trim($line);
            // Cari definisi kolom yang berawal backtick
            if(preg_match('/^\`([^\`]+)\`(.*?),?$/', $line, $cm)) {
                $colName = $cm[1];
                $colDef = trim(rtrim(trim($cm[2]), ','));
                $columns[$colName] = $colDef;
            }
        }
        $schemas[$table] = $columns;
    }
    return $schemas;
}

$s1 = getSchemas('topup (2).sql'); // DB BARU
$s2 = getSchemas('egymarke_production.sql'); // DB LAMA

file_put_contents('diff_schemas.sql', "-- HASIL PERBANDINGAN SCHEMA\n\n");

$output = "-- 1. KOLOM YANG ADA DI DB BARU TAPI TIDAK ADA DI LAMA (HARUS DITAMBAHKAN KE PROD) --\n";
foreach($s1 as $table => $cols) {
    if(!isset($s2[$table])) {
        $output .= "-- Table $table is MISSING in prod entirely. You need to create it:\n";
        // Temukan CREATE TABLE statement utuh
        $content = file_get_contents('topup (2).sql');
        if(preg_match('/CREATE TABLE \`'.$table.'\` \((.*?)\) ENGINE=/si', $content, $m)) {
             $output .= "CREATE TABLE `$table` (".$m[1].") ENGINE=InnoDB;\n\n";
        }
        continue;
    }
    
    $addedCols = [];
    foreach($cols as $col => $def) {
        if(!isset($s2[$table][$col])) {
            $addedCols[] = "ADD `$col` $def";
        }
    }
    
    if (count($addedCols) > 0) {
        $output .= "ALTER TABLE `$table`\n  " . implode(",\n  ", $addedCols) . ";\n\n";
    }
}

file_put_contents('diff_schemas.sql', $output, FILE_APPEND);

$output2 = "-- 2. KOLOM YANG ADA DI DB LAMA TAPI TIDAK ADA DI BARU (INFO SAJA, MUNGKIN TIDAK DIPAKAI LAGI) --\n";
foreach($s2 as $table => $cols) {
    if(!isset($s1[$table])) {
        $output2 .= "-- Table $table is in Prod but MISSING in new db.\n";
        continue;
    }
    foreach($cols as $col => $def) {
        if(!isset($s1[$table][$col])) {
             $output2 .= "-- ALTER TABLE `$table` DROP `$col`; -- (exist in prod, missing in new)\n";
        }
    }
}

file_put_contents('diff_schemas.sql', $output2, FILE_APPEND);
echo "Berhasil membandingkan, silakan cek file diff_schemas.sql\n";
