<?php
$coords = json_decode(file_get_contents('i:\XAMPP\htdocs\Sinkyu_Massage_System\storage\app\config\medical_assistance_massage_coordinates.json'), true);

$missingFields = [
  'relationship',
  'insurer_name',
  'financial_institution_name_1',
  'financial_institution_name_2',
  'signature_date_year',
  'signature_date_month',
  'signature_date_day',
  'public_burden_ratio',
  'fee_public_burden_amount',
];

echo "座標ファイルに定義されているフィールド:\n";
foreach ($missingFields as $field) {
  if (isset($coords[$field])) {
    echo "  ✓ $field: x={$coords[$field]['x']}, y={$coords[$field]['y']}\n";
  } else {
    echo "  ✗ $field: 未定義\n";
  }
}
