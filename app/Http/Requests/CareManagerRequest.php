<?php
//-- app/Http/Requests/CareManagerRequest.php --//


namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CareManagerRequest extends FormRequest
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
      'last_name' => 'required|max:255',
      'first_name' => 'required|max:255',
      'last_name_kana' => 'nullable|max:255',
      'first_name_kana' => 'nullable|max:255',
      'service_providers_id' => 'nullable|integer|exists:service_providers,id',
      'service_provider_name_custom' => 'nullable|max:255',
      'postal_code' => 'nullable|max:8',
      'address_1' => 'nullable|max:255',
      'address_2' => 'nullable|max:255',
      'address_3' => 'nullable|max:255',
      'phone' => 'nullable|max:255',
      'cell_phone' => 'nullable|max:255',
      'fax' => 'nullable|max:255',
      'email' => 'nullable|email|max:255',
      'note' => 'nullable|max:255',
    ];
  }

  /**
   * フィールドのラベル名を定義
   */
  public function attributes(): array
  {
    return [
      'last_name'                    => '姓',
      'first_name'                   => '名',
      'last_name_kana'               => 'セイ',
      'first_name_kana'              => 'メイ',
      'service_providers_id'         => '事業所',
      'service_provider_name_custom' => '事業所名（新規）',
      'postal_code'                  => '郵便番号',
      'address_1'                    => '都道府県',
      'address_2'                    => '市区町村番地以下',
      'address_3'                    => 'アパート・マンション名等',
      'phone'                        => '電話番号',
      'cell_phone'                   => '携帯番号',
      'fax'                          => 'FAX番号',
      'email'                        => 'メールアドレス',
      'note'                         => 'メモ',
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
