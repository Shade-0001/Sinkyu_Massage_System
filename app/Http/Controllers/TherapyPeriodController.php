<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 要加療期間リスト管理コントローラー
 *
 * 現在の年月日が要加療期間範囲内の利用者を表示する。
 */
class TherapyPeriodController extends Controller
{
  /**
   * 要加療期間リスト一覧を表示
   */
  public function index()
  {
    $today = Carbon::today();

    // consents_massage から要加療期間内の利用者を取得
    $massagePeriods = DB::table('consents_massage as cm')
      ->join('clinic_users as cu', 'cm.clinic_user_id', '=', 'cu.id')
      ->leftJoin('doctors as d', 'cm.consenting_doctor_id', '=', 'd.id')
      ->leftJoin('medical_institutions as mi', 'd.medical_institutions_id', '=', 'mi.id')
      ->whereNotNull('cm.therapy_period_start_date')
      ->whereNotNull('cm.therapy_period_end_date')
      ->whereDate('cm.therapy_period_start_date', '<=', $today)
      ->whereDate('cm.therapy_period_end_date', '>=', $today)
      ->select(
        'cu.id as clinic_user_id',
        'cu.last_name',
        'cu.first_name',
        DB::raw("'あんま・マッサージ' as category"),
        'cm.therapy_period_start_date',
        'cm.therapy_period_end_date',
        'cm.consenting_start_date',
        'cm.consenting_end_date',
        DB::raw("CONCAT(d.last_name, '\u{2000}', d.first_name) as consenting_doctor_name"),
        'd.id as doctor_id',
        'mi.medical_institution_name',
        'cm.id as consent_id'
      );

    // consents_acupuncture から要加療期間内の利用者を取得
    $acupuncturePeriods = DB::table('consents_acupuncture as ca')
      ->join('clinic_users as cu', 'ca.clinic_user_id', '=', 'cu.id')
      ->leftJoin('doctors as d', 'ca.consenting_doctor_id', '=', 'd.id')
      ->leftJoin('medical_institutions as mi', 'd.medical_institutions_id', '=', 'mi.id')
      ->whereNotNull('ca.therapy_period_start_date')
      ->whereNotNull('ca.therapy_period_end_date')
      ->whereDate('ca.therapy_period_start_date', '<=', $today)
      ->whereDate('ca.therapy_period_end_date', '>=', $today)
      ->select(
        'cu.id as clinic_user_id',
        'cu.last_name',
        'cu.first_name',
        DB::raw("'鍼灸' as category"),
        'ca.therapy_period_start_date',
        'ca.therapy_period_end_date',
        'ca.consenting_start_date',
        'ca.consenting_end_date',
        DB::raw("CONCAT(d.last_name, '\u{2000}', d.first_name) as consenting_doctor_name"),
        'd.id as doctor_id',
        'mi.medical_institution_name',
        'ca.id as consent_id'
      );

    // 2つのクエリを結合して要加療期間開始年月日が新しい順にソート
    $therapyPeriods = $massagePeriods
      ->union($acupuncturePeriods)
      ->orderBy('therapy_period_start_date', 'desc')
      ->get();

    $page_header_title = '要加療期間リスト';

    return view('therapy-periods.therapy-periods_index', compact('therapyPeriods', 'page_header_title'));
  }
}
