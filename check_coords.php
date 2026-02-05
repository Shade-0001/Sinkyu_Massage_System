<?php
$json = json_decode(file_get_contents('storage/app/config/medical_assistance_acupuncture_coordinates.json'), true);

echo "Y座標245以下のフィールド:\n";
foreach ($json as $key => $value) {
    if (isset($value['y']) && $value['y'] >= 245) {
        echo sprintf("  %s: Y=%s\n", $key, $value['y']);
    }
}
