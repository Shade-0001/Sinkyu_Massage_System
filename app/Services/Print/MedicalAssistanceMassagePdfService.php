<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * あんま・マッサージ医療助成費支給申請書PDF生成サービス
 */
class MedicalAssistanceMassagePdfService extends BasePdfService
{
  /**
   * デフォルト座標ファイルパスを取得
   */
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/medical_assistance_massage_coordinates.json');
  }

  /**
   * デフォルト座標を取得
   */
  protected function getDefaultCoordinates(): array
  {
    // JSONファイルと同じ構造でデフォルト値を返す
    $configPath = $this->getDefaultCoordinatesPath();
    if (file_exists($configPath)) {
      $json = file_get_contents($configPath);
      return json_decode($json, true);
    }
    // フォールバック：基本的な座標のみを返す
    return [
      'title_year_era' => ['x' => 0, 'y' => 0, 'fontSize' => 10, 'letterSpacing' => 0, 'label' => 'タイトル（年・元号）'],
      'title_year_number' => ['x' => 0, 'y' => 0, 'fontSize' => 10, 'letterSpacing' => 0, 'label' => 'タイトル（年・数字）'],
    ];
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



    // 同意医師情報取得
    $doctor = null;
    if ($consent && $consent->consenting_doctor_name) {
      // スペース（半角・全角）で分割して姓名を取得
      $nameParts = preg_split('/[\s　]+/u', trim($consent->consenting_doctor_name), 2);
      
      if (count($nameParts) === 2) {
        $doctor = DB::table('doctors')
          ->where('last_name', $nameParts[0])
          ->where('first_name', $nameParts[1])
          ->first();
      }
    }

    // 施術実績取得（対象年月）
    $records = DB::table('records')
      ->where('clinic_user_id', $clinicUserId)
      ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$serviceYearMonth])
      ->orderBy('date')
      ->get();



    // 施術所情報取得
    $clinicInfo = DB::table('clinic_info')->first();


    // 施術料金データ取得（最新のデータ）
    $treatmentFees = DB::table('treatment_fees')
      ->orderBy('created_at', 'desc')
      ->first();



    return [
      'clinic_user' => $clinicUser,
      'insurance' => $insurance,
      'consent' => $consent,
      'doctor' => $doctor,
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
    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/acupuncture_and_massage/医療助成費支給申請書（あんま･マッサージ）.pdf');

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
    $doctor = $data['doctor'] ?? null;
    $records = $data['records'];
    $clinicInfo = $data['clinic_info'];

    $fullName = ($clinicUser->last_name ?? '') . ' ' . ($clinicUser->first_name ?? '');
    list($year, $month) = explode('-', $data['service_year_month']);
    $japaneseYear = $this->convertToJapaneseYear($year, $month);

    $this->fillTitleYearMonth($pdf, $japaneseYear, (int)$month);
    $this->fillInstitutionAndPublicFunds($pdf, $clinicInfo, $insurance);
    $this->fillInsuranceType($pdf, $insurance);
    $this->fillPartialPaymentEllipse($pdf, $insurance);
    $this->fillInsuranceInfoSection($pdf, $insurance);
    $this->fillPatientBasicInfo($pdf, $clinicUser, $insurance, $fullName);
    $this->fillPatientBirthday($pdf, $clinicUser);
    $this->fillPatientAddressInfo($pdf, $clinicUser);
    $this->fillTreatmentPeriodSection($pdf, $records);
    $this->fillDiseaseAndSymptoms($pdf, $consent);
    $this->fillOnsetInfo($pdf, $consent);
    $this->fillFirstTreatmentDate($pdf, $records);
    $this->fillTreatmentDayCount($pdf, $records);
    $this->fillBillCategorySection($pdf, $consent);
    $this->fillOutcomeSection($pdf, $consent);
    $this->fillWorkRelatedSection($pdf, $consent);
    $this->fillCauseAndProgressSection($pdf, $consent);
    $this->fillTreatmentMonth($pdf, $data['service_year_month']);
    $this->fillDiseaseAndSymptomsMassage($pdf, $consent);
    $this->fillDiseaseCheckboxes($pdf, $consent);
    $this->fillTreatmentDayCalendar($pdf, $records);
    $this->fillAbstractSection($pdf, $records);
    $this->fillTherapistSection($pdf, $consent);
    $this->fillHealthOfficeRegistration($pdf, $consent);
    $this->fillConsentRecordSection($pdf, $consent);
    $this->fillApplicationSection($pdf, $submissionDate);
    $this->fillPaymentInstitutionSection($pdf, $clinicInfo);
    $this->fillDelegationSection($pdf, $insurance, $doctor);
    $this->fillTreatmentFeeSection($pdf, $records, $insurance);
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
      'code_number' => $custom['insurance_symbol_code'] ?? '54321',
      'account_number' => $custom['insurance_symbol_number'] ?? '09876',
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

    // サンプル同意医師情報
    $doctor = (object)[
      'name' => $custom['doctor_name'] ?? '田中医師',
      'postal_code' => $custom['consent_record_doctor_postal_code'] ?? '1600001',
      'address' => $custom['consent_record_doctor_address'] ?? '東京都新宿区新宿1-1-1',
    ];

    return [
      'clinic_user' => $clinicUser,
      'insurance' => $insurance,
      'consent' => $consent,
      'doctor' => $doctor,
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

    // 通常モード:施術実績から料金を計算
    $therapyTypeCounts = [];
    $bodypartCounts = []; // 部位ごとのカウント
    $isFirstTreatment = false;

    \Log::info('=== ノーマルモード：料金計算開始 ===', [
      'records_count' => $records->count(),
      'has_treatment_fees' => !empty($treatmentFees)
    ]);

    // 施術実績を集計
    foreach ($records as $index => $record) {
      $therapyContentId = $record->therapy_content_id ?? null;

      // 初検かどうかを判定（最初のレコードのみ）
      if ($index === 0) {
        $isFirstTreatment = true;
      }

      // 施術内容ごとにカウント
      if ($therapyContentId) {
        if (!isset($therapyTypeCounts[$therapyContentId])) {
          $therapyTypeCounts[$therapyContentId] = 0;
        }
        $therapyTypeCounts[$therapyContentId]++;

        // therapy_content_id = 18 (マッサージ)の場合、部位情報を取得
        if ($therapyContentId == 18) {
          $bodyparts = DB::table('bodyparts-records')
            ->where('records_id', $record->id)
            ->pluck('therapy_type_bodyparts_id');

          foreach ($bodyparts as $bodypartId) {
            if (!isset($bodypartCounts[$bodypartId])) {
              $bodypartCounts[$bodypartId] = 0;
            }
            $bodypartCounts[$bodypartId]++;
          }
        }
      }
    }

    \Log::info('施術内容集計結果', [
      'therapy_type_counts' => $therapyTypeCounts,
      'bodypart_counts' => $bodypartCounts,
      'is_first_treatment' => $isFirstTreatment
    ]);

    $totalFee = 0;

    // マッサージ料金（躯幹）bodypart_id: 1
    $count = $bodypartCounts[1] ?? 0;
    $feeKey = $isFirstTreatment ? 'massage_trunk_first' : 'massage_trunk_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    \Log::info('マッサージ料金（躯幹）描画', [
      'bodypart_id' => 1,
      'count' => $count,
      'fee_key' => $feeKey,
      'unit_price' => $unitPrice,
      'total' => $total,
      'has_coord' => $this->hasCoord('fee_massage_trunk_unit')
    ]);

    if ($this->hasCoord('fee_massage_trunk_unit')) {
      $pdf->SetFontSize($this->coord('fee_massage_trunk_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_massage_trunk_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_massage_trunk_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_massage_trunk_total', (string)$total);
      $totalFee += $total;
    }

    // マッサージ料金（右上肢）bodypart_id: 2
    $count = $bodypartCounts[2] ?? 0;
    $feeKey = $isFirstTreatment ? 'massage_upper_limb_r_first' : 'massage_upper_limb_r_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_massage_upper_limb_r_unit')) {
      $pdf->SetFontSize($this->coord('fee_massage_upper_limb_r_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_total', (string)$total);
      $totalFee += $total;
    }

    // マッサージ料金（左上肢）bodypart_id: 3
    $count = $bodypartCounts[3] ?? 0;
    $feeKey = $isFirstTreatment ? 'massage_upper_limb_l_first' : 'massage_upper_limb_l_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_massage_upper_limb_l_unit')) {
      $pdf->SetFontSize($this->coord('fee_massage_upper_limb_l_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_total', (string)$total);
      $totalFee += $total;
    }

    // マッサージ料金（右下肢）bodypart_id: 4
    $count = $bodypartCounts[4] ?? 0;
    $feeKey = $isFirstTreatment ? 'massage_lower_limb_r_first' : 'massage_lower_limb_r_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_massage_lower_limb_r_unit')) {
      $pdf->SetFontSize($this->coord('fee_massage_lower_limb_r_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_total', (string)$total);
      $totalFee += $total;
    }

    // マッサージ料金（左下肢）bodypart_id: 5
    $count = $bodypartCounts[5] ?? 0;
    $feeKey = $isFirstTreatment ? 'massage_lower_limb_l_first' : 'massage_lower_limb_l_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_massage_lower_limb_l_unit')) {
      $pdf->SetFontSize($this->coord('fee_massage_lower_limb_l_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_total', (string)$total);
      $totalFee += $total;
    }

    // 変形徒手矯正術 therapy_content_id: 19
    $count = $therapyTypeCounts[19] ?? 0;
    $feeKey = $isFirstTreatment ? 'manual_correction_first' : 'manual_correction_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_manual_correction_unit')) {
      $pdf->SetFontSize($this->coord('fee_manual_correction_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_manual_correction_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_manual_correction_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_manual_correction_total', (string)$total);
      $totalFee += $total;
    }

    // 温罨法 therapy_content_id: 20
    $count = $therapyTypeCounts[20] ?? 0;
    $feeKey = $isFirstTreatment ? 'fomentation_first' : 'fomentation_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_fomentation_unit')) {
      $pdf->SetFontSize($this->coord('fee_fomentation_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_fomentation_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_fomentation_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_fomentation_total', (string)$total);
      $totalFee += $total;
    }

    // 温罨法・電光線器具 therapy_content_id: 21
    $count = $therapyTypeCounts[21] ?? 0;
    $feeKey = $isFirstTreatment ? 'fomentation_and_elec_ray_first' : 'fomentation_and_elec_ray_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_fomentation_electric_light_unit')) {
      $pdf->SetFontSize($this->coord('fee_fomentation_electric_light_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_total', (string)$total);
      $totalFee += $total;
    }

    // 往療料4km以下（マッサージ関連の施術のみカウント、0 < housecall_distance <= 4）
    $massageContentIds = [18, 19, 20, 21];
    $housecallCount = 0;
    foreach ($records as $record) {
      $distance = $record->housecall_distance ?? 0;
      if ($distance > 0 && $distance <= 4 && in_array($record->therapy_content_id ?? null, $massageContentIds)) {
        $housecallCount++;
      }
    }

    $feeKey = $isFirstTreatment ? 'housecall_max_2km_first' : 'housecall_max_2km_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $housecallCount;

    if ($this->hasCoord('fee_housecall_unit')) {
      $pdf->SetFontSize($this->coord('fee_housecall_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_housecall_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_housecall_count', (string)$housecallCount);
      $this->drawTextByKey($pdf, 'fee_housecall_total', (string)$total);
      $totalFee += $total;
    }

    // 往療料4km超（マッサージ関連の施術のみカウント、housecall_distance > 4で判定）
    $housecallAdditionalCount = 0;
    foreach ($records as $record) {
      if (isset($record->housecall_distance) && $record->housecall_distance > 4 && in_array($record->therapy_content_id ?? null, $massageContentIds)) {
        $housecallAdditionalCount++;
      }
    }

    $feeKey = $isFirstTreatment ? 'housecall_additional_max_4km_first' : 'housecall_additional_max_4km_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $housecallAdditionalCount;

    if ($this->hasCoord('fee_housecall_additional_unit')) {
      $pdf->SetFontSize($this->coord('fee_housecall_additional_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_housecall_additional_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_housecall_additional_count', (string)$housecallAdditionalCount);
      $this->drawTextByKey($pdf, 'fee_housecall_additional_total', (string)$total);
      $totalFee += $total;
    }

    // 合計
    if ($this->hasCoord('fee_subtotal')) {
      $pdf->SetFontSize($this->coord('fee_subtotal', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_subtotal', (string)$totalFee);
    }

    // 一部負担金計算
    $burdenRatio = 0;
    if (isset($insurance->expenses_borne_ratio)) {
      $burdenRatio = (int)$insurance->expenses_borne_ratio;
    }
    $partialPayment = (int)floor($totalFee * $burdenRatio / 100);

    if ($this->hasCoord('fee_partial_payment')) {
      $pdf->SetFontSize($this->coord('fee_partial_payment', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_partial_payment', (string)$partialPayment);
    }

    // 請求額
    $claimAmount = $totalFee - $partialPayment;
    if ($this->hasCoord('fee_total_claim')) {
      $pdf->SetFontSize($this->coord('fee_total_claim', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_total_claim', (string)$claimAmount);
    }

    $pdf->SetFontSize(10);
  }
  protected function fillTitleYearMonth(Fpdi $pdf, array $japaneseYear, int $month): void {}
  protected function fillInstitutionAndPublicFunds(Fpdi $pdf, $clinicInfo, $insurance): void {}
  protected function fillInsuranceType(Fpdi $pdf, $insurance): void {}
  protected function fillPartialPaymentEllipse(Fpdi $pdf, $insurance): void {}
  protected function fillInsuranceInfoSection(Fpdi $pdf, $insurance): void {}
  protected function fillPatientBasicInfo(Fpdi $pdf, $clinicUser, $insurance, string $fullName): void {}
  protected function fillPatientBirthday(Fpdi $pdf, $clinicUser): void {}
  protected function fillPatientAddressInfo(Fpdi $pdf, $clinicUser): void {}
  protected function fillTreatmentPeriodSection(Fpdi $pdf, $records): void {}
  protected function fillDiseaseAndSymptoms(Fpdi $pdf, $consent): void {}
  protected function fillOnsetInfo(Fpdi $pdf, $consent): void {}
  protected function fillFirstTreatmentDate(Fpdi $pdf, $records): void {}
  protected function fillTreatmentDayCount(Fpdi $pdf, $records): void {}
  protected function fillBillCategorySection(Fpdi $pdf, $consent): void {}
  protected function fillOutcomeSection(Fpdi $pdf, $consent): void {}
  protected function fillWorkRelatedSection(Fpdi $pdf, $consent): void {}
  protected function fillCauseAndProgressSection(Fpdi $pdf, $consent): void {}
  protected function fillTreatmentMonth(Fpdi $pdf, string $serviceYearMonth): void {}
  protected function fillDiseaseAndSymptomsMassage(Fpdi $pdf, $consent): void {}
  protected function fillDiseaseCheckboxes(Fpdi $pdf, $consent): void {}
  protected function fillTreatmentDayCalendar(Fpdi $pdf, $records): void {}
  protected function fillAbstractSection(Fpdi $pdf, $records): void {}
  protected function fillTherapistSection(Fpdi $pdf, $consent): void {}
  protected function fillHealthOfficeRegistration(Fpdi $pdf, $consent): void {}
  protected function fillConsentRecordSection(Fpdi $pdf, $consent): void {}
  protected function fillApplicationSection(Fpdi $pdf, string $submissionDate): void {}
  protected function fillPaymentInstitutionSection(Fpdi $pdf, $clinicInfo): void {}
  protected function fillDelegationSection(Fpdi $pdf, $insurance, $doctor): void {}
  protected function fillTreatmentFeeSection(Fpdi $pdf, $records, $insurance): void {}

}
