<?php
//-- app/Http/Controllers/ScheduleController.php --//

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;

/**
 * スケジュール管理コントローラー
 *
 * 施術者のスケジュール表示を担当する。
 * - スケジュール一覧表示（週表示・月表示）
 * - 施術者による絞り込み
 * - recordsテーブルからの施術データ取得
 */
class ScheduleController extends Controller
{
  /**
   * スケジュール画面を表示
   *
   * @param Request $request
   * @return \Illuminate\Http\Response
   */
  public function index(Request $request)
  {
    // 施術者リストを取得（ID降順）
    $therapists = DB::table('therapists')
      ->select('id', 'last_name', 'first_name', 'last_name_kana', 'first_name_kana')
      ->orderBy('id', 'desc')
      ->get();

    // 施術者IDの最大桁数を算出（0埋め用）
    $maxTherapistId = $therapists->max('id') ?? 1;
    $therapistIdLength = strlen((string) $maxTherapistId);

    // 利用者リストを取得（ID降順）
    $clinicUsers = DB::table('clinic_users')
      ->select('id', 'last_name', 'first_name', 'last_kana', 'first_kana')
      ->orderBy('id', 'desc')
      ->get();

    // 利用者IDの最大桁数を算出（0埋め用）
    $maxClinicUserId = $clinicUsers->max('id') ?? 1;
    $clinicUserIdLength = strlen((string) $maxClinicUserId);

    // 選択された施術者ID（優先順位：リクエスト > Cookie > デフォルト）
    $selectedTherapistId = $request->input('therapist_id');

    if (!$selectedTherapistId) {
      // リクエストで未指定の場合はCookieから取得
      $selectedTherapistId = $request->cookie('schedule_therapist_id');

      // Cookieにもない場合はIDが最大の施術者を選択
      if (!$selectedTherapistId && $therapists->isNotEmpty()) {
        $selectedTherapistId = $therapists->first()->id;
      }
    }

    // 営業時間と定休日を取得
    $clinicInfo = DB::table('clinic_info')->orderByDesc('id')->first();
    $businessHoursStart = $clinicInfo->business_hours_start ?? '09:00:00';
    $businessHoursEnd = $clinicInfo->business_hours_end ?? '18:00:00';

    // 定休日情報を取得
    $closedDays = [
      'monday' => $clinicInfo->closed_day_monday ?? 0,
      'tuesday' => $clinicInfo->closed_day_tuesday ?? 0,
      'wednesday' => $clinicInfo->closed_day_wednesday ?? 0,
      'thursday' => $clinicInfo->closed_day_thursday ?? 0,
      'friday' => $clinicInfo->closed_day_friday ?? 0,
      'saturday' => $clinicInfo->closed_day_saturday ?? 0,
      'sunday' => $clinicInfo->closed_day_sunday ?? 0,
    ];

    // 時刻行を生成（営業時間に基づく）
    $timeSlots = $this->generateTimeSlots($businessHoursStart, $businessHoursEnd);

    // 実績データの登録可能範囲を取得（records.jsの設定と同期）
    $recordsStartYear = 2020; // records.jsのstartYearと一致
    $recordsStartMonth = 1;   // records.jsのstartMonth + 1と一致
    $futureMonths = 2;        // records.jsの現在+2ヶ月と一致

    // ビューを生成してCookieを付与（365日間保持）
    return response()
      ->view('schedules.schedules_index', [
        'therapists' => $therapists,
        'therapistIdLength' => $therapistIdLength,
        'clinicUsers' => $clinicUsers,
        'clinicUserIdLength' => $clinicUserIdLength,
        'selectedTherapistId' => $selectedTherapistId,
        'businessHoursStart' => $businessHoursStart,
        'businessHoursEnd' => $businessHoursEnd,
        'timeSlots' => $timeSlots,
        'closedDays' => $closedDays,
        'recordsStartYear' => $recordsStartYear,
        'recordsStartMonth' => $recordsStartMonth,
        'futureMonths' => $futureMonths,
        'page_header_title' => 'スケジュール',
      ])
      ->cookie('schedule_therapist_id', $selectedTherapistId, 60 * 24 * 365);
  }

  /**
   * 営業時間に基づいて時刻スロットを生成
   *
   * @param string $startTime 開始時刻（HH:MM:SS形式）
   * @param string $endTime 終了時刻（HH:MM:SS形式）
   * @return array 時刻スロットの配列
   */
  private function generateTimeSlots($startTime, $endTime)
  {
    $slots = [];
    $start = new DateTime($startTime);
    $end = new DateTime($endTime);

    // 開始時刻から終了時刻まで1時間刻みで生成
    $current = clone $start;
    while ($current <= $end) {
      $slots[] = $current->format('H:i');
      $current->modify('+1 hour');
    }

    return $slots;
  }

  /**
   * スケジュールデータをJSON形式で取得
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function getData(Request $request)
  {
    $therapistId = $request->input('therapist_id');
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    $query = DB::table('records')
      ->join('clinic_users', 'records.clinic_user_id', '=', 'clinic_users.id')
      ->select(
        'records.id',
        'records.date',
        'records.start_time',
        'records.end_time',
        'records.therapy_type',
        DB::raw('CONCAT(clinic_users.last_name, " ", clinic_users.first_name) as user_name')
      )
      ->whereBetween('records.date', [$startDate, $endDate]);

    // 'all'以外の施術者が選択されている場合は施術者でフィルタリング
    if ($therapistId && $therapistId !== 'all') {
      $query->where('records.therapist_id', $therapistId);
    }

    $records = $query->get();

    // 表示期間に関わるclinic_infoの定休日変化点を取得
    // 「$startDate以前で最新」＋「$startDate〜$endDate間に作成されたもの」を結合
    $clinicInfoInRange = DB::table('clinic_info')
      ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
      ->orderBy('created_at')
      ->get(['created_at', 'closed_day_sunday', 'closed_day_monday', 'closed_day_tuesday',
             'closed_day_wednesday', 'closed_day_thursday', 'closed_day_friday', 'closed_day_saturday']);

    $baseClinicInfo = DB::table('clinic_info')
      ->where('created_at', '<', $startDate . ' 00:00:00')
      ->orderByDesc('created_at')
      ->first(['created_at', 'closed_day_sunday', 'closed_day_monday', 'closed_day_tuesday',
               'closed_day_wednesday', 'closed_day_thursday', 'closed_day_friday', 'closed_day_saturday']);

    // フォールバック：startDate以前にレコードがない場合は最古のレコード
    if (!$baseClinicInfo) {
      $baseClinicInfo = DB::table('clinic_info')
        ->orderBy('created_at')
        ->first(['created_at', 'closed_day_sunday', 'closed_day_monday', 'closed_day_tuesday',
                 'closed_day_wednesday', 'closed_day_thursday', 'closed_day_friday', 'closed_day_saturday']);
    }

    // 変化点の配列を構築（from日付 => 定休日設定）
    $closedDayRanges = [];

    if ($baseClinicInfo) {
      $closedDayRanges[] = [
        'from' => $startDate,
        'closed_days' => $this->extractClosedDays($baseClinicInfo),
      ];
    }

    foreach ($clinicInfoInRange as $info) {
      $fromDate = substr($info->created_at, 0, 10);
      $closedDayRanges[] = [
        'from' => $fromDate,
        'closed_days' => $this->extractClosedDays($info),
      ];
    }

    return response()->json([
      'records' => $records,
      'closed_day_ranges' => $closedDayRanges,
    ]);
  }

  /**
   * clinic_infoオブジェクトから定休日配列を生成
   */
  private function extractClosedDays(object $info): array
  {
    return [
      'sunday'    => (int)($info->closed_day_sunday ?? 0),
      'monday'    => (int)($info->closed_day_monday ?? 0),
      'tuesday'   => (int)($info->closed_day_tuesday ?? 0),
      'wednesday' => (int)($info->closed_day_wednesday ?? 0),
      'thursday'  => (int)($info->closed_day_thursday ?? 0),
      'friday'    => (int)($info->closed_day_friday ?? 0),
      'saturday'  => (int)($info->closed_day_saturday ?? 0),
    ];
  }
}
