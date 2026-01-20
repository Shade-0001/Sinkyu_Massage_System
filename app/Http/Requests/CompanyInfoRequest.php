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
   * カスタムエラーメッセージ
   */
  public function messages(): array
  {
    return [
      'clinic_name.max' => '事業所名は255文字以内で入力してください。',
      'owner_last_name.max' => '代表者氏名（姓）は255文字以内で入力してください。',
      'owner_first_name.max' => '代表者氏名（名）は255文字以内で入力してください。',
      'email.email' => '正しいメールアドレス形式で入力してください。',
      'email.max' => 'メールアドレスは255文字以内で入力してください。',
    ];
  }
}
