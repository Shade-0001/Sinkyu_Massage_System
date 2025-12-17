<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckTherapyType extends Command
{
  protected $signature = 'check:therapy-type';
  protected $description = 'Check therapy type values';

  public function handle()
  {
    $therapyTypes = DB::table('records')
      ->select('therapy_type')
      ->distinct()
      ->get();

    $this->info('療法タイプ:');
    foreach ($therapyTypes as $type) {
      $this->line('  ' . $type->therapy_type);
    }

    // サンプルデータを確認
    $sampleRecords = DB::table('records')->limit(3)->get();
    $this->info("\nサンプルレコード:");
    foreach ($sampleRecords as $record) {
      $this->line("  Date: " . $record->date . ", Type: " . $record->therapy_type);
    }
  }
}
