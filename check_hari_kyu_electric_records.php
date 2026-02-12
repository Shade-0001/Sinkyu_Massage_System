<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "はり･きゅう併用（電気併用）レコードの存在確認\n";
echo str_repeat('=', 120) . "\n";

// therapy_content_id: 16, 17 が電気併用関連
echo "\ntherapy_contentsマスタの確認:\n";
echo str_repeat('=', 120) . "\n";

$therapyContents = DB::table('therapy_contents')->orderBy('id')->get();
foreach ($therapyContents as $content) {
    echo sprintf(
        "ID:%2d | therapy_type:%s | 内容:%s\n",
        $content->id,
        $content->therapy_type,
        $content->therapy_content ?? 'null'
    );
}

// 電気併用関連のID
echo "\n電気併用関連のtherapy_content_id:\n";
echo str_repeat('=', 120) . "\n";
echo "  ID:14 - 電療（電気針）\n";
echo "  ID:15 - 電療（電気温灸器）\n";
echo "  ID:16 - 電療（電気光線器具）\n";
echo "  ID:17 - （存在するか？）\n";

// 全レコードの療法内容ID別集計
echo "\n全recordsの療法内容ID別集計:\n";
echo str_repeat('=', 120) . "\n";

$contentCounts = DB::table('records')
    ->select('therapy_content_id', DB::raw('count(*) as count'))
    ->groupBy('therapy_content_id')
    ->orderBy('therapy_content_id')
    ->get();

foreach ($contentCounts as $row) {
    $content = DB::table('therapy_contents')->where('id', $row->therapy_content_id)->first();
    $contentName = $content->therapy_content ?? 'マスタに存在しない';

    echo sprintf(
        "ID:%2s | 件数:%3d | 内容:%s\n",
        $row->therapy_content_id ?? 'null',
        $row->count,
        $contentName
    );
}

// 電気併用（ID:16, 17）のレコード詳細
echo "\n電気関連レコードの詳細:\n";
echo str_repeat('=', 120) . "\n";

$electricIds = [14, 15, 16, 17];
$electricRecords = DB::table('records')
    ->whereIn('therapy_content_id', $electricIds)
    ->get();

if ($electricRecords->isEmpty()) {
    echo "電気関連のレコードは存在しません。\n";
} else {
    echo "電気関連レコード: " . $electricRecords->count() . "件\n\n";

    foreach ($electricRecords as $record) {
        $content = DB::table('therapy_contents')->where('id', $record->therapy_content_id)->first();
        echo sprintf(
            "ID:%d | clinic_user_id:%s | 日付:%s | 療法内容ID:%s(%s) | 請求区分:%s\n",
            $record->id,
            $record->clinic_user_id ?? 'null',
            $record->date ?? 'null',
            $record->therapy_content_id,
            $content->therapy_content ?? 'unknown',
            $record->bill_category_id ?? 'null'
        );
    }
}

// 実際の初検料判定でどう処理されるかシミュレーション
echo "\n初検料判定ロジックでの扱い:\n";
echo str_repeat('=', 120) . "\n";

echo "
【現在のロジック】
  \$hariKyuElectricCount = (\$therapyTypeCounts[16] ?? 0) + (\$therapyTypeCounts[17] ?? 0);

  → ID:16, 17 のカウントを「はり･きゅう併用（電気併用）」として扱う
  → しかし therapy_contents マスタでは:
     - ID:16 = 電療（電気光線器具）
     - ID:17 = 存在しない（または削除済み）

【問題】
  ID:16は「電気光線器具」で、はり･きゅう併用とは限らない。
  ID:14（電気針）とID:15（電気温灸器）も電療だが、別カウントされている。

【結論】
  はり･きゅう併用（電気併用）を正しく判定するには:
  - はりのレコード且つ電療のレコードが同じ月に存在
  - きゅうのレコード且つ電療のレコードが同じ月に存在
  という組み合わせで判定すべき。
";
