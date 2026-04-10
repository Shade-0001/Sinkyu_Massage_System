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
   * フィールドのラベル名を定義
   */
  public function attributes(): array
  {
    return [
      'last_name'                   => '姓',
      'first_name'                  => '名',
      'last_name_kana'              => 'セイ',
      'first_name_kana'             => 'メイ',
      'postal_code'                 => '郵便番号',
      'address_1'                   => '都道府県',
      'address_2'                   => '市区町村番地以下',
      'address_3'                   => 'アパート・マンション名等',
      'phone'                       => '電話番号',
      'cell_phone'                  => '携帯番号',
      'fax'                         => 'FAX番号',
      'email'                       => 'メールアドレス',
      'license_hari_code_number'    => '免許番号（はり）',
      'license_hari_issued_date'    => '免許交付日（はり）',
      'license_kyu_code_number'     => '免許番号（きゅう）',
      'license_kyu_issued_date'     => '免許交付日（きゅう）',
      'license_massage_code_number' => '免許番号（マッサージ）',
      'license_massage_issued_date' => '免許交付日（マッサージ）',
      'member_number'               => '会員番号',
      'note'                        => 'メモ',
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
