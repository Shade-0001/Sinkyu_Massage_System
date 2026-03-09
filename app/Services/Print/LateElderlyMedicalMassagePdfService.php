<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * あんま・マッサージ後期高齢者医療療養費支給申請書PDF生成サービス
 *
 * フォーマットはMedicalAssistanceMassagePdfServiceと同一。
 * テンプレートPDFと座標JSONのみ後期高齢者医療専用のものを使用。
 */
class LateElderlyMedicalMassagePdfService extends BasePdfService
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
    return storage_path('app/config/late_elderly_medical_massage_coordinates.json');
  }

  /**
   * デフォルト座標を取得
   */
  protected function getDefaultCoordinates(): array
  {
    $configPath = $this->getDefaultCoordinatesPath();
    if (file_exists($configPath)) {
      $json = file_get_contents($configPath);
      return json_decode($json, true);
    }
    return [];
  }

  /**
   * PDF生成
   *
   * @param array $clinicUserIds 利用者ID配列
   * @param string $serviceYearMonth サービス提供年月 (YYYY-MM)
   * @param string $submissionDate 提出年月日 (YYYY-MM-DD)
   * @return string PDFバイナリデータ
   */
  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = ''): string
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

    return $pdf->Output('', 'S');
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
    if ($this->sampleDataMode) {
      return $this->getSampleData($serviceYearMonth);
    }

    $clinicUser = DB::table('clinic_users')
      ->leftJoin('gender', 'clinic_users.gender_id', '=', 'gender.id')
      ->where('clinic_users.id', $clinicUserId)
      ->select('clinic_users.*', 'gender.gender')
      ->first();

    if (!$clinicUser) {
      return null;
    }

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

    $doctor = null;
    if ($consent && $consent->consenting_doctor_id) {
      $doctor = DB::table('doctors')
        ->where('id', $consent->consenting_doctor_id)
        ->first();
    }

    $records = DB::table('records')
      ->where('clinic_user_id', $clinicUserId)
      ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$serviceYearMonth])
      ->orderBy('date')
      ->get();

    $clinicInfo = DB::table('clinic_info')->orderByDesc('id')->first();

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
   */
  protected function addPage(Fpdi $pdf, array $data, string $submissionDate): void
  {
    $pdf->AddPage();

    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/acupuncture_and_massage/後期高齢者医療療養費支給申請書（あんま･マッサージ）.pdf');

    if (file_exists($templatePath)) {
      $pageCount = $pdf->setSourceFile($templatePath);
      $tplId = $pdf->importPage(1);
      $pdf->useTemplate($tplId, 0, 0, null, null, true);
    }

    $pdf->SetFont('kozminproregular', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    $this->fillFormFields($pdf, $data, $submissionDate);
  }

  /**
   * フォームフィールド埋め込み
   */
  protected function fillFormFields(Fpdi $pdf, array $data, string $submissionDate): void
  {
    $clinicUser = $data['clinic_user'];
    $insurance = $data['insurance'];
    $consent = $data['consent'];
    $doctor = $data['doctor'] ?? null;
    $records = $data['records'];
    $clinicInfo = $data['clinic_info'];

    $fullName = ($clinicUser->last_name ?? '') . '  ' . ($clinicUser->first_name ?? '');
    list($year, $month) = explode('-', $data['service_year_month']);
    $japaneseYear = $this->convertToJapaneseYear($year, $month);

    $this->fillCustomTitleAndSubmissionCount($pdf, $data);
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
