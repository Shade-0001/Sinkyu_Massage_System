<?php

namespace App\Services\Print\Traits;

use Illuminate\Support\Facades\DB;

/**
 * はり・きゅう医療助成費支給申請書PDF - サンプルデータ生成
 */
trait MedicalAssistanceAcupunctureSampleDataTrait
{
  protected function getSampleData(string $serviceYearMonth): array
  {
    // カスタムサンプルデータがあればそれを優先的に使用
    $custom = $this->customSampleData;

    \Log::info('getSampleData実行', [
      'custom_exists' => !empty($custom),
      'last_name' => $custom['last_name'] ?? 'なし',
      'first_name' => $custom['first_name'] ?? 'なし',
      'last_kana' => $custom['last_kana'] ?? 'なし',
      'first_kana' => $custom['first_kana'] ?? 'なし',
    ]);

    // サンプル利用者情報
    $clinicUser = (object)[
      'id' => 999,
      'last_name' => $custom['last_name'] ?? '山田',
      'first_name' => $custom['first_name'] ?? '太郎',
      'last_kana' => $custom['last_kana'] ?? 'ヤマダ',
      'first_kana' => $custom['first_kana'] ?? 'タロウ',
      'gender' => $custom['gender'] ?? '男',
      'birthday' => $custom['birthdate'] ?? '1950-04-15',
      'postal_code' => '123-4567',
      'address_1' => $custom['address'] ?? '東京都新宿区西新宿1-2-3',
      'address_2' => '',
      'address_3' => '',
    ];

    // サンプル保険情報
    $insurance = (object)[
      'insurer_number' => $custom['insurer_number'] ?? '12345678',
      'insurer_name' => $custom['insurer_name'] ?? 'サンプル健康保険組合',
      'insurance_type_1_id' => 1,
      'insurance_type_1' => $custom['insurance_type_1'] ?? '社･国･組',
      'insurance_type_3' => $custom['insurance_type_3'] ?? '本外',
      'expenses_borne_ratio' => $custom['expenses_borne_ratio'] ?? '３割',
      'code_number' => $custom['insurance_symbol_code'] ?? '12345',
      'account_number' => $custom['insurance_symbol_number'] ?? '67890',
      'insured_number' => $custom['insurance_number'] ?? '1234567890',
      'relationship' => $custom['relationship'] ?? '本人',
      'public_funds_payer_code' => '12345678',
      'public_funds_recipient_code' => '1234567',
      'locality_code' => '123456',
      'recipient_code' => '123456',
    ];

    // サンプル同意書情報
    $consent = (object)[
      'consenting_date' => $custom['consent_date'] ?? date('Y-m-d'),
      'consenting_doctor_name' => $custom['doctor_name'] ?? '鈴木医師',
      'illness_name_acupuncture_id' => 1,
      'illness_name_acupuncture_addendum' => $custom['disease'] ?? '追加症状',
      'onset_and_injury_date' => '2024-01-01',
      'therapy_period' => '6ヶ月',
      'bill_category' => '新規',
      'outcome' => '継続',
      'work_scope_type' => 'その他',
    ];

    // サンプル施術実績（月の1日、5日、10日、15日、20日、25日）
    $treatmentDays = $custom['treatment_days'] ?? 15;
    $abstractText = $custom['abstract'] ?? '特記事項なし';
    // therapy_content_id を付与してエラー回避＆描画対象判定を可能にする
    // 11:はり, 12:きゅう, 14:電気針, 15:電気温灸器, 16:電気光線器具
    $records = collect([
      (object)['date' => $serviceYearMonth . '-01', 'therapy_category' => 1, 'therapy_content_id' => 11, 'abstract' => $abstractText],
      (object)['date' => $serviceYearMonth . '-05', 'therapy_category' => 1, 'therapy_content_id' => 11, 'abstract' => $abstractText],
      (object)['date' => $serviceYearMonth . '-10', 'therapy_category' => 2, 'therapy_content_id' => 12, 'abstract' => $abstractText],
      (object)['date' => $serviceYearMonth . '-15', 'therapy_category' => 1, 'therapy_content_id' => 14, 'abstract' => $abstractText],
      (object)['date' => $serviceYearMonth . '-20', 'therapy_category' => 2, 'therapy_content_id' => 15, 'abstract' => $abstractText],
      (object)['date' => $serviceYearMonth . '-25', 'therapy_category' => 1, 'therapy_content_id' => 16, 'abstract' => $abstractText],
    ]);

    // サンプル施術所情報
    $clinicInfo = (object)[
      'medical_institution_number' => $custom['institution_code'] ?? '1234567',
      'clinic_name' => $custom['clinic_name'] ?? 'サンプル鍼灸院',
      'postal_code' => '100-0001',
      'address_1' => $custom['clinic_address'] ?? '東京都千代田区千代田1-1-1',
      'address_2' => '',
      'address_3' => '',
      'phone' => $custom['clinic_phone'] ?? '03-1234-5678',
      'therapist_number' => '東京-12345',
      'health_center_registerd_location' => '施術所所在地',
    ];

    // サンプル施術料金データを取得
    $treatmentFees = DB::table('treatment_fees')
      ->orderBy('created_at', 'desc')
      ->first();

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

}