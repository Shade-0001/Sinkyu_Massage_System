<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * あんま・マッサージ医療助成費支給申請書PDF生成サービス
 *
 * 【データ取得の注意点】
 * - $consent: consents_massageテーブルから取得、複数テーブルとJOIN
 *   - illness_name: illnesses_massageテーブルとJOINで取得（injury_and_illness_name_idから）
 *   - therapy_period: 通常は空、therapy_period_start_date/end_dateから生成が必要な場合あり
 * - $doctor: doctorsテーブルから取得、consent.consenting_doctor_nameで検索
 *   - postal_code, address_1/2/3 を提供
 * - サンプルデータとのフィールド名の違いに注意（MedicalAssistanceMassageSampleDataTrait参照）
 *
 * 【施術日カレンダー描画の注意点】
 * - fillServiceDates()を使用（fillTreatmentDayCalendar()ではない）
 * - fillServiceDates()はtherapy_categoryに応じて単円(○)/二重丸(◎)を描画
 * - fillTreatmentDayCalendar()は単円のみで二重丸に非対応
 * - therapy_category: 1=通院(○)、2=往療初回(◎)
 */
class MedicalAssistanceMassagePdfService extends BasePdfService
{
  use \App\Services\Print\Traits\MedicalAssistanceMassageFormFieldsTrait;
  use \App\Services\Print\Traits\MedicalAssistanceMassageSampleDataTrait;
  use \App\Services\Print\Traits\MedicalAssistanceMassageDrawingHelpersTrait;

  /**
   * 署名オプション
   */
  protected $signatureOption = null;

  /**
   * 署名オプションを設定
   */
  public function setSignatureOption(?string $option): void
  {
    $this->signatureOption = $option;
  }

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
    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/acupuncture_and_massage/医療費支給申請書（あんま･マッサージ）.pdf');

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
    $this->fillDiseaseCheckboxes($pdf, $consent);
    $this->fillServiceDates($pdf, $records);
    $this->fillAbstractSection($pdf, $records);
    $this->fillTreatmentFees($pdf, $data);
    $this->fillClinicInfoSection($pdf, $clinicInfo, $submissionDate);
    $this->fillTherapistSection($pdf, $consent);
    $this->fillHealthOfficeRegistration($pdf, $consent);
    $this->fillConsentRecordSection($pdf, $consent, $doctor);
    $this->fillApplicationSection($pdf, $submissionDate);
    $this->fillApplicantInfo($pdf, $clinicUser, $fullName);
    $this->fillAgentInfo($pdf, $clinicInfo, $clinicUser);
    $this->fillPaymentInstitutionSection($pdf, $clinicInfo);
    $this->fillTemporaryInsurerName($pdf, $fullName);
    $this->fillDelegationSection($pdf, $insurance, $doctor);
  }
}
