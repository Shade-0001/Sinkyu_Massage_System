<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Deposit;

/**
 * 入金管理コントローラー
 */
class DepositsController extends Controller
{
  /**
   * 入金管理一覧を表示
   *
   * @param Request $request
   * @return \Illuminate\View\View
   */
  public function index(Request $request)
  {

    // 2020年01月から現在年月+2ヵ月までの年月リストを生成
    $startDate = new \DateTime('2020-01-01');
    $currentDate = new \DateTime();
    $endDate = (clone $currentDate)->modify('+2 months');

    $yearMonths = [];
    $tempDate = clone $startDate;
    while ($tempDate <= $endDate) {
      $yearMonth = $tempDate->format('Y-m');
      // 各月のデータ有無をチェック
      $hasData = Deposit::where('year_month', $yearMonth)->exists();

      $yearMonths[] = [
        'year' => (int)$tempDate->format('Y'),
        'month' => (int)$tempDate->format('m'),
        'year_month' => $yearMonth,
        'has_data' => $hasData,
      ];
      $tempDate->modify('+1 month');
    }

    // 逆順に並べる
    $yearMonths = array_reverse($yearMonths);

    // 年ごとのデータ件数を取得
    $depositsData = Deposit::select('year_month')->get();
    $yearCounts = [];
    foreach ($depositsData as $deposit) {
      $year = (int)substr($deposit->year_month, 0, 4);
      if (!isset($yearCounts[$year])) {
        $yearCounts[$year] = 0;
      }
      $yearCounts[$year]++;
    }

    // 年ごとにグループ化
    $depositsByYear = collect($yearMonths)->groupBy('year')->map(function ($months, $year) use ($yearCounts) {
      // その年に入金データが1件でもあるかチェック（月データのhas_dataフラグを確認）
      $hasDeposits = collect($months)->contains('has_data', true);
      $count = $yearCounts[$year] ?? 0;

      return [
        'has_deposits' => $hasDeposits,
        'count' => $count,
        'months' => $months->values()->all(),
      ];
    })->all();

    // スクロール位置の決定
    $scrollToYearMonth = $currentDate->format('Y-m');

    return view('deposits.deposits_index', [
      'depositsByYear' => $depositsByYear,
      'scrollToYearMonth' => $scrollToYearMonth,
      'page_header_title' => '入金管理',
    ]);
  }

  /**
   * 指定年月の入金データを取得
   *
   * @param string $yearMonth (YYYY-MM形式)
   * @return \Illuminate\Http\JsonResponse
   */
  public function getMonthData($yearMonth)
  {
    // 施術料金マスタを取得（最新）
    $treatmentFees = DB::table('treatment_fees')->orderBy('id', 'desc')->first();

    // therapy_content_id → treatment_feesカラム名マッピング（通常料金）
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

    // 負担割合マップ
    $ratioMap = [1 => 0.1, 2 => 0.2, 3 => 0.3];

    $deposits = Deposit::where('year_month', $yearMonth)
      ->with(['clinicUser', 'insurer'])
      ->orderBy('id')
      ->get()
      ->map(function ($deposit) use ($treatmentFees, $columnMap, $ratioMap) {
        // 治療日を縦並びで表示用にフォーマット
        $treatmentDatesFormatted = collect($deposit->treatment_dates)
          ->map(function ($date) {
            return date('Y/m/d', strtotime($date));
          })
          ->join("\n");

        // 療養費合計を算出：該当施術レコードのtherapy_content_id単価×件数の合計
        $totalAmount = 0;
        if ($deposit->clinic_user_id && !empty($deposit->treatment_dates) && $treatmentFees) {
          $records = DB::table('records')
            ->where('clinic_user_id', $deposit->clinic_user_id)
            ->whereIn('date', $deposit->treatment_dates)
            ->where('therapy_type', $deposit->treatment_type)
            ->get();

          foreach ($records as $record) {
            $column = $columnMap[$record->therapy_content_id] ?? null;
            if ($column && isset($treatmentFees->$column)) {
              $totalAmount += (int)$treatmentFees->$column;
            }
          }
        }

        // 負担割合を取得
        $insurance = DB::table('insurances')
          ->where('clinic_user_id', $deposit->clinic_user_id)
          ->orderBy('id', 'desc')
          ->first();
        $ratioId = $insurance->expenses_borne_ratio_id ?? 1;
        $ratio = $ratioMap[$ratioId] ?? 0.1;

        // 自己負担額：療養費合計 × 負担割合（10円単位四捨五入）
        $selfpayAmount = (int)round($totalAmount * $ratio, -1);

        // 保険請求額：療養費合計 − 自己負担額
        $insuranceBillingAmount = $totalAmount - $selfpayAmount;

        return [
          'id' => $deposit->id,
          'insurer_name' => $deposit->insurer->insurer_name ?? '',
          'insured_name' => $deposit->insured_name ?? '',
          'clinic_user_name' => $deposit->clinicUser ? ($deposit->clinicUser->last_name . ' ' . $deposit->clinicUser->first_name) : '',
          'treatment_dates' => $treatmentDatesFormatted,
          'treatment_type' => $deposit->treatment_type == 1 ? '鍼灸' : 'マッサージ',
          'total_amount' => $totalAmount,
          'selfpay_amount' => $selfpayAmount,
          'insurance_billing_amount' => $insuranceBillingAmount,
          'deposit_amount' => $deposit->deposit_amount ?? 0,
          'deposit_date' => $deposit->deposit_date ? $deposit->deposit_date->format('Y-m-d') : '',
        ];
      });

    return response()->json([
      'success' => true,
      'deposits' => $deposits,
    ]);
  }

  /**
   * 入金データを登録・更新
   *
   * @param Request $request
   * @param int $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function update(Request $request, $id)
  {

    $validated = $request->validate([
      'deposit_amount' => 'nullable|integer|min:0',
      'deposit_date' => 'nullable|date',
    ]);

    try {
      $deposit = Deposit::findOrFail($id);

      $deposit->update([
        'deposit_amount' => $validated['deposit_amount'] ?? 0,
        'deposit_date' => $validated['deposit_date'] ?? null,
      ]);

      return response()->json([
        'success' => true,
        'message' => '入金データを登録しました。',
      ]);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'データの登録に失敗しました：' . $e->getMessage(),
      ], 500);
    }
  }
}
