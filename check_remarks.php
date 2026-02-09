<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// consents_massageテーブル構造を確認
echo "consents_massageテーブルのカラム一覧:\n";
$columns = DB::connection()->getSchemaBuilder()->getColumns('consents_massage');
foreach ($columns as $col) {
  if (strpos($col['name'], 'cause') !== false || 
      strpos($col['name'], 'progress') !== false || 
      strpos($col['name'], 'remark') !== false ||
      strpos($col['name'], 'origin') !== false) {
    echo "  * " . $col['name'] . " (" . $col['type_name'] . ")\n";
  }
}

echo "\n\nclinic_user_id=298の同意書データ:\n";
$consent = DB::table('consents_massage')
  ->where('clinic_user_id', 298)
  ->orderBy('consenting_date', 'desc')
  ->first();

if ($consent) {
  echo json_encode($consent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
  echo "データなし\n";
}
