<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "clinic_user_id=298の2026-12月の初検料描画調査\n";
echo str_repeat('=', 120) . "\n";

// 2026-12月のレコードを取得
$records = DB::table('records')
    ->where('clinic_user_id', 298)
    ->where('date', 'LIKE', '2026-12%')
    ->orderBy('date')
    ->get();

echo "\n2026-12月のレコード (全" . $records->count() . "件):\n";
echo str_repeat('=', 120) . "\n";

if ($records->isEmpty()) {
    echo "レコードが存在しない\n";
    exit;
}

foreach ($records as $record) {
    $content = DB::table('therapy_contents')->where('id', $record->therapy_content_id)->first();
    echo sprintf(
        "ID:%d | 日付:%s | 請求区分ID:%s | 療法内容ID:%s(%s) | therapy_type:%s\n",
        $record->id,
        $record->date,
        $record->bill_category_id ?? 'null',
        $record->therapy_content_id ?? 'null',
        $content->therapy_content ?? 'unknown',
        $record->therapy_type ?? 'null'
    );
}

// 初検料描画判定
echo "\n初検料描画判定:\n";
echo str_repeat('=', 120) . "\n";

// 請求区分=1（新規）の確認
$hasNew = false;
foreach ($records as $record) {
    if ($record->bill_category_id == 1) {
        $hasNew = true;
        break;
    }
}

echo "請求区分=1（新規）の存在: " . ($hasNew ? 'あり ✓' : 'なし ✗') . "\n";

// 施術タイプカウント（はり・きゅう関連のみ）
$therapyTypeCounts = [];
foreach ($records as $record) {
    $contentId = $record->therapy_content_id ?? null;
    if ($contentId) {
        if (!isset($therapyTypeCounts[$contentId])) {
            $therapyTypeCounts[$contentId] = 0;
        }
        $therapyTypeCounts[$contentId]++;
    }
}

echo "\n施術内容別カウント:\n";
foreach ($therapyTypeCounts as $id => $count) {
    $content = DB::table('therapy_contents')->where('id', $id)->first();
    echo sprintf("  ID:%d (%s) → %d件\n", $id, $content->therapy_content ?? 'unknown', $count);
}

// 初検料タイプ判定ロジック（MedicalAssistanceAcupunctureFormFieldsTraitと同じ）
$hariCount = ($therapyTypeCounts[11] ?? 0) + ($therapyTypeCounts[13] ?? 0);
$kyuCount = ($therapyTypeCounts[12] ?? 0) + ($therapyTypeCounts[13] ?? 0);
$hariElectricCount = $therapyTypeCounts[14] ?? 0;
$kyuElectricCount = $therapyTypeCounts[15] ?? 0;
$hariKyuElectricCount = ($therapyTypeCounts[16] ?? 0) + ($therapyTypeCounts[17] ?? 0);

echo "\n初検料判定用カウント:\n";
echo "  はりカウント: {$hariCount}\n";
echo "  きゅうカウント: {$kyuCount}\n";
echo "  はり（電気鍼）カウント: {$hariElectricCount}\n";
echo "  きゅう（電気温灸器）カウント: {$kyuElectricCount}\n";
echo "  はり・きゅう（電気併用）カウント: {$hariKyuElectricCount}\n";

$hasElectric = ($hariElectricCount + $kyuElectricCount + $hariKyuElectricCount) > 0;
echo "  電療使用: " . ($hasElectric ? 'あり' : 'なし') . "\n";

// 初検料タイプ決定
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

echo "\n初検料タイプ判定結果:\n";
echo "  判定結果: " . ($key ? $key : '判定不能（カウントがすべて0）') . "\n";
echo "  料金DBキー: " . ($feeDbKey ? $feeDbKey : 'なし') . "\n";

// 治療費マスタから初検料を取得
if ($feeDbKey) {
    $treatmentFees = DB::table('treatment_fees')->first();
    $initialExaminationFee = $treatmentFees->$feeDbKey ?? null;
    echo "  初検料金額: " . ($initialExaminationFee ?? '取得失敗') . "円\n";

    if ($initialExaminationFee === 0 || $initialExaminationFee === '0') {
        echo "\n⚠️ 問題発見: 初検料金額が0円\n";
        echo "  → treatment_feesテーブルの{$feeDbKey}カラムが0になっている可能性\n";
    }
}

// treatment_feesテーブルの初検料関連フィールドを確認
echo "\ntreatment_feesテーブルの初検料関連フィールド:\n";
echo str_repeat('=', 120) . "\n";

$treatmentFees = DB::table('treatment_fees')->first();
$initialFeeFields = [
    'hari_first',
    'kyu_first',
    'hari_and_kyu_first',
    'hari_and_elec_needle_first',
    'kyu_and_elec_moxa_heater_first',
    'hari_and_kyu_and_elec_first'
];

foreach ($initialFeeFields as $field) {
    $value = $treatmentFees->$field ?? 'カラムなし';
    $status = ($value === 0 || $value === '0') ? '⚠️ 0円' : '✓';
    echo sprintf("  %s: %s %s\n", $field, $value, $status);
}
