<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 施術録（はり・きゅう）PDF生成サービス
 */
class TreatmentRecordAcupuncturePdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/treatment_record_acupuncture_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    $configPath = storage_path('app/config/treatment_record_acupuncture_coordinates.json');
    if (file_exists($configPath)) {
      $json = file_get_contents($configPath);
      return json_decode($json, true);
    }
    return [];
  }

  /**
   * PDF生成
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
   */
  protected function fetchData(int $clinicUserId, string $serviceYearMonth): ?array
  {
    // サンプルデータ表示モードの場合
    if ($this->sampleDataMode) {
      return $this->getSampleData($serviceYearMonth);
    }

    // 利用者情報取得
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
      ->leftJoin('self_or_family', 'insurances.self_or_family_id', '=', 'self_or_family.id')
      ->where('insurances.clinic_user_id', $clinicUserId)
      ->orderBy('insurances.created_at', 'desc')
      ->select(
        'insurances.*',
        'insurers.insurer_number',
        'insurers.insurer_name',
        'insurers.postal_code as insurer_postal_code',
        'insurers.address as insurer_address',
        'relationships_with_clinic_user.relationship',
        'self_or_family.subject_type'
      )
      ->first();

    // 同意書情報取得
    $consent = DB::table('consents_acupuncture')
      ->leftJoin('outcomes', 'consents_acupuncture.outcome_id', '=', 'outcomes.id')
      ->leftJoin('doctors', 'consents_acupuncture.consenting_doctor_id', '=', 'doctors.id')
      ->leftJoin('medical_institutions', 'doctors.medical_institutions_id', '=', 'medical_institutions.id')
      ->leftJoin('bill_categories', 'consents_acupuncture.bill_category_id', '=', 'bill_categories.id')
      ->leftJoin('illnesses_acupuncture', 'consents_acupuncture.illness_name_acupuncture_id', '=', 'illnesses_acupuncture.id')
      ->leftJoin('conditions', 'consents_acupuncture.condition_id', '=', 'conditions.id')
      ->where('consents_acupuncture.clinic_user_id', $clinicUserId)
      ->orderBy('consents_acupuncture.consenting_date', 'desc')
      ->select(
        'consents_acupuncture.*',
        'outcomes.outcome',
        'doctors.last_name as doctor_last_name',
        'doctors.first_name as doctor_first_name',
        'doctors.last_name_kana as doctor_last_name_kana',
        'doctors.first_name_kana as doctor_first_name_kana',
        'doctors.address_1 as doctor_address_1',
        'doctors.address_2 as doctor_address_2',
        'doctors.address_3 as doctor_address_3',
        'doctors.phone as doctor_phone',
        'doctors.cell_phone as doctor_cell_phone',
        'medical_institutions.medical_institution_name',
        'bill_categories.bill_category',
        'illnesses_acupuncture.illness_name_acupuncture',
        'conditions.condition_name'
      )
      ->first();

    // 施術実績取得（はり・きゅう: therapy_content_id 11-16）
    $records = DB::table('records')
      ->where('clinic_user_id', $clinicUserId)
      ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$serviceYearMonth])
      ->whereIn('therapy_content_id', [11, 12, 13, 14, 15, 16])
      ->orderBy('date')
      ->get();

    // 施術所情報取得
    $clinicInfo = $this->getClinicInfoForDate($serviceYearMonth . '-01');

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
   * サンプルデータ生成
   */
  protected function getSampleData(string $serviceYearMonth): array
  {
    $custom = $this->customSampleData ?? [];

    $clinicUser = (object)[
      'last_name' => $custom['last_name'] ?? '田中',
      'first_name' => $custom['first_name'] ?? '太郎',
      'gender' => $custom['insured_person_gender'] ?? $custom['user_gender'] ?? '男',
      'birthday' => '1955-05-10', // insured_person_birthday のサンプル固定値（和暦変換後: 昭和30年5月10日）
      'postal_code' => $custom['insured_person_postal_code'] ?? '1600022',
      'address_1' => $custom['insured_person_address'] ?? '東京都新宿区新宿2-3-4',
      'address_2' => '',
      'address_3' => '',
    ];

    $insurance = (object)[
      'code_number' => $custom['insurance_symbol_code'] ?? '12345',
      'account_number' => $custom['insurance_symbol_number'] ?? '67890',
      'insured_name' => $custom['insured_person_name'] ?? '田中 太郎',
      'subject_type' => '本人',
      'expiry_date' => '2035-03-31',
      'license_acquisition_date' => '2026-04-01',
      'relationship' => $custom['user_relationship'] ?? '本人',
      'public_funds_payer_code' => $custom['public_funds_payer_number'] ?? '12345678',
      'public_funds_recipient_code' => $custom['public_funds_recipient_number'] ?? '1234567',
      'locality_code' => $custom['locality_code'] ?? '123456',
      'recipient_code' => $custom['recipient_number'] ?? '123456',
      'insurer_number' => $custom['insurer_number'] ?? '12345678',
      'insurer_name' => $custom['insurer_name'] ?? 'サンプル健康保険組合',
      'insurer_address' => $custom['insurer_address'] ?? '東京都千代田区霞が関1-1-1',
    ];

    $consent = (object)[
      'illness_name_acupuncture' => '腰痛症',
      'illness_name_acupuncture_addendum' => '',
      'onset_and_injury_date' => '2025-11-15',
      'first_care_date' => '2025-11-20',
      'consenting_end_date' => '2025-12-31',
      'therapy_period' => $custom['treatment_period'] ?? '3ヶ月',
      'outcome' => $custom['outcome'] ?? '継続',
      'doctor_last_name' => '山田',
      'doctor_first_name' => '太郎',
      'doctor_last_name_kana' => 'ヤマダ',
      'doctor_first_name_kana' => 'タロウ',
      'doctor_address_1' => $custom['doctor_address'] ?? '東京都新宿区〇〇1-2-3',
      'doctor_address_2' => '',
      'doctor_address_3' => '',
      'doctor_phone' => '03-3456-7890',
      'doctor_cell_phone' => '090-1234-5678',
      'medical_institution_name' => $custom['medical_institution'] ?? '〇〇病院',
      'bill_category' => $custom['consent_category'] ?? '新規',
      'condition_name' => '階段からの転落',
    ];

    $records = collect([
      (object)['date' => $serviceYearMonth . '-01'],
      (object)['date' => $serviceYearMonth . '-05'],
      (object)['date' => $serviceYearMonth . '-10'],
      (object)['date' => $serviceYearMonth . '-15'],
      (object)['date' => $serviceYearMonth . '-20'],
    ]);

    $clinicInfo = (object)[
      'clinic_name' => $custom['clinic_name'] ?? 'サンプル鍼灸院',
      'postal_code' => $custom['clinic_postal_code'] ?? '1500001',
      'address_1' => '東京都渋谷区',
      'address_2' => '〇〇1-2-3',
      'address_3' => '',
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
   * PDFページ追加
   */
  protected function addPage(Fpdi $pdf, array $data, string $submissionDate): void
  {
    $pdf->AddPage();

    // テンプレートPDF読み込み
    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/acupuncture_and_massage/施術録（はり・きゅう）.pdf');

    if (file_exists($templatePath)) {
      $pageCount = $pdf->setSourceFile($templatePath);
      $tplId = $pdf->importPage(1);
      $pdf->useTemplate($tplId, 0, 0, 210, 297);
    }

    // フィールド描画
    $this->drawFields($pdf, $data);
  }

  /**
   * フィールド描画
   */
  protected function drawFields(Fpdi $pdf, array $data): void
  {
    $clinicUser = $data['clinic_user'];
    $insurance = $data['insurance'] ?? null;
    $consent = $data['consent'] ?? null;
    $records = $data['records'];
    $clinicInfo = $data['clinic_info'] ?? null;

    $pdf->SetFont('kozminproregular', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    foreach ($this->coordinates as $key => $coord) {
      if (!isset($coord['x']) || !isset($coord['y'])) continue;

      $value = $this->getFieldValue($key, $data);
      if ($value === null || $value === '') continue;

      // drawTextByKey()を使用してletterSpacingを適用
      $this->drawTextByKey($pdf, $key, $value);
    }
  }

  /**
   * フィールド値取得
   */
  protected function getFieldValue(string $key, array $data): ?string
  {
    $clinicUser = $data['clinic_user'];
    $insurance = $data['insurance'] ?? null;
    $consent = $data['consent'] ?? null;
    $records = $data['records'];
    $clinicInfo = $data['clinic_info'] ?? null;

    switch ($key) {
      // 各種番号
      case 'locality_code': return $insurance->locality_code ?? null;
      case 'recipient_number': return $insurance->recipient_code ?? null;
      case 'public_funds_payer_number': return $insurance->public_funds_payer_code ?? null;
      case 'public_funds_recipient_number': return $insurance->public_funds_recipient_code ?? null;

      // 被保険者情報（本人の場合は利用者情報を使用）
      case 'insurance_symbol_code': return $insurance->code_number ?? null;
      case 'insurance_symbol_number': return $insurance->account_number ?? null;
      case 'insured_person_name':
        // 本人の場合は利用者氏名、それ以外はinsured_name
        if ($insurance && isset($insurance->subject_type) && $insurance->subject_type === '本人') {
          return ($clinicUser->last_name ?? '') . "\u{2002}" . ($clinicUser->first_name ?? '');
        }
        return $insurance->insured_name ?? null;
      case 'insured_person_gender':
        // 家族以外（本人・六歳・高齢等）は利用者の性別を使用
        if ($insurance && isset($insurance->subject_type) && $insurance->subject_type !== '家族') {
          return $clinicUser->gender ?? null;
        }
        return null;
      case 'insured_person_birthday':
        // サンプルモード時は customSampleData の和暦文字列をそのまま返す
        if ($this->sampleDataMode && isset($this->customSampleData['insured_person_birthday'])) {
          return $this->customSampleData['insured_person_birthday'];
        }
        // 家族以外（本人・六歳・高齢等）は利用者の生年月日を和暦変換
        if ($insurance && isset($insurance->subject_type) && $insurance->subject_type !== '家族') {
          if (isset($clinicUser->birthday)) {
            return $this->convertToJapaneseDate($clinicUser->birthday);
          }
        }
        return null;
      case 'insurance_valid_until':
        if (!$insurance || !isset($insurance->expiry_date)) return null;
        return $this->convertToJapaneseDate($insurance->expiry_date);
      case 'insured_person_postal_code':
        // 家族以外（本人・六歳・高齢等）は利用者の郵便番号を使用
        if ($insurance && isset($insurance->subject_type) && $insurance->subject_type !== '家族') {
          return $clinicUser->postal_code ?? null;
        }
        return null;
      case 'insured_person_address':
        // 家族以外（本人・六歳・高齢等）は利用者の住所を使用（address_1, address_2, address_3を結合）
        if ($insurance && isset($insurance->subject_type) && $insurance->subject_type !== '家族') {
          $address = ($clinicUser->address_1 ?? '') . ($clinicUser->address_2 ?? '') . ($clinicUser->address_3 ?? '');
          return !empty($address) ? $address : null;
        }
        return null;
      case 'insurance_qualification_date':
        if (!$insurance || !isset($insurance->license_acquisition_date)) return null;
        return $this->convertToJapaneseDate($insurance->license_acquisition_date);

      // 利用者情報
      case 'user_name': return ($clinicUser->last_name ?? '') . "\u{2002}" . ($clinicUser->first_name ?? '');
      case 'user_gender': return $clinicUser->gender ?? null;
      case 'user_birthday':
        if (isset($clinicUser->birthday)) {
          return $this->convertToJapaneseDate($clinicUser->birthday);
        }
        return null;
      case 'user_relationship': return $insurance->relationship ?? null;

      // 事業所情報
      case 'clinic_name': return $clinicInfo->clinic_name ?? null;
      case 'clinic_address':
        if (!$clinicInfo) return null;
        $address = ($clinicInfo->address_1 ?? '') . ($clinicInfo->address_2 ?? '') . ($clinicInfo->address_3 ?? '');
        return !empty($address) ? $address : null;

      // 保険者情報
      case 'insurer_address': return $insurance->insurer_address ?? null;
      case 'insurer_name': return $insurance->insurer_name ?? null;
      case 'insurer_number': return $insurance->insurer_number ?? null;

      // 傷病･施術情報
      case 'illness_name':
        if (!$consent) return null;
        $illnessName = $consent->illness_name_acupuncture ?? '';
        return !empty($illnessName) ? $illnessName : null;
      case 'onset_date':
        if ($consent && isset($consent->onset_and_injury_date)) {
          return $this->convertToJapaneseDate($consent->onset_and_injury_date);
        }
        return null;
      case 'first_treatment_date':
        if ($consent && isset($consent->first_care_date)) {
          return $this->convertToJapaneseDate($consent->first_care_date);
        }
        return null;
      case 'treatment_end_date':
        if ($consent && isset($consent->consenting_end_date)) {
          return $this->convertToJapaneseDate($consent->consenting_end_date);
        }
        return null;
      case 'treatment_days_count': return (string)$records->count();
      case 'treatment_count': return (string)$records->count();
      case 'outcome': return $consent->outcome ?? null;

      // 同意記録
      case 'medical_institution_name': return $consent->medical_institution_name ?? null;
      case 'medical_institution_address':
        if (!$consent) return null;
        $address = ($consent->doctor_address_1 ?? '') . ($consent->doctor_address_2 ?? '') . ($consent->doctor_address_3 ?? '');
        return !empty($address) ? $address : null;
      case 'medical_institution_phone':
        if (!$consent) return null;
        // 固定電話優先、なければ携帯電話
        return $consent->doctor_phone ?? $consent->doctor_cell_phone ?? null;
      case 'doctor_name_kana':
        if (!$consent) return null;
        return trim(($consent->doctor_last_name_kana ?? '') . "\u{2002}" . ($consent->doctor_first_name_kana ?? ''));
      case 'doctor_name':
        if (!$consent) return null;
        return trim(($consent->doctor_last_name ?? '') . "\u{2002}" . ($consent->doctor_first_name ?? ''));
      case 'consent_category': return $consent->bill_category ?? null;
      case 'treatment_period': return $consent->therapy_period ?? null;
      case 'onset_cause': return $consent->condition_name ?? null;

      default: return null;
    }
  }
}
