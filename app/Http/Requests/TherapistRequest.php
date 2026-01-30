<?php
//-- app/Http/Requests/TherapistRequest.php --//


namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TherapistRequest extends FormRequest
{
  /**
   * リクエストが許可されているか判定
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * バリデーション前のデータ準備
   */
  protected function prepareForValidation()
  {
    // 空文字列をnullに変換（integer型フィールド）
    $integerFields = [
      'license_hari_code_number',
      'license_kyu_code_number',
      'license_massage_code_number',
      'member_number',
    ];

    $data = [];
    foreach ($integerFields as $field) {
      if ($this->has($field) && $this->input($field) === '') {
        $data[$field] = null;
      }
    }

    if (!empty($data)) {
      $this->merge($data);
    }
  }

  /**
   * バリデーションルールを取得
   */
  public function rules(): array
  {
    return [
      'last_name' => 'required|max:255',
      'first_name' => 'required|max:255',
      'last_name_kana' => 'nullable|max:255',
      'first_name_kana' => 'nullable|max:255',
      'postal_code' => 'nullable|max:8',
      'address_1' => 'nullable|max:255',
      'address_2' => 'nullable|max:255',
      'address_3' => 'nullable|max:255',
      'phone' => 'nullable|max:255',
      'cell_phone' => 'nullable|max:255',
      'fax' => 'nullable|max:255',
      'email' => 'nullable|email|max:255',
      'license_hari_code_number' => 'nullable|integer',
      'license_hari_issued_date' => 'nullable|date',
      'license_kyu_code_number' => 'nullable|integer',
      'license_kyu_issued_date' => 'nullable|date',
      'license_massage_code_number' => 'nullable|integer',
      'license_massage_issued_date' => 'nullable|date',
      'member_number' => 'nullable|integer',
      'note' => 'nullable|max:255',
    ];
  }

  /**
   * カスタムエラーメッセージ
   */
  public function messages(): array
  {
    return [
      'last_name.required' => '姓は必須です。',
      'last_name.max' => '姓は255文字以内で入力してください。',
      'first_name.required' => '名は必須です。',
      'first_name.max' => '名は255文字以内で入力してください。',
      'email.email' => '正しいメールアドレス形式で入力してください。',
    ];
  }
}
