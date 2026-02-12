<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "初検料サークル『はり･きゅう併用』の場合の描画値調査\n";
echo str_repeat('=', 120) . "\n";

// treatment_feesテーブルから初検料を取得
$treatmentFees = DB::table('treatment_fees')->first();

if (!$treatmentFees) {
    echo "treatment_feesテーブルにデータが存在しません。\n";
    exit;
}

echo "\ntreatment_feesテーブルの初検料フィールド:\n";
echo str_repeat('=', 120) . "\n";

$initialFeeFields = [
    'hari_first' => 'はり',
    'kyu_first' => 'きゅう',
    'hari_and_kyu_first' => 'はり･きゅう併用',
    'hari_and_elec_needle_first' => 'はり（電気鍼併用）',
    'kyu_and_elec_moxa_heater_first' => 'きゅう（電気温灸器併用）',
    'hari_and_kyu_and_elec_first' => 'はり･きゅう併用（電気併用）'
];

foreach ($initialFeeFields as $field => $label) {
    $value = $treatmentFees->$field ?? 'カラム存在せず';
    $displayValue = is_numeric($value) ? number_format($value) . '円' : $value;
    echo sprintf("  %-35s (%s): %s\n", $field, $label, $displayValue);
}

// 判定ロジックの説明
echo "\n初検料タイプ判定ロジック:\n";
echo str_repeat('=', 120) . "\n";

echo "
【はり･きゅう併用】の判定条件:
  - はりカウント > 0 且つ きゅうカウント > 0 且つ 電療なし
  - 使用するDBフィールド: hari_and_kyu_first
  - 描画される金額: " . ($treatmentFees->hari_and_kyu_first ?? 'N/A') . "円

【はり･きゅう併用（電気併用）】の判定条件:
  - はりカウント > 0 且つ きゅうカウント > 0 且つ 電療あり
  - 使用するDBフィールド: hari_and_kyu_and_elec_first
  - 描画される金額: " . ($treatmentFees->hari_and_kyu_and_elec_first ?? 'N/A') . "円
";

// 実際の描画シミュレーション（2026-04月のデータで）
echo "\n実際のデータでシミュレーション:\n";
echo str_repeat('=', 120) . "\n";

// 2026-04月のレコードで検証
$records = DB::table('records')
    ->where('clinic_user_id', 298)
    ->where('date', 'LIKE', '2026-04%')
    ->get();

if ($records->isNotEmpty()) {
    echo "\n2026-04月のレコード:\n";

    $therapyTypeCounts = [];
    $isFirstTreatment = false;
    $acupunctureContentIds = [11, 12, 13, 14, 15, 16, 17];

    foreach ($records as $record) {
        $therapyContentId = $record->therapy_content_id ?? null;
        $billCategoryId = $record->bill_category_id ?? null;

        if (!$isFirstTreatment && $billCategoryId == 1 && in_array($therapyContentId, $acupunctureContentIds)) {
            $isFirstTreatment = true;
        }

        if ($therapyContentId) {
            if (!isset($therapyTypeCounts[$therapyContentId])) {
                $therapyTypeCounts[$therapyContentId] = 0;
            }
            $therapyTypeCounts[$therapyContentId]++;
        }

        $content = DB::table('therapy_contents')->where('id', $therapyContentId)->first();
        echo sprintf(
            "  ID:%d | 療法内容:%s | 請求区分:%s\n",
            $record->id,
            $content->therapy_content ?? 'unknown',
            $billCategoryId
        );
    }

    if ($isFirstTreatment) {
        // 初検料タイプ判定
        $hariCount = ($therapyTypeCounts[11] ?? 0) + ($therapyTypeCounts[13] ?? 0);
        $kyuCount = ($therapyTypeCounts[12] ?? 0) + ($therapyTypeCounts[13] ?? 0);
        $hariElectricCount = $therapyTypeCounts[14] ?? 0;
        $kyuElectricCount = $therapyTypeCounts[15] ?? 0;
        $hariKyuElectricCount = ($therapyTypeCounts[16] ?? 0) + ($therapyTypeCounts[17] ?? 0);

        $hasElectric = ($hariElectricCount + $kyuElectricCount + $hariKyuElectricCount) > 0;

        echo "\n  判定用カウント:\n";
        echo "    はりカウント: {$hariCount}\n";
        echo "    きゅうカウント: {$kyuCount}\n";
        echo "    電療使用: " . ($hasElectric ? 'あり' : 'なし') . "\n";

        $key = null;
        $feeDbKey = null;

        if ($hariCount > 0 && $kyuCount > 0) {
            if ($hasElectric) {
                $key = 'fee_initial_examination_hari_kyu_electric';
                $feeDbKey = 'hari_and_kyu_and_elec_first';
            } else {
                $key = 'fee_initial_examination_combined';
                $feeDbKey = 'hari_and_kyu_first';
            }
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

        echo "\n  判定結果:\n";
        echo "    初検料タイプ: {$key}\n";
        echo "    DBフィールド: {$feeDbKey}\n";
        echo "    描画される金額: " . ($treatmentFees->$feeDbKey ?? 0) . "円\n";
    }
}
