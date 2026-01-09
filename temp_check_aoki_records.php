<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== 青木明美のレコード ===\n";
$aokiAkemi = DB::table('clinic_users')->where('last_name', '青木')->where('first_name', '明美')->first();
if ($aokiAkemi) {
    echo "ID: {$aokiAkemi->id}\n";
    $records = DB::table('records')
        ->where('clinic_user_id', $aokiAkemi->id)
        ->where('created_at', '>=', '2025-01-01')
        ->get(['id', 'therapy_conetnt_id', 'created_at']);
    foreach ($records as $r) {
        echo "  Record ID: {$r->id}, therapy_conetnt_id: {$r->therapy_conetnt_id}, Date: {$r->created_at}\n";
    }
    $counts = DB::table('records')
        ->select('therapy_conetnt_id', DB::raw('COUNT(*) as count'))
        ->where('clinic_user_id', $aokiAkemi->id)
        ->where('created_at', '>=', '2025-01-01')
        ->groupBy('therapy_conetnt_id')
        ->get();
    echo "  集計:\n";
    foreach ($counts as $c) {
        echo "    therapy_conetnt_id {$c->therapy_conetnt_id}: {$c->count}件\n";
    }
}

echo "\n=== 青木花子のレコード ===\n";
$aokiHanako = DB::table('clinic_users')->where('last_name', '青木')->where('first_name', '花子')->first();
if ($aokiHanako) {
    echo "ID: {$aokiHanako->id}\n";
    $records = DB::table('records')
        ->where('clinic_user_id', $aokiHanako->id)
        ->where('created_at', '>=', '2025-01-01')
        ->get(['id', 'therapy_conetnt_id', 'created_at']);
    foreach ($records as $r) {
        echo "  Record ID: {$r->id}, therapy_conetnt_id: {$r->therapy_conetnt_id}, Date: {$r->created_at}\n";
    }
    $counts = DB::table('records')
        ->select('therapy_conetnt_id', DB::raw('COUNT(*) as count'))
        ->where('clinic_user_id', $aokiHanako->id)
        ->where('created_at', '>=', '2025-01-01')
        ->groupBy('therapy_conetnt_id')
        ->get();
    echo "  集計:\n";
    foreach ($counts as $c) {
        echo "    therapy_conetnt_id {$c->therapy_conetnt_id}: {$c->count}件\n";
    }
}

echo "\n=== therapy_contents マスタ ===\n";
$therapyContents = DB::table('therapy_contents')->orderBy('id')->get(['id', 'name']);
foreach ($therapyContents as $tc) {
    echo "ID {$tc->id}: {$tc->name}\n";
}
