<?php
$json = json_decode(file_get_contents('storage/app/config/medical_assistance_acupuncture_coordinates.json'), true);

echo "=== Y座標245~270の全フィールド ===\n";
$filtered = array_filter($json, function($v) {
    return isset($v['y']) && $v['y'] >= 245 && $v['y'] <= 270;
});
uksort($filtered, function($a, $b) use ($filtered) {
    return $filtered[$a]['y'] <=> $filtered[$b]['y'];
});
foreach ($filtered as $key => $value) {
    echo sprintf("Y=%-6s %s\n", $value['y'], $key);
}

echo "\n=== consent_record 関連フィールド ===\n";
$consentFields = array_filter($json, function($k) {
    return stripos($k, 'consent_record') !== false;
}, ARRAY_FILTER_USE_KEY);
uksort($consentFields, function($a, $b) use ($consentFields) {
    return ($consentFields[$a]['y'] ?? 999) <=> ($consentFields[$b]['y'] ?? 999);
});
foreach ($consentFields as $key => $value) {
    echo sprintf("Y=%-6s %s\n", $value['y'] ?? 'なし', $key);
}
