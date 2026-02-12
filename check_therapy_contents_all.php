<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// 療法内容マスタを全件取得
echo "療法内容マスタ (全件):\n";
echo str_repeat('=', 100) . "\n";

$therapyContents = DB::table('therapy_contents')->orderBy('id')->get();
foreach ($therapyContents as $content) {
    echo sprintf(
        "ID:%2d | 名称:%s\n",
        $content->id,
        $content->name ?? 'null'
    );
}

// 特定IDの詳細確認
echo "\n2025-12月の新規レコードで使用されている療法内容:\n";
echo str_repeat('=', 100) . "\n";

$usedIds = [7, 10];
$usedContents = DB::table('therapy_contents')->whereIn('id', $usedIds)->get();

foreach ($usedContents as $content) {
    echo sprintf(
        "ID:%d | 名称:%s\n",
        $content->id,
        $content->name ?? '(名称未設定)'
    );
}
