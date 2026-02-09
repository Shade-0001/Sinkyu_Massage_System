<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$insurance = DB::table('insurances')
  ->leftJoin('insurers', 'insurances.insurers_id', '=', 'insurers.id')
  ->leftJoin('relationships_with_clinic_user', 'insurances.relationship_with_clinic_user_id', '=', 'relationships_with_clinic_user.id')
  ->leftJoin('expenses_borne_ratios', 'insurances.expenses_borne_ratio_id', '=', 'expenses_borne_ratios.id')
  ->leftJoin('insurance_types_1', 'insurances.insurance_type_1_id', '=', 'insurance_types_1.id')
  ->leftJoin('insurance_types_3', 'insurances.insurance_type_3_id', '=', 'insurance_types_3.id')
  ->where('insurances.clinic_user_id', 298)
  ->orderBy('insurances.created_at', 'desc')
  ->select('insurances.*')
  ->first();

echo "insurancesテーブルのカラム一覧:\n";
$columns = DB::connection()->getSchemaBuilder()->getColumns('insurances');
foreach ($columns as $col) {
  echo "  - " . $col['name'] . " (" . $col['type_name'] . ")\n";
}

echo "\n\nclinic_user_id=298の保険情報:\n";
echo json_encode($insurance, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
