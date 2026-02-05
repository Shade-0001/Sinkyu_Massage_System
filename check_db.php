<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$clinicUser = DB::table('clinic_users')->where('id', 298)->first();
if ($clinicUser) {
    echo "clinic_user_id=298:\n";
    echo "  名前: " . ($clinicUser->last_name ?? '') . " " . ($clinicUser->first_name ?? '') . "\n";
    echo "  郵便番号: " . ($clinicUser->postal_code ?? '') . "\n";
} else {
    echo "clinic_user_id=298 が見つからない\n";
}

$clinicInfo = DB::table('clinic_info')->first();
if ($clinicInfo) {
    echo "\nclinic_info:\n";
    echo "  院名: " . ($clinicInfo->clinic_name ?? '') . "\n";
    echo "  電話番号: " . ($clinicInfo->tel ?? '') . "\n";
    echo "  郵便番号: " . ($clinicInfo->postal_code ?? '') . "\n";
    echo "  住所: " . ($clinicInfo->address ?? '') . "\n";
}
