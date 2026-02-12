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
    $this->fillTreatmentMonth($pdf, $data['service_year_month']);
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
      } elseif ($consent && isset($consent->onset_and_injury_date) && $consent->onset_and_injury_date) {
        [$onsetYear, $onsetMonth, $onsetDay] = explode('-', $consent->onset_and_injury_date);
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
    // 注：座標ファイルには onset_illness_name が存在しない場合があるため、
    // hasCoord でチェックしてから描画
    if ($this->hasCoord('onset_illness_name')) {
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
      // サンプルデータモードは親クラスのfillConsentRecordSectionを呼び出し（2引数のみ）
      $this->fillConsentRecordSection($pdf, $consent);
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
    // 座標ファイルでは consent_record_illness_name を使用
    if ($this->hasCoord('consent_record_illness_name')) {
      $consentIllnessName = $consent->illness_name ?? '';
      if ($consentIllnessName) {
        $pdf->SetFontSize($this->coord('consent_record_illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_illness_name', (string)$consentIllnessName);
        $pdf->SetFontSize(10);
      }
    }

    // 要加療期間
    if ($this->hasCoord('required_treatment_period')) {
      $therapyPeriodText = '';
      // therapy_period_end_dateをYYYY/MM/DD形式で表示
      if (isset($consent->therapy_period_end_date) && $consent->therapy_period_end_date) {
        $endDate = new \DateTime($consent->therapy_period_end_date);
        $therapyPeriodText = $endDate->format('Y/m/d');
      } elseif (isset($consent->therapy_period) && $consent->therapy_period) {
        // フォールバック: therapy_periodフィールドがある場合はそのまま使用
        $therapyPeriodText = $consent->therapy_period;
      }
      if ($therapyPeriodText) {
        $pdf->SetFontSize($this->coord('required_treatment_period', 'fontSize'));
        $this->drawTextByKey($pdf, 'required_treatment_period', (string)$therapyPeriodText);
        $pdf->SetFontSize(10);
      }
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
   * 支払機関セクション埋め込み（マッサージ用）
   */
  protected function fillPaymentInstitutionSection($pdf, $clinicInfo): void
  {
    // clinic_infoテーブルから銀行口座情報を取得（ノーマルモード用）
    $clinicInfoData = null;
    if (!$this->sampleDataMode) {
      $clinicInfoData = DB::table('clinic_info')->first();
    }

    // === 支払機関情報 ===
    // 支払区分
    if ($this->hasCoord('payment_method')) {
      $paymentMethod = $this->customSampleData['payment_method'] ?? '';
      if ($paymentMethod) {
        $pdf->SetFontSize($this->coord('payment_method', 'fontSize'));
        $this->drawTextByKey($pdf, 'payment_method', $paymentMethod);
      }
    }
    // 預金の種類
    if ($this->hasCoord('deposit_type')) {
      $depositType = $this->sampleDataMode && isset($this->customSampleData['deposit_type'])
        ? $this->customSampleData['deposit_type']
        : ($clinicInfoData->bank_account_type ?? '');
      if ($depositType) {
        $pdf->SetFontSize($this->coord('deposit_type', 'fontSize'));
        $this->drawTextByKey($pdf, 'deposit_type', $depositType);
      }
    }
    // 金融機関名（種類）
    if ($this->hasCoord('financial_institution_type')) {
      $financialInstitutionType = $this->customSampleData['financial_institution_type'] ?? '';
      if ($financialInstitutionType) {
        $pdf->SetFontSize($this->coord('financial_institution_type', 'fontSize'));
        $this->drawTextByKey($pdf, 'financial_institution_type', $financialInstitutionType);
      }
    }
    // 金融機関名（詳細）
    if ($this->hasCoord('financial_institution_name')) {
      $financialInstitutionName = $this->sampleDataMode && isset($this->customSampleData['financial_institution_name'])
        ? $this->customSampleData['financial_institution_name']
        : ($clinicInfoData->bank_name ?? '') . ($clinicInfoData->bank_branch_name ?? '');
      if ($financialInstitutionName) {
        $pdf->SetFontSize($this->coord('financial_institution_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'financial_institution_name', $financialInstitutionName);
      }
    }
    // 本店支店出張所（種類）
    if ($this->hasCoord('branch_type')) {
      $branchType = $this->customSampleData['branch_type'] ?? '';
      if ($branchType) {
        $pdf->SetFontSize($this->coord('branch_type', 'fontSize'));
        $this->drawTextByKey($pdf, 'branch_type', $branchType);
      }
    }
    // 支店名
    if ($this->hasCoord('branch_name')) {
      $branchName = $this->sampleDataMode && isset($this->customSampleData['branch_name'])
        ? $this->customSampleData['branch_name']
        : ($clinicInfoData->bank_branch_name ?? '');
      if ($branchName) {
        $pdf->SetFontSize($this->coord('branch_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'branch_name', $branchName);
      }
    }
    // 口座番号
    if ($this->hasCoord('bank_account_number')) {
      $accountNumber = $this->sampleDataMode && isset($this->customSampleData['account_number'])
        ? $this->customSampleData['account_number']
        : ($clinicInfoData->bank_account_number ?? '');
      if ($accountNumber) {
        $pdf->SetFontSize($this->coord('bank_account_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'bank_account_number', (string)$accountNumber);
      }
    }
    // 口座名義（カナ）
    if ($this->hasCoord('bank_account_holder_kana')) {
      $accountHolder = $this->sampleDataMode && isset($this->customSampleData['account_holder'])
        ? $this->customSampleData['account_holder']
        : ($clinicInfoData->bank_account_name_kana ?? '');
      if ($accountHolder) {
        $pdf->SetFontSize($this->coord('bank_account_holder_kana', 'fontSize'));
        $this->drawTextByKey($pdf, 'bank_account_holder_kana', (string)$accountHolder);
      }
    }
    $pdf->SetFontSize(10);
    // === 支払機関欄の楕円描画 ===
    // 支払区分（サンプルデータモードまたは通常モード）
    $paymentMethodKey = null;
    if (isset($this->coordinates['payment_category_account_transfer']['isSelected']) && $this->coordinates['payment_category_account_transfer']['isSelected']) {
      $paymentMethodKey = 'payment_category_account_transfer';
    } elseif (isset($this->coordinates['payment_category_counter_payment']['isSelected']) && $this->coordinates['payment_category_counter_payment']['isSelected']) {
      $paymentMethodKey = 'payment_category_counter_payment';
    } elseif ($this->sampleDataMode && isset($this->customSampleData['payment_category'])) {
      // サンプルデータから支払区分を取得
      $paymentCategoryMap = [
        '口座振替' => 'payment_category_account_transfer',
        '窓口払' => 'payment_category_counter_payment'
      ];
      $paymentMethodKey = $paymentCategoryMap[$this->customSampleData['payment_category']] ?? null;
    } elseif (!$this->sampleDataMode && $this->hasCoord('payment_category_account_transfer')) {
      // 通常モード：デフォルトで口座振替を選択
      $paymentMethodKey = 'payment_category_account_transfer';
    }
    if ($paymentMethodKey) {
      $this->drawEllipseByKey($pdf, $paymentMethodKey);
    }

    // 預金の種類（サンプルデータモードまたは通常モード）
    $depositTypeKey = null;
    if (isset($this->coordinates['deposit_type_ordinary']['isSelected']) && $this->coordinates['deposit_type_ordinary']['isSelected']) {
      $depositTypeKey = 'deposit_type_ordinary';
    } elseif (isset($this->coordinates['deposit_type_current']['isSelected']) && $this->coordinates['deposit_type_current']['isSelected']) {
      $depositTypeKey = 'deposit_type_current';
    } elseif (isset($this->coordinates['deposit_type_savings']['isSelected']) && $this->coordinates['deposit_type_savings']['isSelected']) {
      $depositTypeKey = 'deposit_type_savings';
    } elseif (isset($this->coordinates['deposit_type_other']['isSelected']) && $this->coordinates['deposit_type_other']['isSelected']) {
      $depositTypeKey = 'deposit_type_other';
    } elseif ($this->sampleDataMode && isset($this->customSampleData['deposit_type'])) {
      // サンプルデータから預金種類を取得
      $depositTypeMap = [
        '普通' => 'deposit_type_ordinary',
        '当座' => 'deposit_type_current',
        '貯蓄' => 'deposit_type_savings',
        'その他' => 'deposit_type_other'
      ];
      $depositTypeKey = $depositTypeMap[$this->customSampleData['deposit_type']] ?? null;
    } elseif (!$this->sampleDataMode && $clinicInfoData) {
      // 通常モード：clinic_infoから預金種類を取得
      $accountType = $clinicInfoData->bank_account_type ?? '普通';
      $depositTypeMap = [
        '普通' => 'deposit_type_ordinary',
        '当座' => 'deposit_type_current',
        '貯蓄' => 'deposit_type_savings',
        'その他' => 'deposit_type_other'
      ];
      $depositTypeKey = $depositTypeMap[$accountType] ?? 'deposit_type_ordinary';
    }
    if ($depositTypeKey) {
      $this->drawEllipseByKey($pdf, $depositTypeKey);
    }
    // 金融機関種類
    $financialInstitutionTypeKey = null;
    if (isset($this->coordinates['financial_institution_type_bank']['isSelected']) && $this->coordinates['financial_institution_type_bank']['isSelected']) {
      $financialInstitutionTypeKey = 'financial_institution_type_bank';
    } elseif (isset($this->coordinates['financial_institution_type_kinko']['isSelected']) && $this->coordinates['financial_institution_type_kinko']['isSelected']) {
      $financialInstitutionTypeKey = 'financial_institution_type_kinko';
    } elseif (isset($this->coordinates['financial_institution_type_nokyo']['isSelected']) && $this->coordinates['financial_institution_type_nokyo']['isSelected']) {
      $financialInstitutionTypeKey = 'financial_institution_type_nokyo';
    }
    if ($financialInstitutionTypeKey) {
      $this->drawEllipseByKey($pdf, $financialInstitutionTypeKey);
    }
  }

  /**
   * 施術月埋め込み（マッサージ用）
   */
  protected function fillTreatmentMonth($pdf, string $serviceYearMonth): void
  {
    // === 施術月 ===
    if ($this->hasCoord('treatment_month')) {
      [$year, $month] = explode('-', $serviceYearMonth);
      $pdf->SetFontSize($this->coord('treatment_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_month', (string)(int)$month);
      $pdf->SetFontSize(10);
    }
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
