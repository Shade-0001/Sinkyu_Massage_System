<?php
$json = json_decode(file_get_contents('storage/app/config/medical_assistance_massage_coordinates.json'), true);

echo "施術日関連のフィールド:\n\n";

foreach ($json as $key => $value) {
  $label = $value['label'] ?? '';
  if (strpos($label, '施術日') !== false || strpos($label, '施術年月日') !== false) {
    echo $key . ' => ' . $label . ' (x=' . ($value['x'] ?? 'N/A') . ', y=' . ($value['y'] ?? 'N/A') . ')' . "\n";
  }
}
