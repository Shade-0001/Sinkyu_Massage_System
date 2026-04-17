<?php
// database/seeders/IllnessAcupunctureSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IllnessAcupunctureSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('illnesses_acupuncture')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    // ID1~6: 固定傷病名
    DB::connection('sinkyu_massage_system_db')->table('illnesses_acupuncture')->insert([
      ['id' => 1, 'illness_name_acupuncture' => '神経痛',          'created_at' => now(), 'updated_at' => now()],
      ['id' => 2, 'illness_name_acupuncture' => 'リウマチ',         'created_at' => now(), 'updated_at' => now()],
      ['id' => 3, 'illness_name_acupuncture' => '頚腕症候群',       'created_at' => now(), 'updated_at' => now()],
      ['id' => 4, 'illness_name_acupuncture' => '五十肩',           'created_at' => now(), 'updated_at' => now()],
      ['id' => 5, 'illness_name_acupuncture' => '腰痛症',           'created_at' => now(), 'updated_at' => now()],
      ['id' => 6, 'illness_name_acupuncture' => '頸椎捻挫後遺症',  'created_at' => now(), 'updated_at' => now()],
    ]);

    // ID7~: ダミー傷病名（ID1~6の傷病名を含まない）
    $dummyNames = [
      '坐骨神経痛',
      '変形性膝関節症',
      '関節炎',
      '腱鞘炎',
      '肘関節炎',
      '顔面神経麻痺',
      '帯状疱疹後神経痛',
      '線維筋痛症',
      '胸郭出口症候群',
      '肩甲骨周囲炎',
    ];

    foreach ($dummyNames as $name) {
      DB::connection('sinkyu_massage_system_db')->table('illnesses_acupuncture')->insert([
        'illness_name_acupuncture' => $name,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }
  }
}
