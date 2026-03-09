<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * あんま・マッサージ療養費支給申請書PDF生成サービス
 */
class MassageBenefitPdfService extends BasePdfService
{
  use \App\Services\Print\Traits\MassageFormFieldsTrait;
  use \App\Services\Print\Traits\MassageSampleDataTrait;
  use \App\Services\Print\Traits\MassageDrawingHelpersTrait;

  /**
   * デフォルト座標ファイルパスを取得
   */
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/massage_benefit_coordinates.json');
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
    if ($consent && $consent->consenting_doctor_id) {
      $doctor = DB::table('doctors')
        ->where('id', $consent->consenting_doctor_id)
        ->first();
    }

    // 施術実績取得（対象年月）
    $records = DB::table('records')
      ->where('clinic_user_id', $clinicUserId)
      ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$serviceYearMonth])
      ->orderBy('date')
      ->get();



    // 施術所情報取得
    $clinicInfo = DB::table('clinic_info')->orderByDesc('id')->first();


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
    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/acupuncture_and_massage/療養費支給申請書（マッサージ）.pdf');

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
    // データ取り出し
    $clinicUser = $data['clinic_user'];
    $insurance = $data['insurance'];
    $consent = $data['consent'];
    $doctor = $data['doctor'] ?? null;
    $records = $data['records'];
    $clinicInfo = $data['clinic_info'];
    $fullName = ($clinicUser->last_name ?? '') . '  ' . ($clinicUser->first_name ?? '');
    $serviceYearMonth = $data['service_year_month'];
    [$year, $month] = explode('-', $serviceYearMonth);

    // Phase 1-5: メソッド呼び出し
    $this->fillBasicInfoSection($pdf, $data);
    $this->fillPublicFundsSection($pdf, $insurance, $clinicInfo);
    $this->fillInsuranceSection($pdf, $insurance);
    $this->fillInsuredPersonSection($pdf, $clinicUser, $insurance);
    $this->fillTreatmentPeriodSection($pdf, $records, $serviceYearMonth);
    $this->fillInjuryIllnessSection($pdf, $consent);
    $this->fillOnsetDateSection($pdf, $consent);
    $this->fillOnsetIllnessNameSection($pdf, $consent);
    $this->fillInitialTreatmentDateSection($pdf, $consent);
    $this->fillTreatmentDayCountSection($pdf, $records);
    $this->fillBillingCategorySection($pdf, $consent);
    $this->fillConditionAndMonthSection($pdf, $consent, (int)$month);
    $this->fillServiceDates($pdf, $records);
    $this->fillAbstractSection($pdf, $records);
    $this->fillTherapistSection($pdf, $clinicInfo);
    $this->fillConsentRecordSection($pdf, $consent, $doctor);
    $this->fillApplicationSection($pdf, $insurance, $submissionDate);
    $this->fillPaymentSection($pdf, $clinicInfo, $clinicUser, $submissionDate);
    $this->fillTreatmentFees($pdf, $data);

    // Phase 6: 施術所・申請者系
    $this->fillClinicInfoSection($pdf, $clinicInfo, $submissionDate);
    $this->fillApplicantInfoSection($pdf, $clinicUser);
    $this->fillAgentInfoSection($pdf, $clinicInfo);
    $this->fillPaymentInstitutionSection($pdf);

    // 被保険者情報（空白フラグが立っている場合はスキップ）
    if (!$this->blankInsuredName) {
      $this->fillTemporaryInsurerNameSection($pdf, $fullName);
    }

    // 前回年月
    if ($this->previousYearMonth) {
      [$prevYear, $prevMonth] = explode('-', $this->previousYearMonth);
      $prevJapaneseYear = $this->convertToJapaneseYear((int)$prevYear, (int)$prevMonth);
      if ($this->hasCoord('previous_year_month_era')) {
        $pdf->SetFontSize($this->coord('previous_year_month_era', 'fontSize'));
        $this->drawTextByKey($pdf, 'previous_year_month_era', $prevJapaneseYear['era']);
      }
      if ($this->hasCoord('previous_year_month_year')) {
        $pdf->SetFontSize($this->coord('previous_year_month_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'previous_year_month_year', (string)$prevJapaneseYear['year']);
      }
      if ($this->hasCoord('previous_year_month_month')) {
        $pdf->SetFontSize($this->coord('previous_year_month_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'previous_year_month_month', (string)(int)$prevMonth);
      }
    }
  }
}
