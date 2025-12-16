<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * はり・きゅう療養費支給申請書PDF生成サービス
 */
class AcupunctureBenefitPdfService
{
  /**
   * 座標設定
   */
  protected $coordinates;

  /**
   * コンストラクタ
   */
  public function __construct()
  {
    $this->loadCoordinates();
  }

  /**
   * 座標設定を読み込む
   */
  protected function loadCoordinates(): void
  {
    $configPath = storage_path('app/config/acupuncture_benefit_coordinates.json');

    if (file_exists($configPath)) {
      $json = file_get_contents($configPath);
      $this->coordinates = json_decode($json, true);
    } else {
      // デフォルト座標（JSONファイルがない場合のフォールバック）
      $this->coordinates = $this->getDefaultCoordinates();
    }
  }

  /**
   * デフォルト座標を取得
   */
  protected function getDefaultCoordinates(): array
  {
    // JSONファイルと同じ構造でデフォルト値を返す
    $configPath = storage_path('app/config/acupuncture_benefit_coordinates.json');
    $json = file_get_contents($configPath);
    return json_decode($json, true);
  }

  /**
   * 座標値を取得するヘルパーメソッド
   */
  protected function coord(string $key, string $property = 'x')
  {
    return $this->coordinates[$key][$property] ?? 0;
  }
  /**
   * PDF生成
   *
   * @param array $clinicUserIds 利用者ID配列
   * @param string $serviceYearMonth サービス提供年月 (YYYY-MM)
   * @param string $submissionDate 提出年月日 (YYYY-MM-DD)
   * @return string PDFバイナリデータ
   */
  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);

    foreach ($clinicUserIds as $clinicUserId) {
      $data = $this->fetchData($clinicUserId, $serviceYearMonth);

      if ($data) {
        $this->addPage($pdf, $data, $submissionDate);
      }
    }

    return $pdf->Output('', 'S'); // バイナリとして返却
  }

  /**
   * データ取得
   *
   * @param int $clinicUserId
   * @param string $serviceYearMonth
   * @return array|null
   */
  protected function fetchData(int $clinicUserId, string $serviceYearMonth): ?array
  {
    // 利用者情報取得
    $clinicUser = DB::table('clinic_users')->where('id', $clinicUserId)->first();

    if (!$clinicUser) {
      \Log::error('利用者情報が見つかりません', ['clinic_user_id' => $clinicUserId]);
      return null;
    }

    // 保険情報取得（保険者情報とJOIN）
    $insurance = DB::table('insurances')
      ->leftJoin('insurers', 'insurances.insurers_id', '=', 'insurers.id')
      ->where('insurances.clinic_user_id', $clinicUserId)
      ->orderBy('insurances.created_at', 'desc')
      ->select('insurances.*', 'insurers.insurer_number', 'insurers.insurer_name')
      ->first();

    if (!$insurance) {
      \Log::warning('保険情報が見つかりません', ['clinic_user_id' => $clinicUserId]);
    }

    // はり・きゅう同意書情報取得
    $consent = DB::table('consents_acupuncture')
      ->where('clinic_user_id', $clinicUserId)
      ->orderBy('consenting_date', 'desc')
      ->first();

    if (!$consent) {
      \Log::warning('はり・きゅう同意書情報が見つかりません', ['clinic_user_id' => $clinicUserId]);
    }

    // 施術実績取得（対象年月）
    $records = DB::table('records')
      ->where('clinic_user_id', $clinicUserId)
      ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$serviceYearMonth])
      ->orderBy('date')
      ->get();

    if ($records->isEmpty()) {
      \Log::warning('施術実績が見つかりません', [
        'clinic_user_id' => $clinicUserId,
        'service_year_month' => $serviceYearMonth,
      ]);
    }

    // 施術所情報取得
    $clinicInfo = DB::table('clinic_info')->first();

    if (!$clinicInfo) {
      \Log::error('施術所情報が見つかりません');
    }

    return [
      'clinic_user' => $clinicUser,
      'insurance' => $insurance,
      'consent' => $consent,
      'records' => $records,
      'clinic_info' => $clinicInfo,
      'service_year_month' => $serviceYearMonth,
    ];
  }

  /**
   * PDFページ追加
   *
   * @param Fpdi $pdf
   * @param array $data
   * @param string $submissionDate
   * @return void
   */
  protected function addPage(Fpdi $pdf, array $data, string $submissionDate): void
  {
    $pdf->AddPage();

    // テンプレートPDF読み込み
    $templatePath = storage_path('app/templates/acupuncture_benefit_form.pdf');

    if (file_exists($templatePath)) {
      $pageCount = $pdf->setSourceFile($templatePath);
      $tplId = $pdf->importPage(1);
      $pdf->useTemplate($tplId, 0, 0, null, null, true);
    }

    // デバッグ用グリッド表示（座標調整時のみ有効化）
    // $this->drawDebugGrid($pdf);

    // フォント設定（日本語フォント: kozminproregular）
    $pdf->SetFont('kozminproregular', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // データ埋め込み（座標は後で調整が必要）
    $this->fillFormFields($pdf, $data, $submissionDate);
  }

  /**
   * フォームフィールド埋め込み
   *
   * @param Fpdi $pdf
   * @param array $data
   * @param string $submissionDate
   * @return void
   */
  protected function fillFormFields(Fpdi $pdf, array $data, string $submissionDate): void
  {
    $clinicUser = $data['clinic_user'];
    $insurance = $data['insurance'];
    $consent = $data['consent'];
    $records = $data['records'];
    $clinicInfo = $data['clinic_info'];

    // サービス提供年月を分解
    [$year, $month] = explode('-', $data['service_year_month']);
    $japaneseYear = $this->convertToJapaneseYear($year, $month);

    // === 上部：年月 ===
    // タイトル行「療養費支給申請書（　年　月分）」
    // 年：上段に元号、下段に年数
    $pdf->SetFontSize($this->coord('title_year_era', 'fontSize'));
    $this->drawTextByKey($pdf, 'title_year_era', (string)$japaneseYear['era']);
    $pdf->SetFontSize($this->coord('title_year_number', 'fontSize'));
    $this->drawTextByKey($pdf, 'title_year_number', (string)$japaneseYear['year']);

    // 月
    $pdf->SetFontSize($this->coord('title_month', 'fontSize'));
    $this->drawTextByKey($pdf, 'title_month', (string)(int)$month);

    // === 機関コード（医療機関番号） ===
    if ($clinicInfo && isset($clinicInfo->medical_institution_number)) {
      $pdf->SetFontSize($this->coord('institution_code', 'fontSize'));
      $this->drawTextByKey($pdf, 'institution_code', (string)($clinicInfo->medical_institution_number ?? ''));
      $pdf->SetFontSize(10);
    } else {
      \Log::warning('医療機関番号が設定されていません', ['clinic_info' => $clinicInfo]);
    }

    // === 受給者番号（区市町村番号と種類の下） ===
    if ($insurance && isset($insurance->recipient_code) && $insurance->recipient_code) {
      $pdf->SetFontSize($this->coord('recipient_number', 'fontSize'));
      $letterSpacing = $this->coordinates['recipient_number']['letterSpacing'] ?? 0;
      $this->fillBoxes($pdf, $this->coord('recipient_number', 'x'), $this->coord('recipient_number', 'y'), $insurance->recipient_code, 6, 5.6, (float)$letterSpacing);
      $pdf->SetFontSize(10);
    } else {
      \Log::warning('受給者番号が設定されていません', ['insurance' => $insurance]);
    }

    // === 保険者番号 ===
    if ($insurance && isset($insurance->insurer_number) && $insurance->insurer_number) {
      $pdf->SetFontSize($this->coord('insurer_number', 'fontSize'));
      $letterSpacing = $this->coordinates['insurer_number']['letterSpacing'] ?? 0;
      $this->fillBoxes($pdf, $this->coord('insurer_number', 'x'), $this->coord('insurer_number', 'y'), $insurance->insurer_number, 8, 5.6, (float)$letterSpacing);
      $pdf->SetFontSize(10);
    } else {
      \Log::warning('保険者番号が設定されていません', ['insurance' => $insurance]);
    }

    // === 被保険者証記号番号 ===
    // 保険種別1によって表示形式が異なる
    // 社・国・組(ID:1): 記号(code_number) + 番号(account_number)
    // 公費(ID:2), 後期(ID:3), 退職(ID:4): 被保険者番号(insured_number)のみ
    if ($insurance) {
      $insuranceType1Id = $insurance->insurance_type_1_id ?? null;
      $displayText = '';

      if ($insuranceType1Id == 1) {
        // 社・国・組の場合: 記号・番号形式
        $symbol = $insurance->code_number ?? '';
        $number = $insurance->account_number ?? '';
        if ($symbol || $number) {
          $displayText = trim(($symbol ?: '') . ($symbol && $number ? '・' : '') . ($number ?: ''));
        }
      } else {
        // 公費・後期・退職の場合: 被保険者番号のみ
        $displayText = $insurance->insured_number ?? '';
      }

      if ($displayText) {
        $pdf->SetFontSize($this->coord('insurance_symbol', 'fontSize'));
        $this->drawTextByKey($pdf, 'insurance_symbol', (string)$displayText);
        $pdf->SetFontSize(10);
      } else {
        \Log::warning('被保険者証記号番号が設定されていません', [
          'insurance_type_1_id' => $insuranceType1Id,
          'insurance' => $insurance
        ]);
      }
    } else {
      \Log::warning('保険情報がありません');
    }

    // === 療養を受けた者の氏名 ===
    $fullName = ($clinicUser->last_name ?? '') . ' ' . ($clinicUser->first_name ?? '');
    $fullNameKana = ($clinicUser->last_kana ?? '') . ' ' . ($clinicUser->first_kana ?? '');

    if (empty($fullName)) {
      \Log::warning('患者氏名が設定されていません', ['clinic_user' => $clinicUser]);
    }
    if (empty($fullNameKana)) {
      \Log::warning('患者氏名（カナ）が設定されていません', ['clinic_user' => $clinicUser]);
    }

    $pdf->SetFontSize($this->coord('patient_name_kana', 'fontSize'));
    $this->drawTextByKey($pdf, 'patient_name_kana', (string)$fullNameKana);
    $pdf->SetFontSize($this->coord('patient_name', 'fontSize'));
    $this->drawTextByKey($pdf, 'patient_name', (string)$fullName);
    $pdf->SetFontSize(10);

    // === 生年月日 ===
    if (isset($clinicUser->birthday)) {
      [$birthYear, $birthMonth, $birthDay] = explode('-', $clinicUser->birthday);
      $birthJapaneseYear = $this->convertToJapaneseYear((int)$birthYear, (int)$birthMonth);

      $pdf->SetFontSize($this->coord('birthday_year', 'fontSize'));
      // 明・大・昭・平・令の選択（令和の場合）
      if ($birthJapaneseYear['era'] === '令和') {
        $this->drawTextByKey($pdf, 'birthday_era_reiwa', '○');
      } elseif ($birthJapaneseYear['era'] === '平成') {
        $this->drawTextByKey($pdf, 'birthday_era_heisei', '○');
      } elseif ($birthJapaneseYear['era'] === '昭和') {
        $this->drawTextByKey($pdf, 'birthday_era_showa', '○');
      }

      $this->drawTextByKey($pdf, 'birthday_year', (string)$birthJapaneseYear['year']);
      $this->drawTextByKey($pdf, 'birthday_month', (string)(int)$birthMonth);
      $this->drawTextByKey($pdf, 'birthday_day', (string)(int)$birthDay);
      $pdf->SetFontSize(10);
    } else {
      \Log::warning('生年月日が設定されていません', ['clinic_user' => $clinicUser]);
    }

    // === 初療年月日 ===
    if ($records->isNotEmpty()) {
      $firstRecord = $records->first();
      [$firstYear, $firstMonth, $firstDay] = explode('-', $firstRecord->date);
      $firstJapaneseYear = $this->convertToJapaneseYear((int)$firstYear, (int)$firstMonth);

      $pdf->SetFontSize($this->coord('first_treatment_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'first_treatment_year', (string)$firstJapaneseYear['year']);
      $this->drawTextByKey($pdf, 'first_treatment_month', (string)(int)$firstMonth);
      $this->drawTextByKey($pdf, 'first_treatment_day', (string)(int)$firstDay);
      $pdf->SetFontSize(10);
    }

    // === 施術期間 ===
    if ($records->isNotEmpty()) {
      $firstDate = $records->first()->date;
      $lastDate = $records->last()->date;

      [$startYear, $startMonth, $startDay] = explode('-', $firstDate);
      [$endYear, $endMonth, $endDay] = explode('-', $lastDate);

      $startJapaneseYear = $this->convertToJapaneseYear((int)$startYear, (int)$startMonth);
      $endJapaneseYear = $this->convertToJapaneseYear((int)$endYear, (int)$endMonth);

      $pdf->SetFontSize($this->coord('treatment_start_year', 'fontSize'));
      // 自：開始日
      $this->drawTextByKey($pdf, 'treatment_start_year', (string)$startJapaneseYear['year']);
      $this->drawTextByKey($pdf, 'treatment_start_month', (string)(int)$startMonth);
      $this->drawTextByKey($pdf, 'treatment_start_day', (string)(int)$startDay);

      // 至：終了日
      $this->drawTextByKey($pdf, 'treatment_end_year', (string)$endJapaneseYear['year']);
      $this->drawTextByKey($pdf, 'treatment_end_month', (string)(int)$endMonth);
      $this->drawTextByKey($pdf, 'treatment_end_day', (string)(int)$endDay);

      // 実日数
      $this->drawTextByKey($pdf, 'treatment_days', (string)$records->count());
      $pdf->SetFontSize(10);
    }

    // === 傷病名（同意書から取得） ===
    if ($consent) {
      $illnessName = '';

      // illness_name_acupuncture_idから病名を取得
      if (isset($consent->illness_name_acupuncture_id) && $consent->illness_name_acupuncture_id) {
        $illness = DB::table('illness_name_acupuncture')
          ->where('id', $consent->illness_name_acupuncture_id)
          ->first();
        if ($illness && isset($illness->illness_name)) {
          $illnessName = $illness->illness_name;
        }
      }

      // 追記がある場合は追加
      if (isset($consent->illness_name_acupuncture_addendum) && $consent->illness_name_acupuncture_addendum) {
        $illnessName .= ($illnessName ? '、' : '') . $consent->illness_name_acupuncture_addendum;
      }

      if ($illnessName) {
        $pdf->SetFontSize($this->coord('illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'illness_name', (string)$illnessName);
        $pdf->SetFontSize(10);
      }
    }

    // === 施術日カレンダー（1-31日） ===
    $this->fillServiceDates($pdf, $records);

    // === 施術所情報 ===
    if ($clinicInfo) {
      // 施術日（年月日）
      $submissionParts = explode('-', $submissionDate);
      $submissionJapaneseYear = $this->convertToJapaneseYear((int)$submissionParts[0], (int)$submissionParts[1]);

      $pdf->SetFontSize($this->coord('clinic_date_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_date_year', (string)$submissionJapaneseYear['year']);
      $this->drawTextByKey($pdf, 'clinic_date_month', (string)(int)$submissionParts[1]);
      $this->drawTextByKey($pdf, 'clinic_date_day', (string)(int)$submissionParts[2]);
      $pdf->SetFontSize(10);

      // 施術所所在地
      $clinicAddress = ($clinicInfo->postal_code ?? '') . ' ' .
                       ($clinicInfo->address_1 ?? '') .
                       ($clinicInfo->address_2 ?? '') .
                       ($clinicInfo->address_3 ?? '');
      $pdf->SetFontSize($this->coord('clinic_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_address', (string)$clinicAddress);
      $pdf->SetFontSize(10);

      // 施術所名称
      $pdf->SetFontSize($this->coord('clinic_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_name', (string)($clinicInfo->clinic_name ?? ''));
      $pdf->SetFontSize(10);

      // 施術管理者氏名（施術者情報から取得）
      $therapist = DB::table('therapists')->first();
      if ($therapist) {
        $therapistName = ($therapist->last_name ?? '') . ($therapist->first_name ?? '');
        if (empty($therapistName)) {
          \Log::warning('施術管理者氏名が設定されていません', ['therapist' => $therapist]);
        }
        $pdf->SetFontSize($this->coord('clinic_manager', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_manager', (string)$therapistName);
        $pdf->SetFontSize(10);
      } else {
        \Log::warning('施術者情報が見つかりません');
      }

      // 電話番号
      if (empty($clinicInfo->phone ?? '')) {
        \Log::warning('施術所電話番号が設定されていません', ['clinic_info' => $clinicInfo]);
      }
      $pdf->SetFontSize($this->coord('clinic_phone', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_phone', (string)($clinicInfo->phone ?? ''));
      $pdf->SetFontSize(10);
    }

    // === 申請欄：提出年月日 ===
    $submissionParts = explode('-', $submissionDate);
    $submissionJapaneseYear = $this->convertToJapaneseYear((int)$submissionParts[0], (int)$submissionParts[1]);

    $pdf->SetFontSize($this->coord('submission_date_year', 'fontSize'));
    $this->drawTextByKey($pdf, 'submission_date_year', (string)$submissionJapaneseYear['year']);
    $this->drawTextByKey($pdf, 'submission_date_month', (string)(int)$submissionParts[1]);
    $this->drawTextByKey($pdf, 'submission_date_day', (string)(int)$submissionParts[2]);
    $pdf->SetFontSize(10);

    // === 申請者情報 ===
    $address = ($clinicUser->postal_code ?? '') . ' ' .
               ($clinicUser->address_1 ?? '') .
               ($clinicUser->address_2 ?? '') .
               ($clinicUser->address_3 ?? '');
    $pdf->SetFontSize($this->coord('applicant_address', 'fontSize'));
    $this->drawTextByKey($pdf, 'applicant_address', (string)$address);
    $this->drawTextByKey($pdf, 'applicant_name', (string)$fullName);
    $pdf->SetFontSize(10);
  }

  /**
   * ボックスに数字を均等配置
   *
   * @param Fpdi $pdf
   * @param float $startX
   * @param float $y
   * @param string $text
   * @param int $boxCount
   * @param float $boxWidth
   * @return void
   */
  /**
   * ボックスに数字を均等配置（文字間隔オプション対応）
   *
   * @param Fpdi $pdf
   * @param float $startX
   * @param float $y
   * @param string $text
   * @param int $boxCount
   * @param float $boxWidth
   * @param float $letterSpacing (mm) 追加の文字間隔
   * @return void
   */
  protected function fillBoxes(Fpdi $pdf, float $startX, float $y, string $text, int $boxCount, float $boxWidth, float $letterSpacing = 0): void
  {
    // マルチバイトを安全に分割
    $chars = preg_split('//u', (string)$text, -1, PREG_SPLIT_NO_EMPTY);

    if ($letterSpacing == 0) {
      // 従来通りボックス幅で配置
      for ($i = 0; $i < min(count($chars), $boxCount); $i++) {
        $x = $startX + ($i * $boxWidth);
        $pdf->Text($x, $y, $chars[$i]);
      }
      return;
    }

    // letterSpacing が指定されている場合は幅に加算して配置
    for ($i = 0; $i < min(count($chars), $boxCount); $i++) {
      $x = $startX + ($i * ($boxWidth + $letterSpacing));
      $pdf->Text($x, $y, $chars[$i]);
    }

    // ログ（デバッグ）
    try {
      \Log::debug('fillBoxes with spacing', ['startX' => $startX, 'y' => $y, 'letterSpacing' => $letterSpacing, 'text_sample' => mb_substr($text, 0, 10)]);
    } catch (\Throwable $e) {
      // ignore
    }
  }

  /**
   * 施術日をカレンダーに記入
   *
   * @param Fpdi $pdf
   * @param \Illuminate\Support\Collection $records
   * @return void
   */
  protected function fillServiceDates(Fpdi $pdf, $records): void
  {
    $pdf->SetFontSize($this->coord('calendar_start', 'fontSize'));
    foreach ($records as $record) {
      $day = (int)date('d', strtotime($record->date));

      // 日付に応じて○を記入
      $x = $this->coord('calendar_start', 'x') + ($day - 1) * $this->coord('calendar_start', 'cellWidth');
      $y = $this->coord('calendar_start', 'y');

      $pdf->Text($x, $y, '○');
    }
    $pdf->SetFontSize(10);
  }

  /**
   * デバッグ用グリッド表示
   *
   * @param Fpdi $pdf
   * @return void
   */
  protected function drawDebugGrid(Fpdi $pdf): void
  {
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.1);

    // 縦線（10mm間隔）
    for ($x = 0; $x <= 210; $x += 10) {
      $pdf->Line($x, 0, $x, 297);
      $pdf->SetFontSize(6);
      $pdf->SetTextColor(150, 150, 150);
      $pdf->Text($x + 0.5, 5, (string)$x);
    }

    // 横線（10mm間隔）
    for ($y = 0; $y <= 297; $y += 10) {
      $pdf->Line(0, $y, 210, $y);
      $pdf->SetFontSize(6);
      $pdf->SetTextColor(150, 150, 150);
      $pdf->Text(2, $y + 3, (string)$y);
    }

    // テキスト色を戻す
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFontSize(10);
  }

  /**
   * 文字間隔を考慮したテキスト描画（内部ユーティリティ）
   *
   * @param Fpdi $pdf
   * @param float $startX
   * @param float $y
   * @param string $text
   * @param float $letterSpacing 追加の文字間隔（mm）
   * @return void
   */
  protected function drawTextWithSpacing(Fpdi $pdf, float $startX, float $y, string $text, float $letterSpacing): void
  {
    // マルチバイト対応で1文字ずつに分割
    $chars = preg_split('//u', (string)$text, -1, PREG_SPLIT_NO_EMPTY);
    $x = $startX;

    foreach ($chars as $char) {
      $pdf->Text($x, $y, $char);
      // GetStringWidth は現在のフォントサイズ・フォントを考慮した幅を返す（単位はmm）
      $width = $pdf->GetStringWidth($char);
      $x += $width + $letterSpacing;
    }
  }

  /**
   * 座標キーに基づいて文字間隔設定を反映してテキストを描画する（主に既存コードの置換用）
   *
   * @param Fpdi $pdf
   * @param string $key
   * @param string $text
   * @return void
   */
  protected function drawTextByKey(Fpdi $pdf, string $key, string $text): void
  {
    $x = $this->coord($key, 'x');
    $y = $this->coord($key, 'y');
    $letterSpacing = $this->coordinates[$key]['letterSpacing'] ?? 0;

    if (empty($letterSpacing)) {
      $pdf->Text($x, $y, $text);
      return;
    }

    // ログ（デバッグ）：実際に文字間隔を使って描画されるか確認
    try {
      \Log::debug('drawTextByKey with spacing', ['key' => $key, 'letterSpacing' => $letterSpacing, 'text_sample' => mb_substr($text, 0, 10)]);
    } catch (\Throwable $e) {
      // ログ失敗は致命的でない
    }

    $this->drawTextWithSpacing($pdf, $x, $y, $text, (float)$letterSpacing);
  }

  /**
   * 西暦を和暦に変換
   *
   * @param int $year
   * @param int $month
   * @return array
   */
  protected function convertToJapaneseYear(int $year, int $month): array
  {
    if ($year >= 2019 && ($year > 2019 || $month >= 5)) {
      return ['era' => '令和', 'year' => $year - 2018];
    } elseif ($year >= 1989) {
      return ['era' => '平成', 'year' => $year - 1988];
    } elseif ($year >= 1926) {
      return ['era' => '昭和', 'year' => $year - 1925];
    }
    return ['era' => '', 'year' => $year];
  }

  /**
   * 日付を和暦形式に変換
   *
   * @param string $date YYYY-MM-DD形式
   * @return string
   */
  protected function convertToJapaneseDate(string $date): string
  {
    [$year, $month, $day] = explode('-', $date);
    $japaneseYear = $this->convertToJapaneseYear((int)$year, (int)$month);

    return $japaneseYear['era'] . $japaneseYear['year'] . '年' . (int)$month . '月' . (int)$day . '日';
  }
}
