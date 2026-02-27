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

    $clinicUserIds = DB::connection('sinkyu_massage_system_db')->table('clinic_users')->pluck('id')->toArray();

    // 約70%のclinic_userにケアプランを付与
    shuffle($clinicUserIds);
    $targetIds = array_slice($clinicUserIds, 0, (int) round(count($clinicUserIds) * 0.7));

    $data = [];
    foreach ($targetIds as $userId) {
      $row = Plan::factory()->make()->getAttributes();
      $row['clinic_user_id'] = $userId;
      $row['created_at']     = now();
      $row['updated_at']     = now();
      $data[] = $row;
    }

    foreach (array_chunk($data, 100) as $chunk) {
      DB::connection('sinkyu_massage_system_db')->table('plans')->insert($chunk);
    }
  }
}
