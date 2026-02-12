<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "行政規定に基づく初検料判定の正しい解釈\n";
echo str_repeat('=', 120) . "\n";

echo "\n【行政規定の正確な解釈】\n";
echo str_repeat('-', 120) . "\n";
echo "
初検料の判定は、実績データ登録時に選択された「施術内容」に基づく：

1. ID:13「はり･きゅう併用」が選択された場合のみ
   → 初検料：はり･きゅう併用

2. ID:11「はり」とID:12「きゅう」が別々のレコードとして存在する場合
   → 初検料：「はり･きゅう併用」として扱わない
   → それぞれ独立した施術として扱う

つまり：
  - ID:13の存在 = はり･きゅう併用として扱う
  - ID:11 + ID:12の共存 ≠ はり･きゅう併用として扱わない
";

echo "\n【現在のロジックの問題】\n";
echo str_repeat('-', 120) . "\n";
echo "
現在のロジック（2104-2105行）:
  \$hariCount = (\$therapyTypeCounts[11] ?? 0) + (\$therapyTypeCounts[13] ?? 0);
  \$kyuCount = (\$therapyTypeCounts[12] ?? 0) + (\$therapyTypeCounts[13] ?? 0);

  if (\$hariCount > 0 && \$kyuCount > 0) {
      // はり･きゅう併用として判定
  }

問題点：
  ID:11（はり）とID:12（きゅう）が別々に存在する場合でも
  → \$hariCount = 1, \$kyuCount = 1
  → 「はり･きゅう併用」として判定されてしまう ✗

これは行政規定に反している。
";

echo "\n【正しいロジック】\n";
echo str_repeat('-', 120) . "\n";
echo "
if (\$therapyTypeCounts[13] ?? 0 > 0) {
    // ID:13「はり･きゅう併用」が明示的に選択されている
    \$key = 'fee_initial_examination_combined';
    \$feeDbKey = 'hari_and_kyu_first';
} elseif ((\$therapyTypeCounts[11] ?? 0) > 0 && (\$therapyTypeCounts[12] ?? 0) > 0) {
    // ID:11とID:12が別々に存在するが、併用としては扱わない
    // → より優先度の高い施術を初検料として扱う
    // → または両方の初検料を別々に発生させる？（要確認）
    // 現時点では判定不能または特別処理が必要
} elseif (\$therapyTypeCounts[11] ?? 0 > 0) {
    // はりのみ
    ...
} elseif (\$therapyTypeCounts[12] ?? 0 > 0) {
    // きゅうのみ
    ...
}
";

echo "\n【実際のデータでの問題確認】\n";
echo str_repeat('=', 120) . "\n";

// ID:11とID:12が同時に存在するケースを確認
$records = DB::table('records')
    ->select(DB::raw('SUBSTRING(date, 1, 7) as month'), 'clinic_user_id', 'therapy_content_id', 'bill_category_id')
    ->whereNotNull('therapy_content_id')
    ->orderBy('date')
    ->get();

$byMonth = [];
foreach ($records as $record) {
    $key = $record->clinic_user_id . '_' . $record->month;
    if (!isset($byMonth[$key])) {
        $byMonth[$key] = ['counts' => [], 'hasNew' => false, 'month' => $record->month, 'user' => $record->clinic_user_id];
    }
    $contentId = $record->therapy_content_id;
    if (!isset($byMonth[$key]['counts'][$contentId])) {
        $byMonth[$key]['counts'][$contentId] = 0;
    }
    $byMonth[$key]['counts'][$contentId]++;
    if ($record->bill_category_id == 1) {
        $byMonth[$key]['hasNew'] = true;
    }
}

echo "\nID:11（はり）とID:12（きゅう）が別々に存在し、ID:13が存在しない月:\n";
echo str_repeat('-', 120) . "\n";

$problematicCases = 0;
foreach ($byMonth as $data) {
    $has11 = isset($data['counts'][11]) && $data['counts'][11] > 0;
    $has12 = isset($data['counts'][12]) && $data['counts'][12] > 0;
    $has13 = isset($data['counts'][13]) && $data['counts'][13] > 0;

    if ($has11 && $has12 && !$has13) {
        $problematicCases++;
        echo sprintf(
            "clinic_user_id:%s 月:%s | ID:11=%d件, ID:12=%d件, ID:13=なし | 新規:%s\n",
            $data['user'],
            $data['month'],
            $data['counts'][11],
            $data['counts'][12],
            $data['hasNew'] ? 'あり' : 'なし'
        );
        echo "  → 現在のロジック: 「はり･きゅう併用」として誤判定される ✗\n";
        echo "  → 正しい処理: ID:13が存在しないため「はり･きゅう併用」として扱わない\n";
    }
}

if ($problematicCases == 0) {
    echo "該当なし（現在のデータでは問題が顕在化していない）\n";
} else {
    echo "\n合計: {$problematicCases}件の問題ケースが存在\n";
}

echo "\n【質問】\n";
echo str_repeat('=', 120) . "\n";
echo "
ID:11（はり）とID:12（きゅう）が別々のレコードとして存在する場合：
  1. 初検料は発生しない？
  2. どちらか一方の初検料のみ発生？
  3. 両方の初検料が別々に発生？

行政規定での正しい扱いを教えてください。
";
