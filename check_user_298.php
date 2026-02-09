<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$clinicUser = DB::table('clinic_users')
  ->leftJoin('gender', 'clinic_users.gender_id', '=', 'gender.id')
  ->where('clinic_users.id', 298)
  ->select('clinic_users.*', 'gender.gender')
  ->first();

echo "clinic_user_id=298のデータ:\n";
echo json_encode($clinicUser, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

$fullName = ($clinicUser->last_name ?? '') . ' ' . ($clinicUser->first_name ?? '');
$fullNameKana = ($clinicUser->last_kana ?? '') . ' ' . ($clinicUser->first_kana ?? '');
echo "fullName: " . $fullName . "\n";
echo "fullNameKana: " . $fullNameKana . "\n";
