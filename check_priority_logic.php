<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "ID:11とID:12が別々に存在する場合の優先度確認\n";
echo str_repeat('=', 120) . "\n";

echo "\n【考えられる優先度の基準】\n";
echo str_repeat('-', 120) . "\n";
echo "
1. 料金が高い方を優先
   → treatment_feesテーブルで hari_first と kyu_first を比較

2. レコードの時系列（先に発生した方）を優先
   → 同月内で最初に登録されたレコードの施術内容

3. 固定ルール（例：はりを常に優先）
   → 行政規定で定められている場合

4. ID番号が小さい方を優先
   → ID:11（はり）が優先
";

// treatment_feesから料金を確認
$fees = DB::table('treatment_fees')->first();

echo "\n【料金比較】\n";
echo str_repeat('-', 120) . "\n";
echo sprintf("はり初検料（hari_first）: %s円\n", number_format($fees->hari_first ?? 0));
echo sprintf("きゅう初検料（kyu_first）: %s円\n", number_format($fees->kyu_first ?? 0));

if (($fees->hari_first ?? 0) > ($fees->kyu_first ?? 0)) {
    echo "→ はりの方が高額\n";
} elseif (($fees->hari_first ?? 0) < ($fees->kyu_first ?? 0)) {
    echo "→ きゅうの方が高額\n";
} else {
    echo "→ 同額\n";
}

echo "\n【一般的な行政規定の考え方】\n";
echo str_repeat('-', 120) . "\n";
echo "
通常、保険請求では以下の原則がある：
  - 同一日に複数の施術がある場合、主たる施術を選択
  - 料金が高い方を主たる施術とするケースが多い
  - ただし、具体的なルールは各保険制度によって異なる

鍼灸の場合、一般的には：
  - はりときゅうを別々に行った場合でも、初検料は1回のみ
  - どちらを選択するかは、施術の主従関係や料金で判断
";

echo "\n【質問】\n";
echo str_repeat('=', 120) . "\n";
echo "
ID:11（はり）とID:12（きゅう）が同月に別々のレコードとして存在する場合：

優先度の判定基準は何ですか？
  1. 料金が高い方
  2. 時系列で先に発生した方
  3. 常にはりを優先（または常にきゅうを優先）
  4. その他の基準

正しい基準を教えてください。
";
