<?php
//-- app/Http/Requests/RecordRequest.php --//


namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class RecordRequest extends FormRequest
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
  protected function prepareForValidation(): void
  {
    // consent_expiryが空文字列の場合はnullに変換
    if ($this->consent_expiry === '') {
      $this->merge([
        'consent_expiry' => null,
      ]);
    }
  }

  /**
   * バリデーションルールを取得
   */
  public function rules(): array
  {
    // 営業時間を取得
    $clinicInfo = DB::table('clinic_info')->first();
    $businessHoursStart = $clinicInfo->business_hours_start ? substr($clinicInfo->business_hours_start, 0, 5) : null;
    $businessHoursEnd = $clinicInfo->business_hours_end ? substr($clinicInfo->business_hours_end, 0, 5) : null;

    return [
      'clinic_user_id' => 'required|integer|exists:clinic_users,id',
      'start_time' => ['required', 'date_format:H:i', function ($_, $value, $fail) use ($businessHoursStart, $businessHoursEnd) {
        $minutes = (int) substr($value, 3, 2);
        if ($minutes % 10 !== 0) {
          $fail('開始時刻は10分刻みで入力してください。');
        }
        // 営業時間チェック
        if ($businessHoursStart && $businessHoursEnd) {
          if ($value < $businessHoursStart || $value >= $businessHoursEnd) {
            $fail('開始時刻は営業時間 (' . $businessHoursStart . '～' . $businessHoursEnd . ') の範囲内で入力してください。');
          }
        }
      }],
      'end_time' => ['required', 'date_format:H:i', 'after:start_time', function ($_, $value, $fail) use ($businessHoursStart, $businessHoursEnd) {
        $minutes = (int) substr($value, 3, 2);
        if ($minutes % 10 !== 0) {
          $fail('終了時刻は10分刻みで入力してください。');
        }
        // 営業時間チェック
        if ($businessHoursStart && $businessHoursEnd) {
          if ($value < $businessHoursStart || $value > $businessHoursEnd) {
            $fail('終了時刻は営業時間 (' . $businessHoursStart . '～' . $businessHoursEnd . ') の範囲内で入力してください。');
          }
        }
      }],
      'therapy_type' => 'required|in:1,2',
      'therapy_category' => 'required|in:1,2',
      'insurance_category' => 'required|integer',
      'housecall_distance' => 'nullable|array',
      'housecall_distance.*' => 'nullable|numeric|min:0',
      'consent_expiry' => 'nullable|date_format:Y/m/d',
      'therapy_content_id' => 'required|integer|exists:therapy_contents,id',
      'bill_category_id' => 'required|integer|exists:bill_categories,id',
      'therapist_id' => 'required|integer|exists:therapists,id',
      'bodyparts' => 'nullable|array',
      'bodyparts.*' => 'integer|exists:bodyparts,id',
      'duplicate_massage' => 'nullable|boolean',
      'duplicate_warm_compress' => 'nullable|boolean',
      'duplicate_warm_electric' => 'nullable|boolean',
      'duplicate_manual_correction' => 'nullable|boolean',
      'abstract' => 'nullable|string',
    ];
  }

  /**
   * カスタムエラーメッセージ
   */
  public function messages(): array
  {
    return [
      'clinic_user_id.required' => '利用者IDは必須です。',
      'clinic_user_id.exists' => '指定された利用者が存在しません。',
      'start_time.required' => '開始時刻は必須です。',
      'end_time.required' => '終了時刻は必須です。',
      'end_time.after' => '終了時刻は開始時刻より後にする必要があります。',
      'therapy_type.required' => '施術種類は必須です。',
      'therapy_type.in' => '施術種類が不正です。',
      'therapy_category.required' => '施術区分は必須です。',
      'therapy_category.in' => '施術区分が不正です。',
      'insurance_category.required' => '保険区分は必須です。',
      'housecall_distance.*.min' => '往療距離は0以上にする必要があります。',
      'therapy_content_id.required' => '施術内容は必須です。',
      'therapy_content_id.exists' => '指定された施術内容が存在しません。',
      'bill_category_id.required' => '請求区分は必須です。',
      'bill_category_id.exists' => '指定された請求区分が存在しません。',
      'therapist_id.required' => '施術者は必須です。',
      'therapist_id.exists' => '指定された施術者が存在しません。',
      'bodyparts.*.exists' => '指定された身体部位が存在しません。',
    ];
  }
}
