<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$clinicUserId = 298;

// MedicalAssistanceMassagePdfServiceと同じクエリでconsentを取得
$consent = DB::table('consents_massage')
  ->leftJoin('bill_categories', 'consents_massage.bill_category_id', '=', 'bill_categories.id')
  ->leftJoin('outcomes', 'consents_massage.outcome_id', '=', 'outcomes.id')
  ->leftJoin('work_scope_types', 'consents_massage.work_scope_type_id', '=', 'work_scope_types.id')
  ->leftJoin('illnesses_massage', 'consents_massage.injury_and_illness_name_id', '=', 'illnesses_massage.id')
  ->leftJoin('conditions', 'consents_massage.condition_id', '=', 'conditions.id')
  ->where('consents_massage.clinic_user_id', $clinicUserId)
  ->orderBy('consents_massage.consenting_date', 'desc')
  ->select(
    'consents_massage.*',
    'bill_categories.bill_category',
    'outcomes.outcome',
    'work_scope_types.work_scope_type',
    'illnesses_massage.illness_name',
    'conditions.condition_name'
  )
  ->first();

echo "clinic_user_id={$clinicUserId}のconsent:\n";
echo "notes: " . ($consent->notes ?? 'null') . "\n";
echo "isset(\$consent->notes): " . (isset($consent->notes) ? 'true' : 'false') . "\n";
echo "!empty(\$consent->notes): " . (!empty($consent->notes) ? 'true' : 'false') . "\n";

echo "\n全データ:\n";
echo json_encode($consent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
