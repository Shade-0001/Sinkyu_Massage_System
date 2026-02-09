<?php
// はり・きゅう版で使用されているフィールドを取得
$acuContent = file_get_contents('i:\XAMPP\htdocs\Sinkyu_Massage_System\app\Services\Print\Traits\MedicalAssistanceAcupunctureFormFieldsTrait.php');
preg_match_all("/drawTextByKey\([^,]+, '([^']+)'/", $acuContent, $acuMatches);
preg_match_all("/drawEllipseByKey\([^,]+, '([^']+)'/", $acuContent, $acuEllipseMatches);
$acuFields = array_unique(array_merge($acuMatches[1], $acuEllipseMatches[1]));

// マッサージ版で使用されているフィールドを取得
$massContent = file_get_contents('i:\XAMPP\htdocs\Sinkyu_Massage_System\app\Services\Print\Traits\MedicalAssistanceMassageFormFieldsTrait.php');
preg_match_all("/drawTextByKey\([^,]+, '([^']+)'/", $massContent, $massMatches);
preg_match_all("/drawEllipseByKey\([^,]+, '([^']+)'/", $massContent, $massEllipseMatches);
$massFields = array_unique(array_merge($massMatches[1], $massEllipseMatches[1]));

// マッサージ版の座標ファイルを読み込み
$massCoords = json_decode(file_get_contents('i:\XAMPP\htdocs\Sinkyu_Massage_System\storage\app\config\medical_assistance_massage_coordinates.json'), true);

echo "はり・きゅう版にあるが、マッサージ版にないフィールド:\n";
echo "（かつマッサージ版の座標ファイルに定義されているもの）\n\n";

$missing = [];
foreach ($acuFields as $field) {
  if (!in_array($field, $massFields) && isset($massCoords[$field])) {
    $coord = $massCoords[$field];
    $x = $coord['x'] ?? 'N/A';
    $y = $coord['y'] ?? 'N/A';
    $type = $coord['type'] ?? 'unknown';
    
    $missing[] = [
      'field' => $field,
      'type' => $type,
      'x' => $x,
      'y' => $y
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
    echo "  - {$item['field']} (x={$item['x']}, y={$item['y']})\n";
  }
  echo "\n";
}

echo "合計: " . count($missing) . "個\n";
