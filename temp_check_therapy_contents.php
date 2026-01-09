<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== therapy_contents table structure ===\n";
$columns = DB::connection()->getSchemaBuilder()->getColumns('therapy_contents');
foreach ($columns as $column) {
  echo "- " . $column['name'] . " (" . $column['type_name'] . ")\n";
}

echo "\n=== therapy_contents data ===\n";
$data = DB::table('therapy_contents')->get();
foreach ($data as $row) {
  echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
}
