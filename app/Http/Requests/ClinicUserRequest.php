<?php
//-- app/Http/Requests/ClinicUserRequest.php --//


namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClinicUserRequest extends FormRequest
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
      'id' => 'nullable|integer|exists:clinic_users,id',
      'last_name' => 'required|string|max:255',
      'first_name' => 'required|string|max:255',
      'last_kana' => 'required|string|max:255',
      'first_kana' => 'required|string|max:255',
      'birthday' => 'nullable|date',
      'age' => 'nullable|integer|min:0|max:150',
      'gender_id' => 'nullable|integer|in:1,2',
      'postal_code' => 'required|string|max:8',
      'address_1' => 'required|string|max:255',
      'address_2' => 'required|string|max:255',
      'address_3' => 'required|string|max:255',
      'phone' => 'nullable|string|max:20',
      'cell_phone' => 'nullable|string|max:20',
      'fax' => 'nullable|string|max:20',
      'email' => 'nullable|email|max:255',
      'housecall_distance' => 'nullable|integer|min:0',
      'housecall_additional_distance' => 'nullable|integer|min:0',
      'is_redeemed' => 'nullable|boolean',
      'application_count' => 'nullable|integer|min:0',
      'note' => 'nullable|string|max:1000'
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
      'last_kana'                    => 'セイ',
      'first_kana'                   => 'メイ',
      'birthday'                     => '生年月日',
      'age'                          => '年齢',
      'gender_id'                    => '性別',
      'postal_code'                  => '郵便番号',
      'address_1'                    => '都道府県',
      'address_2'                    => '市区町村番地以下',
      'address_3'                    => 'アパート・マンション名等',
      'phone'                        => '電話番号',
      'cell_phone'                   => '携帯番号',
      'fax'                          => 'FAX番号',
      'email'                        => 'メールアドレス',
      'housecall_distance'           => '往診距離',
      'housecall_additional_distance' => '往診加算距離',
      'is_redeemed'                  => '償還対象',
      'application_count'            => '申請書提出開始回数',
      'note'                         => 'メモ',
    ];
  }

  /**
   * バリデーション前の処理（チェックボックスの変換）
   */
  protected function prepareForValidation(): void
  {
    $this->merge([
      'is_redeemed' => $this->has('is_redeemed')
    ]);
  }
}
