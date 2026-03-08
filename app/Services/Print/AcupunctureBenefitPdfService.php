<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * はり・きゅう療養費支給申請書PDF生成サービス
 */
class AcupunctureBenefitPdfService extends BasePdfService
{
  use \App\Services\Print\Traits\AcupunctureFormFieldsTrait;
  use \App\Services\Print\Traits\AcupunctureSampleDataTrait;
  use \App\Services\Print\Traits\AcupunctureDrawingHelpersTrait;

  /**
   * デフォルト座標ファイルパスを取得
   */
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/acupuncture_benefit_coordinates.json');
  }

  /**
   * デフォルト座標を取得
   */
  protected function getDefaultCoordinates(): array
  {
    $configPath = storage_path('app/config/acupuncture_benefit_coordinates.json');
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

    // 保険情報取得（保険者情報、続柄、給付割合もJOIN）
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
    } else {
      \Log::info('保険情報取得成功', [
        'clinic_user_id' => $clinicUserId,
        'insurance_id' => $insurance->id ?? null,
        'expenses_borne_ratio_id' => $insurance->expenses_borne_ratio_id ?? null,
        'expenses_borne_ratio' => $insurance->expenses_borne_ratio ?? null,
      ]);
    }

    // はり・きゅう同意書情報取得（請求区分、転帰、業務上区分もJOIN）
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
    $clinicInfo = DB::table('clinic_info')->orderByDesc('id')->first();

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
    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/acupuncture_and_massage/療養費支給申請書（はり・きゅう）.pdf');

    if (file_exists($templatePath)) {
      $pageCount = $pdf->setSourceFile($templatePath);
      $tplId = $pdf->importPage(1);
      $pdf->useTemplate($tplId, 0, 0, null, null, true);
    }


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
    [$year, $month] = explode('-', $data['service_year_month']);
    $japaneseYear = $this->convertToJapaneseYear($year, $month);
    $fullName = ($clinicUser->last_name ?? '') . "\u{2000}" . ($clinicUser->first_name ?? '');

    // タイトル年月
    $this->fillTitleYearMonth($pdf, $japaneseYear, (int)$month);

    // 機関コード・公費情報
    $this->fillInstitutionAndPublicFunds($pdf, $clinicInfo, $insurance);

    // 保険情報
    $this->fillInsuranceSection($pdf, $insurance);

    // 患者基本情報
    $this->fillPatientBasicInfo($pdf, $clinicUser, $insurance, $fullName);

    // 患者生年月日
    $this->fillPatientBirthday($pdf, $clinicUser);

    // 業務上･外･第三者行為の有無
    $this->fillWorkScopeType($pdf, $consent);

    // 発病・負傷情報
    $this->fillOnsetInfo($pdf, $consent);

    // 初療年月日
    $this->fillFirstTreatmentDate($pdf, $records);

    // 施術期間
    $this->fillTreatmentPeriodSection($pdf, $records);

    // 実日数
    $this->fillTreatmentDayCount($pdf, $records);

    // 請求区分・転帰
    $this->fillBillCategoryAndOutcome($pdf, $consent);

    // 傷病名チェックボックス
    $this->fillIllnessCheckboxes($pdf, $consent);

    // 施術日カレンダー
    $this->fillServiceDates($pdf, $records);

    // 摘要
    $this->fillAbstractSection($pdf, $records);

    // 施術所情報
    $this->fillClinicInfoSection($pdf, $clinicInfo, $submissionDate);

    // 同意記録欄
    $this->fillConsentRecordSection($pdf, $consent);

    // 申請欄
    $this->fillApplicationSection($pdf, $submissionDate);

    // 申請者情報
    $this->fillApplicantInfo($pdf, $clinicUser, $fullName);

    // 代理人情報
    $this->fillAgentInfo($pdf);

    // 支払機関情報
    $this->fillPaymentInstitutionSection($pdf, $clinicInfo);

    // 被保険者情報
    $this->fillTemporaryInsurerName($pdf, $fullName);

    // 実データモード専用フィールド
    if (!$this->sampleDataMode) {
      $this->fillRealDataModeFields($pdf, $data, $insurance, $consent, $clinicInfo, $clinicUser, $submissionDate);
    }

    // 施術料金情報
    $this->fillTreatmentFees($pdf, $data);
  }
}
