<?php
// database/seeders/DepositSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepositSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('deposits')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    $clinicUserIds = DB::connection('sinkyu_massage_system_db')->table('clinic_users')->pluck('id')->toArray();
    $insurerIds    = DB::connection('sinkyu_massage_system_db')->table('insurers')->pluck('id')->toArray();

    // 各clinic_userに対して直近6ヶ月分の請求データを生成
    $data = [];
    foreach ($clinicUserIds as $userId) {
      $insurerId = $insurerIds[array_rand($insurerIds)];
      for ($m = 0; $m < 6; $m++) {
        $yearMonth  = date('Y-m', strtotime("-{$m} months"));
        $periodEnd  = date('Y-m-t', strtotime($yearMonth));
        $periodStart = date('Y-m-01', strtotime($yearMonth));
        $totalAmount = rand(3000, 15000);
        $selfpay     = (int) round($totalAmount * 0.3);
        $insAmt      = $totalAmount - $selfpay;

        // 施術日を3〜12日ランダムに生成
        $treatmentDates = [];
        $daysInMonth = (int) date('t', strtotime($yearMonth));
        $numDays = rand(3, min(12, $daysInMonth));
        $days = array_rand(array_fill(1, $daysInMonth, true), $numDays);
        if (!is_array($days)) { $days = [$days]; }
        foreach ($days as $d) {
          $treatmentDates[] = $yearMonth . '-' . sprintf('%02d', $d + 1);
        }

        $data[] = [
          'clinic_user_id'           => $userId,
          'year_month'               => $yearMonth,
          'insurer_id'               => $insurerId,
          'insured_name'             => null,
          'therapy_period_start'     => $periodStart,
          'therapy_period_end'       => $periodEnd,
          'treatment_type'           => rand(1, 2),
          'treatment_dates'          => json_encode($treatmentDates),
          'total_amount'             => $totalAmount,
          'selfpay_amount'           => $selfpay,
          'insurance_billing_amount' => $insAmt,
          'deposit_amount'           => rand(0, 1) ? $insAmt : 0,
          'deposit_date'             => rand(0, 1) ? date('Y-m-d', strtotime($periodEnd . ' +45 days')) : null,
          'created_at'               => now(),
          'updated_at'               => now(),
        ];
      }
    }

    foreach (array_chunk($data, 500) as $chunk) {
      DB::connection('sinkyu_massage_system_db')->table('deposits')->insert($chunk);
    }
  }
}
