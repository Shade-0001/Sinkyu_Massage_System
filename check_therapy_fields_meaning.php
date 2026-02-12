<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "recordsテーブルのtherapy_category・therapy_typeの分析\n";
echo str_repeat('=', 120) . "\n";

// 全レコードの値の分布を確認
echo "\ntherapy_categoryの分布:\n";
$categories = DB::table('records')
    ->select('therapy_category', DB::raw('count(*) as count'))
    ->groupBy('therapy_category')
    ->orderBy('therapy_category')
    ->get();

foreach ($categories as $cat) {
    echo sprintf("  therapy_category:%s → %d件\n", $cat->therapy_category ?? 'null', $cat->count);
}

echo "\ntherapy_typeの分布:\n";
$types = DB::table('records')
    ->select('therapy_type', DB::raw('count(*) as count'))
    ->groupBy('therapy_type')
    ->orderBy('therapy_type')
    ->get();

foreach ($types as $type) {
    echo sprintf("  therapy_type:%s → %d件\n", $type->therapy_type ?? 'null', $type->count);
}

// 組み合わせパターンを確認
echo "\ntherapy_type × therapy_categoryの組み合わせ:\n";
echo str_repeat('=', 120) . "\n";

$combinations = DB::table('records')
    ->select('therapy_type', 'therapy_category', DB::raw('count(*) as count'))
    ->groupBy('therapy_type', 'therapy_category')
    ->orderBy('therapy_type')
    ->orderBy('therapy_category')
    ->get();

foreach ($combinations as $combo) {
    echo sprintf(
        "therapy_type:%s × therapy_category:%s → %d件\n",
        $combo->therapy_type ?? 'null',
        $combo->therapy_category ?? 'null',
        $combo->count
    );
}

// therapy_contentsマスタのtherapy_typeと比較
echo "\ntherapy_contentsマスタのtherapy_type:\n";
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

// サンプルレコードで詳細確認
echo "\nサンプルレコードでの値の確認（正常なIDを持つレコード）:\n";
echo str_repeat('=', 120) . "\n";

$validIds = DB::table('therapy_contents')->pluck('id')->toArray();
$samples = DB::table('records')
    ->whereIn('therapy_content_id', $validIds)
    ->limit(10)
    ->get();

foreach ($samples as $s) {
    $contentInfo = DB::table('therapy_contents')->where('id', $s->therapy_content_id)->first();
    echo sprintf(
        "ID:%d | therapy_content_id:%s(%s) | records.therapy_type:%s | records.therapy_category:%s | master.therapy_type:%s\n",
        $s->id,
        $s->therapy_content_id,
        $contentInfo->therapy_content ?? 'unknown',
        $s->therapy_type ?? 'null',
        $s->therapy_category ?? 'null',
        $contentInfo->therapy_type ?? 'null'
    );
}

// 矛盾レコード（therapy_type=2 & therapy_category=1）の詳細
echo "\n矛盾レコード（therapy_type=2 & therapy_category=1）の詳細:\n";
echo str_repeat('=', 120) . "\n";

$conflicts = DB::table('records')
    ->where('therapy_type', 2)
    ->where('therapy_category', 1)
    ->get();

if ($conflicts->isEmpty()) {
    echo "矛盾レコードは存在しません。\n";
} else {
    foreach ($conflicts as $c) {
        echo sprintf(
            "ID:%d | clinic_user_id:%s | 日付:%s | therapy_content_id:%s | therapy_type:%s | therapy_category:%s\n",
            $c->id,
            $c->clinic_user_id ?? 'null',
            $c->date ?? 'null',
            $c->therapy_content_id ?? 'null',
            $c->therapy_type ?? 'null',
            $c->therapy_category ?? 'null'
        );
    }
}
