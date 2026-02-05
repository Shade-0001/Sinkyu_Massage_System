<?php
$json = json_decode(file_get_contents('storage/app/config/medical_assistance_acupuncture_coordinates.json'), true);
$keys = array_keys($json);
$filtered = array_filter($keys, function($k) {
    return stripos($k, 'clinic') !== false 
        || stripos($k, 'postal_code') !== false 
        || stripos($k, 'address') !== false 
        || stripos($k, 'name') !== false 
        || stripos($k, 'tel') !== false;
});
sort($filtered);
foreach ($filtered as $key) {
    echo $key . "\n";
}
