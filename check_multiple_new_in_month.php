<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "同月内に複数の新規レコードが存在するか確認\n";
echo str_repeat('=', 120) . "\n";

$result = DB::select("
    SELECT
        clinic_user_id,
        SUBSTRING(date, 1, 7) as month,
        COUNT(*) as new_count
    FROM records
    WHERE bill_category_id = 1
    GROUP BY clinic_user_id, month
    HAVING new_count > 1
");

if (empty($result)) {
    echo "\n同月内に複数の新規レコードが存在する月: 0件\n";
    echo "→ 新規レコードは同月内に必ず1件のみ\n";
} else {
    echo "\n同月内に複数の新規レコードが存在する月: " . count($result) . "件\n";
    echo str_repeat('-', 120) . "\n";
    foreach ($result as $r) {
        echo sprintf("clinic_user_id:%s | 月:%s | 新規件数:%d\n", $r->clinic_user_id, $r->month, $r->new_count);
    }
}

echo "\n【結論】\n";
echo str_repeat('=', 120) . "\n";
if (empty($result)) {
    echo "
新規レコードは同月内に1件のみ発生する。
よって、時系列判定のロジックは実質的に不要。
ただし、将来的なデータの安全性のため、現在のロジックを維持することを推奨。
";
} else {
    echo "
同月内に複数の新規レコードが存在するケースがある。
時系列判定のロジックは必要。
";
}
