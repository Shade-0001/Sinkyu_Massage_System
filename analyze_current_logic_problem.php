<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "現在のロジックの問題分析\n";
echo str_repeat('=', 120) . "\n";

echo "\n現在のロジック（2104-2105行）:\n";
echo str_repeat('-', 120) . "\n";
echo "  \$hariCount = (\$therapyTypeCounts[11] ?? 0) + (\$therapyTypeCounts[13] ?? 0);\n";
echo "  \$kyuCount = (\$therapyTypeCounts[12] ?? 0) + (\$therapyTypeCounts[13] ?? 0);\n";

echo "\ntherapy_contentsマスタの実態:\n";
echo str_repeat('-', 120) . "\n";

$contents = DB::table('therapy_contents')->orderBy('id')->get();
foreach ($contents as $c) {
    echo sprintf("  ID:%2d | %s\n", $c->id, $c->therapy_content);
}

echo "\n問題点の分析:\n";
echo str_repeat('=', 120) . "\n";

echo "
【ケース1】はり･きゅう併用（ID:13）のみ存在
  \$therapyTypeCounts[13] = 1
  → \$hariCount = 0 + 1 = 1
  → \$kyuCount = 0 + 1 = 1
  → 判定: はり･きゅう併用 ✓ 正しい

【ケース2】はり（ID:11）とはり･きゅう併用（ID:13）が存在
  \$therapyTypeCounts[11] = 1
  \$therapyTypeCounts[13] = 1
  → \$hariCount = 1 + 1 = 2
  → \$kyuCount = 0 + 1 = 1
  → 判定: はり･きゅう併用 ✓ 正しい（はりときゅう両方が存在）

【ケース3】はり（ID:11）のみ存在
  \$therapyTypeCounts[11] = 1
  → \$hariCount = 1 + 0 = 1
  → \$kyuCount = 0 + 0 = 0
  → 判定: はりのみ ✓ 正しい

【ケース4】きゅう（ID:12）のみ存在
  \$therapyTypeCounts[12] = 1
  → \$hariCount = 0 + 0 = 0
  → \$kyuCount = 1 + 0 = 1
  → 判定: きゅうのみ ✓ 正しい
";

echo "\n現在のロジックの妥当性:\n";
echo str_repeat('=', 120) . "\n";

echo "
ID:13「はり･きゅう併用」は、はりときゅうの両方を含むため：
  - はりカウントに加算 ✓
  - きゅうカウントに加算 ✓

これは論理的に正しい。

しかし、君の提案「therapy_content_idで直接判定」も検討に値する。

【提案されたロジック】
  if (\$therapyTypeCounts[13] ?? 0 > 0) {
      // はり･きゅう併用が存在
      \$key = 'fee_initial_examination_combined';
      \$feeDbKey = 'hari_and_kyu_first';
  } elseif ((\$therapyTypeCounts[11] ?? 0) > 0 && (\$therapyTypeCounts[12] ?? 0) > 0) {
      // はりときゅうが別々に存在
      \$key = 'fee_initial_examination_combined';
      \$feeDbKey = 'hari_and_kyu_first';
  } elseif (\$therapyTypeCounts[11] ?? 0 > 0) {
      // はりのみ
      ...
  }

【メリット】
  1. より直接的で理解しやすい
  2. ID:13の二重カウント（はりとしてもきゅうとしても）を避けられる
  3. 意図が明確

【デメリット】
  1. 分岐が増える
  2. 現在のロジックも正しく動作している

【結論】
  どちらも正しく動作するが、君の提案の方がより明示的で理解しやすい。
  ただし、現在のロジックにバグはない。
";

echo "\n実際のデータでの検証:\n";
echo str_repeat('=', 120) . "\n";

// 全レコードをチェック
$records = DB::table('records')
    ->select(DB::raw('SUBSTRING(date, 1, 7) as month'), 'clinic_user_id', 'therapy_content_id', 'bill_category_id')
    ->whereNotNull('therapy_content_id')
    ->orderBy('date')
    ->get();

$byMonth = [];
foreach ($records as $record) {
    $key = $record->clinic_user_id . '_' . $record->month;
    if (!isset($byMonth[$key])) {
        $byMonth[$key] = ['counts' => [], 'hasNew' => false];
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

echo "\nID:11とID:13が同時に存在する月:\n";
$found = false;
foreach ($byMonth as $key => $data) {
    if (isset($data['counts'][11]) && isset($data['counts'][13])) {
        $found = true;
        list($userId, $month) = explode('_', $key);
        echo sprintf("  clinic_user_id:%s 月:%s | ID:11=%d件, ID:13=%d件\n",
            $userId, $month, $data['counts'][11], $data['counts'][13]);
    }
}
if (!$found) {
    echo "  該当なし\n";
}

echo "\n";
