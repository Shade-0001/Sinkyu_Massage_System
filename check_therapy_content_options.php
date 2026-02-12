<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "実績登録画面の療法内容選択肢\n";
echo str_repeat('=', 120) . "\n";

// RecordsControllerと同じクエリ
$therapyContents = DB::table('therapy_contents')
    ->select('id', 'therapy_type', 'therapy_content')
    ->orderBy('id')
    ->get();

echo "\n選択肢一覧:\n";
foreach ($therapyContents as $content) {
    echo sprintf(
        "ID:%2d | therapy_type:%d | %s\n",
        $content->id,
        $content->therapy_type,
        $content->therapy_content
    );
}

echo "\n分類:\n";
echo str_repeat('=', 120) . "\n";

$byType = [];
foreach ($therapyContents as $content) {
    $type = $content->therapy_type;
    if (!isset($byType[$type])) {
        $byType[$type] = [];
    }
    $byType[$type][] = $content;
}

foreach ($byType as $type => $contents) {
    $typeName = $type == 1 ? 'はり・きゅう' : 'マッサージ';
    echo "\n【therapy_type:{$type} - {$typeName}】\n";
    foreach ($contents as $c) {
        echo sprintf("  ID:%2d | %s\n", $c->id, $c->therapy_content);
    }
}

echo "\n確認事項:\n";
echo str_repeat('=', 120) . "\n";
echo "
質問: 『はり･きゅう併用（電気併用）』の選択肢は存在するか？

回答: ";

$hasHariKyuElectric = false;
foreach ($therapyContents as $content) {
    if (strpos($content->therapy_content, 'はり') !== false
        && strpos($content->therapy_content, 'きゅう') !== false
        && strpos($content->therapy_content, '電気') !== false) {
        $hasHariKyuElectric = true;
        break;
    }
}

if ($hasHariKyuElectric) {
    echo "存在する\n";
} else {
    echo "存在しない\n";
    echo "\n該当する選択肢:\n";
    echo "  - ID:13 | はり･きゅう併用（電気なし）\n";
    echo "  - ID:14 | 電療（電気針）\n";
    echo "  - ID:15 | 電療（電気温灸器）\n";
    echo "  - ID:16 | 電療（電気光線器具）\n";
    echo "\n結論: ユーザーは「はり･きゅう併用」と「電療」を別々のレコードとして登録する。\n";
    echo "       よって、初検料判定では同月内に両方が存在する場合に「電気併用」と判定する必要がある。\n";
}
