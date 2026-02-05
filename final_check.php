<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== 実データ確認 ===\n\n";

// clinic_info
$clinicInfo = DB::table('clinic_info')->first();
echo "【clinic_info】\n";
echo "  postal_code: " . ($clinicInfo->postal_code ?? '') . "\n";
echo "  address: " . (($clinicInfo->address_1 ?? '') . ($clinicInfo->address_2 ?? '') . ($clinicInfo->address_3 ?? '')) . "\n";
echo "  clinic_name: " . ($clinicInfo->clinic_name ?? '') . "\n";
echo "  phone: " . ($clinicInfo->phone ?? '') . "\n";
echo "  owner: " . (($clinicInfo->owner_last_name ?? '') . ' ' . ($clinicInfo->owner_first_name ?? '')) . "\n";

// clinic_user 298
$user = DB::table('clinic_users')->where('id', 298)->first();
echo "\n【clinic_user_id=298】\n";
echo "  氏名: " . (($user->last_name ?? '') . ' ' . ($user->first_name ?? '')) . "\n";
echo "  郵便番号: " . ($user->postal_code ?? '') . "\n";
echo "  住所: " . (($user->address_1 ?? '') . ($user->address_2 ?? '') . ($user->address_3 ?? '')) . "\n";

// therapist
$therapist = DB::table('therapists')->first();
echo "\n【therapist】\n";
if ($therapist) {
    echo "  氏名: " . (($therapist->last_name ?? '') . ' ' . ($therapist->first_name ?? '')) . "\n";
} else {
    echo "  データなし\n";
}
