<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

if (DB::connection()->getSchemaBuilder()->hasTable('illnesses_acupuncture')) {
    echo "illnesses_acupunctureテーブルは存在します\n";
    $columns = DB::connection()->getSchemaBuilder()->getColumnListing('illnesses_acupuncture');
    echo "カラム数: " . count($columns) . "\n";
    foreach ($columns as $col) {
        echo "- $col\n";
    }
} else {
    echo "illnesses_acupunctureテーブルは存在しません\n";
}
