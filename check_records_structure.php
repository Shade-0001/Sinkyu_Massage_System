<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "recordsテーブルのカラム情報:\n";
$columns = DB::select("DESCRIBE records");
foreach ($columns as $col) {
    echo sprintf("  - %s (%s)\n", $col->Field, $col->Type);
}
