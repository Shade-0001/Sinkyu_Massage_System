<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "はり･きゅう併用（電気併用）判定ロジックの必要性検証\n";
echo str_repeat('=', 120) . "\n";

// ケース1: はり･きゅう併用 + 電療が同月に存在する場合
echo "\n【ケース1】同月に以下のレコードが存在する場合:\n";
echo "  - 12/1: はり･きゅう併用（ID:13）請求区分:新規\n";
echo "  - 12/5: 電療（電気温灸器）（ID:15）請求区分:継続\n";
echo str_repeat('-', 120) . "\n";

// シミュレーション
$therapyTypeCounts = [
    13 => 1,  // はり･きゅう併用
    15 => 1,  // 電療（電気温灸器）
];

$hariCount = ($therapyTypeCounts[11] ?? 0) + ($therapyTypeCounts[13] ?? 0);
$kyuCount = ($therapyTypeCounts[12] ?? 0) + ($therapyTypeCounts[13] ?? 0);
$hariElectricCount = $therapyTypeCounts[14] ?? 0;
$kyuElectricCount = $therapyTypeCounts[15] ?? 0;
$hariKyuElectricCount = ($therapyTypeCounts[16] ?? 0) + ($therapyTypeCounts[17] ?? 0);

$hasElectric = ($hariElectricCount + $kyuElectricCount + $hariKyuElectricCount) > 0;

echo "  判定結果:\n";
echo "    はりカウント: {$hariCount} (ID:13から)\n";
echo "    きゅうカウント: {$kyuCount} (ID:13から)\n";
echo "    電療使用: " . ($hasElectric ? 'あり' : 'なし') . " (ID:15から)\n";
echo "    → はりカウント > 0 且つ きゅうカウント > 0 且つ 電療あり\n";
echo "    → 判定: はり･きゅう併用（電気併用）\n";
echo "    → 使用するDBフィールド: hari_and_kyu_and_elec_first\n";

// ケース2: はり + きゅう + 電療が別々のレコードで存在
echo "\n【ケース2】同月に以下のレコードが存在する場合:\n";
echo "  - 12/1: はり（ID:11）請求区分:新規\n";
echo "  - 12/3: きゅう（ID:12）請求区分:継続\n";
echo "  - 12/5: 電療（電気針）（ID:14）請求区分:継続\n";
echo str_repeat('-', 120) . "\n";

$therapyTypeCounts2 = [
    11 => 1,  // はり
    12 => 1,  // きゅう
    14 => 1,  // 電療（電気針）
];

$hariCount2 = ($therapyTypeCounts2[11] ?? 0) + ($therapyTypeCounts2[13] ?? 0);
$kyuCount2 = ($therapyTypeCounts2[12] ?? 0) + ($therapyTypeCounts2[13] ?? 0);
$hariElectricCount2 = $therapyTypeCounts2[14] ?? 0;
$kyuElectricCount2 = $therapyTypeCounts2[15] ?? 0;
$hariKyuElectricCount2 = ($therapyTypeCounts2[16] ?? 0) + ($therapyTypeCounts2[17] ?? 0);

$hasElectric2 = ($hariElectricCount2 + $kyuElectricCount2 + $hariKyuElectricCount2) > 0;

echo "  判定結果:\n";
echo "    はりカウント: {$hariCount2} (ID:11から)\n";
echo "    きゅうカウント: {$kyuCount2} (ID:12から)\n";
echo "    電療使用: " . ($hasElectric2 ? 'あり' : 'なし') . " (ID:14から)\n";
echo "    → はりカウント > 0 且つ きゅうカウント > 0 且つ 電療あり\n";
echo "    → 判定: はり･きゅう併用（電気併用）\n";
echo "    → 使用するDBフィールド: hari_and_kyu_and_elec_first\n";

// 実際のデータで検証
echo "\n実際のデータでの検証:\n";
echo str_repeat('=', 120) . "\n";

$allRecords = DB::table('records')
    ->select(DB::raw('SUBSTRING(date, 1, 7) as month'), 'clinic_user_id', 'therapy_content_id', 'bill_category_id')
    ->whereNotNull('therapy_content_id')
    ->orderBy('date')
    ->get();

// 月別・ユーザー別に集計
$byMonth = [];
foreach ($allRecords as $record) {
    $key = $record->clinic_user_id . '_' . $record->month;
    if (!isset($byMonth[$key])) {
        $byMonth[$key] = [
            'clinic_user_id' => $record->clinic_user_id,
            'month' => $record->month,
            'counts' => [],
            'hasNew' => false
        ];
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

// はり･きゅう併用（電気併用）になるケースを抽出
echo "\nはり･きゅう併用（電気併用）と判定される月:\n";
echo str_repeat('-', 120) . "\n";

$foundCases = 0;
foreach ($byMonth as $data) {
    $counts = $data['counts'];

    $hariCount = ($counts[11] ?? 0) + ($counts[13] ?? 0);
    $kyuCount = ($counts[12] ?? 0) + ($counts[13] ?? 0);
    $hariElectricCount = $counts[14] ?? 0;
    $kyuElectricCount = $counts[15] ?? 0;
    $hariKyuElectricCount = ($counts[16] ?? 0) + ($counts[17] ?? 0);

    $hasElectric = ($hariElectricCount + $kyuElectricCount + $hariKyuElectricCount) > 0;

    if ($hariCount > 0 && $kyuCount > 0 && $hasElectric) {
        $foundCases++;
        echo sprintf(
            "clinic_user_id:%s | 月:%s | はり:%d きゅう:%d 電療:%s | 新規:%s\n",
            $data['clinic_user_id'],
            $data['month'],
            $hariCount,
            $kyuCount,
            ($hasElectric ? 'あり' : 'なし'),
            ($data['hasNew'] ? 'あり' : 'なし')
        );

        echo "  療法内容詳細:\n";
        foreach ($counts as $id => $count) {
            $content = DB::table('therapy_contents')->where('id', $id)->first();
            echo sprintf("    ID:%d (%s) × %d\n", $id, $content->therapy_content ?? 'unknown', $count);
        }
    }
}

if ($foundCases == 0) {
    echo "該当なし（現在のデータでは発生していない）\n";
} else {
    echo "\n合計: {$foundCases}件\n";
}

echo "\n結論:\n";
echo str_repeat('=', 120) . "\n";
echo "
現在のロジックでは、以下のケースで「はり･きゅう併用（電気併用）」と判定される:
  1. はり･きゅう併用（ID:13） + 電療（ID:14/15/16）が同月に存在
  2. はり（ID:11） + きゅう（ID:12） + 電療（ID:14/15/16）が同月に存在

これらは実際に発生しうるケースであり、ロジックは必要。
ただし、現在のデータでは" . ($foundCases > 0 ? "発生している。" : "発生していない。") . "

もし君の主張が「1つの療法内容として『はり･きゅう併用（電気併用）』は存在しない」であれば、それは正しい。
しかし、「複数レコードの組み合わせで判定するロジックは不要」というのは誤り。
";
