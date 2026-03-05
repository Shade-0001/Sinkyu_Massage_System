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

    // --- マスタデータ取得 ---

    // therapy_content_id → treatment_feesカラム名マッピング（通常料金）
    // DepositsController::getMonthData() の $columnMap と同一定義
    $columnMap = [
      11 => 'hari_normal',
      12 => 'kyu_normal',
      13 => 'hari_and_kyu_normal',
      14 => 'hari_and_elec_needle_normal',
      15 => 'kyu_and_elec_moxa_heater_normal',
      16 => 'hari_and_kyu_elec_ray_normal',
      18 => 'massage_trunk_normal',
      19 => 'manual_correction_normal',
      20 => 'fomentation_normal',
      21 => 'fomentation_and_elec_ray_normal',
    ];

    // 負担割合マップ（expenses_borne_ratio_id → 割合）
    $ratioMap = [1 => 0.1, 2 => 0.2, 3 => 0.3];

    // 治療費マスタ（最新1件）
    $treatmentFees = DB::connection('sinkyu_massage_system_db')
      ->table('treatment_fees')
      ->orderBy('id', 'desc')
      ->first();
    $treatmentFeesArr = $treatmentFees ? (array)$treatmentFees : [];

    // insurances: clinic_user_id ごとに最新1件をキャッシュ
    $insuranceMap = DB::connection('sinkyu_massage_system_db')
      ->table('insurances')
      ->orderBy('id', 'asc')
      ->get()
      ->groupBy('clinic_user_id')
      ->map(fn($rows) => $rows->first());

    // --- recordsからグループを集約 ---
    // (clinic_user_id, year_month, therapy_type) の単位でグループ化し
    // 実際の施術日・therapy_content_idを取得

    $records = DB::connection('sinkyu_massage_system_db')
      ->table('records')
      ->select('clinic_user_id', 'date', 'therapy_type', 'therapy_content_id')
      ->orderBy('clinic_user_id')
      ->orderBy('date')
      ->get();

    // グループ化: key = "clinic_user_id|year_month|therapy_type"
    $groups = [];
    foreach ($records as $rec) {
      $yearMonth = substr($rec->date, 0, 7); // "YYYY-MM"
      $key = "{$rec->clinic_user_id}|{$yearMonth}|{$rec->therapy_type}";
      $groups[$key]['clinic_user_id'] = $rec->clinic_user_id;
      $groups[$key]['year_month']     = $yearMonth;
      $groups[$key]['therapy_type']   = $rec->therapy_type;
      $groups[$key]['dates'][]        = $rec->date;
      $groups[$key]['content_ids'][]  = $rec->therapy_content_id;
    }

    // --- deposits データ生成 ---
    $data = [];
    $now  = now()->toDateTimeString();

    foreach ($groups as $group) {
      $userId      = $group['clinic_user_id'];
      $yearMonth   = $group['year_month'];
      $therapyType = $group['therapy_type'];

      // 保険情報取得
      $insurance = $insuranceMap[$userId] ?? null;
      if (!$insurance) {
        continue; // 保険情報なしはスキップ（syncDepositと同仕様）
      }
      $insurerId    = $insurance->insurers_id;
      $insuredName  = $insurance->insured_name;
      $ratioId      = $insurance->expenses_borne_ratio_id ?? 1;
      $ratio        = $ratioMap[$ratioId] ?? 0.1;

      // 施術日：重複除去・昇順ソート・不正日付除外
      $treatmentDates = array_unique($group['dates']);
      $treatmentDates = array_values(array_filter($treatmentDates, function ($d) {
        try {
          $dt = new \DateTime($d);
          return $dt->format('Y-m-d') === $d;
        } catch (\Exception $e) {
          return false;
        }
      }));
      sort($treatmentDates);

      if (empty($treatmentDates)) {
        continue;
      }

      $therapyPeriodStart = $treatmentDates[0];
      $therapyPeriodEnd   = end($treatmentDates);

      // 療養費合計：各recordのtherapy_content_id単価の合計
      $totalAmount = 0;
      foreach ($group['content_ids'] as $contentId) {
        $col = $columnMap[$contentId] ?? null;
        if ($col && isset($treatmentFeesArr[$col])) {
          $totalAmount += (int)$treatmentFeesArr[$col];
        }
      }

      // 自己負担額（10円単位四捨五入）
      $selfpayAmount = (int)round($totalAmount * $ratio, -1);

      // 保険請求額
      $insuranceBillingAmount = $totalAmount - $selfpayAmount;

      // 入金額・入金日：概ね半数を入金済み扱い
      // 入金日 = 施術期間終了月の翌月末 +10〜45日（請求から入金までのラグ）
      $hasDeposit  = (bool)rand(0, 1);
      $depositAmount = $hasDeposit ? $insuranceBillingAmount : 0;
      $depositDate   = null;
      if ($hasDeposit) {
        $periodEndLastDay  = date('Y-m-t', strtotime($therapyPeriodEnd));
        $lagDays           = rand(10, 45);
        $depositDate       = date('Y-m-d', strtotime($periodEndLastDay . " +{$lagDays} days"));
      }

      $data[] = [
        'clinic_user_id'           => $userId,
        'year_month'               => $yearMonth,
        'insurer_id'               => $insurerId,
        'insured_name'             => $insuredName,
        'therapy_period_start'     => $therapyPeriodStart,
        'therapy_period_end'       => $therapyPeriodEnd,
        'treatment_type'           => $therapyType,
        'treatment_dates'          => json_encode($treatmentDates),
        'total_amount'             => $totalAmount,
        'selfpay_amount'           => $selfpayAmount,
        'insurance_billing_amount' => $insuranceBillingAmount,
        'deposit_amount'           => $depositAmount,
        'deposit_date'             => $depositDate,
        'created_at'               => $now,
        'updated_at'               => $now,
      ];
    }

    foreach (array_chunk($data, 500) as $chunk) {
      DB::connection('sinkyu_massage_system_db')->table('deposits')->insert($chunk);
    }
  }
}
