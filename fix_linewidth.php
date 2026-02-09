<?php
$jsonPath = 'storage/app/config/medical_assistance_massage_coordinates.json';
$json = json_decode(file_get_contents($jsonPath), true);

$modified = 0;
foreach ($json as $key => &$value) {
  if (isset($value['lineWidth']) && $value['lineWidth'] == 0.5) {
    $value['lineWidth'] = 0.2;
    $modified++;
  }
}

file_put_contents($jsonPath, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "修正完了: {$modified} 個のフィールドのlineWidthを0.5から0.2に変更\n";
