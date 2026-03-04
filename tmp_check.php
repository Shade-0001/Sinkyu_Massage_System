<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cols = DB::connection()->getSchemaBuilder()->getColumnListing('consents_massage');
echo "consents_massage: " . implode(', ', $cols) . "\n";
exit;

// clinic_user_id=100 の保険情報確認
$insurance = DB::table('insurances')
  ->leftJoin('self_or_family', 'insurances.self_or_family_id', '=', 'self_or_family.id')
  ->where('insurances.clinic_user_id', 100)
  ->orderBy('insurances.created_at', 'desc')
  ->select('insurances.id', 'insurances.self_or_family_id', 'self_or_family.subject_type')
  ->first();

echo "insurance: " . json_encode($insurance) . "\n";

// self_or_family テーブルの全データ確認
$selfOrFamilyAll = DB::table('self_or_family')->get();
echo "self_or_family table: " . json_encode($selfOrFamilyAll) . "\n";

// clinic_user_id=100 の利用者情報確認
$clinicUser = DB::table('clinic_users')
  ->leftJoin('gender', 'clinic_users.gender_id', '=', 'gender.id')
  ->where('clinic_users.id', 100)
  ->select('clinic_users.id', 'clinic_users.gender_id', 'gender.gender', 'clinic_users.birthday', 'clinic_users.postal_code', 'clinic_users.address_1')
  ->first();

echo "clinic_user: " . json_encode($clinicUser) . "\n";
