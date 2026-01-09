<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$contents = Storage::disk('config')->get('acupuncture_benefit_coordinates.json');
$data = json_decode($contents, true);

echo "=== 料金関連キーの出現回数 ===\n";
echo "fee_hari_kyu_total: " . substr_count($contents, 'fee_hari_kyu_total') . "回\n";
echo "fee_kyu_total: " . substr_count($contents, 'fee_kyu_total') . "回\n";
echo "fee_hari_total: " . substr_count($contents, 'fee_hari_total') . "回\n";
echo "fee_electric_total: " . substr_count($contents, 'fee_electric_total') . "回\n";

echo "\n=== 実際のキー定義 ===\n";
if (isset($data['fee_hari_kyu_total'])) {
    echo "fee_hari_kyu_total: ";
    if (is_array($data['fee_hari_kyu_total'])) {
        echo "配列型 (要素数: " . count($data['fee_hari_kyu_total']) . ")\n";
        foreach ($data['fee_hari_kyu_total'] as $i => $coord) {
            echo "  [$i] x={$coord['x']}, y={$coord['y']}\n";
        }
    } else {
        echo "オブジェクト型\n";
        print_r($data['fee_hari_kyu_total']);
    }
}

if (isset($data['fee_kyu_total'])) {
    echo "fee_kyu_total: ";
    if (is_array($data['fee_kyu_total'])) {
        echo "配列型 (要素数: " . count($data['fee_kyu_total']) . ")\n";
        foreach ($data['fee_kyu_total'] as $i => $coord) {
            echo "  [$i] x={$coord['x']}, y={$coord['y']}\n";
        }
    } else {
        echo "オブジェクト型\n";
        print_r($data['fee_kyu_total']);
    }
}
