<?php
$json = json_decode(file_get_contents('storage/app/config/medical_assistance_massage_coordinates.json'), true);

echo "代理人・委任者関連のフィールド:\n\n";

foreach ($json as $key => $value) {
  if (isset($value['label']) && (strpos($value['label'], '代理人') !== false || strpos($value['label'], '委任者') !== false)) {
    echo $key . ' => ' . $value['label'] . ' (x=' . ($value['x'] ?? 'N/A') . ', y=' . ($value['y'] ?? 'N/A') . ')' . "\n";
  }
}
