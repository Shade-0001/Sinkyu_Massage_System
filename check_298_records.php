<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// clinic_user_id=298の施術実績を取得
$records = DB::table('records')
    ->where('clinic_user_id', 298)
    ->orderBy('treatment_date')
    ->get();

if ($records->isEmpty()) {
    echo "レコードが存在しない\n";
} else {
    echo "clinic_user_id=298の施術実績 (全" . $records->count() . "件):\n";
    echo str_repeat('=', 100) . "\n";

    // 先頭10件を表示
    foreach ($records->take(10) as $record) {
        echo sprintf(
            "ID:%d | 施術日:%s | 請求区分ID:%s | 療法内容ID:%s\n",
            $record->id,
            $record->treatment_date ?? 'null',
            $record->bill_category_id ?? 'null',
            $record->therapy_content_id ?? 'null'
        );
    }

    if ($records->count() > 10) {
        echo "... (残り" . ($records->count() - 10) . "件)\n";
    }

    // 月ごとに新規判定
    echo "\n月別の初検料描画判定:\n";
    echo str_repeat('=', 100) . "\n";
    $byMonth = [];
    foreach ($records as $record) {
        $month = substr($record->treatment_date ?? '', 0, 7); // YYYY-MM
        if (empty($month)) continue;

        if (!isset($byMonth[$month])) {
            $byMonth[$month] = ['hasNew' => false, 'recordCount' => 0, 'billCategories' => []];
        }
        $byMonth[$month]['recordCount']++;

        if (!in_array($record->bill_category_id, $byMonth[$month]['billCategories'])) {
            $byMonth[$month]['billCategories'][] = $record->bill_category_id;
        }

        if ($record->bill_category_id == 1) {
            $byMonth[$month]['hasNew'] = true;
        }
    }

    ksort($byMonth);
    foreach ($byMonth as $month => $data) {
        $status = $data['hasNew'] ? '✓ 描画される' : '× 描画されない';
        $categories = implode(',', array_filter($data['billCategories'], fn($v) => $v !== null));
        echo sprintf(
            "%s: %s (レコード数:%d, 請求区分ID:%s)\n",
            $month,
            $status,
            $data['recordCount'],
            $categories ?: 'null'
        );
    }
}
