<?php
require 'bootstrap/app.php';
$app = require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$therapyTypes = DB::table('records')
  ->select('therapy_type')
  ->distinct()
  ->get();

echo "療法タイプ:\n";
foreach ($therapyTypes as $type) {
  echo "  " . $type->therapy_type . "\n";
}

// サンプルデータを確認
$sampleRecords = DB::table('records')->limit(3)->get();
echo "\nサンプルレコード:\n";
foreach ($sampleRecords as $record) {
  echo "  Date: " . $record->date . ", Type: " . $record->therapy_type . "\n";
}
