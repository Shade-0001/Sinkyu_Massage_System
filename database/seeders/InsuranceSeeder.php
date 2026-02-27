<?php
// database/seeders/InsuranceSeeder.php

namespace Database\Seeders;

use App\Models\Insurance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsuranceSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('insurances')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    $clinicUserIds = DB::connection('sinkyu_massage_system_db')->table('clinic_users')->pluck('id');
    $insurerIds    = DB::connection('sinkyu_massage_system_db')->table('insurers')->pluck('id')->toArray();

    $data = [];
    foreach ($clinicUserIds as $userId) {
      $insurerId = $insurerIds[array_rand($insurerIds)];
      $row = Insurance::factory()->make()->getAttributes();
      $row['clinic_user_id'] = $userId;
      $row['insurers_id']    = $insurerId;
      $row['created_at']     = now();
      $row['updated_at']     = now();
      $data[] = $row;
    }

    foreach (array_chunk($data, 100) as $chunk) {
      DB::connection('sinkyu_massage_system_db')->table('insurances')->insert($chunk);
    }
  }
}
