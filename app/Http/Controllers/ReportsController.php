<?php
//-- app/Http/Controllers/ReportsController.php --//

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;

/**
 * 報告書データ管理コントローラー
 *
 * 利用者の報告書データの管理を担当する。
 * - 報告書データの一覧表示
 * - 利用者選択による絞り込み
 * - 報告書データの新規登録・編集・複製・削除
 */
class ReportsController extends Controller
{
  /**
   * 報告書データ一覧を表示
   *
   * @param Request $request
   * @return \Illuminate\View\View
   */
  public function index(Request $request)
  {
    // 利用者リストを取得
    $clinicUsers = DB::table('clinic_users')
      ->select('id', 'last_name', 'first_name', 'last_kana', 'first_kana')
      ->orderBy('id', 'desc')
      ->get();

    // 利用者IDの最大桁数を算出（0埋め用）
    $maxClinicUserId = $clinicUsers->max('id') ?? 1;
    $clinicUserIdLength = strlen((string) $maxClinicUserId);

    // 選択された利用者ID（未指定時は最上オプション＝最大IDをデフォルト選択）
    $selectedUserId = $request->input('clinic_user_id', $clinicUsers->first()?->id ?? null);

    // 報告書データ一覧（年ごとにグループ化）
    $reportsByYear = [];
    $scrollToYearMonth = null;

    if ($selectedUserId) {
      // 2020年01月から現在年月+2ヵ月までの年月リストを生成
      $startDate = new DateTime('2020-01-01');
      $currentDate = new DateTime();
      $endDate = (clone $currentDate)->modify('+2 months');

      $yearMonths = [];
      $tempDate = clone $startDate;
      while ($tempDate <= $endDate) {
        $yearMonths[] = [
          'year' => (int)$tempDate->format('Y'),
          'month' => (int)$tempDate->format('m'),
          'date_string' => $tempDate->format('Y-m-01'),
        ];
        $tempDate->modify('+1 month');
      }

      // 各年月ごとの報告書データを取得
      $reportsByMonth = collect();
      foreach ($yearMonths as $ym) {
        $report = DB::table('reports')
          ->where('clinic_user_id', $selectedUserId)
          ->whereYear('service_provide_month', $ym['year'])
          ->whereMonth('service_provide_month', $ym['month'])
          ->first();

        $reportsByMonth->push([
          'year' => $ym['year'],
          'month' => $ym['month'],
          'date_string' => $ym['date_string'],
          'report' => $report,
        ]);
      }

      // 逆順に並べる（新しい年月を上に表示）
      $reportsByMonth = $reportsByMonth->reverse();

      // 年ごとにグループ化
      $groupedByYear = $reportsByMonth->groupBy('year');
      foreach ($groupedByYear as $year => $months) {
        // その年に報告書データが1件でもあるかチェック
        $hasReports = $months->contains(function ($item) {
          return $item['report'] !== null;
        });

        $reportsByYear[$year] = [
          'has_reports' => $hasReports,
          'months' => $months->values()->all(),
        ];
      }

      // スクロール位置の決定（対象年月の1ヶ月後を表示）
      if ($request->has('scroll_year') && $request->has('scroll_month')) {
        // 新規登録・編集・複製後：指定年月の1ヶ月後
        $targetDate = new DateTime(sprintf('%04d-%02d-01', $request->input('scroll_year'), $request->input('scroll_month')));
        $targetDate->modify('+1 month');
        $scrollToYearMonth = $targetDate->format('Y-m');
      } else {
        // 初回表示時：現在年月の1ヶ月後
        $targetDate = clone $currentDate;
        $targetDate->modify('+1 month');
        $scrollToYearMonth = $targetDate->format('Y-m');
      }
    }

    return view('reports.reports_index', [
      'clinicUsers' => $clinicUsers,
      'clinicUserIdLength' => $clinicUserIdLength,
      'selectedUserId' => $selectedUserId,
      'reportsByYear' => $reportsByYear,
      'scrollToYearMonth' => $scrollToYearMonth,
      'currentYear' => (int)(new DateTime())->format('Y'),
      'currentMonth' => (int)(new DateTime())->format('n'),
      'page_header_title' => '報告書データ',
    ]);
  }

  /**
   * 報告書データ新規登録画面を表示
   *
   * @param Request $request
   * @return \Illuminate\View\View
   */
  public function create(Request $request)
  {
    $clinicUserId = $request->input('clinic_user_id');
    $year = $request->input('year');
    $month = $request->input('month');

    if (!$clinicUserId || !$year || !$month) {
      return redirect()
        ->route('reports.index')
        ->withErrors(['error' => '必要なパラメータが不足しています。']);
    }

    // 既に同じ年月のデータが存在するかチェック
    $existingReport = DB::table('reports')
      ->where('clinic_user_id', $clinicUserId)
      ->whereYear('service_provide_month', $year)
      ->whereMonth('service_provide_month', $month)
      ->first();

    if ($existingReport) {
      return redirect()
        ->route('reports.index', [
          'clinic_user_id' => $clinicUserId,
          'scroll_year' => $year,
          'scroll_month' => $month
        ])
        ->withErrors(['error' => sprintf('%04d年%02d月の報告書データは既に登録されています。', $year, $month)]);
    }

    return view('reports.reports_form', [
      'mode' => 'create',
      'clinicUserId' => $clinicUserId,
      'year' => $year,
      'month' => $month,
      'report' => null,
      'page_header_title' => '報告書データ新規登録',
    ]);
  }

  /**
   * 報告書データを登録
   *
   * @param Request $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function store(Request $request)
  {
    $validated = $request->validate([
      'clinic_user_id' => 'required|integer',
      'year' => 'required|integer',
      'month' => 'required|integer|min:1|max:12',
      'subjective_symptom_and_wish' => 'nullable|string|max:1000',
      'objective_symptom' => 'nullable|string|max:1000',
      'therapy_content' => 'nullable|string|max:1000',
      'therapy_plan' => 'nullable|string|max:1000',
    ]);

    // 既に同じ年月のデータが存在するかチェック
    $existingReport = DB::table('reports')
      ->where('clinic_user_id', $validated['clinic_user_id'])
      ->whereYear('service_provide_month', $validated['year'])
      ->whereMonth('service_provide_month', $validated['month'])
      ->first();

    if ($existingReport) {
      return redirect()
        ->back()
        ->withInput()
        ->withErrors(['error' => sprintf('%04d年%02d月の報告書データは既に登録されています。', $validated['year'], $validated['month'])]);
    }

    try {
      DB::beginTransaction();

      $serviceProvideMonth = sprintf('%04d-%02d-01', $validated['year'], $validated['month']);

      DB::table('reports')->insert([
        'clinic_user_id' => $validated['clinic_user_id'],
        'service_provide_month' => $serviceProvideMonth,
        'subjective_symptom_and_wish' => $validated['subjective_symptom_and_wish'] ?? null,
        'objective_symptom' => $validated['objective_symptom'] ?? null,
        'therapy_content' => $validated['therapy_content'] ?? null,
        'therapy_plan' => $validated['therapy_plan'] ?? null,
        'created_at' => now(),
        'updated_at' => now(),
      ]);

      DB::commit();

      return redirect()
        ->route('reports.index', [
          'clinic_user_id' => $validated['clinic_user_id'],
          'scroll_year' => $validated['year'],
          'scroll_month' => $validated['month']
        ])
        ->with('success', '報告書データを登録しました。');

    } catch (\Exception $e) {
      DB::rollBack();
      return redirect()
        ->back()
        ->withInput()
        ->withErrors(['error' => 'データの登録に失敗しました：' . $e->getMessage()]);
    }
  }

  /**
   * 報告書データ編集画面を表示
   *
   * @param int $id
   * @return \Illuminate\View\View
   */
  public function edit($id)
  {
    $report = DB::table('reports')->where('id', $id)->first();

    if (!$report) {
      return redirect()
        ->route('reports.index')
        ->withErrors(['error' => '報告書データが見つかりません。']);
    }

    $serviceDate = new DateTime($report->service_provide_month);
    $year = (int)$serviceDate->format('Y');
    $month = (int)$serviceDate->format('m');

    return view('reports.reports_form', [
      'mode' => 'edit',
      'clinicUserId' => $report->clinic_user_id,
      'year' => $year,
      'month' => $month,
      'report' => $report,
      'page_header_title' => '報告書データ編集',
    ]);
  }

  /**
   * 報告書データを更新
   *
   * @param Request $request
   * @param int $id
   * @return \Illuminate\Http\RedirectResponse
   */
  public function update(Request $request, $id)
  {
    $validated = $request->validate([
      'clinic_user_id' => 'required|integer',
      'year' => 'required|integer',
      'month' => 'required|integer|min:1|max:12',
      'subjective_symptom_and_wish' => 'nullable|string|max:1000',
      'objective_symptom' => 'nullable|string|max:1000',
      'therapy_content' => 'nullable|string|max:1000',
      'therapy_plan' => 'nullable|string|max:1000',
    ]);

    $report = DB::table('reports')->where('id', $id)->first();

    if (!$report) {
      return redirect()
        ->route('reports.index')
        ->withErrors(['error' => '報告書データが見つかりません。']);
    }

    try {
      DB::beginTransaction();

      $serviceProvideMonth = sprintf('%04d-%02d-01', $validated['year'], $validated['month']);

      DB::table('reports')
        ->where('id', $id)
        ->update([
          'subjective_symptom_and_wish' => $validated['subjective_symptom_and_wish'] ?? null,
          'objective_symptom' => $validated['objective_symptom'] ?? null,
          'therapy_content' => $validated['therapy_content'] ?? null,
          'therapy_plan' => $validated['therapy_plan'] ?? null,
          'updated_at' => now(),
        ]);

      DB::commit();

      return redirect()
        ->route('reports.index', [
          'clinic_user_id' => $validated['clinic_user_id'],
          'scroll_year' => $validated['year'],
          'scroll_month' => $validated['month']
        ])
        ->with('success', '報告書データを更新しました。');

    } catch (\Exception $e) {
      DB::rollBack();
      return redirect()
        ->back()
        ->withInput()
        ->withErrors(['error' => 'データの更新に失敗しました：' . $e->getMessage()]);
    }
  }

  /**
   * 報告書データ複製画面を表示
   *
   * @param int $id
   * @return \Illuminate\View\View
   */
  public function duplicate($id)
  {
    $report = DB::table('reports')->where('id', $id)->first();

    if (!$report) {
      return redirect()
        ->route('reports.index')
        ->withErrors(['error' => '報告書データが見つかりません。']);
    }

    $serviceDate = new DateTime($report->service_provide_month);
    $year = (int)$serviceDate->format('Y');
    $month = (int)$serviceDate->format('m');

    return view('reports.reports_form', [
      'mode' => 'duplicate',
      'clinicUserId' => $report->clinic_user_id,
      'year' => $year,
      'month' => $month,
      'report' => $report,
      'page_header_title' => '報告書データ複製',
    ]);
  }

  /**
   * 報告書データを複製して登録
   *
   * @param Request $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function duplicateStore(Request $request)
  {
    $validated = $request->validate([
      'clinic_user_id' => 'required|integer',
      'year' => 'required|integer',
      'month' => 'required|integer|min:1|max:12',
      'subjective_symptom_and_wish' => 'nullable|string|max:1000',
      'objective_symptom' => 'nullable|string|max:1000',
      'therapy_content' => 'nullable|string|max:1000',
      'therapy_plan' => 'nullable|string|max:1000',
    ]);

    // 既に同じ年月のデータが存在するかチェック
    $existingReport = DB::table('reports')
      ->where('clinic_user_id', $validated['clinic_user_id'])
      ->whereYear('service_provide_month', $validated['year'])
      ->whereMonth('service_provide_month', $validated['month'])
      ->first();

    if ($existingReport) {
      return redirect()
        ->back()
        ->withInput()
        ->withErrors(['error' => sprintf('%04d年%02d月の報告書データは既に登録されています。', $validated['year'], $validated['month'])]);
    }

    try {
      DB::beginTransaction();

      $serviceProvideMonth = sprintf('%04d-%02d-01', $validated['year'], $validated['month']);

      DB::table('reports')->insert([
        'clinic_user_id' => $validated['clinic_user_id'],
        'service_provide_month' => $serviceProvideMonth,
        'subjective_symptom_and_wish' => $validated['subjective_symptom_and_wish'] ?? null,
        'objective_symptom' => $validated['objective_symptom'] ?? null,
        'therapy_content' => $validated['therapy_content'] ?? null,
        'therapy_plan' => $validated['therapy_plan'] ?? null,
        'created_at' => now(),
        'updated_at' => now(),
      ]);

      DB::commit();

      return redirect()
        ->route('reports.index', [
          'clinic_user_id' => $validated['clinic_user_id'],
          'scroll_year' => $validated['year'],
          'scroll_month' => $validated['month']
        ])
        ->with('success', '報告書データを複製しました。');

    } catch (\Exception $e) {
      DB::rollBack();
      return redirect()
        ->back()
        ->withInput()
        ->withErrors(['error' => 'データの複製に失敗しました：' . $e->getMessage()]);
    }
  }

  /**
   * 報告書データを削除
   *
   * @param int $id
   * @return \Illuminate\Http\RedirectResponse
   */
  public function destroy($id)
  {
    try {
      DB::beginTransaction();

      $report = DB::table('reports')->where('id', $id)->first();

      if (!$report) {
        return redirect()
          ->route('reports.index')
          ->withErrors(['error' => '報告書データが見つかりません。']);
      }

      $serviceDate = new DateTime($report->service_provide_month);
      $year = (int)$serviceDate->format('Y');
      $month = (int)$serviceDate->format('m');

      DB::table('reports')->where('id', $id)->delete();

      DB::commit();

      return redirect()
        ->route('reports.index', [
          'clinic_user_id' => $report->clinic_user_id,
          'scroll_year' => $year,
          'scroll_month' => $month
        ])
        ->with('success', '報告書データを削除しました。');

    } catch (\Exception $e) {
      DB::rollBack();
      return redirect()
        ->back()
        ->withErrors(['error' => 'データの削除に失敗しました：' . $e->getMessage()]);
    }
  }
}
