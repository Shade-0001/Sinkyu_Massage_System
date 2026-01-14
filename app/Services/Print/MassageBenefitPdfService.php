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
      ->leftJoin('illnesses_massage', 'consents_massage.injury_and_illness_name_id', '=', 'illnesses_massage.id')
      ->leftJoin('conditions', 'consents_massage.condition_id', '=', 'conditions.id')
      ->where('consents_massage.clinic_user_id', $clinicUserId)
      ->orderBy('consents_massage.consenting_date', 'desc')
      ->select(
        'consents_massage.*',
        'bill_categories.bill_category',
        'outcomes.outcome',
        'work_scope_types.work_scope_type',
        'illnesses_massage.illness_name',
        'conditions.condition_name'
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

    // 施術料金データ取得（最新のデータ）
    $treatmentFees = DB::table('treatment_fees')
      ->orderBy('created_at', 'desc')
      ->first();

    if (!$treatmentFees) {
      \Log::warning('施術料金データが見つかりません');
    }

    return [
      'clinic_user' => $clinicUser,
      'insurance' => $insurance,
      'consent' => $consent,
      'records' => $records,
      'clinic_info' => $clinicInfo,
      'treatment_fees' => $treatmentFees,
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

    // デバッグ:データソース情報をログ出力
    \Log::info('=== PDF描画開始 ===' . ($this->sampleDataMode ? ' [サンプルデータモード]' : ' [ノーマルモード]'), [
      'sample_data_mode' => $this->sampleDataMode,
      'has_clinic_user' => !empty($clinicUser),
      'has_insurance' => !empty($insurance),
      'has_consent' => !empty($consent),
      'records_count' => $records->count(),
      'has_clinic_info' => !empty($clinicInfo),
      'service_year_month' => $data['service_year_month'],
      'custom_sample_data_keys' => $this->sampleDataMode && $this->customSampleData ? array_keys($this->customSampleData) : []
    ]);

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
    if ($this->sampleDataMode && isset($this->customSampleData['institution_code'])) {
      $pdf->SetFontSize($this->coord('institution_code', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'institution_code', (string)$this->customSampleData['institution_code'], 7, 5.6);
      $pdf->SetFontSize(10);
    } elseif ($clinicInfo && isset($clinicInfo->medical_institution_number)) {
      $pdf->SetFontSize($this->coord('institution_code', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'institution_code', (string)$clinicInfo->medical_institution_number, 7, 5.6);
      $pdf->SetFontSize(10);
    }

    // === 公費負担者番号 ===
    if ($this->sampleDataMode && isset($this->customSampleData['public_funds_payer_number'])) {
      if ($this->customSampleData['public_funds_payer_number']) {
        $pdf->SetFontSize($this->coord('public_funds_payer_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'public_funds_payer_number', (string)$this->customSampleData['public_funds_payer_number']);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->public_funds_payer_code) && $insurance->public_funds_payer_code) {
      $pdf->SetFontSize($this->coord('public_funds_payer_number', 'fontSize'));
      $this->drawTextByKey($pdf, 'public_funds_payer_number', (string)$insurance->public_funds_payer_code);
      $pdf->SetFontSize(10);
    }

    // === 公費受給者番号 ===
    if ($this->sampleDataMode && isset($this->customSampleData['public_funds_recipient_number'])) {
      if ($this->customSampleData['public_funds_recipient_number']) {
        $pdf->SetFontSize($this->coord('public_funds_recipient_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'public_funds_recipient_number', (string)$this->customSampleData['public_funds_recipient_number']);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->public_funds_recipient_code) && $insurance->public_funds_recipient_code) {
      $pdf->SetFontSize($this->coord('public_funds_recipient_number', 'fontSize'));
      $this->drawTextByKey($pdf, 'public_funds_recipient_number', (string)$insurance->public_funds_recipient_code);
      $pdf->SetFontSize(10);
    }

    // === 区市町村番号 ===
    if ($this->sampleDataMode && isset($this->customSampleData['locality_code'])) {
      if ($this->customSampleData['locality_code']) {
        $pdf->SetFontSize($this->coord('locality_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'locality_code', (string)$this->customSampleData['locality_code']);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->locality_code) && $insurance->locality_code) {
      $pdf->SetFontSize($this->coord('locality_code', 'fontSize'));
      $this->drawTextByKey($pdf, 'locality_code', (string)$insurance->locality_code);
      $pdf->SetFontSize(10);
    }

    // === 受給者番号 ===
    if ($this->sampleDataMode && isset($this->customSampleData['recipient_number'])) {
      if ($this->customSampleData['recipient_number']) {
        $pdf->SetFontSize($this->coord('recipient_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'recipient_number', (string)$this->customSampleData['recipient_number']);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->recipient_code) && $insurance->recipient_code) {
      $pdf->SetFontSize($this->coord('recipient_number', 'fontSize'));
      $this->drawTextByKey($pdf, 'recipient_number', (string)$insurance->recipient_code);
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
        } elseif (isset($this->coordinates['birthday_era_taisho']['isSelected']) && $this->coordinates['birthday_era_taisho']['isSelected']) {
          $birthdayEraKey = 'birthday_era_taisho';
        } elseif (isset($this->coordinates['birthday_era_meiji']['isSelected']) && $this->coordinates['birthday_era_meiji']['isSelected']) {
          $birthdayEraKey = 'birthday_era_meiji';
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
        } elseif ($birthJapaneseYear['era'] === '大正') {
          $this->drawEllipseByKey($pdf, 'birthday_era_taisho');
        } elseif ($birthJapaneseYear['era'] === '明治') {
          $this->drawEllipseByKey($pdf, 'birthday_era_meiji');
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

    // === 患者郵便番号・住所・電話番号 ===
    if ($this->sampleDataMode && isset($this->customSampleData['patient_postal_code'])) {
      if ($this->hasCoord('patient_postal_code') && $this->customSampleData['patient_postal_code']) {
        $pdf->SetFontSize($this->coord('patient_postal_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'patient_postal_code', (string)$this->customSampleData['patient_postal_code']);
        $pdf->SetFontSize(10);
      }
    } elseif ($clinicUser && isset($clinicUser->postal_code)) {
      if ($this->hasCoord('patient_postal_code') && $clinicUser->postal_code) {
        $pdf->SetFontSize($this->coord('patient_postal_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'patient_postal_code', (string)$clinicUser->postal_code);
        $pdf->SetFontSize(10);
      }
    }

    if ($this->sampleDataMode && isset($this->customSampleData['patient_address'])) {
      if ($this->hasCoord('patient_address') && $this->customSampleData['patient_address']) {
        $pdf->SetFontSize($this->coord('patient_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'patient_address', (string)$this->customSampleData['patient_address']);
        $pdf->SetFontSize(10);
      }
    } elseif ($clinicUser) {
      $patientAddress = ($clinicUser->address_1 ?? '') . ($clinicUser->address_2 ?? '') . ($clinicUser->address_3 ?? '');
      if ($this->hasCoord('patient_address') && $patientAddress) {
        $pdf->SetFontSize($this->coord('patient_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'patient_address', (string)$patientAddress);
        $pdf->SetFontSize(10);
      }
    }

    if ($this->sampleDataMode && isset($this->customSampleData['patient_phone'])) {
      if ($this->hasCoord('patient_phone') && $this->customSampleData['patient_phone']) {
        $pdf->SetFontSize($this->coord('patient_phone', 'fontSize'));
        $this->drawTextByKey($pdf, 'patient_phone', (string)$this->customSampleData['patient_phone']);
        $pdf->SetFontSize(10);
      }
    } elseif ($clinicUser && isset($clinicUser->phone)) {
      if ($this->hasCoord('patient_phone') && $clinicUser->phone) {
        $pdf->SetFontSize($this->coord('patient_phone', 'fontSize'));
        $this->drawTextByKey($pdf, 'patient_phone', (string)$clinicUser->phone);
        $pdf->SetFontSize(10);
      }
    }

    // === 施術期間 ===
    if ($this->sampleDataMode && isset($this->customSampleData['treatment_start_year'])) {
      // サンプルデータモード：customSampleDataから取得
      $startYear = $this->customSampleData['treatment_start_year'] ?? '';
      $startMonth = $this->customSampleData['treatment_start_month'] ?? '';
      $startDay = $this->customSampleData['treatment_start_day'] ?? '';
      $endYear = $this->customSampleData['treatment_end_year'] ?? '';
      $endMonth = $this->customSampleData['treatment_end_month'] ?? '';
      $endDay = $this->customSampleData['treatment_end_day'] ?? '';
      $treatmentDays = $this->customSampleData['treatment_days'] ?? '';

      // 自：開始日
      if ($startYear && $this->hasCoord('treatment_start_year')) {
        $pdf->SetFontSize($this->coord('treatment_start_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_start_year', (string)$startYear);
      }

      if ($startMonth && $this->hasCoord('treatment_start_month')) {
        $pdf->SetFontSize($this->coord('treatment_start_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_start_month', (string)$startMonth);
      }

      if ($startDay && $this->hasCoord('treatment_start_day')) {
        $pdf->SetFontSize($this->coord('treatment_start_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_start_day', (string)$startDay);
      }

      // 至：終了日
      if ($endYear && $this->hasCoord('treatment_end_year')) {
        $pdf->SetFontSize($this->coord('treatment_end_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_end_year', (string)$endYear);
      }

      if ($endMonth && $this->hasCoord('treatment_end_month')) {
        $pdf->SetFontSize($this->coord('treatment_end_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_end_month', (string)$endMonth);
      }

      if ($endDay && $this->hasCoord('treatment_end_day')) {
        $pdf->SetFontSize($this->coord('treatment_end_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_end_day', (string)$endDay);
      }

      // 実日数
      if ($treatmentDays && $this->hasCoord('treatment_days')) {
        $pdf->SetFontSize($this->coord('treatment_days', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_days', (string)$treatmentDays);
      }

      $pdf->SetFontSize(10);
    } elseif ($records->isNotEmpty()) {
      // 通常モード：実データから取得
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

    // === 発病負傷年月日 ===
    if ($this->sampleDataMode && isset($this->customSampleData['onset_date_year'])) {
      if ($this->hasCoord('onset_date_year')) {
        $pdf->SetFontSize($this->coord('onset_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date_year', (string)$this->customSampleData['onset_date_year']);
      }
      if ($this->hasCoord('onset_date_month')) {
        $pdf->SetFontSize($this->coord('onset_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date_month', (string)$this->customSampleData['onset_date_month']);
      }
      if ($this->hasCoord('onset_date_day')) {
        $pdf->SetFontSize($this->coord('onset_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date_day', (string)$this->customSampleData['onset_date_day']);
      }
      $pdf->SetFontSize(10);
    } elseif ($consent && isset($consent->onset_and_injury_date)) {
      [$onsetYear, $onsetMonth, $onsetDay] = explode('-', $consent->onset_and_injury_date);
      $onsetJapaneseYear = $this->convertToJapaneseYear((int)$onsetYear, (int)$onsetMonth);
      
      if ($this->hasCoord('onset_date_year')) {
        $pdf->SetFontSize($this->coord('onset_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date_year', (string)$onsetJapaneseYear['year']);
      }
      if ($this->hasCoord('onset_date_month')) {
        $pdf->SetFontSize($this->coord('onset_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date_month', (string)(int)$onsetMonth);
      }
      if ($this->hasCoord('onset_date_day')) {
        $pdf->SetFontSize($this->coord('onset_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date_day', (string)(int)$onsetDay);
      }
      $pdf->SetFontSize(10);
    }

    // === 発病負傷の傷病名 ===
    if ($this->sampleDataMode && isset($this->customSampleData['onset_illness_name'])) {
      if ($this->hasCoord('onset_illness_name') && $this->customSampleData['onset_illness_name']) {
        $pdf->SetFontSize($this->coord('onset_illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_illness_name', (string)$this->customSampleData['onset_illness_name']);
        $pdf->SetFontSize(10);
      }
    } elseif ($consent && isset($consent->illness_name)) {
      if ($this->hasCoord('onset_illness_name') && $consent->illness_name) {
        $pdf->SetFontSize($this->coord('onset_illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_illness_name', (string)$consent->illness_name);
        $pdf->SetFontSize(10);
      }
    }

    // === 初療年月日 ===
    if ($this->sampleDataMode && isset($this->customSampleData['first_treatment_year'])) {
      // サンプルデータモード：元号
      if ($this->hasCoord('first_treatment_era') && isset($this->customSampleData['first_treatment_era'])) {
        $pdf->SetFontSize($this->coord('first_treatment_era', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_era', (string)$this->customSampleData['first_treatment_era']);
      }
      if ($this->hasCoord('first_treatment_year')) {
        $pdf->SetFontSize($this->coord('first_treatment_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_year', (string)$this->customSampleData['first_treatment_year']);
      }
      if ($this->hasCoord('first_treatment_month')) {
        $pdf->SetFontSize($this->coord('first_treatment_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_month', (string)$this->customSampleData['first_treatment_month']);
      }
      if ($this->hasCoord('first_treatment_day')) {
        $pdf->SetFontSize($this->coord('first_treatment_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_day', (string)$this->customSampleData['first_treatment_day']);
      }
      $pdf->SetFontSize(10);
    } elseif ($consent && isset($consent->first_care_date)) {
      [$firstYear, $firstMonth, $firstDay] = explode('-', $consent->first_care_date);
      $firstJapaneseYear = $this->convertToJapaneseYear((int)$firstYear, (int)$firstMonth);
      
      // 実データモード：元号（1文字目のみ）
      if ($this->hasCoord('first_treatment_era')) {
        $pdf->SetFontSize($this->coord('first_treatment_era', 'fontSize'));
        $eraFirstChar = mb_substr($firstJapaneseYear['era'], 0, 1);
        $this->drawTextByKey($pdf, 'first_treatment_era', $eraFirstChar);
      }
      if ($this->hasCoord('first_treatment_year')) {
        $pdf->SetFontSize($this->coord('first_treatment_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_year', (string)$firstJapaneseYear['year']);
      }
      if ($this->hasCoord('first_treatment_month')) {
        $pdf->SetFontSize($this->coord('first_treatment_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_month', (string)(int)$firstMonth);
      }
      if ($this->hasCoord('first_treatment_day')) {
        $pdf->SetFontSize($this->coord('first_treatment_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_day', (string)(int)$firstDay);
      }
      $pdf->SetFontSize(10);
    }

    // === 実日数 ===
    if ($this->hasCoord('treatment_day_count')) {
      $dayCount = $this->sampleDataMode && isset($this->customSampleData['treatment_days'])
        ? $this->customSampleData['treatment_days']
        : $records->count();
      
      $pdf->SetFontSize($this->coord('treatment_day_count', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_day_count', (string)$dayCount);
      $pdf->SetFontSize(10);
    }

    // === 請求区分（新規・継続） ===
    if ($this->sampleDataMode) {
      if (isset($this->coordinates['bill_category_new']['isSelected']) && $this->coordinates['bill_category_new']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'bill_category_new');
      } elseif (isset($this->coordinates['bill_category_continued']['isSelected']) && $this->coordinates['bill_category_continued']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'bill_category_continued');
      } elseif (isset($this->customSampleData['bill_category'])) {
        if ($this->customSampleData['bill_category'] === '新規') {
          $this->drawEllipseByKey($pdf, 'bill_category_new');
        } elseif ($this->customSampleData['bill_category'] === '継続') {
          $this->drawEllipseByKey($pdf, 'bill_category_continued');
        }
      }
    } elseif ($consent && isset($consent->bill_category)) {
      if ($consent->bill_category === '新規') {
        $this->drawEllipseByKey($pdf, 'bill_category_new');
      } elseif ($consent->bill_category === '継続') {
        $this->drawEllipseByKey($pdf, 'bill_category_continued');
      }
    }

    // === 転帰 ===
    if ($this->sampleDataMode) {
      if (isset($this->coordinates['outcome_continued']['isSelected']) && $this->coordinates['outcome_continued']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'outcome_continued');
      } elseif (isset($this->coordinates['outcome_cured']['isSelected']) && $this->coordinates['outcome_cured']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'outcome_cured');
      } elseif (isset($this->coordinates['outcome_discontinued']['isSelected']) && $this->coordinates['outcome_discontinued']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'outcome_discontinued');
      } elseif (isset($this->coordinates['outcome_transferred']['isSelected']) && $this->coordinates['outcome_transferred']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'outcome_transferred');
      } elseif (isset($this->customSampleData['outcome'])) {
        $outcomeMap = [
          '継続' => 'outcome_continued',
          '治癒' => 'outcome_cured',
          '中止' => 'outcome_discontinued',
          '転医' => 'outcome_transferred',
        ];
        $key = $outcomeMap[$this->customSampleData['outcome']] ?? null;
        if ($key) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }
    } elseif ($consent && isset($consent->outcome)) {
      $outcomeMap = [
        '継続' => 'outcome_continued',
        '治癒' => 'outcome_cured',
        '中止' => 'outcome_discontinued',
        '転医' => 'outcome_transferred',
      ];
      $key = $outcomeMap[$consent->outcome] ?? null;
      if ($key) {
        $this->drawEllipseByKey($pdf, $key);
      }
    }

    // === 業務上・外・第三者行為 ===
    if ($this->sampleDataMode) {
      if (isset($this->coordinates['work_scope_type_1']['isSelected']) && $this->coordinates['work_scope_type_1']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'work_scope_type_1');
      } elseif (isset($this->coordinates['work_scope_type_2']['isSelected']) && $this->coordinates['work_scope_type_2']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'work_scope_type_2');
      } elseif (isset($this->coordinates['work_scope_type_3']['isSelected']) && $this->coordinates['work_scope_type_3']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'work_scope_type_3');
      } elseif (isset($this->customSampleData['work_scope_type'])) {
        $workScopeMap = [
          '業務上' => 'work_scope_type_1',
          '第三者行為' => 'work_scope_type_2',
          'その他' => 'work_scope_type_3',
        ];
        $key = $workScopeMap[$this->customSampleData['work_scope_type']] ?? null;
        if ($key) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }
    } elseif ($consent && isset($consent->work_scope_type)) {
      $workScopeMap = [
        '業務上' => 'work_scope_type_1',
        '第三者行為' => 'work_scope_type_2',
        'その他' => 'work_scope_type_3',
      ];
      $key = $workScopeMap[$consent->work_scope_type] ?? null;
      if ($key) {
        $this->drawEllipseByKey($pdf, $key);
      }
    }

    // === 発病負傷の原因･経過 ===
    if ($this->sampleDataMode && isset($this->customSampleData['condition'])) {
      if ($this->hasCoord('condition') && $this->customSampleData['condition']) {
        $pdf->SetFontSize($this->coord('condition', 'fontSize'));
        $this->drawTextByKey($pdf, 'condition', (string)$this->customSampleData['condition']);
        $pdf->SetFontSize(10);
      }
    } elseif ($consent && isset($consent->condition_name)) {
      if ($this->hasCoord('condition') && $consent->condition_name) {
        $pdf->SetFontSize($this->coord('condition', 'fontSize'));
        $this->drawTextByKey($pdf, 'condition', (string)$consent->condition_name);
        $pdf->SetFontSize(10);
      }
    }

    // === 施術月 ===
    if ($this->hasCoord('treatment_month')) {
      $treatmentMonth = $this->sampleDataMode && isset($this->customSampleData['treatment_month'])
        ? $this->customSampleData['treatment_month']
        : (int)$month;
      
      $pdf->SetFontSize($this->coord('treatment_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_month', (string)$treatmentMonth);
      $pdf->SetFontSize(10);
    }

    // === 傷病名・症状（マッサージ用） ===
    if ($this->sampleDataMode && isset($this->customSampleData['illness_name_symptom'])) {
      if ($this->hasCoord('illness_name_symptom') && $this->customSampleData['illness_name_symptom']) {
        $pdf->SetFontSize($this->coord('illness_name_symptom', 'fontSize'));
        $this->drawTextByKey($pdf, 'illness_name_symptom', (string)$this->customSampleData['illness_name_symptom']);
        $pdf->SetFontSize(10);
      }
    }

    // === 傷病名（施術内容欄のチェックボックス） ===
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

    // === 摘要 ===
    if ($this->sampleDataMode && isset($this->customSampleData['abstract']) && $this->customSampleData['abstract']) {
      // サンプルデータモード
      \Log::info('摘要描画 [サンプルデータモード]', [
        'has_coord' => $this->hasCoord('abstract'),
        'text_length' => mb_strlen($this->customSampleData['abstract'])
      ]);
      
      if ($this->hasCoord('abstract')) {
        $pdf->SetFontSize($this->coord('abstract', 'fontSize'));
        [$x, $y] = [$this->coord('abstract', 'x'), $this->coord('abstract', 'y')];
        $width = $this->coord('abstract', 'width') ?? 180;
        $lineHeight = $this->coordinates['abstract']['lineHeight'] ?? 5;
        $pdf->SetXY($x, $y);
        $pdf->MultiCell($width, $lineHeight, (string)$this->customSampleData['abstract'], 0, 'L', false, 1);
        $pdf->SetFontSize(10);
      }
    } elseif ($records->isNotEmpty()) {
      // 通常モード:全レコードの摘要を結合（重複排除）
      $abstracts = $records->pluck('abstract')->filter()->unique()->toArray();
      
      \Log::info('摘要描画 [ノーマルモード]', [
        'records_count' => $records->count(),
        'abstracts_count' => count($abstracts),
        'has_coord' => $this->hasCoord('abstract'),
        'abstracts_preview' => !empty($abstracts) ? mb_substr(implode('/', $abstracts), 0, 100) : null
      ]);
      
      if (!empty($abstracts)) {
        // 「。」で区切る（前後に既に「。」がある場合は重複しないように）
        $abstractText = '';
        foreach ($abstracts as $i => $abstract) {
          if ($i > 0) {
            // 前の文字列の末尾と現在の文字列の先頭をチェック
            $lastChar = mb_substr($abstractText, -1);
            $firstChar = mb_substr($abstract, 0, 1);
            if ($lastChar !== '。' && $firstChar !== '。') {
              $abstractText .= '。';
            }
          }
          $abstractText .= $abstract;
        }
        
        if ($this->hasCoord('abstract')) {
          $x = $this->coord('abstract', 'x');
          $y = $this->coord('abstract', 'y');
          $fontSize = $this->coord('abstract', 'fontSize');
          $width = $this->coordinates['abstract']['width'] ?? 180;
          $lineHeight = $this->coordinates['abstract']['lineHeight'] ?? 5;

          $pdf->SetFontSize($fontSize);
          $pdf->SetXY($x, $y);
          $pdf->MultiCell($width, $lineHeight, $abstractText, 0, 'L', false, 1);
          $pdf->SetFontSize(10);
        }
      }
    }

    // === 施術者登録番号 ===
    if ($this->sampleDataMode && isset($this->customSampleData['therapist_registration_number'])) {
      if ($this->hasCoord('therapist_registration_number') && $this->customSampleData['therapist_registration_number']) {
        $pdf->SetFontSize($this->coord('therapist_registration_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'therapist_registration_number', (string)$this->customSampleData['therapist_registration_number']);
        $pdf->SetFontSize(10);
      }
    } else {
      $therapist = DB::table('therapists')->first();
      if ($therapist && isset($therapist->registration_number)) {
        if ($this->hasCoord('therapist_registration_number') && $therapist->registration_number) {
          $pdf->SetFontSize($this->coord('therapist_registration_number', 'fontSize'));
          $this->drawTextByKey($pdf, 'therapist_registration_number', (string)$therapist->registration_number);
          $pdf->SetFontSize(10);
        }
      }
    }

    // === 施術証明年月日の保健所登録区分 ===
    if ($this->sampleDataMode) {
      if (isset($this->coordinates['health_center_registration_1']['isSelected']) && $this->coordinates['health_center_registration_1']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'health_center_registration_1');
      } elseif (isset($this->coordinates['health_center_registration_2']['isSelected']) && $this->coordinates['health_center_registration_2']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'health_center_registration_2');
      } elseif (isset($this->customSampleData['health_center_registration'])) {
        if ($this->customSampleData['health_center_registration'] === '施術所所在地') {
          $this->drawEllipseByKey($pdf, 'health_center_registration_1');
        } elseif ($this->customSampleData['health_center_registration'] === '出張専門施術者住所地') {
          $this->drawEllipseByKey($pdf, 'health_center_registration_2');
        }
      }
    }

    // === 同意記録欄 ===
    // 医師氏名
    if ($this->sampleDataMode && isset($this->customSampleData['consent_record_doctor_name'])) {
      if ($this->hasCoord('consent_record_doctor_name') && $this->customSampleData['consent_record_doctor_name']) {
        $pdf->SetFontSize($this->coord('consent_record_doctor_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_doctor_name', (string)$this->customSampleData['consent_record_doctor_name']);
        $pdf->SetFontSize(10);
      }
    } elseif ($consent && isset($consent->consenting_doctor_name)) {
      if ($this->hasCoord('consent_record_doctor_name') && $consent->consenting_doctor_name) {
        $pdf->SetFontSize($this->coord('consent_record_doctor_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_doctor_name', (string)$consent->consenting_doctor_name);
        $pdf->SetFontSize(10);
      }
    }

    // 同意年月日
    if ($this->sampleDataMode && isset($this->customSampleData['consent_record_date_year'])) {
      if ($this->hasCoord('consent_record_date_year')) {
        $pdf->SetFontSize($this->coord('consent_record_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_date_year', (string)$this->customSampleData['consent_record_date_year']);
      }
      if ($this->hasCoord('consent_record_date_month')) {
        $pdf->SetFontSize($this->coord('consent_record_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_date_month', (string)$this->customSampleData['consent_record_date_month']);
      }
      if ($this->hasCoord('consent_record_date_day')) {
        $pdf->SetFontSize($this->coord('consent_record_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_date_day', (string)$this->customSampleData['consent_record_date_day']);
      }
      $pdf->SetFontSize(10);
    } elseif ($consent && isset($consent->consenting_date)) {
      [$consentYear, $consentMonth, $consentDay] = explode('-', $consent->consenting_date);
      $consentJapaneseYear = $this->convertToJapaneseYear((int)$consentYear, (int)$consentMonth);
      
      if ($this->hasCoord('consent_record_date_year')) {
        $pdf->SetFontSize($this->coord('consent_record_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_date_year', (string)$consentJapaneseYear['year']);
      }
      if ($this->hasCoord('consent_record_date_month')) {
        $pdf->SetFontSize($this->coord('consent_record_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_date_month', (string)(int)$consentMonth);
      }
      if ($this->hasCoord('consent_record_date_day')) {
        $pdf->SetFontSize($this->coord('consent_record_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_date_day', (string)(int)$consentDay);
      }
      $pdf->SetFontSize(10);
    }

    // 同意記録の傷病名（illness_nameを使用）
    if ($this->sampleDataMode && isset($this->customSampleData['consent_record_illness_name'])) {
      if ($this->hasCoord('consent_record_illness_name') && $this->customSampleData['consent_record_illness_name']) {
        $pdf->SetFontSize($this->coord('consent_record_illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_illness_name', (string)$this->customSampleData['consent_record_illness_name']);
        $pdf->SetFontSize(10);
      }
    } elseif ($consent && isset($consent->illness_name)) {
      if ($this->hasCoord('consent_record_illness_name') && $consent->illness_name) {
        $pdf->SetFontSize($this->coord('consent_record_illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_illness_name', (string)$consent->illness_name);
        $pdf->SetFontSize(10);
      }
    }

    // 要加療期間（therapy_periodを使用）
    if ($this->sampleDataMode && isset($this->customSampleData['required_treatment_period'])) {
      if ($this->hasCoord('required_treatment_period') && $this->customSampleData['required_treatment_period']) {
        $pdf->SetFontSize($this->coord('required_treatment_period', 'fontSize'));
        $this->drawTextByKey($pdf, 'required_treatment_period', (string)$this->customSampleData['required_treatment_period']);
        $pdf->SetFontSize(10);
      }
    } elseif ($consent && isset($consent->therapy_period)) {
      if ($this->hasCoord('required_treatment_period') && $consent->therapy_period) {
        $pdf->SetFontSize($this->coord('required_treatment_period', 'fontSize'));
        $this->drawTextByKey($pdf, 'required_treatment_period', (string)$consent->therapy_period);
        $pdf->SetFontSize(10);
      }
    }

    // === 申請欄 ===
    // 申請年月日
    if ($this->sampleDataMode && isset($this->customSampleData['submission_date_year'])) {
      if ($this->hasCoord('submission_date_year')) {
        $pdf->SetFontSize($this->coord('submission_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'submission_date_year', (string)$this->customSampleData['submission_date_year']);
      }
      if ($this->hasCoord('submission_date_month')) {
        $pdf->SetFontSize($this->coord('submission_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'submission_date_month', (string)$this->customSampleData['submission_date_month']);
      }
      if ($this->hasCoord('submission_date_day')) {
        $pdf->SetFontSize($this->coord('submission_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'submission_date_day', (string)$this->customSampleData['submission_date_day']);
      }
      $pdf->SetFontSize(10);
    } else {
      $today = date('Y-m-d');
      [$subYear, $subMonth, $subDay] = explode('-', $today);
      $subJapaneseYear = $this->convertToJapaneseYear((int)$subYear, (int)$subMonth);
      
      if ($this->hasCoord('submission_date_year')) {
        $pdf->SetFontSize($this->coord('submission_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'submission_date_year', (string)$subJapaneseYear['year']);
      }
      if ($this->hasCoord('submission_date_month')) {
        $pdf->SetFontSize($this->coord('submission_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'submission_date_month', (string)(int)$subMonth);
      }
      if ($this->hasCoord('submission_date_day')) {
        $pdf->SetFontSize($this->coord('submission_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'submission_date_day', (string)(int)$subDay);
      }
      $pdf->SetFontSize(10);
    }

    // 保険者名称
    if ($this->sampleDataMode && isset($this->customSampleData['insurer_name'])) {
      if ($this->hasCoord('insurer_name') && $this->customSampleData['insurer_name']) {
        $pdf->SetFontSize($this->coord('insurer_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'insurer_name', (string)$this->customSampleData['insurer_name']);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->insurer_name)) {
      if ($this->hasCoord('insurer_name') && $insurance->insurer_name) {
        $pdf->SetFontSize($this->coord('insurer_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'insurer_name', (string)$insurance->insurer_name);
        $pdf->SetFontSize(10);
      }
    }

    // === 支払機関欄 ===
    \Log::info('支払機関欄描画', [
      'sample_data_mode' => $this->sampleDataMode,
      'has_clinic_info' => !empty($clinicInfo)
    ]);
    
    // 支払区分（振込、銀行送金、郵便局送金、当地払）
    if ($this->sampleDataMode) {
      if (isset($this->coordinates['payment_category_furikomi']['isSelected']) && $this->coordinates['payment_category_furikomi']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'payment_category_furikomi');
      } elseif (isset($this->coordinates['payment_category_bank_transfer']['isSelected']) && $this->coordinates['payment_category_bank_transfer']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'payment_category_bank_transfer');
      } elseif (isset($this->coordinates['payment_category_post_transfer']['isSelected']) && $this->coordinates['payment_category_post_transfer']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'payment_category_post_transfer');
      } elseif (isset($this->coordinates['payment_category_local_payment']['isSelected']) && $this->coordinates['payment_category_local_payment']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'payment_category_local_payment');
      } elseif (isset($this->customSampleData['payment_category'])) {
        $paymentCategoryMap = [
          '振込' => 'payment_category_furikomi',
          '銀行送金' => 'payment_category_bank_transfer',
          '郵便局送金' => 'payment_category_post_transfer',
          '当地払' => 'payment_category_local_payment',
        ];
        $key = $paymentCategoryMap[$this->customSampleData['payment_category']] ?? null;
        if ($key) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }
    } else {
      // ノーマルモード：支払区分は「振込」で固定
      if ($this->hasCoord('payment_category_furikomi')) {
        $this->drawEllipseByKey($pdf, 'payment_category_furikomi');
      }
    }

    // 預金種別（普通、当座、通知、別段）
    if ($this->sampleDataMode) {
      if (isset($this->coordinates['deposit_type_ordinary']['isSelected']) && $this->coordinates['deposit_type_ordinary']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'deposit_type_ordinary');
      } elseif (isset($this->coordinates['deposit_type_current']['isSelected']) && $this->coordinates['deposit_type_current']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'deposit_type_current');
      } elseif (isset($this->coordinates['deposit_type_notice']['isSelected']) && $this->coordinates['deposit_type_notice']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'deposit_type_notice');
      } elseif (isset($this->coordinates['deposit_type_betsudan']['isSelected']) && $this->coordinates['deposit_type_betsudan']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'deposit_type_betsudan');
      } elseif (isset($this->customSampleData['deposit_type'])) {
        $depositTypeMap = [
          '普通' => 'deposit_type_ordinary',
          '当座' => 'deposit_type_current',
          '通知' => 'deposit_type_notice',
          '別段' => 'deposit_type_betsudan',
        ];
        $key = $depositTypeMap[$this->customSampleData['deposit_type']] ?? null;
        if ($key) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }
    } else {
      // ノーマルモード：clinic_info.bank_account_typeを参照
      if ($clinicInfo && isset($clinicInfo->bank_account_type)) {
        $accountType = $clinicInfo->bank_account_type;
        if ($accountType === '普通' && $this->hasCoord('deposit_type_ordinary')) {
          $this->drawEllipseByKey($pdf, 'deposit_type_ordinary');
        } elseif ($accountType === '当座' && $this->hasCoord('deposit_type_current')) {
          $this->drawEllipseByKey($pdf, 'deposit_type_current');
        } elseif ($accountType === '通知' && $this->hasCoord('deposit_type_notice')) {
          $this->drawEllipseByKey($pdf, 'deposit_type_notice');
        } elseif ($accountType === '別段' && $this->hasCoord('deposit_type_betsudan')) {
          $this->drawEllipseByKey($pdf, 'deposit_type_betsudan');
        }
      }
    }

    // 金融機関情報
    if ($this->sampleDataMode && isset($this->customSampleData['financial_institution_name1'])) {
      if ($this->hasCoord('financial_institution_name_1') && $this->customSampleData['financial_institution_name1']) {
        $pdf->SetFontSize($this->coord('financial_institution_name_1', 'fontSize'));
        $this->drawTextByKey($pdf, 'financial_institution_name_1', (string)$this->customSampleData['financial_institution_name1']);
        $pdf->SetFontSize(10);
      }
    } elseif (!$this->sampleDataMode && $clinicInfo && isset($clinicInfo->bank_name)) {
      // ノーマルモード：clinic_info.bank_nameを参照
      if ($this->hasCoord('financial_institution_name_1')) {
        $pdf->SetFontSize($this->coord('financial_institution_name_1', 'fontSize'));
        $bankName = (string)$clinicInfo->bank_name;
        // 末尾の「銀行」「金庫」「農協」を除去
        $bankName = preg_replace('/(銀行|金庫|農協)$/', '', $bankName);
        $this->drawTextByKey($pdf, 'financial_institution_name_1', $bankName);
        $pdf->SetFontSize(10);
      }
    }

    if ($this->sampleDataMode && isset($this->customSampleData['financial_institution_name2'])) {
      if ($this->hasCoord('financial_institution_name_2') && $this->customSampleData['financial_institution_name2']) {
        $pdf->SetFontSize($this->coord('financial_institution_name_2', 'fontSize'));
        $this->drawTextByKey($pdf, 'financial_institution_name_2', (string)$this->customSampleData['financial_institution_name2']);
        $pdf->SetFontSize(10);
      }
    } elseif (!$this->sampleDataMode && $clinicInfo && isset($clinicInfo->bank_branch_name)) {
      // ノーマルモード：clinic_info.bank_branch_nameを参照
      if ($this->hasCoord('financial_institution_name_2')) {
        $pdf->SetFontSize($this->coord('financial_institution_name_2', 'fontSize'));
        $branchName = (string)$clinicInfo->bank_branch_name;
        // 末尾の「本店」「支店」「出張所」を除去
        $branchName = preg_replace('/(本店|支店|出張所)$/', '', $branchName);
        $this->drawTextByKey($pdf, 'financial_institution_name_2', $branchName);
        $pdf->SetFontSize(10);
      }
    }

    // 金融機関種別（銀行、金庫、農協）
    if ($this->sampleDataMode) {
      if (isset($this->coordinates['financial_institution_type_bank']['isSelected']) && $this->coordinates['financial_institution_type_bank']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'financial_institution_type_bank');
      } elseif (isset($this->coordinates['financial_institution_type_kinko']['isSelected']) && $this->coordinates['financial_institution_type_kinko']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'financial_institution_type_kinko');
      } elseif (isset($this->coordinates['financial_institution_type_nokyo']['isSelected']) && $this->coordinates['financial_institution_type_nokyo']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'financial_institution_type_nokyo');
      } elseif (isset($this->customSampleData['financial_institution_type'])) {
        $fiTypeMap = [
          '銀行' => 'financial_institution_type_bank',
          '金庫' => 'financial_institution_type_kinko',
          '農協' => 'financial_institution_type_nokyo',
        ];
        $key = $fiTypeMap[$this->customSampleData['financial_institution_type']] ?? null;
        if ($key) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }
    } else {
      // ノーマルモード：clinic_info.bank_nameの末尾から推測
      if ($clinicInfo && isset($clinicInfo->bank_name)) {
        $bankName = $clinicInfo->bank_name;
        if (str_ends_with($bankName, '銀行') && $this->hasCoord('financial_institution_type_bank')) {
          $this->drawEllipseByKey($pdf, 'financial_institution_type_bank');
        } elseif (str_ends_with($bankName, '金庫') && $this->hasCoord('financial_institution_type_kinko')) {
          $this->drawEllipseByKey($pdf, 'financial_institution_type_kinko');
        } elseif (str_ends_with($bankName, '農協') && $this->hasCoord('financial_institution_type_nokyo')) {
          $this->drawEllipseByKey($pdf, 'financial_institution_type_nokyo');
        }
      }
    }

    // 支店種別（本店、支店、出張所）
    if ($this->sampleDataMode) {
      if (isset($this->coordinates['branch_type_honten']['isSelected']) && $this->coordinates['branch_type_honten']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'branch_type_honten');
      } elseif (isset($this->coordinates['branch_type_shiten']['isSelected']) && $this->coordinates['branch_type_shiten']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'branch_type_shiten');
      } elseif (isset($this->coordinates['branch_type_shucchoujo']['isSelected']) && $this->coordinates['branch_type_shucchoujo']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'branch_type_shucchoujo');
      } elseif (isset($this->customSampleData['branch_type'])) {
        $branchTypeMap = [
          '本店' => 'branch_type_honten',
          '支店' => 'branch_type_shiten',
          '出張所' => 'branch_type_shucchoujo',
        ];
        $key = $branchTypeMap[$this->customSampleData['branch_type']] ?? null;
        if ($key) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }
    } else {
      // ノーマルモード：clinic_info.bank_branch_nameの末尾から推測
      if ($clinicInfo && isset($clinicInfo->bank_branch_name)) {
        $branchName = $clinicInfo->bank_branch_name;
        if (str_ends_with($branchName, '本店') && $this->hasCoord('branch_type_honten')) {
          $this->drawEllipseByKey($pdf, 'branch_type_honten');
        } elseif (str_ends_with($branchName, '支店') && $this->hasCoord('branch_type_shiten')) {
          $this->drawEllipseByKey($pdf, 'branch_type_shiten');
        } elseif (str_ends_with($branchName, '出張所') && $this->hasCoord('branch_type_shucchoujo')) {
          $this->drawEllipseByKey($pdf, 'branch_type_shucchoujo');
        }
      }
    }

    // 口座名義人
    if ($this->sampleDataMode && isset($this->customSampleData['bank_account_holder_kana'])) {
      if ($this->hasCoord('bank_account_holder_kana') && $this->customSampleData['bank_account_holder_kana']) {
        $pdf->SetFontSize($this->coord('bank_account_holder_kana', 'fontSize'));
        $this->drawTextByKey($pdf, 'bank_account_holder_kana', (string)$this->customSampleData['bank_account_holder_kana']);
        $pdf->SetFontSize(10);
      }
    } elseif (!$this->sampleDataMode && $clinicInfo && isset($clinicInfo->bank_account_name_kana)) {
      // ノーマルモード：clinic_info.bank_account_name_kanaを参照
      if ($this->hasCoord('bank_account_holder_kana')) {
        $pdf->SetFontSize($this->coord('bank_account_holder_kana', 'fontSize'));
        $this->drawTextByKey($pdf, 'bank_account_holder_kana', (string)$clinicInfo->bank_account_name_kana);
        $pdf->SetFontSize(10);
      }
    }

    // 口座番号
    if ($this->sampleDataMode && isset($this->customSampleData['bank_account_number'])) {
      if ($this->hasCoord('bank_account_number') && $this->customSampleData['bank_account_number']) {
        $pdf->SetFontSize($this->coord('bank_account_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'bank_account_number', (string)$this->customSampleData['bank_account_number']);
        $pdf->SetFontSize(10);
      }
    } elseif (!$this->sampleDataMode && $clinicInfo && isset($clinicInfo->bank_account_number)) {
      // ノーマルモード：clinic_info.bank_account_numberを参照
      if ($this->hasCoord('bank_account_number')) {
        $pdf->SetFontSize($this->coord('bank_account_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'bank_account_number', (string)$clinicInfo->bank_account_number);
        $pdf->SetFontSize(10);
      }
    } elseif ($this->hasCoord('account_number')) {
      // 旧フィールド名への互換対応
      if ($this->sampleDataMode && isset($this->customSampleData['account_number'])) {
        $pdf->SetFontSize($this->coord('account_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'account_number', (string)$this->customSampleData['account_number']);
        $pdf->SetFontSize(10);
      }
    }

    // === 委任欄 ===
    // 委任年月日
    if ($this->sampleDataMode && isset($this->customSampleData['signature_date_year'])) {
      if ($this->hasCoord('signature_date_year')) {
        $pdf->SetFontSize($this->coord('signature_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'signature_date_year', (string)$this->customSampleData['signature_date_year']);
      }
      if ($this->hasCoord('signature_date_month')) {
        $pdf->SetFontSize($this->coord('signature_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'signature_date_month', (string)$this->customSampleData['signature_date_month']);
      }
      if ($this->hasCoord('signature_date_day')) {
        $pdf->SetFontSize($this->coord('signature_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'signature_date_day', (string)$this->customSampleData['signature_date_day']);
      }
      $pdf->SetFontSize(10);
    }

    // 代理人郵便番号・住所・氏名（既に上で実装済み、agent_postal_code, agent_address, agent_name）

    // 一時保険者名称
    if ($this->sampleDataMode && isset($this->customSampleData['temporary_insurer_name'])) {
      if ($this->hasCoord('temporary_insurer_name') && $this->customSampleData['temporary_insurer_name']) {
        $pdf->SetFontSize($this->coord('temporary_insurer_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'temporary_insurer_name', (string)$this->customSampleData['temporary_insurer_name']);
        $pdf->SetFontSize(10);
      }
    }

    // === 施術料金 ===
    $this->fillTreatmentFees($pdf, $data);

    // === 施術所情報 ===
    if ($clinicInfo) {
      // 施術証明年月日
      if ($this->sampleDataMode && isset($this->customSampleData['clinic_date_year'])) {
        $pdf->SetFontSize($this->coord('clinic_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_date_year', (string)$this->customSampleData['clinic_date_year']);

        $pdf->SetFontSize($this->coord('clinic_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_date_month', (string)($this->customSampleData['clinic_date_month'] ?? ''));

        $pdf->SetFontSize($this->coord('clinic_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_date_day', (string)($this->customSampleData['clinic_date_day'] ?? ''));
      } else {
        $submissionParts = explode('-', $submissionDate);
        $submissionJapaneseYear = $this->convertToJapaneseYear((int)$submissionParts[0], (int)$submissionParts[1]);

        $pdf->SetFontSize($this->coord('clinic_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_date_year', (string)$submissionJapaneseYear['year']);

        $pdf->SetFontSize($this->coord('clinic_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_date_month', (string)(int)$submissionParts[1]);

        $pdf->SetFontSize($this->coord('clinic_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_date_day', (string)(int)$submissionParts[2]);
      }

      $pdf->SetFontSize(10);

      // 施術所郵便番号
      if ($this->hasCoord('clinic_postal_code')) {
        $pdf->SetFontSize($this->coord('clinic_postal_code', 'fontSize'));
        $clinicPostalCode = $this->sampleDataMode && isset($this->customSampleData['clinic_postal_code'])
          ? $this->customSampleData['clinic_postal_code']
          : ($clinicInfo->postal_code ?? '');
        // ハイフンの前後に半角スペースを追加
        $formattedPostalCode = preg_replace('/(\d{3})-?(\d{4})/', '$1 - $2', (string)$clinicPostalCode);
        $this->drawTextByKey($pdf, 'clinic_postal_code', '〒 ' . $formattedPostalCode);
      }

      // 施術所住所
      if ($this->sampleDataMode && isset($this->customSampleData['clinic_address'])) {
        $clinicAddress = $this->customSampleData['clinic_address'];
      } else {
        $clinicAddress = ($clinicInfo->address_1 ?? '') .
                         ($clinicInfo->address_2 ?? '') .
                         ($clinicInfo->address_3 ?? '');
      }
      $pdf->SetFontSize($this->coord('clinic_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_address', (string)$clinicAddress);
      $pdf->SetFontSize(10);

      // 施術所名称
      $pdf->SetFontSize($this->coord('clinic_name', 'fontSize'));
      $clinicName = $this->sampleDataMode && isset($this->customSampleData['clinic_name'])
        ? $this->customSampleData['clinic_name']
        : ($clinicInfo->clinic_name ?? '');
      $this->drawTextByKey($pdf, 'clinic_name', (string)$clinicName);
      $pdf->SetFontSize(10);

      // 施術管理者氏名（施術者情報から取得）
      if ($this->sampleDataMode && isset($this->customSampleData['clinic_manager'])) {
        $therapistName = $this->customSampleData['clinic_manager'];
        $pdf->SetFontSize($this->coord('clinic_manager', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_manager', (string)$therapistName);
        $pdf->SetFontSize(10);
      } else {
        $therapist = DB::table('therapists')->first();
        if ($therapist) {
          $therapistName = ($therapist->last_name ?? '') . ' ' . ($therapist->first_name ?? '');
          $pdf->SetFontSize($this->coord('clinic_manager', 'fontSize'));
          $this->drawTextByKey($pdf, 'clinic_manager', (string)$therapistName);
          $pdf->SetFontSize(10);
        }
      }

      // 施術所電話番号
      $pdf->SetFontSize($this->coord('clinic_phone', 'fontSize'));
      $clinicPhone = $this->sampleDataMode && isset($this->customSampleData['clinic_phone'])
        ? $this->customSampleData['clinic_phone']
        : ($clinicInfo->phone ?? '');
      $this->drawTextByKey($pdf, 'clinic_phone', (string)$clinicPhone);
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
    
    // デバッグ：描画成功ログ
    \Log::info("テキスト描画", [
      'key' => $key,
      'x' => $x,
      'y' => $y,
      'text' => mb_strlen($text) > 50 ? mb_substr($text, 0, 50) . '...' : $text,
      'length' => mb_strlen($text)
    ]);
    
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
      \Log::warning("楕円描画スキップ: キーが存在しない", ['key' => $key]);
      return;
    }

    // ellipseX/ellipseY を優先、存在しない場合は x/y を使用
    $x = $this->coordinates[$key]['ellipseX'] ?? $this->coordinates[$key]['x'] ?? 0;
    $y = $this->coordinates[$key]['ellipseY'] ?? $this->coordinates[$key]['y'] ?? 0;
    $ellipseWidth = $this->coordinates[$key]['ellipseWidth'] ?? 2.5;
    $ellipseHeight = $this->coordinates[$key]['ellipseHeight'] ?? 2.5;
    $lineWidth = $this->coordinates[$key]['lineWidth'] ?? 0.5;

    // デバッグ：楕円描画成功ログ
    \Log::info("楕円描画", [
      'key' => $key,
      'x' => $x,
      'y' => $y,
      'width' => $ellipseWidth,
      'height' => $ellipseHeight
    ]);

    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth($lineWidth);
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

  /**
   * 施術料金情報を印字
   *
   * @param Fpdi $pdf
   * @param array $data
   * @return void
   */
  protected function fillTreatmentFees(Fpdi $pdf, array $data): void
  {
    $treatmentFees = $data['treatment_fees'] ?? null;
    $records = $data['records'];
    $insurance = $data['insurance'];

    // デバッグ:施術料金描画開始
    \Log::info('=== 施術料金描画開始 ===', [
      'sample_data_mode' => $this->sampleDataMode,
      'has_treatment_fees' => !empty($treatmentFees),
      'has_custom_sample_data' => $this->sampleDataMode && !empty($this->customSampleData),
      'records_count' => $records->count()
    ]);

    // サンプルデータモードでカスタムサンプルデータがある場合は直接使用
    if ($this->sampleDataMode && $this->customSampleData) {
      $custom = $this->customSampleData;

      // マッサージ料金（躯幹）
      if (isset($custom['fee_massage_trunk_unit']) && $this->hasCoord('fee_massage_trunk_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_trunk_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_trunk_unit', (string)$custom['fee_massage_trunk_unit']);
      }
      if (isset($custom['fee_massage_trunk_count']) && $this->hasCoord('fee_massage_trunk_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_trunk_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_trunk_count', (string)$custom['fee_massage_trunk_count']);
      }
      if (isset($custom['fee_massage_trunk_total']) && $this->hasCoord('fee_massage_trunk_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_trunk_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_trunk_total', (string)$custom['fee_massage_trunk_total']);
      }

      // マッサージ料金（右上肢）
      if (isset($custom['fee_massage_upper_limb_r_unit']) && $this->hasCoord('fee_massage_upper_limb_r_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_r_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_unit', (string)$custom['fee_massage_upper_limb_r_unit']);
      }
      if (isset($custom['fee_massage_upper_limb_r_count']) && $this->hasCoord('fee_massage_upper_limb_r_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_r_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_count', (string)$custom['fee_massage_upper_limb_r_count']);
      }
      if (isset($custom['fee_massage_upper_limb_r_total']) && $this->hasCoord('fee_massage_upper_limb_r_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_r_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_total', (string)$custom['fee_massage_upper_limb_r_total']);
      }

      // マッサージ料金（左上肢）
      if (isset($custom['fee_massage_upper_limb_l_unit']) && $this->hasCoord('fee_massage_upper_limb_l_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_l_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_unit', (string)$custom['fee_massage_upper_limb_l_unit']);
      }
      if (isset($custom['fee_massage_upper_limb_l_count']) && $this->hasCoord('fee_massage_upper_limb_l_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_l_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_count', (string)$custom['fee_massage_upper_limb_l_count']);
      }
      if (isset($custom['fee_massage_upper_limb_l_total']) && $this->hasCoord('fee_massage_upper_limb_l_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_l_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_total', (string)$custom['fee_massage_upper_limb_l_total']);
      }

      // マッサージ料金（右下肢）
      if (isset($custom['fee_massage_lower_limb_r_unit']) && $this->hasCoord('fee_massage_lower_limb_r_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_r_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_unit', (string)$custom['fee_massage_lower_limb_r_unit']);
      }
      if (isset($custom['fee_massage_lower_limb_r_count']) && $this->hasCoord('fee_massage_lower_limb_r_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_r_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_count', (string)$custom['fee_massage_lower_limb_r_count']);
      }
      if (isset($custom['fee_massage_lower_limb_r_total']) && $this->hasCoord('fee_massage_lower_limb_r_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_r_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_total', (string)$custom['fee_massage_lower_limb_r_total']);
      }

      // マッサージ料金（左下肢）
      if (isset($custom['fee_massage_lower_limb_l_unit']) && $this->hasCoord('fee_massage_lower_limb_l_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_l_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_unit', (string)$custom['fee_massage_lower_limb_l_unit']);
      }
      if (isset($custom['fee_massage_lower_limb_l_count']) && $this->hasCoord('fee_massage_lower_limb_l_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_l_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_count', (string)$custom['fee_massage_lower_limb_l_count']);
      }
      if (isset($custom['fee_massage_lower_limb_l_total']) && $this->hasCoord('fee_massage_lower_limb_l_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_l_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_total', (string)$custom['fee_massage_lower_limb_l_total']);
      }

      // 変形徒手矯正術
      if (isset($custom['fee_manual_correction_unit']) && $this->hasCoord('fee_manual_correction_unit')) {
        $pdf->SetFontSize($this->coord('fee_manual_correction_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_manual_correction_unit', (string)$custom['fee_manual_correction_unit']);
      }
      if (isset($custom['fee_manual_correction_count']) && $this->hasCoord('fee_manual_correction_count')) {
        $pdf->SetFontSize($this->coord('fee_manual_correction_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_manual_correction_count', (string)$custom['fee_manual_correction_count']);
      }
      if (isset($custom['fee_manual_correction_total']) && $this->hasCoord('fee_manual_correction_total')) {
        $pdf->SetFontSize($this->coord('fee_manual_correction_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_manual_correction_total', (string)$custom['fee_manual_correction_total']);
      }

      // 温罨法
      if (isset($custom['fee_fomentation_unit']) && $this->hasCoord('fee_fomentation_unit')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_unit', (string)$custom['fee_fomentation_unit']);
      }
      if (isset($custom['fee_fomentation_count']) && $this->hasCoord('fee_fomentation_count')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_count', (string)$custom['fee_fomentation_count']);
      }
      if (isset($custom['fee_fomentation_total']) && $this->hasCoord('fee_fomentation_total')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_total', (string)$custom['fee_fomentation_total']);
      }

      // 温罨法・電光線器具
      if (isset($custom['fee_fomentation_electric_light_unit']) && $this->hasCoord('fee_fomentation_electric_light_unit')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_electric_light_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_unit', (string)$custom['fee_fomentation_electric_light_unit']);
      }
      if (isset($custom['fee_fomentation_electric_light_count']) && $this->hasCoord('fee_fomentation_electric_light_count')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_electric_light_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_count', (string)$custom['fee_fomentation_electric_light_count']);
      }
      if (isset($custom['fee_fomentation_electric_light_total']) && $this->hasCoord('fee_fomentation_electric_light_total')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_electric_light_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_total', (string)$custom['fee_fomentation_electric_light_total']);
      }

      // 往療料
      if (isset($custom['fee_housecall_unit']) && $this->hasCoord('fee_housecall_unit')) {
        $pdf->SetFontSize($this->coord('fee_housecall_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_unit', (string)$custom['fee_housecall_unit']);
      }
      if (isset($custom['fee_housecall_count']) && $this->hasCoord('fee_housecall_count')) {
        $pdf->SetFontSize($this->coord('fee_housecall_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_count', (string)$custom['fee_housecall_count']);
      }
      if (isset($custom['fee_housecall_total']) && $this->hasCoord('fee_housecall_total')) {
        $pdf->SetFontSize($this->coord('fee_housecall_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_total', (string)$custom['fee_housecall_total']);
      }

      // 往療料4km超
      if (isset($custom['fee_housecall_additional_unit']) && $this->hasCoord('fee_housecall_additional_unit')) {
        $pdf->SetFontSize($this->coord('fee_housecall_additional_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_additional_unit', (string)$custom['fee_housecall_additional_unit']);
      }
      if (isset($custom['fee_housecall_additional_count']) && $this->hasCoord('fee_housecall_additional_count')) {
        $pdf->SetFontSize($this->coord('fee_housecall_additional_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_additional_count', (string)$custom['fee_housecall_additional_count']);
      }
      if (isset($custom['fee_housecall_additional_total']) && $this->hasCoord('fee_housecall_additional_total')) {
        $pdf->SetFontSize($this->coord('fee_housecall_additional_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_additional_total', (string)$custom['fee_housecall_additional_total']);
      }

      // 施術報告書交付料
      if (isset($custom['fee_previous_payment_unit']) && $this->hasCoord('fee_previous_payment_unit')) {
        $pdf->SetFontSize($this->coord('fee_previous_payment_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_previous_payment_unit', (string)$custom['fee_previous_payment_unit']);
      }
      if (isset($custom['fee_previous_payment_count']) && $this->hasCoord('fee_previous_payment_count')) {
        $pdf->SetFontSize($this->coord('fee_previous_payment_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_previous_payment_count', (string)$custom['fee_previous_payment_count']);
      }
      if (isset($custom['fee_previous_payment_total']) && $this->hasCoord('fee_previous_payment_total')) {
        $pdf->SetFontSize($this->coord('fee_previous_payment_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_previous_payment_total', (string)$custom['fee_previous_payment_total']);
      }

      // 合計
      if (isset($custom['fee_subtotal']) && $this->hasCoord('fee_subtotal')) {
        $pdf->SetFontSize($this->coord('fee_subtotal', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_subtotal', (string)$custom['fee_subtotal']);
      }

      // 一部負担金
      if (isset($custom['fee_partial_payment']) && $this->hasCoord('fee_partial_payment')) {
        $pdf->SetFontSize($this->coord('fee_partial_payment', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_partial_payment', (string)$custom['fee_partial_payment']);
      }

      // 請求額
      if (isset($custom['fee_total_claim']) && $this->hasCoord('fee_total_claim')) {
        $pdf->SetFontSize($this->coord('fee_total_claim', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_total_claim', (string)$custom['fee_total_claim']);
      }

      $pdf->SetFontSize(10);
      return;
    }

    // 通常モード:治療費データが存在しない場合は警告
    if (!$treatmentFees) {
      \Log::warning('施術料金データがありません - 描画スキップ');
      return;
    }

    // デバッグ:通常モードで施術料金データあり
    \Log::info('施術料金データ取得成功 - 通常モード描画処理', [
      'treatment_fees_id' => $treatmentFees->id ?? null
    ]);

    // 通常モード:施術実績から料金を計算（簡易版 - 実際のビジネスロジックに応じて調整が必要）
    // TODO: 実際の料金計算ロジックを実装
    // 現状はプレースホルダーとして基本料金を表示
    
    $pdf->SetFontSize(10);
  }
}

