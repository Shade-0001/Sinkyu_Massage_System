<?php
$coords = json_decode(file_get_contents('i:\XAMPP\htdocs\Sinkyu_Massage_System\storage\app\config\medical_assistance_massage_coordinates.json'), true);
$traitContent = file_get_contents('i:\XAMPP\htdocs\Sinkyu_Massage_System\app\Services\Print\Traits\MedicalAssistanceMassageFormFieldsTrait.php');

preg_match_all("/drawTextByKey\([^,]+, '([^']+)'/", $traitContent, $matches);
preg_match_all("/drawEllipseByKey\([^,]+, '([^']+)'/", $traitContent, $ellipseMatches);
preg_match_all("/drawCheckmarkByKey\([^,]+, '([^']+)'/", $traitContent, $checkMatches);

$usedFields = array_unique(array_merge($matches[1], $ellipseMatches[1], $checkMatches[1]));

// 重要そうなフィールドのみをフィルタリング
$importantKeywords = [
  'patient', 'insured', 'insurance', 'illness', 'onset', 'treatment',
  'consent', 'doctor', 'clinic', 'age', 'address', 'postal', 'phone',
  'date', 'period', 'therapist', 'license', 'locality', 'recipient',
  'insurer', 'number', 'code', 'name', 'type'
];

echo "重要そうで描画処理がないフィールド:\n\n";
$missing = [];
foreach ($coords as $key => $config) {
  if (in_array($key, $usedFields)) continue;
  
  // 重要キーワードを含むかチェック
  $isImportant = false;
  foreach ($importantKeywords as $keyword) {
    if (stripos($key, $keyword) !== false) {
      $isImportant = true;
      break;
    }
  }
  
  if ($isImportant && $config['x'] != 0 && $config['y'] != 0) {
    $type = $config['type'] ?? 'unknown';
    $missing[] = [
      'key' => $key,
      'type' => $type,
      'x' => $config['x'],
      'y' => $config['y']
    ];
  }
}

// タイプ別にグループ化
$grouped = [];
foreach ($missing as $item) {
  $grouped[$item['type']][] = $item;
}

foreach ($grouped as $type => $items) {
  echo "[{$type}]\n";
  foreach ($items as $item) {
    echo "  - {$item['key']} (x={$item['x']}, y={$item['y']})\n";
  }
  echo "\n";
}

echo "合計: " . count($missing) . "個\n";
