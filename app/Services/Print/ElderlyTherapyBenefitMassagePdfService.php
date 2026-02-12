<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * 後期高齢者医療療養費支給申請書（あんま･マッサージ）PDF生成サービス
 *
 * 【仕様】
 * - フォーム構造：医療助成費支給申請書（はり･きゅう）と同じ
 * - データ取得：あんま･マッサージ用（consents_massage、therapy_content_id 1-10）
 *
 * 【データ取得の注意点】
 * - $consent: consents_massageテーブルから取得、複数テーブルとJOIN
 * - サンプルデータは医療助成費（はり･きゅう）のものを流用
 */
class ElderlyTherapyBenefitMassagePdfService extends BasePdfService
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
    return storage_path('app/config/elderly_therapy_benefit_massage_coordinates.json');
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
  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate, string $remarks = ''): string
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

    // あんま・マッサージ同意書情報取得（請求区分、転帰、業務上区分もJOIN）
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
    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/acupuncture_and_massage/後期高齢者医療療養費支給申請書（あんま･マッサージ）.pdf');

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
    [$year, $month] = explode('-', $data['service_year_month']);
    $japaneseYear = $this->convertToJapaneseYear($year, $month);
    $fullName = ($clinicUser->last_name ?? '') . ' ' . ($clinicUser->first_name ?? '');
    $fullNameKana = ($clinicUser->last_kana ?? '') . ' ' . ($clinicUser->first_kana ?? '');

    $this->fillTitleYearMonth($pdf, $japaneseYear, (int)$month);
    $this->fillInstitutionAndPublicFunds($pdf, $clinicInfo, $insurance);
    $this->fillInsuranceSection($pdf, $insurance);
    $this->fillPatientBasicInfo($pdf, $clinicUser, $insurance, $fullName, $fullNameKana);
    $this->fillPatientBirthday($pdf, $clinicUser);
    $this->fillOnsetInfoMassage($pdf, $consent);
    $this->fillWorkScopeType($pdf, $consent);
    $this->fillFirstTreatmentDate($pdf, $records);
    $this->fillTreatmentDayCount($pdf, $records);
    $this->fillBillCategoryAndOutcome($pdf, $consent);
    $this->fillIllnessCheckboxesMassage($pdf, $consent);
    $this->fillServiceDates($pdf, $records);
    $this->fillAbstractSection($pdf, $records);
    $this->fillClinicInfoSection($pdf, $clinicInfo, $submissionDate);
    $this->fillConsentRecordSectionMassage($pdf, $consent, $doctor);
    $this->fillTreatmentPeriodFields($pdf, $data);
    $this->fillApplicationSection($pdf, $submissionDate);
    $this->fillApplicantInfo($pdf, $clinicUser, $fullName);
    $this->fillAgentInfo($pdf, $clinicInfo, $clinicUser);
    $this->fillPaymentInstitutionSection($pdf, $clinicInfo);
    $this->fillTemporaryInsurerName($pdf, $fullName);
    if (!$this->sampleDataMode) {
      $this->fillRealDataModeFieldsMassage($pdf, $data, $insurance, $consent, $clinicInfo, $clinicUser, $submissionDate);
    }
    $this->fillTreatmentFees($pdf, $data);
  }

  /**
   * 発病負傷情報埋め込み（マッサージ用）
   */
  protected function fillOnsetInfoMassage($pdf, $consent): void
  {
    // 発病又は負傷年月日
    if ($this->hasCoord('onset_date')) {
      if ($this->sampleDataMode && $this->customSampleData) {
        $onsetDate = $this->customSampleData['onset_date'] ?? '';
        if ($onsetDate) {
          $pdf->SetFontSize($this->coord('onset_date', 'fontSize'));
          $this->drawTextByKey($pdf, 'onset_date', (string)$onsetDate);
          $pdf->SetFontSize(10);
        }
      } elseif ($consent && isset($consent->injury_or_disease_onset_date) && $consent->injury_or_disease_onset_date) {
        [$onsetYear, $onsetMonth, $onsetDay] = explode('-', $consent->injury_or_disease_onset_date);
        $onsetJapaneseYear = $this->convertToJapaneseYear((int)$onsetYear, (int)$onsetMonth);
        $formattedDate = sprintf(
          '%s%d年 %d月 %d日',
          $onsetJapaneseYear['era'],
          $onsetJapaneseYear['year'],
          (int)$onsetMonth,
          (int)$onsetDay
        );
        $pdf->SetFontSize($this->coord('onset_date', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date', $formattedDate);
        $pdf->SetFontSize(10);
      }
    }

    // 傷病名（発病又は負傷年月日の隣）
    if ($this->sampleDataMode && $this->customSampleData) {
      $onsetIllnessName = $this->customSampleData['onset_illness_name'] ?? '';
      if ($onsetIllnessName) {
        $pdf->SetFontSize($this->coord('onset_illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_illness_name', (string)$onsetIllnessName);
        $pdf->SetFontSize(10);
      }
    } elseif ($consent) {
      // マッサージの場合はillness_nameがJOINされている
      $onsetIllnessName = $consent->illness_name ?? '';
      if ($onsetIllnessName) {
        $pdf->SetFontSize($this->coord('onset_illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_illness_name', (string)$onsetIllnessName);
        $pdf->SetFontSize(10);
      }
    }

    // 発病負傷の原因･経過
    if ($this->sampleDataMode && $this->customSampleData) {
      $conditionText = $this->customSampleData['condition'] ?? '';
      if ($conditionText) {
        $pdf->SetFontSize($this->coord('condition', 'fontSize'));
        $this->drawTextByKey($pdf, 'condition', (string)$conditionText);
        $pdf->SetFontSize(10);
      }
    } elseif ($consent) {
      // マッサージの場合はcondition_nameがJOINされている
      $conditionText = $consent->condition_name ?? '';
      if ($conditionText) {
        $pdf->SetFontSize($this->coord('condition', 'fontSize'));
        $this->drawTextByKey($pdf, 'condition', $conditionText);
        $pdf->SetFontSize(10);
      }
    }
  }

  /**
   * 傷病名チェックボックス埋め込み（マッサージ用）
   */
  protected function fillIllnessCheckboxesMassage($pdf, $consent): void
  {
    if ($this->sampleDataMode) {
      // サンプルデータモードの処理は親クラスと同じ
      $illnessSelected = false;
      for ($i = 1; $i <= 7; $i++) {
        $key = 'illness_name_' . $i;
        if (isset($this->coordinates[$key]['isSelected']) && $this->coordinates[$key]['isSelected']) {
          $this->drawEllipseByKey($pdf, $key);
          $illnessSelected = true;
          if ($i === 7 && isset($this->customSampleData['illness_name_other_text']) && $this->customSampleData['illness_name_other_text']) {
            $pdf->SetFontSize($this->coord('illness_name_other_text', 'fontSize'));
            $this->drawTextByKey($pdf, 'illness_name_other_text', (string)$this->customSampleData['illness_name_other_text']);
            $pdf->SetFontSize(10);
          }
          break;
        }
      }
      if (!$illnessSelected && isset($this->customSampleData['illness_name']) && $this->customSampleData['illness_name']) {
        $illnessId = (int)$this->customSampleData['illness_name'];
        if ($illnessId >= 1 && $illnessId <= 7) {
          $this->drawEllipseByKey($pdf, 'illness_name_' . $illnessId);
          if ($illnessId === 7 && isset($this->customSampleData['illness_name_other_text']) && $this->customSampleData['illness_name_other_text']) {
            $pdf->SetFontSize($this->coord('illness_name_other_text', 'fontSize'));
            $this->drawTextByKey($pdf, 'illness_name_other_text', (string)$this->customSampleData['illness_name_other_text']);
            $pdf->SetFontSize(10);
          }
        }
      }
    } elseif ($consent && isset($consent->injury_and_illness_name_id) && $consent->injury_and_illness_name_id) {
      // マッサージの場合：injury_and_illness_name_idを使用
      $illnessId = (int)$consent->injury_and_illness_name_id;
      if ($illnessId >= 1 && $illnessId <= 7) {
        $this->drawEllipseByKey($pdf, 'illness_name_' . $illnessId);
      }
      // 「その他」の場合、illness_nameをテキスト表示
      if ($illnessId === 7 && isset($consent->illness_name) && $consent->illness_name) {
        $pdf->SetFontSize($this->coord('illness_name_other_text', 'fontSize'));
        $this->drawTextByKey($pdf, 'illness_name_other_text', (string)$consent->illness_name);
        $pdf->SetFontSize(10);
      }
    }
  }

  /**
   * 同意記録セクション埋め込み（マッサージ用）
   */
  protected function fillConsentRecordSectionMassage($pdf, $consent, $doctor = null): void
  {
    if ($this->sampleDataMode && $this->customSampleData) {
      // サンプルデータモードは親クラスのfillConsentRecordSectionを呼び出し
      $this->fillConsentRecordSection($pdf, $consent, $doctor);
      return;
    }

    if (!$consent) {
      return;
    }

    // 同意医師氏名
    if ($this->hasCoord('consent_record_doctor_name') && isset($consent->consenting_doctor_name)) {
      $pdf->SetFontSize($this->coord('consent_record_doctor_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_record_doctor_name', (string)$consent->consenting_doctor_name);
      $pdf->SetFontSize(10);
    }

    // 同意医師郵便番号
    if ($this->hasCoord('consent_record_doctor_postal_code') && $doctor && isset($doctor->postal_code)) {
      $doctorPostalCode = $doctor->postal_code;
      // 7桁の数字を "〒 XXX - XXXX" 形式に変換
      $postalCode = preg_replace('/[^0-9]/', '', $doctorPostalCode);
      if (strlen($postalCode) === 7) {
        $postalCode = '〒 ' . substr($postalCode, 0, 3) . ' - ' . substr($postalCode, 3);
      }
      $pdf->SetFontSize($this->coord('consent_record_doctor_postal_code', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_record_doctor_postal_code', (string)$postalCode);
      $pdf->SetFontSize(10);
    }

    // 同意医師住所
    if ($this->hasCoord('consent_record_doctor_address') && $doctor) {
      $doctorAddress = ($doctor->address_1 ?? '') . ($doctor->address_2 ?? '') . ($doctor->address_3 ?? '');
      if ($doctorAddress) {
        $pdf->SetFontSize($this->coord('consent_record_doctor_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_doctor_address', (string)$doctorAddress);
        $pdf->SetFontSize(10);
      }
    }

    // 同意年月日（統合フィールド）
    if ($this->hasCoord('consent_date_full') && isset($consent->consenting_date) && $consent->consenting_date) {
      [$consentYear, $consentMonth, $consentDay] = explode('-', $consent->consenting_date);
      $consentJapaneseYear = $this->convertToJapaneseYear((int)$consentYear, (int)$consentMonth);
      $formattedDate = sprintf(
        '%s%d年 %d月 %d日',
        $consentJapaneseYear['era'],
        $consentJapaneseYear['year'],
        (int)$consentMonth,
        (int)$consentDay
      );
      $pdf->SetFontSize($this->coord('consent_date_full', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_date_full', $formattedDate);
      $pdf->SetFontSize(10);
    }

    // 傷病名（同意記録）
    $consentIllnessName = $consent->illness_name ?? '';
    if ($consentIllnessName) {
      $pdf->SetFontSize($this->coord('consent_illness_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_illness_name', (string)$consentIllnessName);
      $pdf->SetFontSize(10);
    }

    // 要加療期間
    $therapyPeriod = '';
    if (isset($consent->therapy_period) && $consent->therapy_period) {
      $therapyPeriod = $consent->therapy_period;
    }
    if ($therapyPeriod) {
      $pdf->SetFontSize($this->coord('required_treatment_period', 'fontSize'));
      $this->drawTextByKey($pdf, 'required_treatment_period', (string)$therapyPeriod);
      $pdf->SetFontSize(10);
    }
  }

  /**
   * 代理人情報埋め込み（マッサージ用）
   */
  protected function fillAgentInfo($pdf, $clinicInfo = null, $clinicUser = null): void
  {
    // === 代理人情報 ===
    if ($this->sampleDataMode) {
      // サンプルデータモード
      $agentPostalCode = $this->customSampleData['agent_postal_code'] ?? '';
      $agentAddress = $this->customSampleData['agent_address'] ?? '';
      $agentName = $this->customSampleData['agent_name'] ?? '';
    } else {
      // 通常モード：clinic_infoテーブルを参照
      $agentPostalCode = $clinicInfo->postal_code ?? '';
      $agentAddress = ($clinicInfo->address_1 ?? '') . ($clinicInfo->address_2 ?? '') . ($clinicInfo->address_3 ?? '');
      $agentName = isset($clinicInfo->owner_last_name) && isset($clinicInfo->owner_first_name)
        ? trim($clinicInfo->owner_last_name . ' ' . $clinicInfo->owner_first_name)
        : '';
    }

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

    // === 委任者郵便番号・住所 ===
    if (!$this->sampleDataMode && $clinicUser) {
      // 通常モード：利用者のデータを参照
      // 署名オプション: user_address_signature_blank の場合は郵便番号・住所をスキップ
      if ($this->signatureOption !== 'user_address_signature_blank') {
        if ($this->hasCoord('signature_applicant_postal_code') && isset($clinicUser->postal_code)) {
          $pdf->SetFontSize($this->coord('signature_applicant_postal_code', 'fontSize'));
          $this->drawTextByKey($pdf, 'signature_applicant_postal_code', (string)$clinicUser->postal_code);
        }
        if ($this->hasCoord('signature_applicant_address')) {
          $signatureAddress = ($clinicUser->address_1 ?? '') . ($clinicUser->address_2 ?? '') . ($clinicUser->address_3 ?? '');
          if ($signatureAddress) {
            $pdf->SetFontSize($this->coord('signature_applicant_address', 'fontSize'));
            $this->drawTextByKey($pdf, 'signature_applicant_address', (string)$signatureAddress);
          }
        }
      }
    }

    $pdf->SetFontSize(10);
  }

  /**
   * 実データモードフィールド埋め込み（マッサージ用）
   */
  protected function fillRealDataModeFieldsMassage($pdf, $data, $insurance, $consent, $clinicInfo, $clinicUser, $submissionDate): void
  {
    // 実データモードでのみ処理される追加フィールド
    // 基本的に親クラスのfillRealDataModeFieldsと同じだが、マッサージ固有の処理を追加可能
    // 現時点では特別な処理は不要
  }
}
