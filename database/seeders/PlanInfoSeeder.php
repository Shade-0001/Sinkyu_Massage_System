<?php
// database/seeders/PlanInfoSeeder.php

namespace Database\Seeders;

use App\Models\PlanInfo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanInfoSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('plan_infos')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    $clinicUserIds = DB::connection('sinkyu_massage_system_db')->table('clinic_users')->pluck('id')->toArray();

    // 約50%のclinic_userに計画情報を付与
    shuffle($clinicUserIds);
    $targetIds = array_slice($clinicUserIds, 0, (int) round(count($clinicUserIds) * 0.5));

    $data = [];
    foreach ($targetIds as $userId) {
      $row = PlanInfo::factory()->make()->getAttributes();
      $row['clinic_user_id'] = $userId;
      $row['created_at']     = now();
      $row['updated_at']     = now();
      $data[] = $row;
    }

    foreach (array_chunk($data, 100) as $chunk) {
      DB::connection('sinkyu_massage_system_db')->table('plan_infos')->insert($chunk);
    }
  }
}
