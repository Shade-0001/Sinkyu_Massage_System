<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "不正な請求区分データの修正\n";
echo str_repeat('=', 120) . "\n";

// 同月内に複数の新規レコードが存在する月を特定
$problematicMonths = DB::select("
    SELECT
        clinic_user_id,
        SUBSTRING(date, 1, 7) as month,
        COUNT(*) as new_count,
        MIN(date) as first_date
    FROM records
    WHERE bill_category_id = 1
    GROUP BY clinic_user_id, month
    HAVING new_count > 1
");

echo "\n修正対象の月:\n";
echo str_repeat('-', 120) . "\n";

if (empty($problematicMonths)) {
    echo "修正が必要な月はありません。\n";
    exit(0);
}

foreach ($problematicMonths as $month) {
    echo sprintf(
        "clinic_user_id:%s | 月:%s | 新規件数:%d | 最初の日付:%s\n",
        $month->clinic_user_id,
        $month->month,
        $month->new_count,
        $month->first_date
    );
}

echo "\n修正計画:\n";
echo str_repeat('=', 120) . "\n";
echo "
各月について、最初の新規レコードのみを残し、それ以外を継続（bill_category_id=2）に変更する。

修正方針:
  1. 各月で最初の日付のレコードのみ新規（bill_category_id=1）
  2. それ以降の日付のレコードはすべて継続（bill_category_id=2）に変更
";

// 修正プランを作成
$updatePlan = [];

foreach ($problematicMonths as $month) {
    $records = DB::table('records')
        ->where('clinic_user_id', $month->clinic_user_id)
        ->where('date', 'LIKE', $month->month . '%')
        ->where('bill_category_id', 1)
        ->orderBy('date')
        ->orderBy('id')
        ->get();

    $firstDate = null;
    foreach ($records as $record) {
        if ($firstDate === null) {
            $firstDate = $record->date;
            continue; // 最初のレコードはそのまま
        }

        if ($record->date === $firstDate) {
            // 同日のレコードは最初の1件のみ新規とする
            // 2件目以降は継続に変更
            static $firstDateProcessed = [];
            $key = $month->clinic_user_id . '_' . $firstDate;

            if (!isset($firstDateProcessed[$key])) {
                $firstDateProcessed[$key] = true;
                continue; // 同日の最初のレコードは新規のまま
            }
        }

        // それ以外は継続に変更
        $updatePlan[] = [
            'id' => $record->id,
            'clinic_user_id' => $record->clinic_user_id,
            'date' => $record->date,
            'old_bill_category_id' => $record->bill_category_id,
            'new_bill_category_id' => 2
        ];
    }
}

echo "\n修正対象レコード:\n";
echo str_repeat('-', 120) . "\n";

if (empty($updatePlan)) {
    echo "修正が必要なレコードはありません。\n";
    exit(0);
}

foreach ($updatePlan as $plan) {
    echo sprintf(
        "ID:%d | clinic_user_id:%s | 日付:%s | 変更: %d → %d\n",
        $plan['id'],
        $plan['clinic_user_id'],
        $plan['date'],
        $plan['old_bill_category_id'],
        $plan['new_bill_category_id']
    );
}

echo "\n" . str_repeat('=', 120) . "\n";
echo sprintf("修正対象レコード数: %d件\n", count($updatePlan));
echo "\n実行しますか？ (y/n): ";

$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));

if (strtolower($line) !== 'y') {
    echo "中止しました。\n";
    exit(0);
}

// 修正実行
echo "\n修正を実行中...\n";
echo str_repeat('=', 120) . "\n";

$updatedCount = 0;

foreach ($updatePlan as $plan) {
    DB::table('records')
        ->where('id', $plan['id'])
        ->update(['bill_category_id' => $plan['new_bill_category_id']]);

    $updatedCount++;

    echo sprintf(
        "✓ ID:%d | clinic_user_id:%s | 日付:%s | %d → %d\n",
        $plan['id'],
        $plan['clinic_user_id'],
        $plan['date'],
        $plan['old_bill_category_id'],
        $plan['new_bill_category_id']
    );
}

echo "\n" . str_repeat('=', 120) . "\n";
echo "完了。\n";
echo "  - 更新: {$updatedCount}件\n";

// 検証
echo "\n検証:\n";
echo str_repeat('=', 120) . "\n";

$remainingProblems = DB::select("
    SELECT
        clinic_user_id,
        SUBSTRING(date, 1, 7) as month,
        COUNT(*) as new_count
    FROM records
    WHERE bill_category_id = 1
    GROUP BY clinic_user_id, month
    HAVING new_count > 1
");

if (empty($remainingProblems)) {
    echo "✓ 同月内に複数の新規レコードが存在する月: 0件\n";
    echo "✓ すべて修正されました。\n";
} else {
    echo "✗ まだ問題が残っています:\n";
    foreach ($remainingProblems as $p) {
        echo sprintf("  clinic_user_id:%s | 月:%s | 新規件数:%d\n", $p->clinic_user_id, $p->month, $p->new_count);
    }
}
