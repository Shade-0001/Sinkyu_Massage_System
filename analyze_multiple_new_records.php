<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "2025年12月に13件の新規レコードが発生した理由を分析\n";
echo str_repeat('=', 120) . "\n";

// 2025-12月のclinic_user_id:298の新規レコードを詳細確認
$newRecords = DB::table('records')
    ->where('clinic_user_id', 298)
    ->where('date', 'LIKE', '2025-12%')
    ->where('bill_category_id', 1)
    ->orderBy('date')
    ->orderBy('id')
    ->get();

echo "\n2025年12月の新規レコード（clinic_user_id:298）:\n";
echo str_repeat('-', 120) . "\n";
echo sprintf("%-5s | %-12s | %-20s | %-10s | %-10s\n", "ID", "日付", "療法内容", "療法タイプ", "療法区分");
echo str_repeat('-', 120) . "\n";

$therapyContentIds = [];
foreach ($newRecords as $record) {
    $content = DB::table('therapy_contents')->where('id', $record->therapy_content_id)->first();
    echo sprintf(
        "%-5d | %-12s | %-20s | %-10s | %-10s\n",
        $record->id,
        $record->date,
        $content->therapy_content ?? 'unknown',
        $record->therapy_type ?? 'null',
        $record->therapy_category ?? 'null'
    );

    if (!in_array($record->therapy_content_id, $therapyContentIds)) {
        $therapyContentIds[] = $record->therapy_content_id;
    }
}

echo "\n分析:\n";
echo str_repeat('=', 120) . "\n";
echo sprintf("新規レコード総数: %d件\n", $newRecords->count());
echo sprintf("異なる施術内容の種類: %d種類\n", count($therapyContentIds));

// 請求区分マスタを確認
echo "\n請求区分（bill_category）の意味:\n";
echo str_repeat('-', 120) . "\n";
$billCategories = DB::table('bill_categories')->get();
foreach ($billCategories as $cat) {
    echo sprintf("ID:%d | 名称:%s\n", $cat->id, $cat->name ?? '(未設定)');
}

echo "\n考えられる理由:\n";
echo str_repeat('=', 120) . "\n";
echo "
1. 請求区分「新規」の定義が「初回施術」ではなく別の意味を持つ
   → 例：新しい療法内容を開始した場合に「新規」とする

2. データ登録の仕様として、各施術を別レコードで登録する
   → 同日に複数の施術（はり、きゅう、マッサージ等）を行った場合、それぞれ別レコード

3. データ入力ミスまたはテストデータ
   → 実運用では発生しない異常データ

4. 初検の定義が「新規利用者」ではなく「新規療法内容」
   → 利用者が新しい療法を開始するたびに「新規」扱い
";

// 同日に複数の新規レコードがあるか確認
echo "\n同日に複数の新規レコードが存在する日:\n";
echo str_repeat('-', 120) . "\n";

$byDate = [];
foreach ($newRecords as $record) {
    $date = $record->date;
    if (!isset($byDate[$date])) {
        $byDate[$date] = [];
    }
    $byDate[$date][] = $record;
}

foreach ($byDate as $date => $records) {
    if (count($records) > 1) {
        echo sprintf("%s: %d件\n", $date, count($records));
        foreach ($records as $r) {
            $content = DB::table('therapy_contents')->where('id', $r->therapy_content_id)->first();
            echo sprintf("  - ID:%d | %s\n", $r->id, $content->therapy_content ?? 'unknown');
        }
    }
}

echo "\n結論:\n";
echo str_repeat('=', 120) . "\n";
echo "
実際のデータを見ると、同日に異なる療法内容の新規レコードが存在している。
これは以下のいずれかを意味する:

A) 請求区分「新規」= その療法内容を初めて実施した
   → はり、きゅう、マッサージをそれぞれ初めて実施した場合、それぞれ「新規」扱い

B) データ入力の誤り
   → 本来1件のみ「新規」にすべきところ、複数件を「新規」にしてしまった

正しい仕様を確認する必要がある。
";
