<?php
// database/seeders/IllnessMassageSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IllnessMassageSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('illnesses_massage')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    DB::connection('sinkyu_massage_system_db')->table('illnesses_massage')->insert([
      ['illness_name' => '脳梗塞後遺症',              'created_at' => now(), 'updated_at' => now()],
      ['illness_name' => '脳出血後遺症',              'created_at' => now(), 'updated_at' => now()],
      ['illness_name' => 'パーキンソン病',            'created_at' => now(), 'updated_at' => now()],
      ['illness_name' => '頸髄損傷',                  'created_at' => now(), 'updated_at' => now()],
      ['illness_name' => '腰髄損傷',                  'created_at' => now(), 'updated_at' => now()],
      ['illness_name' => '筋萎縮性側索硬化症（ALS）', 'created_at' => now(), 'updated_at' => now()],
      ['illness_name' => '脊髄小脳変性症',            'created_at' => now(), 'updated_at' => now()],
      ['illness_name' => '関節リウマチ',              'created_at' => now(), 'updated_at' => now()],
      ['illness_name' => '変形性膝関節症',            'created_at' => now(), 'updated_at' => now()],
      ['illness_name' => '変形性股関節症',            'created_at' => now(), 'updated_at' => now()],
      ['illness_name' => '五十肩（肩関節周囲炎）',   'created_at' => now(), 'updated_at' => now()],
      ['illness_name' => '腰椎椎間板ヘルニア',       'created_at' => now(), 'updated_at' => now()],
      ['illness_name' => '脊柱管狭窄症',              'created_at' => now(), 'updated_at' => now()],
      ['illness_name' => '廃用症候群',                'created_at' => now(), 'updated_at' => now()],
      ['illness_name' => 'その他',                    'created_at' => now(), 'updated_at' => now()],
    ]);
  }
}
