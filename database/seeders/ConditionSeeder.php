<?php
// database/seeders/ConditionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConditionSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('conditions')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    DB::connection('sinkyu_massage_system_db')->table('conditions')->insert([
      ['condition_name' => '慢性的な疼痛',      'created_at' => now(), 'updated_at' => now()],
      ['condition_name' => '慢性的な筋緊張',    'created_at' => now(), 'updated_at' => now()],
      ['condition_name' => '関節可動域制限',    'created_at' => now(), 'updated_at' => now()],
      ['condition_name' => '神経麻痺（弛緩性）', 'created_at' => now(), 'updated_at' => now()],
      ['condition_name' => '神経麻痺（痙性）',  'created_at' => now(), 'updated_at' => now()],
      ['condition_name' => '廃用性筋萎縮',      'created_at' => now(), 'updated_at' => now()],
      ['condition_name' => 'その他',            'created_at' => now(), 'updated_at' => now()],
    ]);
  }
}
