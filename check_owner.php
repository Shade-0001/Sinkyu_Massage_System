<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$clinicInfo = DB::table('clinic_info')->first();
if ($clinicInfo) {
    echo "clinic_info:\n";
    echo "  owner_last_name: " . ($clinicInfo->owner_last_name ?? '(null)') . "\n";
    echo "  owner_first_name: " . ($clinicInfo->owner_first_name ?? '(null)') . "\n";
    echo "  結合: " . trim(($clinicInfo->owner_last_name ?? '') . ' ' . ($clinicInfo->owner_first_name ?? '')) . "\n";
}
