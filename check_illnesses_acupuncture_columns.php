<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$columns = DB::connection()->getSchemaBuilder()->getColumnListing('illnesses_acupuncture');
echo "illnesses_acupuncture テーブルのカラム:\n";
foreach ($columns as $col) {
    echo "- $col\n";
}
