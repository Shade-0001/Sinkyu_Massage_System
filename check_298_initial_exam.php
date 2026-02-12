<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// clinic_user_id=298の施術実績を取得
$records = DB::table('therapy_records')
    ->where('clinic_user_id', 298)
    ->orderBy('therapy_date')
    ->get(['id', 'clinic_user_id', 'therapy_date', 'bill_category_id', 'therapy_content_id']);

if ($records->isEmpty()) {
    echo "レコードが存在しない\n";
} else {
    echo "clinic_user_id=298の施術実績:\n";
    echo str_repeat('=', 80) . "\n";
    foreach ($records as $record) {
        echo sprintf(
            "ID:%d | 施術日:%s | 請求区分ID:%s | 施術内容ID:%s\n",
            $record->id,
            $record->therapy_date,
            $record->bill_category_id ?? 'null',
            $record->therapy_content_id ?? 'null'
        );
    }

    // 月ごとに新規判定
    echo "\n月別の初検料描画判定:\n";
    echo str_repeat('=', 80) . "\n";
    $byMonth = [];
    foreach ($records as $record) {
        $month = substr($record->therapy_date, 0, 7); // YYYY-MM
        if (!isset($byMonth[$month])) {
            $byMonth[$month] = ['hasNew' => false, 'records' => []];
        }
        $byMonth[$month]['records'][] = $record;
        if ($record->bill_category_id == 1) {
            $byMonth[$month]['hasNew'] = true;
        }
    }

    foreach ($byMonth as $month => $data) {
        $status = $data['hasNew'] ? '✓ 描画される' : '× 描画されない';
        echo sprintf("%s: %s (レコード数:%d)\n", $month, $status, count($data['records']));
    }
}
