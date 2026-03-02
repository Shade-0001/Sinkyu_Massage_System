<?php
// database/seeders/MedicalInstitutionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicalInstitutionSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('medical_institutions')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    $now = now();
    DB::connection('sinkyu_massage_system_db')->table('medical_institutions')->insert([
      // クリニック
      ['medical_institution_name' => '東京中央クリニック',         'created_at' => $now, 'updated_at' => $now],
      ['medical_institution_name' => '京都四条クリニック',         'created_at' => $now, 'updated_at' => $now],
      ['medical_institution_name' => '沖縄那覇クリニック',         'created_at' => $now, 'updated_at' => $now],
      // 医院
      ['medical_institution_name' => '横浜みなと内科医院',         'created_at' => $now, 'updated_at' => $now],
      ['medical_institution_name' => '広島平和通り医院',           'created_at' => $now, 'updated_at' => $now],
      ['medical_institution_name' => '静岡駅前内科医院',           'created_at' => $now, 'updated_at' => $now],
      ['medical_institution_name' => '金沢香林坊内科医院',         'created_at' => $now, 'updated_at' => $now],
      // 病院
      ['medical_institution_name' => '名古屋栄総合病院',           'created_at' => $now, 'updated_at' => $now],
      ['medical_institution_name' => '川崎市立多摩病院',           'created_at' => $now, 'updated_at' => $now],
      ['medical_institution_name' => '長野中央病院',               'created_at' => $now, 'updated_at' => $now],
      // 整形外科
      ['medical_institution_name' => '大阪北整形外科',             'created_at' => $now, 'updated_at' => $now],
      ['medical_institution_name' => '札幌北区整形外科',           'created_at' => $now, 'updated_at' => $now],
      ['medical_institution_name' => '神戸三宮整形外科',           'created_at' => $now, 'updated_at' => $now],
      ['medical_institution_name' => '池袋西口整形外科',           'created_at' => $now, 'updated_at' => $now],
      ['medical_institution_name' => '熊本城南整形外科',           'created_at' => $now, 'updated_at' => $now],
      // 内科・その他科
      ['medical_institution_name' => '仙台泉内科外科',             'created_at' => $now, 'updated_at' => $now],
      ['medical_institution_name' => '埼玉大宮内科',               'created_at' => $now, 'updated_at' => $now],
      ['medical_institution_name' => '渋谷神経内科',               'created_at' => $now, 'updated_at' => $now],
      // 診療所・センター
      ['medical_institution_name' => '福岡天神リハビリテーション診療所', 'created_at' => $now, 'updated_at' => $now],
      ['medical_institution_name' => '千葉中央脳神経外科',         'created_at' => $now, 'updated_at' => $now],
    ]);
  }
}
