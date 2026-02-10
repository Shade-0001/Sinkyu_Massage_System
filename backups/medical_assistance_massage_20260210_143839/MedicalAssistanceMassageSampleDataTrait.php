<?php

namespace App\Services\Print\Traits;

use Illuminate\Support\Facades\DB;

/**
 * あんま・マッサージ医療助成費支給申請書PDF - サンプルデータ生成
 */
trait MedicalAssistanceMassageSampleDataTrait
{
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
      'insured_name' => $custom['insured_person_name'] ?? '佐藤 花子',
    ];

    // サンプル同意書情報
    $consent = (object)[
      'consenting_date' => $custom['consent_date'] ?? '2024-12-10',
      'consenting_doctor_name' => $custom['consent_record_doctor_name'] ?? '伊司田 一郎',
      'injury_and_illness_name' => $custom['consent_record_illness_name'] ?? '五十肩',
      'treatment_period' => $custom['required_treatment_period'] ?? '2026/04/10',
      'bill_category' => '継続',
      'outcome' => '継続',
      'work_scope_type' => 'その他',
      'notes' => $custom['remarks'] ?? '転倒により右肩を負傷。関節可動域制限あり。',
      'condition_name' => $custom['condition'] ?? '良好',
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
      'name' => $custom['consent_record_doctor_name'] ?? '伊司田 一郎',
      'postal_code' => $custom['consent_record_doctor_postal_code'] ?? '8800002',
      'address_1' => $custom['consent_record_doctor_address'] ?? '宮崎県宮崎市中央通254-2',
      'address_2' => '',
      'address_3' => '',
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

}