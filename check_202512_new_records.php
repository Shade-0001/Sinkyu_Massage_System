<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// 2025-12月の請求区分=1（新規）のレコード
$records = DB::table('records')
    ->where('clinic_user_id', 298)
    ->where('date', 'LIKE', '2025-12%')
    ->where('bill_category_id', 1)
    ->orderBy('date')
    ->get();

echo "2025-12月の請求区分：新規のレコード (全" . $records->count() . "件):\n";
echo str_repeat('=', 130) . "\n";

foreach ($records as $record) {
    echo sprintf(
        "ID:%d | 日付:%s | 療法内容ID:%s | 保険区分:%s | 療法区分:%s | 療法種別:%s | 往療距離:%s\n",
        $record->id,
        $record->date,
        $record->therapy_content_id ?? 'null',
        $record->insurance_category ?? 'null',
        $record->therapy_category ?? 'null',
        $record->therapy_type ?? 'null',
        $record->housecall_distance ?? 'null'
    );
}

// 療法内容マスタとマッピング
echo "\n療法内容マスタとの対応:\n";
echo str_repeat('=', 130) . "\n";

$contentIds = $records->pluck('therapy_content_id')->unique()->filter()->toArray();
if (!empty($contentIds)) {
    $therapyContents = DB::table('therapy_contents')
        ->whereIn('id', $contentIds)
        ->get();

    foreach ($therapyContents as $content) {
        $count = $records->where('therapy_content_id', $content->id)->count();
        echo sprintf(
            "ID:%d | 名称:%s | 件数:%d\n",
            $content->id,
            $content->name ?? 'null',
            $count
        );
    }
}

// 請求区分マスタも確認
echo "\n請求区分マスタ:\n";
echo str_repeat('=', 130) . "\n";
$billCategories = DB::table('bill_categories')->get();
foreach ($billCategories as $cat) {
    echo sprintf("ID:%d | 名称:%s\n", $cat->id, $cat->name ?? 'null');
}
