<?php
//-- app/Http/Controllers/RecordsController.php --//

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\RecordRequest;
use App\Models\Record;
use App\Services\ClinicInfoService;
use App\Services\RecordService;

/**
 * 実績データ管理コントローラー
 *
 * 利用者の施術実績データの管理を担当する。
 * - 実績データの一覧表示
 * - 利用者選択による絞り込み
 * - 実績データの編集・更新
 */
class RecordsController extends Controller
{
  protected $clinicInfoService;
  protected $recordService;

  public function __construct(ClinicInfoService $clinicInfoService, RecordService $recordService)
  {
    $this->clinicInfoService = $clinicInfoService;
    $this->recordService = $recordService;
  }
  /**
   * 実績データ一覧を表示
   *
   * @param Request $request
   * @return \Illuminate\View\View
   */
  public function index(Request $request)
  {
    // デバッグログ：リクエストパラメータ確認
    \Log::info('[DEBUG RecordsController::index] リクエストパラメータ:', $request->all());

    // 利用者リストを取得
    $clinicUsers = DB::table('clinic_users')
      ->select('id', 'last_name', 'first_name', 'last_kana', 'first_kana')
      ->orderBy('id', 'desc')
      ->get();

    // IDの最大桁数を算出（0埋め用）
    $maxClinicUserId = $clinicUsers->max('id') ?? 1;
    $clinicUserIdLength = strlen((string) $maxClinicUserId);

    // 選択された利用者ID（未指定時は最上オプション＝最大IDをデフォルト選択）
    $selectedUserId = $request->input('clinic_user_id', $clinicUsers->first()?->id ?? null);

    // 選択された施術者ID（スケジュール画面から遷移時に使用）
    $selectedTherapistId = $request->input('therapist_id');

    // 開始日時（スケジュール画面から遷移時に使用）
    $scheduleStartDate = $request->input('start_date');
    $scheduleStartTime = $request->input('start_time');

    // デバッグログ：スケジュール関連パラメータ確認
    \Log::info('[DEBUG RecordsController::index] スケジュール関連パラメータ:', [
      'selectedUserId' => $selectedUserId,
      'selectedTherapistId' => $selectedTherapistId,
      'scheduleStartDate' => $scheduleStartDate,
      'scheduleStartTime' => $scheduleStartTime,
      'from' => $request->input('from'),
    ]);

    // 利用者が選択されている場合、関連データを取得
    $insurances = null;
    $latestInsuranceId = null;
    $consentsAcupuncture = null;
    $consentsMassage = null;
    $hasRecentRecords = false;
    $records = collect();

    // 選択された年月を取得（デフォルトは開始日の年月、なければ現在の年月）
    if ($scheduleStartDate) {
      $startDateObj = new \DateTime($scheduleStartDate);
      $selectedYear = $request->input('year', $startDateObj->format('Y'));
      $selectedMonth = $request->input('month', $startDateObj->format('m'));
    } else {
      $selectedYear = $request->input('year', date('Y'));
      $selectedMonth = $request->input('month', date('m'));
    }

    // 選択月の月初日を基準に、その時点で有効な clinic_info から定休日情報を取得
    $closedDays = $this->clinicInfoService->getClosedDaysForDate(
      sprintf('%04d-%02d-01', $selectedYear, $selectedMonth)
    );

    if ($selectedUserId) {
      // 保険情報を取得（有効期限の降順）
      $insurances = DB::table('insurances')
        ->leftJoin('insurers', 'insurances.insurers_id', '=', 'insurers.id')
        ->where('insurances.clinic_user_id', $selectedUserId)
        ->select('insurances.*', 'insurers.insurer_number')
        ->orderBy('insurances.expiry_date', 'desc')
        ->get();

      // 最新の保険IDを取得（有効期限の降順で最初のレコード）
      if ($insurances && $insurances->count() > 0) {
        $latestInsuranceId = $insurances->first()->id;
      }

      // 同意書情報（はり・きゅう）を取得（同意終了日の降順）
      $consentsAcupuncture = DB::table('consents_acupuncture')
        ->where('clinic_user_id', $selectedUserId)
        ->orderBy('consenting_end_date', 'desc')
        ->first();

      // 同意書情報（あんま・マッサージ）を取得（同意終了日の降順）
      $consentsMassage = DB::table('consents_massage')
        ->where('clinic_user_id', $selectedUserId)
        ->orderBy('consenting_end_date', 'desc')
        ->first();

      // 請求区分判定（継続 or 新規）
      // 条件1：選択月の前月にデータがあるか
      $previousMonth = sprintf('%04d-%02d', $selectedYear, $selectedMonth);
      $previousMonthDate = date('Y-m-d', strtotime($previousMonth . '-01 -1 month'));
      $previousMonthStart = date('Y-m-01', strtotime($previousMonthDate));
      $previousMonthEnd = date('Y-m-t', strtotime($previousMonthDate));

      $hasRecentRecords = DB::table('records')
        ->where('clinic_user_id', $selectedUserId)
        ->whereBetween('date', [$previousMonthStart, $previousMonthEnd])
        ->exists();

      // 条件2：選択月の前月にデータがなければ、選択月日の1ヶ月前の範囲をチェック
      if (!$hasRecentRecords) {
        $selectedMonthStart = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
        $oneMonthBeforeSelected = date('Y-m-d', strtotime($selectedMonthStart . ' -1 month'));

        $hasRecentRecords = DB::table('records')
          ->where('clinic_user_id', $selectedUserId)
          ->where('date', '>=', $oneMonthBeforeSelected)
          ->where('date', '<', $selectedMonthStart)
          ->exists();
      }

      // 選択された年月の実績データを取得
      $startDate = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
      $endDate = date('Y-m-t', strtotime($startDate));

      $records = DB::table('records')
        ->leftJoin('therapy_contents', 'records.therapy_content_id', '=', 'therapy_contents.id')
        ->leftJoin('self_fees', 'records.self_fee_id', '=', 'self_fees.id')
        ->leftJoin('therapists', 'records.therapist_id', '=', 'therapists.id')
        ->where('records.clinic_user_id', $selectedUserId)
        ->whereBetween('records.date', [$startDate, $endDate])
        ->select(
          'records.*',
          'therapy_contents.therapy_content',
          'self_fees.self_fee_name',
          DB::raw("CONCAT(therapists.last_name, '\u{2000}', therapists.first_name) as therapist_name")
        )
        ->orderBy('records.created_at', 'desc')
        ->get()
        ->groupBy(function($record) {
          // 施術内容、施術者、時刻が同じレコードをグループ化（自費施術も考慮）
          return sprintf(
            '%s_%s_%s_%s_%s',
            $record->therapy_content_id,
            $record->self_fee_id,
            $record->therapist_id,
            $record->start_time,
            $record->end_time
          );
        })
        ->map(function($group) {
          // グループの最初のレコードを代表として使用
          $representative = $group->first();
          // 施術日の配列を追加
          $representative->dates = $group->pluck('date')->toArray();
          return $representative;
        })
        ->values();
    }

    // 施術者リストを取得
    $therapists = DB::table('therapists')
      ->select('id', 'last_name', 'first_name', 'last_name_kana', 'first_name_kana')
      ->orderBy('last_name_kana')
      ->get();

    // 施術内容リストを取得
    $therapyContents = DB::table('therapy_contents')
      ->select('id', 'therapy_type', 'therapy_content')
      ->orderBy('id')
      ->get();

    // 自費施術リストを取得（created_atが古い順）
    $selfFees = DB::table('self_fees')
      ->select('id', 'self_fee_name')
      ->orderBy('created_at', 'asc')
      ->get();

    // 請求区分リストを取得
    $billCategories = DB::table('bill_categories')
      ->select('id', 'bill_category')
      ->get();

    $clinicInfo = DB::table('clinic_info')->first();

    return view('records.records_index', [
      'closedDays' => $closedDays,
      'businessHoursStart' => $clinicInfo->business_hours_start ?? null,
      'businessHoursEnd' => $clinicInfo->business_hours_end ?? null,
      'clinicUsers' => $clinicUsers,
      'clinicUserIdLength' => $clinicUserIdLength,
      'selectedUserId' => $selectedUserId,
      'selectedTherapistId' => $selectedTherapistId,
      'startDate' => $scheduleStartDate,
      'startTime' => $scheduleStartTime,
      'insurances' => $insurances,
      'latestInsuranceId' => $latestInsuranceId,
      'consentsAcupuncture' => $consentsAcupuncture,
      'consentsMassage' => $consentsMassage,
      'hasRecentRecords' => $hasRecentRecords,
      'therapists' => $therapists,
      'therapyContents' => $therapyContents,
      'selfFees' => $selfFees,
      'billCategories' => $billCategories,
      'records' => $records,
      'selectedYear' => $selectedYear,
      'selectedMonth' => $selectedMonth,
      'page_header_title' => '実績データ',
    ]);
  }

  /**
   * 実績データを登録
   *
   * @param RecordRequest $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function store(RecordRequest $request)
  {
    // デバッグログ：リクエストデータ確認
    \Log::info('[DEBUG RecordsController::store] リクエストデータ:', $request->all());

    $validated = $request->validated();

    // デバッグログ：バリデーション済みデータ確認
    \Log::info('[DEBUG RecordsController::store] バリデーション済みデータ:', $validated);

    try {
      // 往療距離の配列を取得
      $housecallDistances = $request->input('housecall_distance', []);

      // デバッグログ：往療距離配列確認
      \Log::info('[DEBUG RecordsController::store] 往療距離配列:', $housecallDistances);
      \Log::info('[DEBUG RecordsController::store] 往療距離配列の要素数:', ['count' => count($housecallDistances)]);

      // 複製オプションを取得
      $duplicateOptions = [
        'duplicate_massage' => $request->input('duplicate_massage'),
        'duplicate_warm_compress' => $request->input('duplicate_warm_compress'),
        'duplicate_warm_electric' => $request->input('duplicate_warm_electric'),
        'duplicate_manual_correction' => $request->input('duplicate_manual_correction'),
      ];

      // RecordServiceを使用してレコードを作成
      $this->recordService->createRecordsForDates($validated, $housecallDistances, $duplicateOptions);

      // デバッグログ：コミット完了
      \Log::info('[DEBUG RecordsController::store] トランザクションコミット完了');
      \Log::info('[DEBUG RecordsController::store] fromパラメータ: ' . $request->input('from'));

      // fromパラメータがscheduleの場合はスケジュール画面に、それ以外は実績データ画面にリダイレクト
      if ($request->input('from') === 'schedule') {
        \Log::info('[DEBUG RecordsController::store] スケジュール画面へリダイレクト');
        return redirect()
          ->route('schedules.index')
          ->with('success', '実績データを登録しました。');
      } else {
        \Log::info('[DEBUG RecordsController::store] 実績データ画面へリダイレクト');
        return redirect()
          ->route('records.index', ['clinic_user_id' => $validated['clinic_user_id']])
          ->with('success', '実績データを登録しました。');
      }

    } catch (\Exception $e) {
      return redirect()
        ->back()
        ->withInput()
        ->withErrors(['error' => 'データの登録に失敗しました：' . $e->getMessage()]);
    }
  }

  /**
   * 実績データ編集画面を表示
   *
   * @param Request $request
   * @param int $id
   * @return \Illuminate\View\View
   */
  public function edit(Request $request, $id)
  {
    // 同一グループの実績データを取得（最初のレコードを代表として取得）
    $record = DB::table('records')
      ->where('id', $id)
      ->first();

    if (!$record) {
      return redirect()
        ->route('records.index')
        ->withErrors(['error' => '実績データが見つかりません。']);
    }

    // 同一グループのレコードを全て取得（施術内容、施術者、時刻が同じもの）
    $groupRecords = DB::table('records')
      ->where('clinic_user_id', $record->clinic_user_id)
      ->where('therapy_content_id', $record->therapy_content_id)
      ->where('therapist_id', $record->therapist_id)
      ->where('start_time', $record->start_time)
      ->where('end_time', $record->end_time)
      ->orderBy('date')
      ->get();

    // 施術日の配列を作成
    $originalDates = $groupRecords->pluck('date')->toArray();

    // 往療距離の配列を作成
    $originalDistances = [];
    foreach ($groupRecords as $rec) {
      $originalDistances[$rec->date] = $rec->housecall_distance;
    }

    // 定休日情報を取得（レコード日付時点で有効な clinic_info 基準）
    $closedDays = $this->clinicInfoService->getClosedDaysForDate($record->date);

    // 保険情報を取得
    $insurances = DB::table('insurances')
      ->leftJoin('insurers', 'insurances.insurers_id', '=', 'insurers.id')
      ->where('insurances.clinic_user_id', $record->clinic_user_id)
      ->select('insurances.*', 'insurers.insurer_number')
      ->orderBy('insurances.expiry_date', 'desc')
      ->get();

    // 同意書情報（はり・きゅう）を取得
    $consentsAcupuncture = DB::table('consents_acupuncture')
      ->where('clinic_user_id', $record->clinic_user_id)
      ->orderBy('consenting_end_date', 'desc')
      ->first();

    // 同意書情報（あんま・マッサージ）を取得
    $consentsMassage = DB::table('consents_massage')
      ->where('clinic_user_id', $record->clinic_user_id)
      ->orderBy('consenting_end_date', 'desc')
      ->first();

    // 請求区分判定（継続 or 新規）
    // 対象レコードの日付を基準にする
    $recordDate = $record->date;

    // 条件1：レコード月の前月にデータがあるか
    $previousMonthDate = date('Y-m-d', strtotime($recordDate . ' -1 month'));
    $previousMonthStart = date('Y-m-01', strtotime($previousMonthDate));
    $previousMonthEnd = date('Y-m-t', strtotime($previousMonthDate));

    $hasRecentRecords = DB::table('records')
      ->where('clinic_user_id', $record->clinic_user_id)
      ->whereBetween('date', [$previousMonthStart, $previousMonthEnd])
      ->exists();

    // 条件2：前月にデータがなければ、レコード月の1ヶ月前の範囲をチェック
    if (!$hasRecentRecords) {
      $recordMonthStart = date('Y-m-01', strtotime($recordDate));
      $oneMonthBeforeRecord = date('Y-m-d', strtotime($recordMonthStart . ' -1 month'));

      $hasRecentRecords = DB::table('records')
        ->where('clinic_user_id', $record->clinic_user_id)
        ->where('date', '>=', $oneMonthBeforeRecord)
        ->where('date', '<', $recordMonthStart)
        ->exists();
    }

    // 施術者リストを取得
    $therapists = DB::table('therapists')
      ->select('id', 'last_name', 'first_name', 'last_name_kana', 'first_name_kana')
      ->orderBy('last_name_kana')
      ->get();

    // 施術内容リストを取得
    $therapyContents = DB::table('therapy_contents')
      ->select('id', 'therapy_type', 'therapy_content')
      ->orderBy('id')
      ->get();

    // 身体部位情報を取得（あんま・マッサージの場合）
    $selectedBodyparts = [];
    if ($record->therapy_type == 2) {
      $selectedBodyparts = DB::table('bodyparts-records')
        ->where('records_id', $id)
        ->pluck('therapy_type_bodyparts_id')
        ->map(function($item) {
          return (string)$item;
        })
        ->toArray();
    }

    $clinicInfo = DB::table('clinic_info')->first();

    return view('records.records_edit', [
      'record' => $record,
      'originalDates' => $originalDates,
      'originalDistances' => $originalDistances,
      'closedDays' => $closedDays,
      'businessHoursStart' => $clinicInfo->business_hours_start ?? null,
      'businessHoursEnd' => $clinicInfo->business_hours_end ?? null,
      'insurances' => $insurances,
      'consentsAcupuncture' => $consentsAcupuncture,
      'consentsMassage' => $consentsMassage,
      'hasRecentRecords' => $hasRecentRecords,
      'therapists' => $therapists,
      'therapyContents' => $therapyContents,
      'selectedBodyparts' => $selectedBodyparts,
      'from' => $request->input('from'),
      'page_header_title' => '実績データ編集',
    ]);
  }

  /**
   * 実績データを更新
   *
   * @param RecordRequest $request
   * @param int $id
   * @return \Illuminate\Http\RedirectResponse
   */
  public function update(RecordRequest $request, $id)
  {
    $validated = $request->validated();

    try {
      // 往療距離の配列を取得
      $housecallDistances = $request->input('housecall_distance', []);

      // 複製オプションを取得
      $duplicateOptions = [
        'duplicate_massage' => $request->input('duplicate_massage'),
        'duplicate_warm_compress' => $request->input('duplicate_warm_compress'),
        'duplicate_warm_electric' => $request->input('duplicate_warm_electric'),
        'duplicate_manual_correction' => $request->input('duplicate_manual_correction'),
      ];

      // RecordServiceを使用してレコードを更新
      $this->recordService->updateRecordsForDates($id, $validated, $housecallDistances, $duplicateOptions);

      // fromパラメータがscheduleの場合はスケジュール画面に、それ以外は実績データ画面にリダイレクト
      if ($request->input('from') === 'schedule') {
        return redirect()
          ->route('schedules.index')
          ->with('success', '実績データを更新しました。');
      } else {
        return redirect()
          ->route('records.index', ['clinic_user_id' => $validated['clinic_user_id']])
          ->with('success', '実績データを更新しました。');
      }

    } catch (\Exception $e) {
      return redirect()
        ->back()
        ->withInput()
        ->withErrors(['error' => 'データの更新に失敗しました：' . $e->getMessage()]);
    }
  }

  /**
   * 当月へ複製画面を表示
   *
   * @param int $id
   * @return \Illuminate\View\View
   */
  public function duplicateCurrentMonth($id)
  {
    return $this->duplicateForm($id, 'current');
  }

  /**
   * 翌月へ複製画面を表示
   *
   * @param int $id
   * @return \Illuminate\View\View
   */
  public function duplicateNextMonth($id)
  {
    return $this->duplicateForm($id, 'next');
  }

  /**
   * 複製フォームを表示（共通処理）
   *
   * @param int $id
   * @param string $type 'current' or 'next'
   * @return \Illuminate\View\View
   */
  private function duplicateForm($id, $type)
  {
    // 同一グループの実績データを取得
    $record = DB::table('records')
      ->where('id', $id)
      ->first();

    if (!$record) {
      return redirect()
        ->route('records.index')
        ->withErrors(['error' => '実績データが見つかりません。']);
    }

    // 同一グループのレコードを全て取得
    $groupRecords = DB::table('records')
      ->where('clinic_user_id', $record->clinic_user_id)
      ->where('therapy_content_id', $record->therapy_content_id)
      ->where('therapist_id', $record->therapist_id)
      ->where('start_time', $record->start_time)
      ->where('end_time', $record->end_time)
      ->orderBy('date')
      ->get();

    // 施術日の配列を作成
    $originalDates = $groupRecords->pluck('date')->toArray();

    // 複製先の年月を計算
    if ($type === 'next') {
      // 翌月の場合、日付を翌月に変更
      $duplicatedDates = array_map(function($date) {
        $dateObj = new \DateTime($date);
        $dateObj->modify('+1 month');
        return $dateObj->format('Y-m-d');
      }, $originalDates);
    } else {
      // 当月の場合、日付はそのまま
      $duplicatedDates = $originalDates;
    }

    // 往療距離の配列を作成
    $originalDistances = [];
    foreach ($groupRecords as $rec) {
      $originalDistances[$rec->date] = $rec->housecall_distance;
    }

    // 複製先の日付に対応する往療距離を作成
    $duplicatedDistances = [];
    if ($type === 'next') {
      foreach ($originalDistances as $date => $distance) {
        $dateObj = new \DateTime($date);
        $dateObj->modify('+1 month');
        $newDate = $dateObj->format('Y-m-d');
        $duplicatedDistances[$newDate] = $distance;
      }
    } else {
      $duplicatedDistances = $originalDistances;
    }

    // 定休日情報を取得（レコード日付時点で有効な clinic_info 基準）
    $closedDays = $this->clinicInfoService->getClosedDaysForDate($record->date);

    // 保険情報を取得
    $insurances = DB::table('insurances')
      ->leftJoin('insurers', 'insurances.insurers_id', '=', 'insurers.id')
      ->where('insurances.clinic_user_id', $record->clinic_user_id)
      ->select('insurances.*', 'insurers.insurer_number')
      ->orderBy('insurances.expiry_date', 'desc')
      ->get();

    // 同意書情報（はり・きゅう）を取得
    $consentsAcupuncture = DB::table('consents_acupuncture')
      ->where('clinic_user_id', $record->clinic_user_id)
      ->orderBy('consenting_end_date', 'desc')
      ->first();

    // 同意書情報（あんま・マッサージ）を取得
    $consentsMassage = DB::table('consents_massage')
      ->where('clinic_user_id', $record->clinic_user_id)
      ->orderBy('consenting_end_date', 'desc')
      ->first();

    // 請求区分判定（継続 or 新規）
    // 対象レコードの日付を基準にする
    $recordDate = $record->date;

    // 条件1：レコード月の前月にデータがあるか
    $previousMonthDate = date('Y-m-d', strtotime($recordDate . ' -1 month'));
    $previousMonthStart = date('Y-m-01', strtotime($previousMonthDate));
    $previousMonthEnd = date('Y-m-t', strtotime($previousMonthDate));

    $hasRecentRecords = DB::table('records')
      ->where('clinic_user_id', $record->clinic_user_id)
      ->whereBetween('date', [$previousMonthStart, $previousMonthEnd])
      ->exists();

    // 条件2：前月にデータがなければ、レコード月の1ヶ月前の範囲をチェック
    if (!$hasRecentRecords) {
      $recordMonthStart = date('Y-m-01', strtotime($recordDate));
      $oneMonthBeforeRecord = date('Y-m-d', strtotime($recordMonthStart . ' -1 month'));

      $hasRecentRecords = DB::table('records')
        ->where('clinic_user_id', $record->clinic_user_id)
        ->where('date', '>=', $oneMonthBeforeRecord)
        ->where('date', '<', $recordMonthStart)
        ->exists();
    }

    // 施術者リストを取得
    $therapists = DB::table('therapists')
      ->select('id', 'last_name', 'first_name', 'last_name_kana', 'first_name_kana')
      ->orderBy('last_name_kana')
      ->get();

    // 施術内容リストを取得
    $therapyContents = DB::table('therapy_contents')
      ->select('id', 'therapy_type', 'therapy_content')
      ->orderBy('id')
      ->get();

    // 身体部位情報を取得（あんま・マッサージの場合）
    $selectedBodyparts = [];
    if ($record->therapy_type == 2) {
      $selectedBodyparts = DB::table('bodyparts-records')
        ->where('records_id', $id)
        ->pluck('therapy_type_bodyparts_id')
        ->map(function($item) {
          return (string)$item;
        })
        ->toArray();
    }

    $clinicInfo = DB::table('clinic_info')->first();

    return view('records.records_duplicate', [
      'record' => $record,
      'originalDates' => $duplicatedDates,
      'originalDistances' => $duplicatedDistances,
      'closedDays' => $closedDays,
      'businessHoursStart' => $clinicInfo->business_hours_start ?? null,
      'businessHoursEnd' => $clinicInfo->business_hours_end ?? null,
      'insurances' => $insurances,
      'consentsAcupuncture' => $consentsAcupuncture,
      'consentsMassage' => $consentsMassage,
      'hasRecentRecords' => $hasRecentRecords,
      'therapists' => $therapists,
      'therapyContents' => $therapyContents,
      'selectedBodyparts' => $selectedBodyparts,
      'duplicateType' => $type,
      'page_header_title' => $type === 'next' ? '実績データ複製（翌月）' : '実績データ複製（当月）',
    ]);
  }

  /**
   * 複製データを保存
   *
   * @param RecordRequest $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function duplicateStore(RecordRequest $request)
  {
    $validated = $request->validated();

    try {
      // 往療距離の配列を取得
      $housecallDistances = $request->input('housecall_distance', []);

      // 複製オプションを取得
      $duplicateOptions = [
        'duplicate_massage' => $request->input('duplicate_massage'),
        'duplicate_warm_compress' => $request->input('duplicate_warm_compress'),
        'duplicate_warm_electric' => $request->input('duplicate_warm_electric'),
        'duplicate_manual_correction' => $request->input('duplicate_manual_correction'),
      ];

      // RecordServiceを使用してレコードを作成
      $this->recordService->createRecordsForDates($validated, $housecallDistances, $duplicateOptions);

      return redirect()
        ->route('records.index', ['clinic_user_id' => $validated['clinic_user_id']])
        ->with('success', '実績データを複製しました。');

    } catch (\Exception $e) {
      return redirect()
        ->back()
        ->withInput()
        ->withErrors(['error' => 'データの複製に失敗しました：' . $e->getMessage()]);
    }
  }

  /**
   * 実績データを削除
   *
   * @param int $id
   * @return \Illuminate\Http\RedirectResponse
   */
  public function destroy($id)
  {
    try {
      DB::beginTransaction();

      // 削除対象のレコードを取得
      $record = DB::table('records')->where('id', $id)->first();

      if (!$record) {
        return redirect()
          ->route('records.index')
          ->withErrors(['error' => '実績データが見つかりません。']);
      }

      // 同一グループの全レコードを取得（施術内容、施術者、時刻が同じもの）
      $groupRecords = DB::table('records')
        ->where('clinic_user_id', $record->clinic_user_id)
        ->where('therapy_content_id', $record->therapy_content_id)
        ->where('therapist_id', $record->therapist_id)
        ->where('start_time', $record->start_time)
        ->where('end_time', $record->end_time)
        ->get();

      // 削除対象のレコードIDを取得
      $recordIds = $groupRecords->pluck('id')->toArray();

      // bodyparts-recordsテーブルから関連データを削除
      DB::table('bodyparts-records')
        ->whereIn('records_id', $recordIds)
        ->delete();

      // recordsテーブルから削除
      Record::whereIn('id', $recordIds)->delete();

      DB::commit();

      return redirect()
        ->route('records.index', ['clinic_user_id' => $record->clinic_user_id])
        ->with('success', '実績データを削除しました。');

    } catch (\Exception $e) {
      DB::rollBack();
      return redirect()
        ->back()
        ->withErrors(['error' => 'データの削除に失敗しました：' . $e->getMessage()]);
    }
  }

  /**
   * 当月の全実績データを翌月へ一括複製
   *
   * @param Request $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function bulkDuplicateToNextMonth(Request $request)
  {
    $clinicUserId = $request->input('clinic_user_id');
    $year = $request->input('year');
    $month = $request->input('month');

    if (!$clinicUserId || !$year || !$month) {
      return redirect()
        ->back()
        ->withErrors(['error' => '必要なパラメータが不足しています。']);
    }

    try {
      DB::beginTransaction();

      // 当月の実績データを取得
      $startDate = sprintf('%04d-%02d-01', $year, $month);
      $endDate = date('Y-m-t', strtotime($startDate));

      $records = DB::table('records')
        ->where('clinic_user_id', $clinicUserId)
        ->whereBetween('date', [$startDate, $endDate])
        ->get();

      if ($records->count() === 0) {
        return redirect()
          ->route('records.index', ['clinic_user_id' => $clinicUserId])
          ->withErrors(['error' => '当月の実績データがありません。']);
      }

      $duplicatedCount = 0;

      // 各レコードを翌月に複製
      foreach ($records as $record) {
        // 翌月の日付を計算
        $dateObj = new \DateTime($record->date);
        $dateObj->modify('+1 month');
        $newDate = $dateObj->format('Y-m-d');

        // 新しいレコードを作成
        $newRecordId = DB::table('records')->insertGetId([
          'clinic_user_id' => $record->clinic_user_id,
          'date' => $newDate,
          'start_time' => $record->start_time,
          'end_time' => $record->end_time,
          'therapy_type' => $record->therapy_type,
          'therapy_category' => $record->therapy_category,
          'insurance_category' => $record->insurance_category,
          'housecall_distance' => $record->housecall_distance,
          'therapy_days' => $record->therapy_days,
          'consent_expiry' => $record->consent_expiry,
          'therapy_content_id' => $record->therapy_content_id,
          'bill_category_id' => $record->bill_category_id,
          'therapist_id' => $record->therapist_id,
          'abstract' => $record->abstract,
          'created_at' => now(),
          'updated_at' => now(),
        ]);

        // 身体部位情報を複製（あんま・マッサージの場合）
        if ($record->therapy_type == 2) {
          $bodyparts = DB::table('bodyparts-records')
            ->where('records_id', $record->id)
            ->get();

          foreach ($bodyparts as $bodypart) {
            DB::table('bodyparts-records')->insert([
              'records_id' => $newRecordId,
              'therapy_type_bodyparts_id' => $bodypart->therapy_type_bodyparts_id,
              'created_at' => now(),
              'updated_at' => now(),
            ]);
          }
        }

        $duplicatedCount++;
      }

      DB::commit();

      return redirect()
        ->route('records.index', ['clinic_user_id' => $clinicUserId])
        ->with('success', "{$duplicatedCount}件の実績データを翌月へ複製しました。");

    } catch (\Exception $e) {
      DB::rollBack();
      return redirect()
        ->back()
        ->withErrors(['error' => '一括複製に失敗しました：' . $e->getMessage()]);
    }
  }
}

