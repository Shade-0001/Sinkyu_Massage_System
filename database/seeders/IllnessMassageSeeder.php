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

    // ID1~6: 固定傷病名
    DB::connection('sinkyu_massage_system_db')->table('illnesses_massage')->insert([
      ['id' => 1, 'illness_name' => '脳梗塞後遺症',              'created_at' => now(), 'updated_at' => now()],
      ['id' => 2, 'illness_name' => '脳出血後遺症',              'created_at' => now(), 'updated_at' => now()],
      ['id' => 3, 'illness_name' => 'パーキンソン病',            'created_at' => now(), 'updated_at' => now()],
      ['id' => 4, 'illness_name' => '頸髄損傷',                  'created_at' => now(), 'updated_at' => now()],
      ['id' => 5, 'illness_name' => '腰髄損傷',                  'created_at' => now(), 'updated_at' => now()],
      ['id' => 6, 'illness_name' => '筋萎縮性側索硬化症（ALS）', 'created_at' => now(), 'updated_at' => now()],
    ]);

    // ID7~: ダミー傷病名（ID1~6の傷病名を含まない）
    $dummyNames = [
      '脊髄小脳変性症',
      '廃用症候群',
      '脳性麻痺',
      '多発性硬化症',
      '末梢神経障害',
      '筋ジストロフィー',
      '重症筋無力症',
      '強直性脊椎炎',
      '変形性肩関節症',
      '後縦靱帯骨化症',
    ];

    foreach ($dummyNames as $name) {
      DB::connection('sinkyu_massage_system_db')->table('illnesses_massage')->insert([
        'illness_name' => $name,
        'created_at'   => now(),
        'updated_at'   => now(),
      ]);
    }
  }
}
