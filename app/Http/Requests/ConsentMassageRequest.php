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

  public function attributes(): array
  {
    return [
      'consenting_doctor_id'              => '同意医師',
      'consenting_date'                   => '同意日',
      'consenting_start_date'             => '同意開始日',
      'consenting_end_date'               => '同意終了日',
      'benefit_period_start_date'         => '給付期間開始日',
      'benefit_period_end_date'           => '給付期間終了日',
      'first_care_date'                   => '初療日',
      'injury_and_illness_name_id'        => '傷病名',
      'disease_name_custom'               => '傷病名（新規）',
      'illness_name_acupuncture_id'       => '病名',
      'illness_name_acupuncture_addendum' => '病名（補足）',
      'reconsenting_expiry'               => '再同意期限',
      'bill_category_id'                  => '請求区分',
      'outcome_id'                        => '転帰',
      'symptom1'                          => '症状１',
      'symptom2'                          => '症状２',
      'symptom2_joint_disorder'           => '症状２（関節拘縮）',
      'symptom2_other'                    => '症状２（その他）',
      'symptom2_other_text'               => '症状２（その他テキスト）',
      'symptom3_other'                    => '症状３（その他）',
      'symptom3'                          => '症状３',
      'treatment_type1'                   => '施術種類１',
      'treatment_type2'                   => '施術種類２',
      'treatment_type2_corrective_hand'   => '施術種類２（矯正手技）',
      'is_housecall_required'             => '往療要否',
      'housecall_reason_id'               => '往療理由',
      'housecall_reason_addendum'         => '往療理由（補足）',
      'care_level'                        => '介護度',
      'notes'                             => '備考',
      'therapy_period'                    => '施術期間',
      'first_therapy_content_id'          => '初回施術内容',
      'condition_id'                      => '発病負傷経過',
      'disease_progress_custom'           => '発病負傷経過（新規）',
      'condition_custom'                  => '発病負傷経過（新規）',
      'work_scope_type_id'                => '業務上外等区分',
      'onset_and_injury_date'             => '発症・負傷日',
    ];
  }

  /**
   * カスタムエラーメッセージ
   */
  public function messages(): array
  {
    return [
      'consenting_doctor_id.required' => '同意医師は必須項目です。',
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
