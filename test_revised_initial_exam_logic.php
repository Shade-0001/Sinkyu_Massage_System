<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "修正後の初検料判定ロジック検証\n";
echo str_repeat('=', 120) . "\n";

// テストケース
$testCases = [
    [
        'name' => 'ID:13（はり･きゅう併用）のみ',
        'firstId' => 13,
        'counts' => [13 => 1],
        'expected_key' => 'fee_initial_examination_combined',
        'expected_fee' => 'hari_and_kyu_first'
    ],
    [
        'name' => 'ID:11（はり）が最初、その後ID:12（きゅう）',
        'firstId' => 11,
        'counts' => [11 => 1, 12 => 1],
        'expected_key' => 'fee_initial_examination_hari',
        'expected_fee' => 'hari_first'
    ],
    [
        'name' => 'ID:12（きゅう）が最初、その後ID:11（はり）',
        'firstId' => 12,
        'counts' => [11 => 1, 12 => 1],
        'expected_key' => 'fee_initial_examination_kyu',
        'expected_fee' => 'kyu_first'
    ],
    [
        'name' => 'ID:11（はり）のみ',
        'firstId' => 11,
        'counts' => [11 => 1],
        'expected_key' => 'fee_initial_examination_hari',
        'expected_fee' => 'hari_first'
    ],
    [
        'name' => 'ID:11（はり）+ 電療（ID:14）',
        'firstId' => 11,
        'counts' => [11 => 1, 14 => 1],
        'expected_key' => 'fee_initial_examination_hari_electric',
        'expected_fee' => 'hari_and_elec_needle_first'
    ],
    [
        'name' => 'ID:14（電気針）が最初',
        'firstId' => 14,
        'counts' => [14 => 1],
        'expected_key' => 'fee_initial_examination_hari_electric',
        'expected_fee' => 'hari_and_elec_needle_first'
    ],
    [
        'name' => 'ID:13（はり･きゅう併用）+ ID:11（はり）',
        'firstId' => 13,
        'counts' => [13 => 1, 11 => 1],
        'expected_key' => 'fee_initial_examination_combined',
        'expected_fee' => 'hari_and_kyu_first'
    ],
];

foreach ($testCases as $i => $test) {
    echo "\n【テスト" . ($i + 1) . "】" . $test['name'] . "\n";
    echo str_repeat('-', 120) . "\n";

    $therapyTypeCounts = $test['counts'];
    $firstAcupunctureContentId = $test['firstId'];

    // 修正後のロジック
    $hariElectricCount = $therapyTypeCounts[14] ?? 0;
    $kyuElectricCount = $therapyTypeCounts[15] ?? 0;
    $hariKyuElectricCount = ($therapyTypeCounts[16] ?? 0) + ($therapyTypeCounts[17] ?? 0);
    $hasElectric = ($hariElectricCount + $kyuElectricCount + $hariKyuElectricCount) > 0;

    $key = null;
    $feeDbKey = null;

    if (isset($therapyTypeCounts[13]) && $therapyTypeCounts[13] > 0) {
        $key = 'fee_initial_examination_combined';
        $feeDbKey = 'hari_and_kyu_first';
    } elseif ($firstAcupunctureContentId !== null) {
        if ($firstAcupunctureContentId == 11) {
            if ($hasElectric) {
                $key = 'fee_initial_examination_hari_electric';
                $feeDbKey = 'hari_and_elec_needle_first';
            } else {
                $key = 'fee_initial_examination_hari';
                $feeDbKey = 'hari_first';
            }
        } elseif ($firstAcupunctureContentId == 12) {
            if ($hasElectric) {
                $key = 'fee_initial_examination_kyu_electric';
                $feeDbKey = 'kyu_and_elec_moxa_heater_first';
            } else {
                $key = 'fee_initial_examination_kyu';
                $feeDbKey = 'kyu_first';
            }
        } elseif ($firstAcupunctureContentId == 13) {
            $key = 'fee_initial_examination_combined';
            $feeDbKey = 'hari_and_kyu_first';
        } elseif ($firstAcupunctureContentId == 14) {
            $key = 'fee_initial_examination_hari_electric';
            $feeDbKey = 'hari_and_elec_needle_first';
        } elseif ($firstAcupunctureContentId == 15) {
            $key = 'fee_initial_examination_kyu_electric';
            $feeDbKey = 'kyu_and_elec_moxa_heater_first';
        }
    }

    $status = ($key === $test['expected_key'] && $feeDbKey === $test['expected_fee']) ? '✓ PASS' : '✗ FAIL';

    echo "  最初のID: {$firstAcupunctureContentId}\n";
    echo "  電療: " . ($hasElectric ? 'あり' : 'なし') . "\n";
    echo "  判定結果: key={$key}, feeDbKey={$feeDbKey}\n";
    echo "  期待値: key={$test['expected_key']}, feeDbKey={$test['expected_fee']}\n";
    echo "  結果: {$status}\n";
}

echo "\n" . str_repeat('=', 120) . "\n";
echo "全テスト完了\n";
