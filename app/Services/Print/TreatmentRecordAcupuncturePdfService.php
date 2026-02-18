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
      ->where('insurances.clinic_user_id', $clinicUserId)
      ->orderBy('insurances.created_at', 'desc')
      ->select(
        'insurances.*',
        'insurers.insurer_number',
        'insurers.insurer_name',
        'insurers.postal_code as insurer_postal_code',
        'insurers.address as insurer_address',
        'relationships_with_clinic_user.relationship'
      )
      ->first();

    // 同意書情報取得
    $consent = DB::table('consents_acupuncture')
      ->leftJoin('outcomes', 'consents_acupuncture.outcome_id', '=', 'outcomes.id')
      ->where('consents_acupuncture.clinic_user_id', $clinicUserId)
      ->orderBy('consents_acupuncture.consenting_date', 'desc')
      ->select(
        'consents_acupuncture.*',
        'outcomes.outcome'
      )
      ->first();

    // 施術実績取得
    $records = DB::table('records')
      ->where('clinic_user_id', $clinicUserId)
      ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$serviceYearMonth])
      ->orderBy('date')
      ->get();

    // 施術所情報取得
    $clinicInfo = DB::table('clinic_info')->first();

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
      'gender' => $custom['user_gender'] ?? '男',
      'birthday' => $custom['user_birthday'] ?? '昭和55年 3月 15日',
    ];

    $insurance = (object)[
      'code_number' => $custom['insurance_symbol_code'] ?? '12345',
      'account_number' => $custom['insurance_symbol_number'] ?? '67890',
      'insured_person_name' => $custom['insured_person_name'] ?? '田中 花子',
      'insured_person_gender' => $custom['insured_person_gender'] ?? '女',
      'insured_person_birthday' => $custom['insured_person_birthday'] ?? '昭和30年 5月 10日',
      'insurance_valid_until' => $custom['insurance_valid_until'] ?? '令和10年 3月 31日',
      'insured_person_postal_code' => $custom['insured_person_postal_code'] ?? '1600022',
      'insured_person_address' => $custom['insured_person_address'] ?? '東京都新宿区新宿2-3-4',
      'insurance_qualification_date' => $custom['insurance_qualification_date'] ?? '令和7年 4月 1日',
      'relationship' => $custom['user_relationship'] ?? '子',
      'public_funds_payer_code' => $custom['public_funds_payer_number'] ?? '12345678',
      'public_funds_recipient_code' => $custom['public_funds_recipient_number'] ?? '1234567',
      'locality_code' => $custom['locality_code'] ?? '123456',
      'recipient_code' => $custom['recipient_number'] ?? '123456',
      'insurer_number' => $custom['insurer_number'] ?? '12345678',
      'insurer_name' => $custom['insurer_name'] ?? 'サンプル健康保険組合',
      'insurer_postal_code' => $custom['payment_institution_postal_code'] ?? '1000005',
      'insurer_address' => $custom['insurer_address'] ?? '東京都千代田区霞が関1-1-1',
    ];

    $consent = (object)[
      'illness_name_acupuncture_addendum' => $custom['disease'] ?? '腰痛症',
      'onset_and_injury_date' => $custom['onset_date'] ?? '令和7年 11月 15日',
      'first_treatment_date' => $custom['first_treatment_date'] ?? '令和7年 11月 20日',
      'therapy_period' => $custom['treatment_period'] ?? '3ヶ月',
      'outcome' => $custom['outcome'] ?? '継続',
      'consenting_doctor_name' => $custom['doctor_name'] ?? '山田太郎',
      'consenting_doctor_name_kana' => $custom['doctor_name_kana'] ?? 'ヤマダ タロウ',
      'medical_institution_name' => $custom['medical_institution'] ?? '〇〇病院',
      'medical_institution_address' => $custom['doctor_address'] ?? '東京都新宿区〇〇1-2-3',
      'medical_institution_phone' => $custom['medical_institution_phone'] ?? '03-3456-7890',
      'consent_category' => $custom['consent_category'] ?? '新規同意',
      'onset_cause' => $custom['onset_cause'] ?? '階段からの転落',
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
      'address_1' => $custom['clinic_address'] ?? '東京都渋谷区〇〇1-2-3',
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

      $pdf->SetXY($coord['x'], $coord['y']);
      $pdf->SetFontSize($coord['fontSize'] ?? 10);
      $pdf->Cell(0, 0, $value, 0, 0, $coord['textAlign'] ?? 'L');
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

      // 被保険者情報
      case 'insurance_symbol_code': return $insurance->code_number ?? null;
      case 'insurance_symbol_number': return $insurance->account_number ?? null;
      case 'insured_person_name': return $insurance->insured_person_name ?? null;
      case 'insured_person_birthday': return $insurance->insured_person_birthday ?? null;
      case 'insurance_valid_until': return $insurance->insurance_valid_until ?? null;
      case 'insured_person_postal_code': return $insurance->insured_person_postal_code ?? null;
      case 'insured_person_address': return $insurance->insured_person_address ?? null;
      case 'insurance_qualification_date': return $insurance->insurance_qualification_date ?? null;

      // 利用者情報
      case 'user_name': return ($clinicUser->last_name ?? '') . ' ' . ($clinicUser->first_name ?? '');
      case 'user_birthday': return $clinicUser->birthday ?? null;
      case 'user_relationship': return $insurance->relationship ?? null;

      // 事業所情報
      case 'clinic_name': return $clinicInfo->clinic_name ?? null;
      case 'clinic_address': return $clinicInfo->address_1 ?? null;

      // 保険者情報
      case 'insurer_address': return $insurance->insurer_address ?? null;
      case 'insurer_name': return $insurance->insurer_name ?? null;
      case 'insurer_number': return $insurance->insurer_number ?? null;

      // 傷病･施術情報
      case 'illness_name': return $consent->illness_name_acupuncture_addendum ?? null;
      case 'onset_date': return $consent->onset_and_injury_date ?? null;
      case 'first_treatment_date': return $consent->first_treatment_date ?? null;
      case 'treatment_end_date': return $records->last()->date ?? null;
      case 'treatment_days_count': return (string)$records->count();
      case 'treatment_count': return (string)$records->count();
      case 'outcome': return $consent->outcome ?? null;

      // 同意記録
      case 'medical_institution_name': return $consent->medical_institution_name ?? null;
      case 'medical_institution_address': return $consent->medical_institution_address ?? null;
      case 'medical_institution_phone': return $consent->medical_institution_phone ?? null;
      case 'doctor_name_kana': return $consent->consenting_doctor_name_kana ?? null;
      case 'doctor_name': return $consent->consenting_doctor_name ?? null;
      case 'consent_category': return $consent->consent_category ?? null;
      case 'treatment_period': return $consent->therapy_period ?? null;
      case 'onset_cause': return $consent->onset_cause ?? null;

      default: return null;
    }
  }
}
