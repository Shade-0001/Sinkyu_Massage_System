<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * あんま・マッサージ療養費支給申請書PDF生成サービス
 */
class MassageBenefitPdfService
{
  /**
   * 座標設定
   */
  protected $coordinates;

  /**
   * サンプルデータ表示モード
   */
  protected $sampleDataMode = false;

  /**
   * カスタムサンプルデータ
   */
  protected $customSampleData = null;

  /**
   * コンストラクタ
   */
  public function __construct()
  {
    $this->loadCoordinates();
  }

  /**
   * サンプルデータ表示モードを設定
   */
  public function setSampleDataMode(bool $enabled): void
  {
    $this->sampleDataMode = $enabled;
  }

  /**
   * カスタムサンプルデータを設定
   */
  public function setCustomSampleData(array $data): void
  {
    $this->customSampleData = $data;
  }

  /**
   * 座標設定を読み込む
   */
  protected function loadCoordinates(): void
  {
    $configPath = storage_path('app/config/massage_benefit_coordinates.json');

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
    // 基本的な座標のみを返す（後で調整ツールで設定）
    return [
      'title_year_era' => ['x' => 0, 'y' => 0, 'fontSize' => 10, 'letterSpacing' => 0, 'label' => 'タイトル（年・元号）'],
      'title_year_number' => ['x' => 0, 'y' => 0, 'fontSize' => 10, 'letterSpacing' => 0, 'label' => 'タイトル（年・数字）'],
    ];
  }

  /**
   * 座標値を取得するヘルパーメソッド
   */
  protected function coord(string $key, string $property = 'x')
  {
    return $this->coordinates[$key][$property] ?? 0;
  }

  /**
   * 座標が存在するかチェック
   */
  protected function hasCoord(string $key): bool
  {
    return isset($this->coordinates[$key]);
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
    // サンプルデータ表示モードの場合
    if ($this->sampleDataMode) {
      return $this->getSampleData($serviceYearMonth);
    }

    // 利用者情報取得（性別情報もJOIN）
    $clinicUser = DB::table('clinic_users')
      ->leftJoin('gender', 'clinic_users.gender_id', '=', 'gender.id')
      ->where('clinic_users.id', $clinicUserId)
      ->select('clinic_users.*', 'gender.gender')
      ->first();

    if (!$clinicUser) {
      \Log::error('利用者情報が見つかりません', ['clinic_user_id' => $clinicUserId]);
      return null;
    }

    // 保険情報取得
    $insurance = DB::table('insurances')
      ->leftJoin('insurers', 'insurances.insurers_id', '=', 'insurers.id')
      ->leftJoin('relationships_with_clinic_user', 'insurances.relationship_with_clinic_user_id', '=', 'relationships_with_clinic_user.id')
      ->leftJoin('expenses_borne_ratios', 'insurances.expenses_borne_ratio_id', '=', 'expenses_borne_ratios.id')
      ->leftJoin('insurance_types_1', 'insurances.insurance_type_1_id', '=', 'insurance_types_1.id')
      ->leftJoin('insurance_types_3', 'insurances.insurance_type_3_id', '=', 'insurance_types_3.id')
      ->where('insurances.clinic_user_id', $clinicUserId)
      ->orderBy('insurances.created_at', 'desc')
      ->select(
        'insurances.*',
        'insurers.insurer_number',
        'insurers.insurer_name',
        'relationships_with_clinic_user.relationship',
        'expenses_borne_ratios.expenses_borne_ratio',
        'insurance_types_1.insurance_type_1',
        'insurance_types_3.insurance_type_3'
      )
      ->first();

    if (!$insurance) {
      \Log::warning('保険情報が見つかりません', ['clinic_user_id' => $clinicUserId]);
    }

    // あんま・マッサージ同意書情報取得
    $consent = DB::table('consents_massage')
      ->leftJoin('bill_categories', 'consents_massage.bill_category_id', '=', 'bill_categories.id')
      ->leftJoin('outcomes', 'consents_massage.outcome_id', '=', 'outcomes.id')
      ->leftJoin('work_scope_types', 'consents_massage.work_scope_type_id', '=', 'work_scope_types.id')
      ->where('consents_massage.clinic_user_id', $clinicUserId)
      ->orderBy('consents_massage.consenting_date', 'desc')
      ->select(
        'consents_massage.*',
        'bill_categories.bill_category',
        'outcomes.outcome',
        'work_scope_types.work_scope_type'
      )
      ->first();

    if (!$consent) {
      \Log::warning('あんま・マッサージ同意書情報が見つかりません', ['clinic_user_id' => $clinicUserId]);
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
    $templatePath = storage_path('app/templates/massage_benefit_form.pdf');

    if (file_exists($templatePath)) {
      $pageCount = $pdf->setSourceFile($templatePath);
      $tplId = $pdf->importPage(1);
      $pdf->useTemplate($tplId, 0, 0, null, null, true);
    }

    // フォント設定（日本語フォント: kozminproregular）
    $pdf->SetFont('kozminproregular', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // データ埋め込み
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
    $pdf->SetFontSize($this->coord('title_year_era', 'fontSize'));
    $this->drawTextByKey($pdf, 'title_year_era', (string)$japaneseYear['era']);
    $pdf->SetFontSize($this->coord('title_year_number', 'fontSize'));
    $this->drawTextByKey($pdf, 'title_year_number', (string)$japaneseYear['year']);
    $pdf->SetFontSize($this->coord('title_month', 'fontSize'));
    $this->drawTextByKey($pdf, 'title_month', (string)(int)$month);

    // === 機関コード ===
    if ($clinicInfo && isset($clinicInfo->medical_institution_number)) {
      $pdf->SetFontSize($this->coord('institution_code', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'institution_code', (string)$clinicInfo->medical_institution_number, 7, 5.6);
      $pdf->SetFontSize(10);
    }

    // === 保険種別１ ===
    if ($insurance && isset($insurance->insurance_type_1)) {
      $insuranceType1Map = [
        '社･国･組' => 'insurance_type_1_shakoku',
        '公費' => 'insurance_type_1_kouhi',
        '後期' => 'insurance_type_1_kouki',
        '退職' => 'insurance_type_1_taishoku',
      ];
      $key = $insuranceType1Map[$insurance->insurance_type_1] ?? null;
      if ($key) {
        $this->drawEllipseByKey($pdf, $key);
      }
    }

    // === 保険種別３ ===
    if ($insurance && isset($insurance->insurance_type_3)) {
      $insuranceType3Map = [
        '本外' => 'insurance_type_3_hongai',
        '三外' => 'insurance_type_3_sangai',
        '家外' => 'insurance_type_3_kagai',
        '高外９' => 'insurance_type_3_kougai9',
        '高外８' => 'insurance_type_3_kougai8',
      ];
      $key = $insuranceType3Map[$insurance->insurance_type_3] ?? null;
      if ($key) {
        $this->drawEllipseByKey($pdf, $key);
      }
    }

    // === 一部負担金（楕円） ===
    if ($insurance && isset($insurance->expenses_borne_ratio)) {
      // 表示ラベルを数値に変換（サンプルデータ対応）
      $ratioValue = (string)$insurance->expenses_borne_ratio;
      if ($ratioValue === '１割') $ratioValue = '10';
      if ($ratioValue === '２割') $ratioValue = '20';
      if ($ratioValue === '３割') $ratioValue = '30';

      $expensesBorneRatioMap = [
        '10' => 'expenses_borne_ratio_10',
        '20' => 'expenses_borne_ratio_20',
        '30' => 'expenses_borne_ratio_30',
      ];
      $key = $expensesBorneRatioMap[$ratioValue] ?? null;
      if ($key) {
        $this->drawEllipseByKey($pdf, $key);
      }
    }

    // === 保険者番号 ===
    if ($insurance && isset($insurance->insurer_number) && $insurance->insurer_number) {
      $pdf->SetFontSize($this->coord('insurer_number', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'insurer_number', $insurance->insurer_number, 8, 5.6);
      $pdf->SetFontSize(10);
    }

    // === 被保険者証記号番号 ===
    if ($insurance) {
      $insuranceType1Id = $insurance->insurance_type_1_id ?? null;
      $displayText = '';

      if ($insuranceType1Id == 1) {
        $symbol = $insurance->code_number ?? '';
        $number = $insurance->account_number ?? '';
        if ($symbol || $number) {
          $displayText = trim(($symbol ?: '') . ($symbol && $number ? '・' : '') . ($number ?: ''));
        }
      } else {
        $displayText = $insurance->insured_number ?? '';
      }

      if ($displayText) {
        $pdf->SetFontSize($this->coord('insurance_symbol', 'fontSize'));
        $this->drawTextByKey($pdf, 'insurance_symbol', (string)$displayText);
        $pdf->SetFontSize(10);
      }
    }

    // === 療養を受けた者の氏名 ===
    $fullName = ($clinicUser->last_name ?? '') . ' ' . ($clinicUser->first_name ?? '');
    $fullNameKana = ($clinicUser->last_kana ?? '') . ' ' . ($clinicUser->first_kana ?? '');

    $pdf->SetFontSize($this->coord('patient_name_kana', 'fontSize'));
    $this->drawTextByKey($pdf, 'patient_name_kana', (string)$fullNameKana);
    $pdf->SetFontSize($this->coord('patient_name', 'fontSize'));
    $this->drawTextByKey($pdf, 'patient_name', (string)$fullName);
    $pdf->SetFontSize(10);

    // === 続柄 ===
    if ($insurance && isset($insurance->relationship) && $insurance->relationship) {
      $pdf->SetFontSize($this->coord('patient_relationship', 'fontSize'));
      $this->drawTextByKey($pdf, 'patient_relationship', (string)$insurance->relationship);
      $pdf->SetFontSize(10);
    }

    // === 性別 ===
    if (isset($clinicUser->gender) && $clinicUser->gender) {
      if ($clinicUser->gender === '男') {
        $this->drawEllipseByKey($pdf, 'patient_gender_male');
      } elseif ($clinicUser->gender === '女') {
        $this->drawEllipseByKey($pdf, 'patient_gender_female');
      }
    }

    // === 生年月日 ===
    if (isset($clinicUser->birthday)) {
      [$birthYear, $birthMonth, $birthDay] = explode('-', $clinicUser->birthday);
      $birthJapaneseYear = $this->convertToJapaneseYear((int)$birthYear, (int)$birthMonth);

      // サンプルデータモードの場合のみisSelectedを使用、それ以外は実データから判定
      if ($this->sampleDataMode) {
        // isSelectedフラグをチェック（サンプルデータの場合）
        $birthdayEraKey = null;
        if (isset($this->coordinates['birthday_era_reiwa']['isSelected']) && $this->coordinates['birthday_era_reiwa']['isSelected']) {
          $birthdayEraKey = 'birthday_era_reiwa';
        } elseif (isset($this->coordinates['birthday_era_heisei']['isSelected']) && $this->coordinates['birthday_era_heisei']['isSelected']) {
          $birthdayEraKey = 'birthday_era_heisei';
        } elseif (isset($this->coordinates['birthday_era_showa']['isSelected']) && $this->coordinates['birthday_era_showa']['isSelected']) {
          $birthdayEraKey = 'birthday_era_showa';
        }

        if ($birthdayEraKey) {
          $this->drawEllipseByKey($pdf, $birthdayEraKey);
        }
      } else {
        // 実データから判定
        if ($birthJapaneseYear['era'] === '令和') {
          $this->drawEllipseByKey($pdf, 'birthday_era_reiwa');
        } elseif ($birthJapaneseYear['era'] === '平成') {
          $this->drawEllipseByKey($pdf, 'birthday_era_heisei');
        } elseif ($birthJapaneseYear['era'] === '昭和') {
          $this->drawEllipseByKey($pdf, 'birthday_era_showa');
        }
      }

      $pdf->SetFontSize($this->coord('birthday_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'birthday_year', (string)$birthJapaneseYear['year']);
      $pdf->SetFontSize($this->coord('birthday_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'birthday_month', (string)(int)$birthMonth);
      $pdf->SetFontSize($this->coord('birthday_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'birthday_day', (string)(int)$birthDay);
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
      $this->drawTextByKey($pdf, 'treatment_start_year', (string)$startJapaneseYear['year']);
      $pdf->SetFontSize($this->coord('treatment_start_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_start_month', (string)(int)$startMonth);
      $pdf->SetFontSize($this->coord('treatment_start_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_start_day', (string)(int)$startDay);

      $pdf->SetFontSize($this->coord('treatment_end_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_end_year', (string)$endJapaneseYear['year']);
      $pdf->SetFontSize($this->coord('treatment_end_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_end_month', (string)(int)$endMonth);
      $pdf->SetFontSize($this->coord('treatment_end_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_end_day', (string)(int)$endDay);

      $pdf->SetFontSize($this->coord('treatment_days', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_days', (string)$records->count());
      $pdf->SetFontSize(10);
    }

    // === 傷病名（施術内容欄のチェックボックス） ===
    // サンプルデータ表示モードの場合はisSelectedフラグを使用
    if ($this->sampleDataMode) {
      // isSelectedフラグをチェック
      for ($i = 1; $i <= 7; $i++) {
        $key = 'illness_name_' . $i;
        if (isset($this->coordinates[$key]['isSelected']) && $this->coordinates[$key]['isSelected']) {
          $this->drawEllipseByKey($pdf, $key);
          break; // 1つだけ選択
        }
      }
    } elseif ($consent) {
      // 実データモード：consents_massageの症状フラグから判定
      // is_symptom_1: 神経痛
      // is_symptom_2: リウマチ（symtom_2_addendumに追記）
      // is_symptom_3: その他（symtom_3_addendumに追記）

      if (isset($consent->is_symptom_1) && $consent->is_symptom_1) {
        // 神経痛
        $this->drawEllipseByKey($pdf, 'illness_name_1');
      }

      if (isset($consent->is_symptom_2) && $consent->is_symptom_2) {
        // リウマチ
        $this->drawEllipseByKey($pdf, 'illness_name_2');

        // 追記があれば表示
        if (isset($consent->symtom_2_addendum) && $consent->symtom_2_addendum) {
          $pdf->SetFontSize($this->coord('illness_name_other_text', 'fontSize'));
          $this->drawTextByKey($pdf, 'illness_name_other_text', (string)$consent->symtom_2_addendum);
          $pdf->SetFontSize(10);
        }
      }

      if (isset($consent->is_symptom_3) && $consent->is_symptom_3) {
        // その他
        $this->drawEllipseByKey($pdf, 'illness_name_7');

        // 追記があれば表示
        if (isset($consent->symtom_3_addendum) && $consent->symtom_3_addendum) {
          $pdf->SetFontSize($this->coord('illness_name_other_text', 'fontSize'));
          $this->drawTextByKey($pdf, 'illness_name_other_text', (string)$consent->symtom_3_addendum);
          $pdf->SetFontSize(10);
        }
      }
    }

    // === 施術日月ラベル ===
    if ($this->hasCoord('treatment_month_label_1')) {
      $pdf->SetFontSize($this->coord('treatment_month_label_1', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_month_label_1', '月');
      $pdf->SetFontSize(10);
    }
    if ($this->hasCoord('treatment_month_label_2')) {
      $pdf->SetFontSize($this->coord('treatment_month_label_2', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_month_label_2', '日');
      $pdf->SetFontSize(10);
    }

    // === 施術日カレンダー ===
    $this->fillServiceDates($pdf, $records);

    // === 施術料金 ===
    if ($this->sampleDataMode && $this->customSampleData) {
      // マッサージ料金（躯幹）
      if ($this->hasCoord('fee_massage_trunk_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_trunk_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_trunk_unit', $this->customSampleData['fee_massage_trunk_unit'] ?? '');
      }
      if ($this->hasCoord('fee_massage_trunk_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_trunk_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_trunk_count', $this->customSampleData['fee_massage_trunk_count'] ?? '');
      }
      if ($this->hasCoord('fee_massage_trunk_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_trunk_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_trunk_total', $this->customSampleData['fee_massage_trunk_total'] ?? '');
      }

      // マッサージ料金（右上肢）
      if ($this->hasCoord('fee_massage_upper_limb_r_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_r_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_unit', $this->customSampleData['fee_massage_upper_limb_r_unit'] ?? '');
      }
      if ($this->hasCoord('fee_massage_upper_limb_r_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_r_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_count', $this->customSampleData['fee_massage_upper_limb_r_count'] ?? '');
      }
      if ($this->hasCoord('fee_massage_upper_limb_r_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_r_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_total', $this->customSampleData['fee_massage_upper_limb_r_total'] ?? '');
      }

      // マッサージ料金（左上肢）
      if ($this->hasCoord('fee_massage_upper_limb_l_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_l_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_unit', $this->customSampleData['fee_massage_upper_limb_l_unit'] ?? '');
      }
      if ($this->hasCoord('fee_massage_upper_limb_l_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_l_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_count', $this->customSampleData['fee_massage_upper_limb_l_count'] ?? '');
      }
      if ($this->hasCoord('fee_massage_upper_limb_l_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_l_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_total', $this->customSampleData['fee_massage_upper_limb_l_total'] ?? '');
      }

      // マッサージ料金（右下肢）
      if ($this->hasCoord('fee_massage_lower_limb_r_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_r_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_unit', $this->customSampleData['fee_massage_lower_limb_r_unit'] ?? '');
      }
      if ($this->hasCoord('fee_massage_lower_limb_r_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_r_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_count', $this->customSampleData['fee_massage_lower_limb_r_count'] ?? '');
      }
      if ($this->hasCoord('fee_massage_lower_limb_r_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_r_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_total', $this->customSampleData['fee_massage_lower_limb_r_total'] ?? '');
      }

      // マッサージ料金（左下肢）
      if ($this->hasCoord('fee_massage_lower_limb_l_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_l_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_unit', $this->customSampleData['fee_massage_lower_limb_l_unit'] ?? '');
      }
      if ($this->hasCoord('fee_massage_lower_limb_l_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_l_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_count', $this->customSampleData['fee_massage_lower_limb_l_count'] ?? '');
      }
      if ($this->hasCoord('fee_massage_lower_limb_l_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_l_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_total', $this->customSampleData['fee_massage_lower_limb_l_total'] ?? '');
      }

      // 変形徒手矯正術
      if ($this->hasCoord('fee_manual_correction_unit')) {
        $pdf->SetFontSize($this->coord('fee_manual_correction_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_manual_correction_unit', $this->customSampleData['fee_manual_correction_unit'] ?? '');
      }
      if ($this->hasCoord('fee_manual_correction_count')) {
        $pdf->SetFontSize($this->coord('fee_manual_correction_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_manual_correction_count', $this->customSampleData['fee_manual_correction_count'] ?? '');
      }
      if ($this->hasCoord('fee_manual_correction_total')) {
        $pdf->SetFontSize($this->coord('fee_manual_correction_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_manual_correction_total', $this->customSampleData['fee_manual_correction_total'] ?? '');
      }

      // 温罨法
      if ($this->hasCoord('fee_fomentation_unit')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_unit', $this->customSampleData['fee_fomentation_unit'] ?? '');
      }
      if ($this->hasCoord('fee_fomentation_count')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_count', $this->customSampleData['fee_fomentation_count'] ?? '');
      }
      if ($this->hasCoord('fee_fomentation_total')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_total', $this->customSampleData['fee_fomentation_total'] ?? '');
      }

      // 温罨法・電光線器具
      if ($this->hasCoord('fee_fomentation_electric_light_unit')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_electric_light_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_unit', $this->customSampleData['fee_fomentation_electric_light_unit'] ?? '');
      }
      if ($this->hasCoord('fee_fomentation_electric_light_count')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_electric_light_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_count', $this->customSampleData['fee_fomentation_electric_light_count'] ?? '');
      }
      if ($this->hasCoord('fee_fomentation_electric_light_total')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_electric_light_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_total', $this->customSampleData['fee_fomentation_electric_light_total'] ?? '');
      }

      // はり（後方互換用・通常マッサージでは使用しない）
      if ($this->hasCoord('fee_hari_unit')) {
        $pdf->SetFontSize($this->coord('fee_hari_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_hari_unit', $this->customSampleData['fee_hari_unit'] ?? '');
      }
      if ($this->hasCoord('fee_hari_count')) {
        $pdf->SetFontSize($this->coord('fee_hari_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_hari_count', $this->customSampleData['fee_hari_count'] ?? '');
      }
      if ($this->hasCoord('fee_hari_total')) {
        $pdf->SetFontSize($this->coord('fee_hari_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_hari_total', $this->customSampleData['fee_hari_total'] ?? '');
      }

      // きゅう（後方互換用・通常マッサージでは使用しない）
      if ($this->hasCoord('fee_kyu_unit')) {
        $pdf->SetFontSize($this->coord('fee_kyu_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_kyu_unit', $this->customSampleData['fee_kyu_unit'] ?? '');
      }
      if ($this->hasCoord('fee_kyu_count')) {
        $pdf->SetFontSize($this->coord('fee_kyu_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_kyu_count', $this->customSampleData['fee_kyu_count'] ?? '');
      }
      if ($this->hasCoord('fee_kyu_total')) {
        $pdf->SetFontSize($this->coord('fee_kyu_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_kyu_total', $this->customSampleData['fee_kyu_total'] ?? '');
      }

      // はり・きゅう併用（後方互換用・通常マッサージでは使用しない）
      if ($this->hasCoord('fee_hari_kyu_unit')) {
        $pdf->SetFontSize($this->coord('fee_hari_kyu_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_hari_kyu_unit', $this->customSampleData['fee_hari_kyu_unit'] ?? '');
      }
      if ($this->hasCoord('fee_hari_kyu_count')) {
        $pdf->SetFontSize($this->coord('fee_hari_kyu_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_hari_kyu_count', $this->customSampleData['fee_hari_kyu_count'] ?? '');
      }
      if ($this->hasCoord('fee_hari_kyu_total')) {
        $pdf->SetFontSize($this->coord('fee_hari_kyu_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_hari_kyu_total', $this->customSampleData['fee_hari_kyu_total'] ?? '');
      }

      // 電療料（後方互換用・通常マッサージでは使用しない）
      if ($this->hasCoord('fee_electric_unit')) {
        $pdf->SetFontSize($this->coord('fee_electric_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_electric_unit', $this->customSampleData['fee_electric_unit'] ?? '');
      }
      if ($this->hasCoord('fee_electric_count')) {
        $pdf->SetFontSize($this->coord('fee_electric_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_electric_count', $this->customSampleData['fee_electric_count'] ?? '');
      }
      if ($this->hasCoord('fee_electric_total')) {
        $pdf->SetFontSize($this->coord('fee_electric_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_electric_total', $this->customSampleData['fee_electric_total'] ?? '');
      }

      // 往療料4kmまで
      if ($this->hasCoord('fee_housecall_unit')) {
        $pdf->SetFontSize($this->coord('fee_housecall_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_unit', $this->customSampleData['fee_housecall_unit'] ?? '');
      }
      if ($this->hasCoord('fee_housecall_count')) {
        $pdf->SetFontSize($this->coord('fee_housecall_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_count', $this->customSampleData['fee_housecall_count'] ?? '');
      }
      if ($this->hasCoord('fee_housecall_total')) {
        $pdf->SetFontSize($this->coord('fee_housecall_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_total', $this->customSampleData['fee_housecall_total'] ?? '');
      }

      // 往療料4km超
      if ($this->hasCoord('fee_housecall_additional_unit')) {
        $pdf->SetFontSize($this->coord('fee_housecall_additional_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_additional_unit', $this->customSampleData['fee_housecall_additional_unit'] ?? '');
      }
      if ($this->hasCoord('fee_housecall_additional_count')) {
        $pdf->SetFontSize($this->coord('fee_housecall_additional_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_additional_count', $this->customSampleData['fee_housecall_additional_count'] ?? '');
      }
      if ($this->hasCoord('fee_housecall_additional_total')) {
        $pdf->SetFontSize($this->coord('fee_housecall_additional_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_additional_total', $this->customSampleData['fee_housecall_additional_total'] ?? '');
      }

      // 施術報告書交付料
      if ($this->hasCoord('fee_previous_payment_unit')) {
        $pdf->SetFontSize($this->coord('fee_previous_payment_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_previous_payment_unit', $this->customSampleData['fee_previous_payment_unit'] ?? '');
      }
      if ($this->hasCoord('fee_previous_payment_count')) {
        $pdf->SetFontSize($this->coord('fee_previous_payment_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_previous_payment_count', $this->customSampleData['fee_previous_payment_count'] ?? '');
      }
      if ($this->hasCoord('fee_previous_payment_total')) {
        $pdf->SetFontSize($this->coord('fee_previous_payment_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_previous_payment_total', $this->customSampleData['fee_previous_payment_total'] ?? '');
      }

      // 合計
      if ($this->hasCoord('fee_subtotal')) {
        $pdf->SetFontSize($this->coord('fee_subtotal', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_subtotal', $this->customSampleData['fee_subtotal'] ?? '');
      }

      // 一部負担金
      if ($this->hasCoord('fee_partial_payment')) {
        $pdf->SetFontSize($this->coord('fee_partial_payment', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_partial_payment', $this->customSampleData['fee_partial_payment'] ?? '');
      }

      // 請求額
      if ($this->hasCoord('fee_total_claim')) {
        $pdf->SetFontSize($this->coord('fee_total_claim', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_total_claim', $this->customSampleData['fee_total_claim'] ?? '');
      }

      $pdf->SetFontSize(10);
    }

    // === 施術所情報 ===
    if ($clinicInfo) {
      $submissionParts = explode('-', $submissionDate);
      $submissionJapaneseYear = $this->convertToJapaneseYear((int)$submissionParts[0], (int)$submissionParts[1]);

      $pdf->SetFontSize($this->coord('clinic_date_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_date_year', (string)$submissionJapaneseYear['year']);
      $pdf->SetFontSize($this->coord('clinic_date_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_date_month', (string)(int)$submissionParts[1]);
      $pdf->SetFontSize($this->coord('clinic_date_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_date_day', (string)(int)$submissionParts[2]);
      $pdf->SetFontSize(10);

      // 施術所郵便番号
      if ($this->hasCoord('clinic_postal_code')) {
        $pdf->SetFontSize($this->coord('clinic_postal_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_postal_code', (string)($clinicInfo->postal_code ?? ''));
      }

      // 施術所住所
      $clinicAddress = ($clinicInfo->address_1 ?? '') .
                       ($clinicInfo->address_2 ?? '') .
                       ($clinicInfo->address_3 ?? '');
      $pdf->SetFontSize($this->coord('clinic_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_address', (string)$clinicAddress);
      $pdf->SetFontSize(10);

      $pdf->SetFontSize($this->coord('clinic_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_name', (string)($clinicInfo->clinic_name ?? ''));
      $pdf->SetFontSize(10);

      $therapist = DB::table('therapists')->first();
      if ($therapist) {
        $therapistName = ($therapist->last_name ?? '') . ' ' . ($therapist->first_name ?? '');
        $pdf->SetFontSize($this->coord('clinic_manager', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_manager', (string)$therapistName);
        $pdf->SetFontSize(10);
      }

      $pdf->SetFontSize($this->coord('clinic_phone', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_phone', (string)($clinicInfo->phone ?? ''));
      $pdf->SetFontSize(10);
    }

    // === 申請者情報 ===
    if ($this->hasCoord('applicant_postal_code') || $this->hasCoord('applicant_address') || $this->hasCoord('applicant_name')) {
      $applicantPostalCode = $this->customSampleData['applicant_postal_code'] ?? '';
      $applicantAddress = $this->customSampleData['applicant_address'] ?? '';
      $applicantName = $this->customSampleData['applicant_name'] ?? '';

      if ($this->hasCoord('applicant_postal_code') && $applicantPostalCode) {
        $pdf->SetFontSize($this->coord('applicant_postal_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'applicant_postal_code', (string)$applicantPostalCode);
      }

      if ($this->hasCoord('applicant_address') && $applicantAddress) {
        $pdf->SetFontSize($this->coord('applicant_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'applicant_address', (string)$applicantAddress);
      }

      if ($this->hasCoord('applicant_name') && $applicantName) {
        $pdf->SetFontSize($this->coord('applicant_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'applicant_name', (string)$applicantName);
      }

      $pdf->SetFontSize(10);
    }

    // === 代理人情報 ===
    if ($this->hasCoord('agent_postal_code') || $this->hasCoord('agent_address') || $this->hasCoord('agent_name')) {
      $agentPostalCode = $this->customSampleData['agent_postal_code'] ?? '';
      $agentAddress = $this->customSampleData['agent_address'] ?? '';
      $agentName = $this->customSampleData['agent_name'] ?? '';

      if ($this->hasCoord('agent_postal_code') && $agentPostalCode) {
        $pdf->SetFontSize($this->coord('agent_postal_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_postal_code', (string)$agentPostalCode);
      }

      if ($this->hasCoord('agent_address') && $agentAddress) {
        $pdf->SetFontSize($this->coord('agent_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_address', (string)$agentAddress);
      }

      if ($this->hasCoord('agent_name') && $agentName) {
        $pdf->SetFontSize($this->coord('agent_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_name', (string)$agentName);
      }

      $pdf->SetFontSize(10);
    }

    // === 支払機関情報 ===
    if ($this->hasCoord('payment_institution_date_year')) {
      $today = date('Y-m-d');
      $todayParts = explode('-', $today);
      $todayJapaneseYear = $this->convertToJapaneseYear((int)$todayParts[0], (int)$todayParts[1]);

      $paymentYear = $this->customSampleData['payment_institution_date_year'] ?? (string)$todayJapaneseYear['year'];
      $paymentMonth = $this->customSampleData['payment_institution_date_month'] ?? (string)(int)$todayParts[1];
      $paymentDay = $this->customSampleData['payment_institution_date_day'] ?? (string)(int)$todayParts[2];
      $paymentPostalCode = $this->customSampleData['payment_institution_postal_code'] ?? '';
      $paymentAddress = $this->customSampleData['payment_institution_address'] ?? '';
      $paymentName = $this->customSampleData['payment_institution_name'] ?? '';
      $paymentPhone = $this->customSampleData['payment_institution_phone'] ?? '';

      if ($this->hasCoord('payment_institution_date_year')) {
        $pdf->SetFontSize($this->coord('payment_institution_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'payment_institution_date_year', (string)$paymentYear);
      }

      if ($this->hasCoord('payment_institution_date_month')) {
        $pdf->SetFontSize($this->coord('payment_institution_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'payment_institution_date_month', (string)$paymentMonth);
      }

      if ($this->hasCoord('payment_institution_date_day')) {
        $pdf->SetFontSize($this->coord('payment_institution_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'payment_institution_date_day', (string)$paymentDay);
      }

      if ($this->hasCoord('payment_institution_postal_code') && $paymentPostalCode) {
        $pdf->SetFontSize($this->coord('payment_institution_postal_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'payment_institution_postal_code', (string)$paymentPostalCode);
      }

      if ($this->hasCoord('payment_institution_address') && $paymentAddress) {
        $pdf->SetFontSize($this->coord('payment_institution_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'payment_institution_address', (string)$paymentAddress);
      }

      if ($this->hasCoord('payment_institution_name') && $paymentName) {
        $pdf->SetFontSize($this->coord('payment_institution_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'payment_institution_name', (string)$paymentName);
      }

      if ($this->hasCoord('payment_institution_phone') && $paymentPhone) {
        $pdf->SetFontSize($this->coord('payment_institution_phone', 'fontSize'));
        $this->drawTextByKey($pdf, 'payment_institution_phone', (string)$paymentPhone);
      }

      $pdf->SetFontSize(10);
    }

    // === 被保険者情報 ===
    if ($this->hasCoord('temporary_insurer_name')) {
      $tempInsurerName = $this->customSampleData['temporary_insurer_name'] ?? '';

      if ($tempInsurerName) {
        $pdf->SetFontSize($this->coord('temporary_insurer_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'temporary_insurer_name', (string)$tempInsurerName);
        $pdf->SetFontSize(10);
      }
    }
  }

  /**
   * ボックスに数字を均等配置
   */
  protected function fillBoxesByKey(Fpdi $pdf, string $key, string $text, int $boxCount, float $boxWidth): void
  {
    $startX = $this->coord($key, 'x');
    $y = $this->coord($key, 'y');
    $letterSpacing = $this->coordinates[$key]['letterSpacing'] ?? 0;
    $textAlign = $this->coordinates[$key]['textAlign'] ?? 'left';

    $this->fillBoxes($pdf, $startX, $y, $text, $boxCount, $boxWidth, (float)$letterSpacing, $textAlign);
  }

  protected function fillBoxes(Fpdi $pdf, float $startX, float $y, string $text, int $boxCount, float $boxWidth, float $letterSpacing = 0, string $textAlign = 'left'): void
  {
    $chars = preg_split('//u', (string)$text, -1, PREG_SPLIT_NO_EMPTY);

    if ($letterSpacing == 0 && $textAlign === 'left') {
      for ($i = 0; $i < min(count($chars), $boxCount); $i++) {
        $x = $startX + ($i * $boxWidth);
        $pdf->Text($x, $y, $chars[$i]);
      }
      return;
    }

    $totalWidth = 0;
    foreach ($chars as $char) {
      $width = $pdf->GetStringWidth($char);
      $totalWidth += $width + $letterSpacing;
    }
    if ($totalWidth > 0) {
      $totalWidth -= $letterSpacing;
    }

    $alignmentWidth = $boxCount * $boxWidth;

    $x = $startX;
    if ($textAlign === 'center') {
      $x = $startX + ($alignmentWidth - $totalWidth) / 2;
    } elseif ($textAlign === 'right') {
      $x = $startX + ($alignmentWidth - $totalWidth);
    }

    for ($i = 0; $i < min(count($chars), $boxCount); $i++) {
      $pdf->Text($x, $y, $chars[$i]);
      $width = $pdf->GetStringWidth($chars[$i]);
      $x += $width + $letterSpacing;
    }
  }

  /**
   * 施術日をカレンダーに記入
   */
  protected function fillServiceDates(Fpdi $pdf, $records): void
  {
    $letterSpacing = 0; // 追加間隔（現在は使用しない）
    $cellWidth = $this->coord('treatment_days', 'circleSpacing') ?? 6.45; // 円の間隔
    $circleRadius = $this->coord('treatment_days', 'circleRadius') ?? 1.2;
    $innerRadius = $this->coord('treatment_days', 'doubleCircleInnerRadius') ?? 0.4;

    // あんま･マッサージ版：therapy_content_id 18-21のみ描画
    $massageContentIds = [18, 19, 20, 21];

    foreach ($records as $record) {
      // 施術内容があんま･マッサージ関連でない場合はスキップ
      if (!in_array($record->therapy_content_id, $massageContentIds)) {
        continue;
      }
      
      $day = (int)date('d', strtotime($record->date));

      $x = $this->coord('treatment_days', 'x') + ($day - 1) * ($cellWidth + $letterSpacing);
      $y = $this->coord('treatment_days', 'y');

      if ($record->therapy_category == 2) {
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->Ellipse($x, $y, $circleRadius, $circleRadius, 0, 0, 360, 'D');
        $pdf->Ellipse($x, $y, $innerRadius, $innerRadius, 0, 0, 360, 'D');
      } else {
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->Ellipse($x, $y, $circleRadius, $circleRadius, 0, 0, 360, 'D');
      }
    }
  }

  protected function drawTextByKey(Fpdi $pdf, string $key, string $text): void
  {
    // キーが存在しない場合は何もしない
    if (!$this->hasCoord($key)) {
      \Log::warning("描画スキップ: キーが存在しない", ['key' => $key, 'text' => $text]);
      return;
    }

    $x = $this->coord($key, 'x');
    $y = $this->coord($key, 'y');
    
    // デバッグ：座標0,0付近の描画を検出
    if ($x < 5 && $y < 5) {
      \Log::warning("座標0,0付近の描画検出", ['key' => $key, 'x' => $x, 'y' => $y, 'text' => $text]);
    }
    
    $letterSpacing = $this->coordinates[$key]['letterSpacing'] ?? 0;
    $textAlign = $this->coordinates[$key]['textAlign'] ?? 'left';
    $alignmentWidth = $this->coordinates[$key]['alignmentWidth'] ?? 0;

    if ($alignmentWidth <= 0) {
      $alignmentWidth = $pdf->GetPageWidth();
    }

    if (empty($letterSpacing) && $textAlign === 'left') {
      $pdf->Text($x, $y, $text);
      return;
    }

    $this->drawTextWithSpacing($pdf, $x, $y, $text, (float)$letterSpacing, $textAlign, (float)$alignmentWidth);
  }

  /**
   * 楕円をキーで描画
   *
   * @param Fpdi $pdf
   * @param string $key
   * @return void
   */
  protected function drawEllipseByKey(Fpdi $pdf, string $key): void
  {
    // キーが存在しない場合は何もしない
    if (!$this->hasCoord($key)) {
      return;
    }

    $x = $this->coord($key, 'x');
    $y = $this->coord($key, 'y');
    $ellipseWidth = $this->coordinates[$key]['ellipseWidth'] ?? 2.5;
    $ellipseHeight = $this->coordinates[$key]['ellipseHeight'] ?? 2.5;

    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.2);
    $pdf->Ellipse($x, $y, $ellipseWidth, $ellipseHeight, 0, 0, 360, 'D');
  }

  protected function drawTextWithSpacing(Fpdi $pdf, float $startX, float $y, string $text, float $letterSpacing, string $textAlign = 'left', float $alignmentWidth = 0): void
  {
    $chars = preg_split('//u', (string)$text, -1, PREG_SPLIT_NO_EMPTY);

    $totalWidth = 0;
    foreach ($chars as $char) {
      $width = $pdf->GetStringWidth($char);
      $totalWidth += $width + $letterSpacing;
    }
    $totalWidth -= $letterSpacing;

    $x = $startX;
    if ($textAlign === 'center' && $alignmentWidth > 0) {
      $x = $startX + ($alignmentWidth - $totalWidth) / 2;
    } elseif ($textAlign === 'right' && $alignmentWidth > 0) {
      $x = $startX + ($alignmentWidth - $totalWidth);
    }

    foreach ($chars as $char) {
      $pdf->Text($x, $y, $char);
      $width = $pdf->GetStringWidth($char);
      $x += $width + $letterSpacing;
    }
  }

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
   * サンプルデータを生成
   *
   * @param string $serviceYearMonth
   * @return array
   */
  protected function getSampleData(string $serviceYearMonth): array
  {
    // カスタムサンプルデータがあればそれを優先的に使用
    $custom = $this->customSampleData;

    // サンプル利用者情報
    $clinicUser = (object)[
      'id' => 999,
      'last_name' => $custom['last_name'] ?? '佐藤',
      'first_name' => $custom['first_name'] ?? '花子',
      'last_kana' => $custom['last_kana'] ?? 'サトウ',
      'first_kana' => $custom['first_kana'] ?? 'ハナコ',
      'gender' => $custom['gender'] ?? '女',
      'birthday' => $custom['birthdate'] ?? '1960-08-20',
      'postal_code' => '123-4567',
      'address_1' => $custom['address'] ?? '東京都渋谷区渋谷1-2-3',
      'address_2' => '',
      'address_3' => '',
    ];

    // サンプル保険情報
    $insurance = (object)[
      'insurer_number' => $custom['insurer_number'] ?? '87654321',
      'insurer_name' => 'サンプル健康保険組合',
      'insurance_type_1_id' => 1,
      'insurance_type_1' => $custom['insurance_type_1'] ?? '社･国･組',
      'insurance_type_3' => $custom['insurance_type_3'] ?? '本外',
      'expenses_borne_ratio' => $custom['expenses_borne_ratio'] ?? '３割',
      'code_number' => $custom['insurance_symbol'] ?? '54321',
      'account_number' => '09876',
      'insured_number' => $custom['insurance_number'] ?? '0987654321',
      'relationship' => $custom['relationship'] ?? '本人',
    ];

    // サンプル同意書情報
    $consent = (object)[
      'consenting_date' => $custom['consent_date'] ?? date('Y-m-d'),
      'consenting_doctor_name' => $custom['doctor_name'] ?? '田中医師',
      'bill_category' => '継続',
      'outcome' => '継続',
      'work_scope_type' => 'その他',
    ];

    // サンプル施術実績（月の2日、7日、12日、17日、22日、27日）
    $treatmentDays = $custom['treatment_days'] ?? 15;
    $records = collect([
      (object)['date' => $serviceYearMonth . '-02', 'therapy_category' => 1, 'therapy_content_id' => 18],
      (object)['date' => $serviceYearMonth . '-07', 'therapy_category' => 1, 'therapy_content_id' => 18],
      (object)['date' => $serviceYearMonth . '-12', 'therapy_category' => 2, 'therapy_content_id' => 19],
      (object)['date' => $serviceYearMonth . '-17', 'therapy_category' => 1, 'therapy_content_id' => 18],
      (object)['date' => $serviceYearMonth . '-22', 'therapy_category' => 2, 'therapy_content_id' => 19],
      (object)['date' => $serviceYearMonth . '-27', 'therapy_category' => 1, 'therapy_content_id' => 18],
    ]);

    // サンプル施術所情報
    $clinicInfo = (object)[
      'medical_institution_number' => $custom['institution_code'] ?? '7654321',
      'clinic_name' => $custom['clinic_name'] ?? 'サンプルマッサージ治療院',
      'postal_code' => '100-0002',
      'address_1' => $custom['clinic_address'] ?? '東京都港区赤坂1-1-1',
      'address_2' => '',
      'address_3' => '',
      'phone' => $custom['clinic_phone'] ?? '03-9876-5432',
    ];

    return [
      'clinic_user' => $clinicUser,
      'insurance' => $insurance,
      'consent' => $consent,
      'records' => $records,
      'clinic_info' => $clinicInfo,
      'service_year_month' => $serviceYearMonth,
    ];
  }
}
