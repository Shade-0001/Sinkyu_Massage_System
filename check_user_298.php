<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = DB::table('clinic_users')->where('id', 298)->first();
if ($user) {
    echo "clinic_user_id=298:\n";
    echo "  氏名: " . ($user->last_name ?? '') . " " . ($user->first_name ?? '') . "\n";
    echo "  カナ: " . ($user->last_kana ?? '') . " " . ($user->first_kana ?? '') . "\n";
}

// 「瀬良 日須戸」という名前を検索
echo "\n「瀬良」を含む利用者:\n";
$users = DB::table('clinic_users')->where('last_name', 'like', '%瀬良%')->orWhere('last_kana', 'like', '%瀬良%')->get();
foreach ($users as $u) {
    echo "  ID={$u->id}: {$u->last_name} {$u->first_name}\n";
}

echo "\n「日須戸」を含む利用者:\n";
$users = DB::table('clinic_users')->where('first_name', 'like', '%日須戸%')->orWhere('first_kana', 'like', '%日須戸%')->get();
foreach ($users as $u) {
    echo "  ID={$u->id}: {$u->last_name} {$u->first_name}\n";
}
