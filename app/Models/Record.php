<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Record extends Model
{
  protected $fillable = [
    'clinic_user_id',
    'date',
    'start_time',
    'end_time',
    'therapy_type',
    'therapy_category',
    'insurance_category',
    'housecall_distance',
    'therapy_days',
    'consent_expiry',
    'therapy_content_id',
    'self_fee_id',
    'bill_category_id',
    'therapist_id',
    'abstract',
  ];

  protected $casts = [
    'date' => 'date',
  ];

  /**
   * Recordが保存・更新・削除された後にdepositsテーブルを更新
   */
  protected static function booted()
  {
    static::saved(function ($record) {
      self::syncDeposit($record);
    });

    static::deleted(function ($record) {
      self::syncDepositAfterDelete($record);
    });
  }

  /**
   * 実績データからdepositsテーブルを同期
   */
  public static function syncDeposit($record)
  {
    // clinic_user_idからinsuranceとinsurerを取得
    $insurance = DB::table('insurances')
      ->where('clinic_user_id', $record->clinic_user_id)
      ->first();

    if (!$insurance) {
      return; // 保険情報がない場合はスキップ
    }

    $yearMonth = $record->date->format('Y-m');

    // 同じ月・利用者・保険者・被保険者・施術種類のdepositsデータを検索
    $deposit = Deposit::where('clinic_user_id', $record->clinic_user_id)
      ->where('year_month', $yearMonth)
      ->where('insurer_id', $insurance->insurers_id)
      ->where('insured_name', $insurance->insured_name)
      ->where('treatment_type', $record->therapy_type)
      ->first();

    // 該当する全recordsを取得
    $relatedRecords = self::where('clinic_user_id', $record->clinic_user_id)
      ->whereYear('date', $record->date->year)
      ->whereMonth('date', $record->date->month)
      ->where('therapy_type', $record->therapy_type)
      ->get();

    $treatmentDates = $relatedRecords->pluck('date')->map(function ($date) {
      return $date->format('Y-m-d');
    })->sort()->values()->toArray();

    $therapyPeriodStart = min($treatmentDates);
    $therapyPeriodEnd = max($treatmentDates);

    if ($deposit) {
      // 既存のdepositを更新
      $deposit->update([
        'treatment_dates' => $treatmentDates,
        'therapy_period_start' => $therapyPeriodStart,
        'therapy_period_end' => $therapyPeriodEnd,
      ]);
    } else {
      // 新規にdepositを作成
      Deposit::create([
        'clinic_user_id' => $record->clinic_user_id,
        'year_month' => $yearMonth,
        'insurer_id' => $insurance->insurers_id,
        'insured_name' => $insurance->insured_name,
        'therapy_period_start' => $therapyPeriodStart,
        'therapy_period_end' => $therapyPeriodEnd,
        'treatment_type' => $record->therapy_type,
        'treatment_dates' => $treatmentDates,
        'total_amount' => 0,
        'selfpay_amount' => 0,
        'insurance_billing_amount' => 0,
        'deposit_amount' => 0,
        'deposit_date' => null,
      ]);
    }
  }

  /**
   * 実績データ削除後にdepositsテーブルを同期
   */
  public static function syncDepositAfterDelete($record)
  {
    $insurance = DB::table('insurances')
      ->where('clinic_user_id', $record->clinic_user_id)
      ->first();

    if (!$insurance) {
      return;
    }

    $yearMonth = $record->date->format('Y-m');

    // 同じ条件のdepositsデータを検索
    $deposit = Deposit::where('clinic_user_id', $record->clinic_user_id)
      ->where('year_month', $yearMonth)
      ->where('insurer_id', $insurance->insurers_id)
      ->where('insured_name', $insurance->insured_name)
      ->where('treatment_type', $record->therapy_type)
      ->first();

    if (!$deposit) {
      return;
    }

    // 該当する残りのrecordsを取得
    $relatedRecords = self::where('clinic_user_id', $record->clinic_user_id)
      ->whereYear('date', $record->date->year)
      ->whereMonth('date', $record->date->month)
      ->where('therapy_type', $record->therapy_type)
      ->get();

    if ($relatedRecords->isEmpty()) {
      // 該当するrecordsがなくなった場合はdepositを削除
      $deposit->delete();
    } else {
      // まだrecordsが残っている場合は治療日を更新
      $treatmentDates = $relatedRecords->pluck('date')->map(function ($date) {
        return $date->format('Y-m-d');
      })->sort()->values()->toArray();

      $therapyPeriodStart = min($treatmentDates);
      $therapyPeriodEnd = max($treatmentDates);

      $deposit->update([
        'treatment_dates' => $treatmentDates,
        'therapy_period_start' => $therapyPeriodStart,
        'therapy_period_end' => $therapyPeriodEnd,
      ]);
    }
  }
}
