<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Records with therapy_conetnt_id ===\n";
$records = DB::table('records')
  ->select('id', 'therapy_conetnt_id')
  ->whereNotNull('therapy_conetnt_id')
  ->limit(20)
  ->get();

foreach ($records as $record) {
  echo "Record ID: {$record->id}, therapy_conetnt_id: {$record->therapy_conetnt_id}\n";
}

echo "\n=== Therapy content ID counts ===\n";
$counts = DB::table('records')
  ->select('therapy_conetnt_id', DB::raw('COUNT(*) as count'))
  ->whereNotNull('therapy_conetnt_id')
  ->groupBy('therapy_conetnt_id')
  ->get();

foreach ($counts as $count) {
  $therapyContent = DB::table('therapy_contents')
    ->where('id', $count->therapy_conetnt_id)
    ->first();

  $name = $therapyContent ? $therapyContent->therapy_content : 'Unknown';
  echo "ID {$count->therapy_conetnt_id} ({$name}): {$count->count} records\n";
}
