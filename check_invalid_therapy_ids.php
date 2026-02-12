<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// 正しいtherapy_content_idの一覧を取得
$validIds = DB::table('therapy_contents')->pluck('id')->toArray();
echo "正しいtherapy_content_id: " . implode(', ', $validIds) . "\n";
echo str_repeat('=', 120) . "\n";

// recordsテーブルで使用されているすべてのtherapy_content_id
$usedIds = DB::table('records')
    ->select('therapy_content_id', DB::raw('count(*) as count'))
    ->groupBy('therapy_content_id')
    ->orderBy('therapy_content_id')
    ->get();

echo "\nrecordsテーブルで使用されているtherapy_content_id:\n";
echo str_repeat('=', 120) . "\n";

$invalidIds = [];
foreach ($usedIds as $row) {
    $id = $row->therapy_content_id;
    $count = $row->count;
    $status = ($id === null || in_array($id, $validIds)) ? '✓ 正常' : '✗ 不正';

    if ($id !== null && !in_array($id, $validIds)) {
        $invalidIds[] = $id;
    }

    echo sprintf(
        "ID:%s | 使用件数:%d | %s\n",
        $id ?? 'null',
        $count,
        $status
    );
}

// 不正なIDを使用しているレコードの詳細
if (!empty($invalidIds)) {
    echo "\n不正なtherapy_content_idを使用しているレコードの詳細:\n";
    echo str_repeat('=', 120) . "\n";

    foreach ($invalidIds as $invalidId) {
        $records = DB::table('records')
            ->where('therapy_content_id', $invalidId)
            ->orderBy('date')
            ->get();

        echo sprintf("\n【療法内容ID:%d】使用レコード数:%d件\n", $invalidId, $records->count());
        echo "  clinic_user_id別集計:\n";

        $byUser = [];
        foreach ($records as $r) {
            $userId = $r->clinic_user_id ?? 'null';
            if (!isset($byUser[$userId])) {
                $byUser[$userId] = ['count' => 0, 'dates' => []];
            }
            $byUser[$userId]['count']++;
            $byUser[$userId]['dates'][] = $r->date;
        }

        foreach ($byUser as $userId => $data) {
            $dateRange = sprintf("%s ～ %s", min($data['dates']), max($data['dates']));
            echo sprintf("    - clinic_user_id:%s → %d件 (%s)\n", $userId, $data['count'], $dateRange);
        }

        // サンプル表示（最初の5件）
        echo "  サンプルレコード（最初の5件）:\n";
        foreach ($records->take(5) as $r) {
            echo sprintf(
                "    ID:%d | clinic_user_id:%s | 日付:%s | 請求区分ID:%s | 療法区分:%s\n",
                $r->id,
                $r->clinic_user_id ?? 'null',
                $r->date ?? 'null',
                $r->bill_category_id ?? 'null',
                $r->therapy_category ?? 'null'
            );
        }
    }

    // 影響を受けるclinic_user_idの一覧
    echo "\n不正なIDの影響を受けるclinic_user_id一覧:\n";
    echo str_repeat('=', 120) . "\n";

    $affectedUsers = DB::table('records')
        ->whereIn('therapy_content_id', $invalidIds)
        ->select('clinic_user_id', DB::raw('count(*) as count'))
        ->groupBy('clinic_user_id')
        ->orderBy('count', 'desc')
        ->get();

    foreach ($affectedUsers as $user) {
        echo sprintf("clinic_user_id:%s → %d件\n", $user->clinic_user_id ?? 'null', $user->count);
    }
}

// 統計サマリー
echo "\n統計サマリー:\n";
echo str_repeat('=', 120) . "\n";
$totalRecords = DB::table('records')->count();
$invalidRecords = DB::table('records')->whereIn('therapy_content_id', $invalidIds)->count();
$nullRecords = DB::table('records')->whereNull('therapy_content_id')->count();
$validRecords = $totalRecords - $invalidRecords - $nullRecords;

echo sprintf("全レコード数: %d件\n", $totalRecords);
echo sprintf("  - 正常なID: %d件 (%.1f%%)\n", $validRecords, ($validRecords / $totalRecords) * 100);
echo sprintf("  - 不正なID: %d件 (%.1f%%)\n", $invalidRecords, ($invalidRecords / $totalRecords) * 100);
echo sprintf("  - null: %d件 (%.1f%%)\n", $nullRecords, ($nullRecords / $totalRecords) * 100);
