<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "行政規定準拠後の初検料判定ロジック検証\n";
echo str_repeat('=', 120) . "\n";

// テストケース
$testCases = [
    [
        'name' => 'はり･きゅう併用のみ',
        'counts' => [13 => 1],
        'expected_key' => 'fee_initial_examination_combined',
        'expected_fee' => 'hari_and_kyu_first'
    ],
    [
        'name' => 'はり･きゅう併用 + 電気温灸器',
        'counts' => [13 => 1, 15 => 1],
        'expected_key' => 'fee_initial_examination_combined',
        'expected_fee' => 'hari_and_kyu_first'
    ],
    [
        'name' => 'はり + きゅう',
        'counts' => [11 => 1, 12 => 1],
        'expected_key' => 'fee_initial_examination_combined',
        'expected_fee' => 'hari_and_kyu_first'
    ],
    [
        'name' => 'はり + きゅう + 電気針',
        'counts' => [11 => 1, 12 => 1, 14 => 1],
        'expected_key' => 'fee_initial_examination_combined',
        'expected_fee' => 'hari_and_kyu_first'
    ],
    [
        'name' => 'はりのみ',
        'counts' => [11 => 1],
        'expected_key' => 'fee_initial_examination_hari',
        'expected_fee' => 'hari_first'
    ],
    [
        'name' => 'はり + 電気針',
        'counts' => [11 => 1, 14 => 1],
        'expected_key' => 'fee_initial_examination_hari_electric',
        'expected_fee' => 'hari_and_elec_needle_first'
    ],
    [
        'name' => 'きゅうのみ',
        'counts' => [12 => 1],
        'expected_key' => 'fee_initial_examination_kyu',
        'expected_fee' => 'kyu_first'
    ],
    [
        'name' => 'きゅう + 電気温灸器',
        'counts' => [12 => 1, 15 => 1],
        'expected_key' => 'fee_initial_examination_kyu_electric',
        'expected_fee' => 'kyu_and_elec_moxa_heater_first'
    ],
];

foreach ($testCases as $i => $test) {
    echo "\n【テスト" . ($i + 1) . "】" . $test['name'] . "\n";
    echo str_repeat('-', 120) . "\n";

    $therapyTypeCounts = $test['counts'];

    // 修正後のロジックをシミュレーション
    $hariCount = ($therapyTypeCounts[11] ?? 0) + ($therapyTypeCounts[13] ?? 0);
    $kyuCount = ($therapyTypeCounts[12] ?? 0) + ($therapyTypeCounts[13] ?? 0);
    $hariElectricCount = $therapyTypeCounts[14] ?? 0;
    $kyuElectricCount = $therapyTypeCounts[15] ?? 0;
    $hariKyuElectricCount = ($therapyTypeCounts[16] ?? 0) + ($therapyTypeCounts[17] ?? 0);

    $hasElectric = ($hariElectricCount + $kyuElectricCount + $hariKyuElectricCount) > 0;

    $key = null;
    $feeDbKey = null;

    // 行政規定準拠ロジック
    if ($hariCount > 0 && $kyuCount > 0) {
        $key = 'fee_initial_examination_combined';
        $feeDbKey = 'hari_and_kyu_first';
    } elseif ($hariCount > 0) {
        if ($hasElectric) {
            $key = 'fee_initial_examination_hari_electric';
            $feeDbKey = 'hari_and_elec_needle_first';
        } else {
            $key = 'fee_initial_examination_hari';
            $feeDbKey = 'hari_first';
        }
    } elseif ($kyuCount > 0) {
        if ($hasElectric) {
            $key = 'fee_initial_examination_kyu_electric';
            $feeDbKey = 'kyu_and_elec_moxa_heater_first';
        } else {
            $key = 'fee_initial_examination_kyu';
            $feeDbKey = 'kyu_first';
        }
    }

    $status = ($key === $test['expected_key'] && $feeDbKey === $test['expected_fee']) ? '✓ PASS' : '✗ FAIL';

    echo "  カウント: はり={$hariCount}, きゅう={$kyuCount}, 電療=" . ($hasElectric ? 'あり' : 'なし') . "\n";
    echo "  判定結果: key={$key}, feeDbKey={$feeDbKey}\n";
    echo "  期待値: key={$test['expected_key']}, feeDbKey={$test['expected_fee']}\n";
    echo "  結果: {$status}\n";
}

echo "\n" . str_repeat('=', 120) . "\n";
echo "全テスト完了\n";
