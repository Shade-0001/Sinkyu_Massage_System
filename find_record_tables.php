<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "施術記録関連テーブル:\n";
$tables = DB::select('SHOW TABLES');
foreach ($tables as $table) {
    $tableName = array_values((array)$table)[0];
    if (strpos($tableName, 'clinic') !== false ||
        strpos($tableName, 'treatment') !== false ||
        strpos($tableName, 'record') !== false ||
        strpos($tableName, 'bill') !== false) {
        echo "  - " . $tableName . "\n";
    }
}
