<?php
// database/seeders/BodypartConsentMassageSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BodypartConsentMassageSeeder extends Seeder
{
  /**
   * 重み付きランダムで部位数を決定する
   *
   * @param array $distribution ['count' => int, 'weight' => int][] の配列
   * @return int
   */
  private function pickCount(array $distribution): int
  {
    $total = array_sum(array_column($distribution, 'weight'));
    $rand  = rand(1, $total);
    $cumulative = 0;
    foreach ($distribution as $item) {
      $cumulative += $item['weight'];
      if ($rand <= $cumulative) {
        return $item['count'];
      }
    }
    return $distribution[0]['count'];
  }

  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('bodyparts-consents_massage')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    // is_* フラグが1のレコードのみ対象
    $consents = DB::connection('sinkyu_massage_system_db')
      ->table('consents_massage')
      ->select('id', 'is_symptom_1', 'is_symptom_2', 'is_therapy_type_1', 'is_therapy_type_2')
      ->get();

    if ($consents->isEmpty()) {
      return;
    }

    // 症状1 / 施術1 / 施術2 で選択可能な部位ID（左右上肢・下肢 = 4種）
    // bodyparts テーブル: upper_limb_r=2, upper_limb_l=3, lower_limb_r=4, lower_limb_l=5
    $symptom1Parts      = [2, 3, 4, 5];
    $therapyType1Parts  = [2, 3, 4, 5];
    $therapyType2Parts  = [2, 3, 4, 5];

    // 症状2 で選択可能な部位ID（各関節 12種）
    // shoulder_r=6, shoulder_l=7, elbow_r=8, elbow_l=9,
    // wrist_r=10, wrist_l=11, coxa_r=12, coxa_l=13,
    // knee_r=14, knee_l=15, ankle_r=16, ankle_l=17
    $symptom2Parts = [6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17];

    // 部位数の分布設定（weight は相対的な重み、合計100相当）
    // 症状1 / 施術1 / 施術2：3部位5% / 2部位35% / 1部位60%
    $dist134 = [
      ['count' => 3, 'weight' => 5],
      ['count' => 2, 'weight' => 35],
      ['count' => 1, 'weight' => 60],
    ];

    // 症状2：6部位1% / 5部位1% / 4部位3% / 3部位5% / 2部位30% / 1部位60%
    $dist2 = [
      ['count' => 6, 'weight' => 1],
      ['count' => 5, 'weight' => 1],
      ['count' => 4, 'weight' => 3],
      ['count' => 3, 'weight' => 5],
      ['count' => 2, 'weight' => 30],
      ['count' => 1, 'weight' => 60],
    ];

    $data = [];
    $now  = now();

    foreach ($consents as $consent) {
      $consentId = $consent->id;

      // 各フィールドで選択された部位IDリストを生成（is_*=0 なら空配列）
      $s1Ids = [];
      $s2Ids = [];
      $t1Ids = [];
      $t2Ids = [];

      if ($consent->is_symptom_1) {
        $pool  = $symptom1Parts;
        $count = min($this->pickCount($dist134), count($pool));
        shuffle($pool);
        $s1Ids = array_slice($pool, 0, $count);
      }

      if ($consent->is_symptom_2) {
        $pool  = $symptom2Parts;
        $count = min($this->pickCount($dist2), count($pool));
        shuffle($pool);
        $s2Ids = array_slice($pool, 0, $count);
      }

      if ($consent->is_therapy_type_1) {
        $pool  = $therapyType1Parts;
        $count = min($this->pickCount($dist134), count($pool));
        shuffle($pool);
        $t1Ids = array_slice($pool, 0, $count);
      }

      if ($consent->is_therapy_type_2) {
        $pool  = $therapyType2Parts;
        $count = min($this->pickCount($dist134), count($pool));
        shuffle($pool);
        $t2Ids = array_slice($pool, 0, $count);
      }

      // 最大部位数に合わせて行数を決定（全て0なら1行だけNULLで挿入）
      $maxRows = max(count($s1Ids), count($s2Ids), count($t1Ids), count($t2Ids), 1);

      for ($i = 0; $i < $maxRows; $i++) {
        $data[] = [
          'consents_massage_id'         => $consentId,
          'symtom_1_bodyparts_id'       => $s1Ids[$i] ?? null,
          'symtom_2_bodyparts_id'       => $s2Ids[$i] ?? null,
          'therapy_type_1_bodyparts_id' => $t1Ids[$i] ?? null,
          'therapy_type_2_bodyparts_id' => $t2Ids[$i] ?? null,
          'created_at'                  => $now,
          'updated_at'                  => $now,
        ];
      }
    }

    foreach (array_chunk($data, 100) as $chunk) {
      DB::connection('sinkyu_massage_system_db')->table('bodyparts-consents_massage')->insert($chunk);
    }
  }
}
