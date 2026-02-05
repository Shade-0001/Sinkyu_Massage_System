<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$clinicInfo = DB::table('clinic_info')->first();
if ($clinicInfo) {
    echo "clinic_info テーブル内容:\n";
    foreach ((array)$clinicInfo as $key => $value) {
        if (in_array($key, ['postal_code', 'address', 'address_1', 'address_2', 'address_3', 'clinic_name', 'tel', 'phone'])) {
            echo "  $key: " . ($value ?? '(null)') . "\n";
        }
    }
}

echo "\n\nカラム一覧:\n";
$columns = DB::connection()->getSchemaBuilder()->getColumnListing('clinic_info');
foreach ($columns as $col) {
    echo "  - $col\n";
}
