<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tables = DB::select('SHOW TABLES');
echo "療法関連テーブル:\n";
foreach ($tables as $table) {
    $tableName = array_values((array)$table)[0];
    if (strpos($tableName, 'therapy') !== false || strpos($tableName, 'treatment') !== false) {
        echo "  - " . $tableName . "\n";
    }
}
