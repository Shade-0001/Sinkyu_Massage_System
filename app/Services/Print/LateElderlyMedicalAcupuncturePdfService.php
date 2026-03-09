<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * はり・きゅう後期高齢者医療療養費支給申請書PDF生成サービス
 *
 * フォーマットはMedicalAssistanceAcupuncturePdfServiceと同一。
 * テンプレートPDFと座標JSONのみ後期高齢者医療専用のものを使用。
 */
class LateElderlyMedicalAcupuncturePdfService extends BasePdfService
{
  use \App\Services\Print\Traits\MedicalAssistanceAcupunctureFormFieldsTrait;
  use \App\Services\Print\Traits\MedicalAssistanceAcupunctureSampleDataTrait;
  use \App\Services\Print\Traits\MedicalAssistanceAcupunctureDrawingHelpersTrait;

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
    return storage_path('app/config/late_elderly_medical_acupuncture_coordinates.json');
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
      \Log::error('利用者情報が見つかりません', ['clinic_user_id' => $clinicUserId]);
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

    if (!$insurance) {
      \Log::warning('保険情報が見つかりません', ['clinic_user_id' => $clinicUserId]);
    }

    $consent = DB::table('consents_acupuncture')
      ->leftJoin('bill_categories', 'consents_acupuncture.bill_category_id', '=', 'bill_categories.id')
      ->leftJoin('outcomes', 'consents_acupuncture.outcome_id', '=', 'outcomes.id')
      ->leftJoin('work_scope_types', 'consents_acupuncture.work_scope_type_id', '=', 'work_scope_types.id')
      ->where('consents_acupuncture.clinic_user_id', $clinicUserId)
      ->orderBy('consents_acupuncture.consenting_date', 'desc')
      ->select(
        'consents_acupuncture.*',
        'bill_categories.bill_category',
        'outcomes.outcome',
        'work_scope_types.work_scope_type'
      )
      ->first();

    if (!$consent) {
      \Log::warning('はり・きゅう同意書情報が見つかりません', ['clinic_user_id' => $clinicUserId]);
    }

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

    if ($records->isEmpty()) {
      \Log::warning('施術実績が見つかりません', [
        'clinic_user_id' => $clinicUserId,
        'service_year_month' => $serviceYearMonth,
      ]);
    }

    $clinicInfo = DB::table('clinic_info')->orderByDesc('id')->first();

    if (!$clinicInfo) {
      \Log::error('施術所情報が見つかりません');
    }

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

    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/acupuncture_and_massage/後期高齢者医療療養費支給申請書（はり･きゅう）.pdf');

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
    $records = $data['records'];
    $clinicInfo = $data['clinic_info'];
    [$year, $month] = explode('-', $data['service_year_month']);
    $japaneseYear = $this->convertToJapaneseYear($year, $month);
    $fullName = ($clinicUser->last_name ?? '') . '  ' . ($clinicUser->first_name ?? '');
    $fullNameKana = ($clinicUser->last_kana ?? '') . '  ' . ($clinicUser->first_kana ?? '');

    $this->fillCustomTitleAndSubmissionCount($pdf, $data);
    $this->fillTitleYearMonth($pdf, $japaneseYear, (int)$month);
    $this->fillInstitutionAndPublicFunds($pdf, $clinicInfo, $insurance);
    $this->fillInsuranceSection($pdf, $insurance);
    $this->fillPatientBasicInfo($pdf, $clinicUser, $insurance, $fullName, $fullNameKana);
    $this->fillPatientBirthday($pdf, $clinicUser);
    $this->fillOnsetInfo($pdf, $consent);
    $this->fillWorkScopeType($pdf, $consent);
    $this->fillFirstTreatmentDate($pdf, $records);
    $this->fillTreatmentDayCount($pdf, $records);
    $this->fillBillCategoryAndOutcome($pdf, $consent);
    $this->fillIllnessCheckboxes($pdf, $consent);
    $this->fillServiceDates($pdf, $records);
    $this->fillAbstractSection($pdf, $records);
    $this->fillClinicInfoSection($pdf, $clinicInfo, $submissionDate);
    $this->fillConsentRecordSection($pdf, $consent);
    $this->fillTreatmentPeriodFields($pdf, $data);
    $this->fillApplicationSection($pdf, $submissionDate);
    $this->fillApplicantInfo($pdf, $clinicUser, $fullName);
    $this->fillAgentInfo($pdf);
    $this->fillPaymentInstitutionSection($pdf, $clinicInfo);
    $this->fillTemporaryInsurerName($pdf, $fullName);
    if (!$this->sampleDataMode) {
      $this->fillRealDataModeFields($pdf, $data, $insurance, $consent, $clinicInfo, $clinicUser, $submissionDate);
    }
    $this->fillTreatmentFees($pdf, $data);
  }
}
