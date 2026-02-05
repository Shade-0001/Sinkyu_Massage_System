<?php
$json = json_decode(file_get_contents('storage/app/config/medical_assistance_acupuncture_coordinates.json'), true);

echo "=== Y座標180~210の施術所関連フィールド ===\n";
$filtered = array_filter($json, function($v, $k) {
    $isClinic = stripos($k, 'clinic') !== false || stripos($k, 'therapist') !== false;
    $inRange = isset($v['y']) && $v['y'] >= 180 && $v['y'] <= 210;
    return $isClinic && $inRange;
}, ARRAY_FILTER_USE_BOTH);
uksort($filtered, function($a, $b) use ($filtered) {
    return $filtered[$a]['y'] <=> $filtered[$b]['y'];
});
foreach ($filtered as $key => $value) {
    echo sprintf("Y=%-6s %s\n", $value['y'], $key);
}

echo "\n=== therapist関連フィールド ===\n";
$therapistFields = array_filter($json, function($k) {
    return stripos($k, 'therapist') !== false;
}, ARRAY_FILTER_USE_KEY);
uksort($therapistFields, function($a, $b) use ($therapistFields) {
    return ($therapistFields[$a]['y'] ?? 999) <=> ($therapistFields[$b]['y'] ?? 999);
});
foreach ($therapistFields as $key => $value) {
    echo sprintf("Y=%-6s %s\n", $value['y'] ?? 'なし', $key);
}
