<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "2025-12月の残りの新規レコードを確認\n";
echo str_repeat('=', 120) . "\n";

$records = DB::table('records')
    ->where('clinic_user_id', 298)
    ->where('date', 'LIKE', '2025-12%')
    ->where('bill_category_id', 1)
    ->orderBy('date')
    ->orderBy('id')
    ->get();

echo sprintf("新規レコード数: %d件\n\n", $records->count());

foreach ($records as $r) {
    $content = DB::table('therapy_contents')->where('id', $r->therapy_content_id)->first();
    echo sprintf(
        "ID:%d | 日付:%s | 療法内容:%s\n",
        $r->id,
        $r->date,
        $content->therapy_content ?? 'unknown'
    );
}
