<?php
//-- app/Http/Requests/CompanyInfoRequest.php --//

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyInfoRequest extends FormRequest
{
  /**
   * リクエストが許可されているか判定
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * バリデーションルールを取得
   */
  public function rules(): array
  {
    return [
      'clinic_name' => 'nullable|max:255',
      'owner_last_name' => 'nullable|max:255',
      'owner_first_name' => 'nullable|max:255',
      'owner_birthday' => 'nullable|date',
      'postal_code' => 'nullable|max:8',
      'address_1' => 'nullable|max:255',
      'address_2' => 'nullable|max:255',
      'address_3' => 'nullable|max:255',
      'phone' => 'nullable|max:255',
      'cellphone' => 'nullable|max:255',
      'freephone' => 'nullable|max:255',
      'fax' => 'nullable|max:255',
      'email' => 'nullable|email|max:255',
      'website_url' => 'nullable|max:255',
      'business_hours_start' => 'nullable',
      'business_hours_end' => 'nullable',
      'closed_day_monday' => 'nullable|boolean',
      'closed_day_tuesday' => 'nullable|boolean',
      'closed_day_wednesday' => 'nullable|boolean',
      'closed_day_thursday' => 'nullable|boolean',
      'closed_day_friday' => 'nullable|boolean',
      'closed_day_saturday' => 'nullable|boolean',
      'closed_day_sunday' => 'nullable|boolean',
      'bank_account_type' => 'nullable|max:255',
      'bank_name' => 'nullable|max:255',
      'bank_branch_name' => 'nullable|max:255',
      'bank_account_name' => 'nullable|max:255',
      'bank_account_name_kana' => 'nullable|max:255',
      'bank_code' => 'nullable|integer',
      'bank_branch_code' => 'nullable|integer',
      'bank_account_number' => 'nullable|integer',
      'health_center_registerd_location' => 'nullable|max:255',
      'license_hari_number' => 'nullable|integer',
      'license_hari_issued_date' => 'nullable|date',
      'license_kyu_number' => 'nullable|integer',
      'license_kyu_issued_date' => 'nullable|date',
      'license_massage_number' => 'nullable|integer',
      'license_massage_issued_date' => 'nullable|date',
      'billing_prefecture' => 'nullable|max:255',
      'therapist_number' => 'nullable|integer',
      'medical_institution_number' => 'nullable|integer',
      'should_round_amount' => 'nullable|boolean',
      'document_formats' => 'nullable|max:255',
    ];
  }

  /**
   * フィールドのラベル名を定義
   */
  public function attributes(): array
  {
    return [
      'clinic_name'                       => '事業所名',
      'owner_last_name'                   => '代表者氏名（姓）',
      'owner_first_name'                  => '代表者氏名（名）',
      'owner_birthday'                    => '代表者生年月日',
      'postal_code'                       => '郵便番号',
      'address_1'                         => '都道府県',
      'address_2'                         => '市区町村番地以下',
      'address_3'                         => 'アパート・マンション名等',
      'phone'                             => '電話番号',
      'cellphone'                         => '携帯番号',
      'freephone'                         => 'フリーダイヤル',
      'fax'                               => 'FAX番号',
      'email'                             => 'メールアドレス',
      'website_url'                       => 'ウェブサイトURL',
      'business_hours_start'              => '営業開始時間',
      'business_hours_end'                => '営業終了時間',
      'closed_day_monday'                 => '定休日（月）',
      'closed_day_tuesday'                => '定休日（火）',
      'closed_day_wednesday'              => '定休日（水）',
      'closed_day_thursday'               => '定休日（木）',
      'closed_day_friday'                 => '定休日（金）',
      'closed_day_saturday'               => '定休日（土）',
      'closed_day_sunday'                 => '定休日（日）',
      'bank_account_type'                 => '口座種別',
      'bank_name'                         => '銀行名',
      'bank_branch_name'                  => '支店名',
      'bank_account_name'                 => '口座名義',
      'bank_account_name_kana'            => '口座名義（カナ）',
      'bank_code'                         => '銀行コード',
      'bank_branch_code'                  => '支店コード',
      'bank_account_number'               => '口座番号',
      'health_center_registerd_location'  => '保健所登録所在地',
      'license_hari_number'               => '免許番号（はり）',
      'license_hari_issued_date'          => '免許交付日（はり）',
      'license_kyu_number'                => '免許番号（きゅう）',
      'license_kyu_issued_date'           => '免許交付日（きゅう）',
      'license_massage_number'            => '免許番号（マッサージ）',
      'license_massage_issued_date'       => '免許交付日（マッサージ）',
      'billing_prefecture'                => '請求先都道府県',
      'therapist_number'                  => '施術者数',
      'medical_institution_number'        => '医療機関コード',
      'should_round_amount'               => '金額丸め処理',
      'document_formats'                  => '書類フォーマット',
    ];
  }

  /**
   * カスタムエラーメッセージ
   */
  public function messages(): array
  {
    return [];
  }
}
