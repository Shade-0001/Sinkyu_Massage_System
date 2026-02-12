<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "不正なtherapy_content_idの修正処理\n";
echo str_repeat('=', 120) . "\n";

// 正しいID一覧を取得
$hariKyuIds = DB::table('therapy_contents')
    ->where('therapy_type', 1)
    ->pluck('id')
    ->toArray();

$massageIds = DB::table('therapy_contents')
    ->where('therapy_type', 2)
    ->pluck('id')
    ->toArray();

echo "正しいID一覧:\n";
echo "  - はり・きゅう系 (therapy_type=1): " . implode(', ', $hariKyuIds) . "\n";
echo "  - マッサージ系 (therapy_type=2): " . implode(', ', $massageIds) . "\n";
echo "\n";

// 不正なIDを持つレコードを取得
$validIds = array_merge($hariKyuIds, $massageIds);
$invalidRecords = DB::table('records')
    ->whereNotNull('therapy_content_id')
    ->whereNotIn('therapy_content_id', $validIds)
    ->get();

echo "不正なIDを持つレコード数: " . $invalidRecords->count() . "件\n";
echo str_repeat('=', 120) . "\n";

if ($invalidRecords->isEmpty()) {
    echo "修正が必要なレコードはありません。\n";
    exit(0);
}

// therapy_categoryで判定してIDを割り当て
$updatePlan = [];
$unknownRecords = [];

foreach ($invalidRecords as $record) {
    $therapyCategory = $record->therapy_category ?? null;
    $therapyType = $record->therapy_type ?? null;
    $oldId = $record->therapy_content_id;

    // 判定ロジック
    // therapy_type: 1=はり・きゅう, 2=あんま・マッサージ（施術タイプ）
    // therapy_category: 1=通院, 2=往療初回（施術形態）
    // → therapy_typeを優先して判定

    $newId = null;
    $reason = '';

    if ($therapyType == 1) {
        // はり・きゅう系
        $newId = $hariKyuIds[array_rand($hariKyuIds)];
        $reason = "therapy_type=1（はり・きゅう） → はり・きゅう系";
    } elseif ($therapyType == 2) {
        // マッサージ系
        $newId = $massageIds[array_rand($massageIds)];
        $reason = "therapy_type=2（マッサージ） → マッサージ系";
    } else {
        // 判定不能
        $unknownRecords[] = $record;
        $reason = "判定不能: therapy_type={$therapyType}";
    }

    if (!isset($updatePlan[$oldId])) {
        $updatePlan[$oldId] = [];
    }

    $updatePlan[$oldId][] = [
        'record_id' => $record->id,
        'old_id' => $oldId,
        'new_id' => $newId,
        'reason' => $reason,
        'date' => $record->date,
        'clinic_user_id' => $record->clinic_user_id
    ];
}

// 更新計画を表示
echo "\n更新計画:\n";
echo str_repeat('=', 120) . "\n";

foreach ($updatePlan as $oldId => $plans) {
    $grouped = [];
    foreach ($plans as $plan) {
        $key = $plan['new_id'] ?? 'unknown';
        if (!isset($grouped[$key])) {
            $grouped[$key] = ['count' => 0, 'sample' => null];
        }
        $grouped[$key]['count']++;
        if ($grouped[$key]['sample'] === null) {
            $grouped[$key]['sample'] = $plan;
        }
    }

    echo "\n旧ID:{$oldId} の修正:\n";
    foreach ($grouped as $newId => $info) {
        $sample = $info['sample'];
        if ($newId === 'unknown') {
            echo sprintf("  → 判定不能 (%d件) - %s\n", $info['count'], $sample['reason']);
        } else {
            echo sprintf("  → 新ID:%s (%d件) - %s\n", $newId, $info['count'], $sample['reason']);
        }
    }
}

// 判定不能レコードの詳細
if (!empty($unknownRecords)) {
    echo "\n⚠️ 判定不能レコード (" . count($unknownRecords) . "件):\n";
    echo str_repeat('=', 120) . "\n";
    foreach ($unknownRecords as $r) {
        echo sprintf(
            "ID:%d | clinic_user_id:%s | 日付:%s | 旧ID:%s | therapy_type:%s | therapy_category:%s\n",
            $r->id,
            $r->clinic_user_id ?? 'null',
            $r->date ?? 'null',
            $r->therapy_content_id ?? 'null',
            $r->therapy_type ?? 'null',
            $r->therapy_category ?? 'null'
        );
    }
    echo "\n判定不能レコードは更新されません。手動で確認が必要。\n";
}

// 実行確認
echo "\n" . str_repeat('=', 120) . "\n";
echo "このまま実行しますか？ (y/n): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));

if (strtolower($line) !== 'y') {
    echo "中止しました。\n";
    exit(0);
}

// 更新実行
echo "\n更新を実行中...\n";
echo str_repeat('=', 120) . "\n";

$updatedCount = 0;
$skippedCount = 0;

foreach ($updatePlan as $oldId => $plans) {
    foreach ($plans as $plan) {
        if ($plan['new_id'] === null) {
            $skippedCount++;
            continue;
        }

        DB::table('records')
            ->where('id', $plan['record_id'])
            ->update(['therapy_content_id' => $plan['new_id']]);

        $updatedCount++;

        echo sprintf(
            "✓ レコードID:%d | 旧ID:%s → 新ID:%s | clinic_user_id:%s | 日付:%s\n",
            $plan['record_id'],
            $plan['old_id'],
            $plan['new_id'],
            $plan['clinic_user_id'] ?? 'null',
            $plan['date'] ?? 'null'
        );
    }
}

echo "\n" . str_repeat('=', 120) . "\n";
echo "完了。\n";
echo "  - 更新: {$updatedCount}件\n";
echo "  - スキップ: {$skippedCount}件\n";
