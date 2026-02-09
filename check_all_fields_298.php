<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$clinicUserId = 298;
$serviceYearMonth = '2025-01';

echo "=== clinic_user_id={$clinicUserId} のデータ確認 ===\n\n";

// 利用者情報
$clinicUser = DB::table('clinic_users')->where('id', $clinicUserId)->first();
echo "【利用者情報】\n";
echo "  氏名: {$clinicUser->last_name} {$clinicUser->first_name}\n";
echo "  郵便番号: {$clinicUser->postal_code}\n";
echo "  住所: {$clinicUser->address_1} {$clinicUser->address_2} {$clinicUser->address_3}\n\n";

// 施術所情報
$clinicInfo = DB::table('clinic_info')->first();
echo "【施術所情報（代理人情報）】\n";
echo "  郵便番号: {$clinicInfo->postal_code}\n";
echo "  住所: {$clinicInfo->address_1} {$clinicInfo->address_2} {$clinicInfo->address_3}\n";
echo "  開設者氏名: {$clinicInfo->owner_last_name} {$clinicInfo->owner_first_name}\n\n";

// 施術実績
$records = DB::table('records')
  ->where('clinic_user_id', $clinicUserId)
  ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$serviceYearMonth])
  ->orderBy('date')
  ->get();

echo "【施術実績】\n";
echo "  件数: {$records->count()}\n";
if ($records->isNotEmpty()) {
  echo "  初療年月日: {$records->first()->date}\n";
  echo "  施術期間: {$records->first()->date} ~ {$records->last()->date}\n";
}
