<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "clinic_user_id=298の全レコードを月別に集計\n";
echo str_repeat('=', 120) . "\n";

$records = DB::table('records')
    ->where('clinic_user_id', 298)
    ->orderBy('date')
    ->get();

echo "全レコード数: " . $records->count() . "件\n\n";

// 月別集計
$byMonth = [];
foreach ($records as $record) {
    $month = substr($record->date ?? '', 0, 7); // YYYY-MM
    if (empty($month)) continue;

    if (!isset($byMonth[$month])) {
        $byMonth[$month] = [
            'total' => 0,
            'hasNew' => false,
            'hariKyuCount' => 0,
            'massageCount' => 0,
            'billCategories' => []
        ];
    }

    $byMonth[$month]['total']++;

    // 請求区分チェック
    if ($record->bill_category_id == 1) {
        $byMonth[$month]['hasNew'] = true;
    }
    if (!in_array($record->bill_category_id, $byMonth[$month]['billCategories'])) {
        $byMonth[$month]['billCategories'][] = $record->bill_category_id;
    }

    // 施術タイプチェック
    if ($record->therapy_type == 1) {
        $byMonth[$month]['hariKyuCount']++;
    } elseif ($record->therapy_type == 2) {
        $byMonth[$month]['massageCount']++;
    }
}

echo "月別集計（初検料描画条件付き）:\n";
echo str_repeat('=', 120) . "\n";
echo sprintf(
    "%-10s | %5s | %8s | %10s | %10s | %s\n",
    "年月", "件数", "新規あり", "鍼灸件数", "マ件数", "初検料描画"
);
echo str_repeat('-', 120) . "\n";

ksort($byMonth);
foreach ($byMonth as $month => $data) {
    $canDrawInitialFee = $data['hasNew'] && $data['hariKyuCount'] > 0;
    $status = $canDrawInitialFee ? '✓ 描画' : '✗';

    echo sprintf(
        "%-10s | %5d | %8s | %10d | %10d | %s\n",
        $month,
        $data['total'],
        $data['hasNew'] ? 'あり' : 'なし',
        $data['hariKyuCount'],
        $data['massageCount'],
        $status
    );
}

// 初検料が描画される月の詳細
echo "\n初検料が描画される月の詳細:\n";
echo str_repeat('=', 120) . "\n";

foreach ($byMonth as $month => $data) {
    if ($data['hasNew'] && $data['hariKyuCount'] > 0) {
        echo "\n【{$month}】\n";

        $monthRecords = DB::table('records')
            ->where('clinic_user_id', 298)
            ->where('date', 'LIKE', $month . '%')
            ->where('bill_category_id', 1) // 新規のみ
            ->get();

        echo "  新規レコード: {$monthRecords->count()}件\n";
        foreach ($monthRecords as $r) {
            $content = DB::table('therapy_contents')->where('id', $r->therapy_content_id)->first();
            echo sprintf(
                "    ID:%d | 日付:%s | 療法内容:%s | therapy_type:%s\n",
                $r->id,
                $r->date,
                $content->therapy_content ?? 'unknown',
                $r->therapy_type ?? 'null'
            );
        }
    }
}
