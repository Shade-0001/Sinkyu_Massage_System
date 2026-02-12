<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "修正後の初検料描画ロジックの検証\n";
echo str_repeat('=', 120) . "\n";

// 2025-12月のレコードで検証
$records = DB::table('records')
    ->where('clinic_user_id', 298)
    ->where('date', 'LIKE', '2025-12%')
    ->get();

echo "\n2025-12月のレコード検証:\n";
echo str_repeat('=', 120) . "\n";

$therapyTypeCounts = [];
$isFirstTreatment = false;
$acupunctureContentIds = [11, 12, 13, 14, 15, 16, 17];

echo "レコード処理:\n";
foreach ($records as $record) {
    $therapyContentId = $record->therapy_content_id ?? null;
    $billCategoryId = $record->bill_category_id ?? null;

    // 初検判定（修正後のロジック）
    $isAcupuncture = in_array($therapyContentId, $acupunctureContentIds);
    $isNew = ($billCategoryId == 1);

    if (!$isFirstTreatment && $isNew && $isAcupuncture) {
        $isFirstTreatment = true;
        echo sprintf(
            "  ★初検フラグON: ID:%d | 日付:%s | 療法内容ID:%s | 請求区分ID:%s\n",
            $record->id,
            $record->date,
            $therapyContentId,
            $billCategoryId
        );
    }

    // 施術内容カウント
    if ($therapyContentId) {
        if (!isset($therapyTypeCounts[$therapyContentId])) {
            $therapyTypeCounts[$therapyContentId] = 0;
        }
        $therapyTypeCounts[$therapyContentId]++;
    }
}

echo "\n判定結果:\n";
echo "  isFirstTreatment: " . ($isFirstTreatment ? 'true' : 'false') . "\n";
echo "  → 初検料描画: " . ($isFirstTreatment ? '✓ する' : '✗ しない') . "\n";

// 2026-04月でも検証
echo "\n\n2026-04月のレコード検証:\n";
echo str_repeat('=', 120) . "\n";

$records2 = DB::table('records')
    ->where('clinic_user_id', 298)
    ->where('date', 'LIKE', '2026-04%')
    ->get();

$therapyTypeCounts2 = [];
$isFirstTreatment2 = false;

echo "レコード処理:\n";
foreach ($records2 as $record) {
    $therapyContentId = $record->therapy_content_id ?? null;
    $billCategoryId = $record->bill_category_id ?? null;

    $isAcupuncture = in_array($therapyContentId, $acupunctureContentIds);
    $isNew = ($billCategoryId == 1);

    if (!$isFirstTreatment2 && $isNew && $isAcupuncture) {
        $isFirstTreatment2 = true;
        echo sprintf(
            "  ★初検フラグON: ID:%d | 日付:%s | 療法内容ID:%s | 請求区分ID:%s\n",
            $record->id,
            $record->date,
            $therapyContentId,
            $billCategoryId
        );
    }

    if ($therapyContentId) {
        if (!isset($therapyTypeCounts2[$therapyContentId])) {
            $therapyTypeCounts2[$therapyContentId] = 0;
        }
        $therapyTypeCounts2[$therapyContentId]++;
    }
}

echo "\n判定結果:\n";
echo "  isFirstTreatment: " . ($isFirstTreatment2 ? 'true' : 'false') . "\n";
echo "  → 初検料描画: " . ($isFirstTreatment2 ? '✓ する' : '✗ しない') . "\n";
