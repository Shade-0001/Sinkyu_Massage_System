<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "同日の2件目の新規レコードを継続に変更\n";
echo str_repeat('=', 120) . "\n";

DB::table('records')->where('id', 55)->update(['bill_category_id' => 2]);

echo "✓ ID:55を継続（bill_category_id=2）に変更完了\n";

// 検証
$remaining = DB::select("
    SELECT
        clinic_user_id,
        SUBSTRING(date, 1, 7) as month,
        COUNT(*) as new_count
    FROM records
    WHERE bill_category_id = 1
    GROUP BY clinic_user_id, month
    HAVING new_count > 1
");

echo "\n検証:\n";
echo str_repeat('=', 120) . "\n";

if (empty($remaining)) {
    echo "✓ 同月内に複数の新規レコードが存在する月: 0件\n";
    echo "✓ すべて修正されました。\n";
} else {
    echo "✗ まだ問題が残っています:\n";
    foreach ($remaining as $p) {
        echo sprintf("  clinic_user_id:%s | 月:%s | 新規件数:%d\n", $p->clinic_user_id, $p->month, $p->new_count);
    }
}
