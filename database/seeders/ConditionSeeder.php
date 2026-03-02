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

    $conditions = [
      '症状が継続している',
      '痛みが増悪傾向',
      '痛みが軽減傾向',
      '症状に変化なし',
      '間欠的に疼痛あり',
      '安静時痛が持続',
      '夜間痛が著明',
      '動作時痛が残存',
      '可動域が制限中',
      '可動域が改善傾向',
      '筋力低下が継続',
      '筋力が回復傾向',
      'しびれが継続中',
      'しびれが軽減傾向',
      'むくみが持続中',
      'むくみが改善傾向',
      '炎症が残存中',
      '炎症が鎮静傾向',
      '痙攣が断続的',
      '痙性が軽減傾向',
      '麻痺が残存中',
      '麻痺に改善みられる',
      '廃用が進行中',
      '廃用が改善傾向',
      '関節拘縮が進行',
      '拘縮が改善傾向',
      '骨折後の経過中',
      '術後の経過観察中',
      '症状が寛解傾向',
      '症状が再燃中',
      '疲労感が持続中',
      '倦怠感が継続中',
      '機能障害が残存',
      '機能が回復傾向',
      '歩行困難が継続',
      '歩行能力が改善',
      '日常動作に支障',
      '動作能力が改善',
      '自覚症状が残存',
      '自覚症状が改善',
      '慢性期に移行中',
      '回復期に移行中',
      '維持期の経過中',
      '急性期が持続中',
      '亜急性期の経過',
      '悪化と改善を繰返す',
      '経過は緩徐',
      '経過は良好',
      '経過は不良',
      '症状が複合的に残存',
    ];

    $rows = array_map(fn($name) => [
      'condition_name' => $name,
      'created_at'     => now(),
      'updated_at'     => now(),
    ], $conditions);

    DB::connection('sinkyu_massage_system_db')->table('conditions')->insert($rows);
  }
}
