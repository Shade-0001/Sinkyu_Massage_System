<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "therapy_contentsテーブルの構造:\n";
echo str_repeat('=', 100) . "\n";

$columns = DB::select("DESCRIBE therapy_contents");
foreach ($columns as $col) {
    echo sprintf("  - %s (%s)\n", $col->Field, $col->Type);
}

echo "\ntherapy_contentsテーブルの全データ:\n";
echo str_repeat('=', 100) . "\n";

$data = DB::table('therapy_contents')->orderBy('id')->get();
foreach ($data as $row) {
    $fields = [];
    foreach ((array)$row as $key => $value) {
        $fields[] = "$key:" . ($value ?? 'null');
    }
    echo implode(' | ', $fields) . "\n";
}

// ID=7,10が存在するか確認
echo "\nID=7,10の存在確認:\n";
echo str_repeat('=', 100) . "\n";
$specific = DB::table('therapy_contents')->whereIn('id', [7, 10])->get();
echo "取得件数: " . $specific->count() . "\n";
if ($specific->isEmpty()) {
    echo "→ ID=7,10のレコードは存在しない\n";
}
