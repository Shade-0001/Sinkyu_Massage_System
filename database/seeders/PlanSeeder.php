<?php
// database/seeders/PlanSeeder.php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('plans')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    // ユーザーごとの施術日一覧を取得（昇順）
    $rows = DB::connection('sinkyu_massage_system_db')
      ->table('records')
      ->select('clinic_user_id', 'date')
      ->orderBy('clinic_user_id')
      ->orderBy('date')
      ->get();

    $byUser = [];
    foreach ($rows as $row) {
      $byUser[$row->clinic_user_id][] = $row->date;
    }

    $data = [];

    foreach ($byUser as $userId => $dates) {
      $dates = array_values(array_unique($dates));
      sort($dates);

      // 計画書が必要な区間の開始日リストを算出
      // ・最初の施術日は必ず1件目
      // ・前の区間開始から6ヶ月以上経過したら新規
      // ・前の施術日から1ヶ月以上空白があったら新規
      $planStartDates = [ $dates[0] ];
      $lastPlanStart  = strtotime($dates[0]);

      for ($i = 1; $i < count($dates); $i++) {
        $prev    = strtotime($dates[$i - 1]);
        $current = strtotime($dates[$i]);

        $gapDays          = ($current - $prev) / 86400;
        $monthsSincePlan  = ($current - $lastPlanStart) / (86400 * 30);

        if ($gapDays >= 31 || $monthsSincePlan >= 6) {
          $planStartDates[] = $dates[$i];
          $lastPlanStart    = $current;
        }
      }

      foreach ($planStartDates as $startDate) {
        $row = Plan::factory()->make()->getAttributes();
        $row['clinic_user_id'] = $userId;
        // assessment_dateは施術開始日の0〜7日前
        $offset = rand(0, 7);
        $row['assessment_date']            = date('Y-m-d', strtotime($startDate . " -{$offset} days"));
        $row['user_and_family_consent_date'] = $row['assessment_date'];
        $row['created_at'] = now();
        $row['updated_at'] = now();
        $data[] = $row;
      }
    }

    foreach (array_chunk($data, 100) as $chunk) {
      DB::connection('sinkyu_massage_system_db')->table('plans')->insert($chunk);
    }

    $this->command->info('PlanSeeder: ' . count($data) . '件挿入完了。');
  }
}
