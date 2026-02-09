<?php
$coords = json_decode(file_get_contents('i:\XAMPP\htdocs\Sinkyu_Massage_System\storage\app\config\medical_assistance_massage_coordinates.json'), true);

echo "remarksの座標情報:\n";
echo json_encode($coords['remarks'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

echo "\n座標チェック:\n";
echo "x = " . $coords['remarks']['x'] . " (23が期待値)\n";
echo "y = " . $coords['remarks']['y'] . " (198.5が期待値)\n";
echo "maxCharsPerLine = " . ($coords['remarks']['maxCharsPerLine'] ?? 'なし') . "\n";
