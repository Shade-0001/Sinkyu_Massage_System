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

    // 自費施術の場合、insurance_categoryが空文字列ならnullに変換
    if ($this->isSelfFeeSelected() && $this->insurance_category === '') {
      $this->merge([
        'insurance_category' => null,
      ]);
    }
  }

  /**
   * 自費施術が選択されているかチェック
   */
  protected function isSelfFeeSelected(): bool
  {
    return str_starts_with($this->therapy_content_id ?? '', 'self_');
  }

  /**
   * バリデーションルールを取得
   */
  public function rules(): array
  {
    // 登録対象日時点で有効な clinic_info から営業時間を取得
    $recordDate  = $this->input('date') ?? date('Y-m-d');
    $clinicInfo  = DB::table('clinic_info')
      ->where('created_at', '<=', $recordDate . ' 23:59:59')
      ->orderByDesc('created_at')
      ->first();
    if (!$clinicInfo) {
      $clinicInfo = DB::table('clinic_info')->orderBy('created_at')->first();
    }
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
      'insurance_category' => $this->isSelfFeeSelected() ? 'nullable|integer' : 'required|integer',
      'housecall_distance' => 'nullable|array',
      'housecall_distance.*' => 'nullable|numeric|min:0',
      'consent_expiry' => 'nullable|date_format:Y/m/d',
      'therapy_content_id' => ['required', function ($attribute, $value, $fail) {
        // 自費施術の場合（self_で始まる）
        if (str_starts_with($value, 'self_')) {
          $selfFeeId = (int) str_replace('self_', '', $value);
          if (!DB::table('self_fees')->where('id', $selfFeeId)->exists()) {
            $fail('指定された自費施術が存在しません。');
          }
        } else {
          // 通常の施術内容の場合
          if (!is_numeric($value) || !DB::table('therapy_contents')->where('id', $value)->exists()) {
            $fail('指定された施術内容が存在しません。');
          }
        }
      }],
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

  public function attributes(): array
  {
    return [
      'clinic_user_id'           => '利用者',
      'start_time'               => '開始時刻',
      'end_time'                 => '終了時刻',
      'therapy_type'             => '施術種類',
      'therapy_category'         => '施術区分',
      'insurance_category'       => '保険区分',
      'housecall_distance'       => '往療距離',
      'consent_expiry'           => '同意有効期限',
      'therapy_content_id'       => '施術内容',
      'bill_category_id'         => '請求区分',
      'therapist_id'             => '施術者',
      'bodyparts'                => '身体部位',
      'duplicate_massage'        => '重複（マッサージ）',
      'duplicate_warm_compress'  => '重複（温罨法）',
      'duplicate_warm_electric'  => '重複（温電気）',
      'duplicate_manual_correction' => '重複（変形徒手矯正術）',
      'abstract'                 => '摘要',
    ];
  }

  /**
   * カスタムエラーメッセージ
   */
  public function messages(): array
  {
    return [
      'clinic_user_id.required' => '利用者IDは必須項目です。',
      'clinic_user_id.exists' => '指定された利用者が存在しません。',
      'start_time.required' => '開始時刻は必須項目です。',
      'end_time.required' => '終了時刻は必須項目です。',
      'end_time.after' => '終了時刻は開始時刻より後にする必要があります。',
      'therapy_type.required' => '施術種類は必須項目です。',
      'therapy_type.in' => '施術種類が不正です。',
      'therapy_category.required' => '施術区分は必須項目です。',
      'therapy_category.in' => '施術区分が不正です。',
      'insurance_category.required' => '保険区分は必須項目です。',
      'housecall_distance.*.min' => '往療距離は0以上にする必要があります。',
      'therapy_content_id.required' => '施術内容は必須項目です。',
      'therapy_content_id.exists' => '指定された施術内容が存在しません。',
      'bill_category_id.required' => '請求区分は必須項目です。',
      'bill_category_id.exists' => '指定された請求区分が存在しません。',
      'therapist_id.required' => '施術者は必須項目です。',
      'therapist_id.exists' => '指定された施術者が存在しません。',
      'bodyparts.*.exists' => '指定された身体部位が存在しません。',
    ];
  }
}
