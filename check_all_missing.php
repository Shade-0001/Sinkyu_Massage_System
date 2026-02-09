<?php
// 座標ファイルから全フィールドを取得
$coords = json_decode(file_get_contents('i:\XAMPP\htdocs\Sinkyu_Massage_System\storage\app\config\medical_assistance_massage_coordinates.json'), true);

// 描画処理で使用されているフィールドを取得
$traitContent = file_get_contents('i:\XAMPP\htdocs\Sinkyu_Massage_System\app\Services\Print\Traits\MedicalAssistanceMassageFormFieldsTrait.php');
preg_match_all("/drawTextByKey\([^,]+, '([^']+)'/", $traitContent, $matches);
preg_match_all("/drawEllipseByKey\([^,]+, '([^']+)'/", $traitContent, $ellipseMatches);
preg_match_all("/drawCheckmarkByKey\([^,]+, '([^']+)'/", $traitContent, $checkMatches);

$usedFields = array_unique(array_merge($matches[1], $ellipseMatches[1], $checkMatches[1]));

echo "座標定義されているが描画処理がないフィールド:\n";
$missing = [];
foreach ($coords as $key => $config) {
  if (!in_array($key, $usedFields)) {
    $type = $config['type'] ?? 'unknown';
    $missing[] = ['key' => $key, 'type' => $type, 'x' => $config['x'], 'y' => $config['y']];
  }
}

// タイプ別にソート
usort($missing, function($a, $b) {
  if ($a['type'] !== $b['type']) {
    return strcmp($a['type'], $b['type']);
  }
  return strcmp($a['key'], $b['key']);
});

$currentType = '';
foreach ($missing as $item) {
  if ($currentType !== $item['type']) {
    $currentType = $item['type'];
    echo "\n[{$item['type']}]\n";
  }
  echo "  - {$item['key']} (x={$item['x']}, y={$item['y']})\n";
}

echo "\n\n合計: " . count($missing) . "個のフィールドが描画処理なし\n";
