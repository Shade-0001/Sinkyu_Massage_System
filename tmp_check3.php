<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo "=== consents_massage columns ===\n";
$cols = DB::connection()->getSchemaBuilder()->getColumnListing('consents_massage');
echo implode(', ', $cols) . "\n\n";
echo "=== consents_massage sample (therapy columns) ===\n";
$row = DB::table('consents_massage')->first();
if ($row) {
    $arr = (array)$row;
    foreach (array_keys($arr) as $k) {
        if (strpos($k, 'therapy') !== false || strpos($k, 'period') !== false || strpos($k, 'consent') !== false || strpos($k, 'insured') !== false) {
            echo "$k: " . $arr[$k] . "\n";
        }
    }
}
