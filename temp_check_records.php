<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$columns = DB::connection()->getSchemaBuilder()->getColumns('records');

foreach ($columns as $column) {
  if (stripos($column['name'], 'therapy') !== false) {
    echo $column['name'] . "\n";
  }
}
