<?php
//-- app/Http/Requests/InsuranceRequest.php --//


namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InsuranceRequest extends FormRequest
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
    $rules = [
      'insurance_type_1' => 'required|string',
      'insurance_type_2' => 'required|string',
      'insurance_type_3' => 'required|string',
      'insured_person_type' => 'required|string',
      'insured_number' => 'required|integer',
      'code_number' => 'nullable|string',
      'account_number' => 'nullable|string',
      'locality_code' => 'nullable|string',
      'recipient_code' => 'nullable|string',
      'license_acquisition_date' => 'nullable|date',
      'certification_date' => 'nullable|date',
      'issue_date' => 'nullable|date',
      'expenses_borne_ratio' => 'nullable|string',
      'expiry_date' => 'nullable|date',
      'is_redeemed' => 'nullable|boolean',
      'insured_name' => 'nullable|string|max:255',
      'relationship_with_clinic_user' => 'nullable|string',
      'is_healthcare_subsidized' => 'nullable|boolean',
      'public_funds_payer_code' => 'nullable|string',
      'public_funds_recipient_code' => 'nullable|string',
      'locality_code_family' => 'nullable|string',
      'recipient_code_family' => 'nullable|string',
      'selected_insurer' => 'nullable|integer|exists:insurers,id',
      'new_insurer_number' => 'nullable|string|regex:/^\d{6}(\d{2})?$/',
      'new_insurer_name' => 'nullable|string|max:255',
      'new_postal_code' => 'nullable|string|max:8',
      'new_address' => 'nullable|string|max:255',
      'new_recipient_name' => 'nullable|string|max:255'
    ];

    // 選択された保険者がない場合、新規保険者番号は必須
    if (!$this->filled('selected_insurer')) {
      $rules['new_insurer_number'] = 'required|string|regex:/^\d{6}(\d{2})?$/';
    }

    return $rules;
  }

  /**
   * フィールドのラベル名を定義
   */
  public function attributes(): array
  {
    return [
      'insurance_type_1'                  => '保険種別１',
      'insurance_type_2'                  => '保険種別２',
      'insurance_type_3'                  => '保険種別３',
      'insured_person_type'               => '被保険者区分',
      'insured_number'                    => '被保険者番号',
      'code_number'                       => '記号',
      'account_number'                    => '番号',
      'locality_code'                     => '地区コード',
      'recipient_code'                    => '受給者番号',
      'license_acquisition_date'          => '資格取得日',
      'certification_date'                => '認定日',
      'issue_date'                        => '交付年月日',
      'expenses_borne_ratio'              => '負担割合',
      'expiry_date'                       => '有効期限',
      'is_redeemed'                       => '償還対象',
      'insured_name'                      => '被保険者氏名',
      'relationship_with_clinic_user'     => '続柄',
      'is_healthcare_subsidized'          => '医療費助成対象',
      'public_funds_payer_code'           => '公費負担者番号',
      'public_funds_recipient_code'       => '公費受給者番号',
      'locality_code_family'              => '地区コード（家族）',
      'recipient_code_family'             => '受給者番号（家族）',
      'selected_insurer'                  => '保険者',
      'new_insurer_number'                => '保険者番号',
      'new_insurer_name'                  => '保険者名',
      'new_postal_code'                   => '郵便番号',
      'new_address'                       => '所在地',
      'new_recipient_name'                => '給付担当者名',
    ];
  }

  /**
   * カスタムエラーメッセージ
   */
  public function messages(): array
  {
    return [
      'new_insurer_number.required' => '保険者番号は必須項目です。',
      'new_insurer_number.regex' => '保険者番号は6桁または8桁の数字を入力してください。',
    ];
  }

  /**
   * バリデーション前の処理（チェックボックスの変換）
   */
  protected function prepareForValidation(): void
  {
    $this->merge([
      'is_redeemed' => $this->has('is_redeemed'),
      'is_healthcare_subsidized' => $this->has('is_healthcare_subsidized')
    ]);
  }
}
