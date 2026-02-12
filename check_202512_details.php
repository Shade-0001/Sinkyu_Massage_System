<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// 2025-12月のレコードを詳細確認
$records = DB::table('records')
    ->where('clinic_user_id', 298)
    ->where('date', 'LIKE', '2025-12%')
    ->orderBy('date')
    ->get();

echo "2025-12月のレコード詳細 (全" . $records->count() . "件):\n";
echo str_repeat('=', 120) . "\n";

foreach ($records as $record) {
    echo sprintf(
        "ID:%d | 日付:%s | 請求区分ID:%s | 療法内容ID:%s | 保険区分:%s | 療法区分:%s\n",
        $record->id,
        $record->date,
        $record->bill_category_id ?? 'null',
        $record->therapy_content_id ?? 'null',
        $record->insurance_category ?? 'null',
        $record->therapy_category ?? 'null'
    );
}

// 請求区分の内訳
echo "\n請求区分の内訳:\n";
echo str_repeat('=', 120) . "\n";
$billCategories = DB::table('records')
    ->where('clinic_user_id', 298)
    ->where('date', 'LIKE', '2025-12%')
    ->select('bill_category_id', DB::raw('count(*) as count'))
    ->groupBy('bill_category_id')
    ->get();

foreach ($billCategories as $cat) {
    echo sprintf("請求区分ID:%s → %d件\n", $cat->bill_category_id ?? 'null', $cat->count);
}

// 療法内容の内訳
echo "\n療法内容の内訳:\n";
echo str_repeat('=', 120) . "\n";
$therapyContents = DB::table('records')
    ->where('clinic_user_id', 298)
    ->where('date', 'LIKE', '2025-12%')
    ->select('therapy_content_id', DB::raw('count(*) as count'))
    ->groupBy('therapy_content_id')
    ->get();

foreach ($therapyContents as $content) {
    echo sprintf("療法内容ID:%s → %d件\n", $content->therapy_content_id ?? 'null', $content->count);
}

// 療法内容マスタを確認
echo "\n療法内容マスタ（ID:11-17のはり・きゅう関連）:\n";
echo str_repeat('=', 120) . "\n";
$therapyMaster = DB::table('therapy_contents')
    ->whereBetween('id', [11, 17])
    ->get();

foreach ($therapyMaster as $m) {
    echo sprintf("ID:%d | 名称:%s\n", $m->id, $m->name ?? 'null');
}
