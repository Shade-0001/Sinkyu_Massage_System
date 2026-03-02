<?php
//-- app/Http/Requests/ConsentMassageRequest.php --//


namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsentMassageRequest extends FormRequest
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
      'consenting_doctor_id' => 'required|integer|exists:doctors,id',
      'consenting_date' => 'nullable|date',
      'consenting_start_date' => 'nullable|date',
      'consenting_end_date' => 'nullable|date',
      'benefit_period_start_date' => 'nullable|date',
      'benefit_period_end_date' => 'nullable|date',
      'first_care_date' => 'nullable|date',
      // マッサージ用
      'injury_and_illness_name_id' => 'nullable|integer|exists:illnesses_massage,id',
      'disease_name_custom' => 'nullable|string|max:255',
      // 鍼灸用
      'illness_name_acupuncture_id' => 'nullable|integer|exists:illnesses_massage,id',
      'illness_name_acupuncture_addendum' => 'nullable|string|max:255',
      'reconsenting_expiry' => 'nullable|date',
      'bill_category_id' => 'nullable|integer|exists:bill_categories,id',
      'outcome_id' => 'nullable|integer|exists:outcomes,id',
      'symptom1' => 'nullable|array',
      'symptom1.*' => 'string',
      'symptom2_joint_disorder' => 'nullable|boolean',
      'symptom2' => 'nullable|array',
      'symptom2.*' => 'string',
      'symptom2_other' => 'nullable|boolean',
      'symptom2_other_text' => 'nullable|string|max:255',
      'symptom3_other' => 'nullable|boolean',
      'symptom3' => 'nullable|string',
      'treatment_type1' => 'nullable|array',
      'treatment_type1.*' => 'string',
      'treatment_type2_corrective_hand' => 'nullable|boolean',
      'treatment_type2' => 'nullable|array',
      'treatment_type2.*' => 'string',
      'is_housecall_required' => 'nullable|in:0,1',
      'housecall_reason_id' => 'nullable|integer|exists:housecall_reasons,id',
      'housecall_reason_addendum' => 'nullable|string|max:255',
      'care_level' => 'nullable|string|max:255',
      'notes' => 'nullable|string|max:255',
      'therapy_period' => 'nullable|string|max:255',
      'first_therapy_content_id' => 'nullable|integer|exists:therapy_contents,id',
      // マッサージ用
      'condition_id' => 'nullable|integer|exists:conditions,id',
      'disease_progress_custom' => 'nullable|string|max:255',
      // 鍼灸用（condition_idに統一）
      'condition_custom' => 'nullable|string|max:255',
      'work_scope_type_id' => 'nullable|integer|exists:work_scope_types,id',
      'onset_and_injury_date' => 'nullable|date',
    ];
  }

  /**
   * カスタムエラーメッセージ
   */
  public function messages(): array
  {
    return [
      'consenting_doctor_id.required' => '同意医師は必須です。',
      'consenting_doctor_id.exists' => '選択された同意医師が無効です。',
      'consenting_date.date' => '同意日は正しい日付形式で入力してください。',
      'consenting_start_date.date' => '同意開始日は正しい日付形式で入力してください。',
      'consenting_end_date.date' => '同意終了日は正しい日付形式で入力してください。',
      'benefit_period_start_date.date' => '給付期間開始日は正しい日付形式で入力してください。',
      'benefit_period_end_date.date' => '給付期間終了日は正しい日付形式で入力してください。',
      'first_care_date.date' => '初療日は正しい日付形式で入力してください。',
      // マッサージ用
      'injury_and_illness_name_id.exists' => '選択された傷病名が無効です。',
      'disease_name_custom.max' => '傷病名（新規）は255文字以内で入力してください。',
      // 鍼灸用
      'illness_name_acupuncture_id.exists' => '選択された病名が無効です。',
      'illness_name_acupuncture_addendum.max' => '病名（新規）は255文字以内で入力してください。',
      'reconsenting_expiry.date' => '再同意期限は正しい日付形式で入力してください。',
      'bill_category_id.exists' => '選択された請求区分が無効です。',
      'outcome_id.exists' => '選択された転帰が無効です。',
      'housecall_reason_id.exists' => '選択された往療理由が無効です。',
      'first_therapy_content_id.exists' => '選択された初回施術内容が無効です。',
      // マッサージ用
      'condition_id.exists' => '選択された発病負傷経過が無効です。',
      'disease_progress_custom.max' => '発病負傷経過（新規）は255文字以内で入力してください。',
      'condition_custom.max' => '発病負傷経過（新規）は255文字以内で入力してください。',
      'work_scope_type_id.exists' => '選択された業務上外等区分が無効です。',
      'onset_and_injury_date.date' => '発症・負傷日は正しい日付形式で入力してください。',
      'notes.max' => '備考は255文字以内で入力してください。',
    ];
  }
}
