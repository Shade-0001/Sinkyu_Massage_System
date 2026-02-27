<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    // Group A: マスタ固定データ（データ未挿入の2テーブル）
    $this->call([
      ConditionSeeder::class,
      IllnessMassageSeeder::class,
    ]);

    // Group B: マスタダミーデータ
    $this->call([
      InsurerSeeder::class,
      SelfFeeSeeder::class,
    ]);

    // Group C: 人物マスタ
    $this->call([
      TherapistSeeder::class,
      DoctorSeeder::class,
      CareManagerSeeder::class,
    ]);

    // clinic_users（既存Seeder）
    $this->call(ClinicUserSeeder::class);

    // Group D: トランザクション（依存関係順）
    $this->call([
      InsuranceSeeder::class,
      ConsentMassageSeeder::class,
      BodypartConsentMassageSeeder::class,
      ConsentAcupunctureSeeder::class,
      RecordSeeder::class,
      DepositSeeder::class,
      PlanSeeder::class,
      PlanInfoSeeder::class,
    ]);
  }
}
