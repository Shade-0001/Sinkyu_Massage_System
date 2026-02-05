<?php
$json = json_decode(file_get_contents('storage/app/config/medical_assistance_acupuncture_coordinates.json'), true);

echo "=== Y座標245以下の氏名関連フィールド ===\n";
$filtered = array_filter($json, function($v, $k) {
    $isName = stripos($k, 'name') !== false;
    $inRange = isset($v['y']) && $v['y'] >= 245;
    return $isName && $inRange;
}, ARRAY_FILTER_USE_BOTH);
uksort($filtered, function($a, $b) use ($filtered) {
    return $filtered[$a]['y'] <=> $filtered[$b]['y'];
});
foreach ($filtered as $key => $value) {
    echo sprintf("Y=%-6s %s\n", $value['y'], $key);
}

echo "\n=== patient_name 関連フィールド（全座標） ===\n";
$patientFields = array_filter($json, function($k) {
    return stripos($k, 'patient_name') !== false;
}, ARRAY_FILTER_USE_KEY);
uksort($patientFields, function($a, $b) use ($patientFields) {
    return ($patientFields[$a]['y'] ?? 999) <=> ($patientFields[$b]['y'] ?? 999);
});
foreach ($patientFields as $key => $value) {
    echo sprintf("Y=%-6s %s\n", $value['y'] ?? 'なし', $key);
}
