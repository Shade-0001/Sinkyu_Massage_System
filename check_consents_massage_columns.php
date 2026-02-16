<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$columns = DB::connection()->getSchemaBuilder()->getColumnListing('consents_massage');
echo "consents_massage テーブルのカラム:\n";
foreach ($columns as $col) {
    echo "- $col\n";
}
