{{-- resources/views/clinic-users/consents-massage/components/consents-massage_form.blade.php --}}

<div class="consenting-form">
  @csrf

  <div class="d-flex flex-column gap-4 container ms-0">

    {{-- 同意医師名 --}}
    <div>
      <label class="form-label-tab" for="consenting_doctor_id">同意医師名
        @error('consenting_doctor_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <select id="consenting_doctor_id" name="consenting_doctor_id">
          <option value="">╌╌╌</option>
          @foreach($doctors ?? [] as $doctor)
            @php
              $doctorFullName = trim(($doctor->last_name ?? '') . "\u{2000}" . ($doctor->first_name ?? ''));
            @endphp
            <option value="{{ $doctor->id }}" {{ old('consenting_doctor_id', $history?->consenting_doctor_id ?? '') == $doctor->id ? 'selected' : '' }}>
              {{ $doctorFullName }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    {{-- 同意日 --}}
    <div>
      <label class="form-label-tab" for="consenting_date">同意日
        @error('consenting_date')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="consenting_date" name="consenting_date" value="{{ old('consenting_date', is_string($history?->consenting_date ?? null) ? $history->consenting_date : ($history?->consenting_date?->format('Y-m-d') ?? '')) }}">
      </div>
    </div>

    {{-- 同意開始年月日 --}}
    <div>
      <label class="form-label-tab" for="consenting_start_date">同意開始年月日
        @error('consenting_start_date')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="consenting_start_date" name="consenting_start_date" value="{{ old('consenting_start_date', is_string($history?->consenting_start_date ?? null) ? $history->consenting_start_date : ($history?->consenting_start_date?->format('Y-m-d') ?? '')) }}">
      </div>
    </div>

    {{-- 同意終了年月日 --}}
    <div>
      <label class="form-label-tab" for="consenting_end_date">同意終了年月日
        @error('consenting_end_date')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="consenting_end_date" name="consenting_end_date" value="{{ old('consenting_end_date', is_string($history?->consenting_end_date ?? null) ? $history->consenting_end_date : ($history?->consenting_end_date?->format('Y-m-d') ?? '')) }}">
      </div>
    </div>

    {{-- 支給期間 開始 --}}
    <div>
      <label class="form-label-tab" for="benefit_period_start_date">支給期間 開始
        @error('benefit_period_start_date')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="benefit_period_start_date" name="benefit_period_start_date" value="{{ old('benefit_period_start_date', is_string($history?->benefit_period_start_date ?? null) ? $history->benefit_period_start_date : ($history?->benefit_period_start_date?->format('Y-m-d') ?? '')) }}">
      </div>
    </div>

    {{-- 支給期間 終了 --}}
    <div>
      <label class="form-label-tab" for="benefit_period_end_date">支給期間 終了
        @error('benefit_period_end_date')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="benefit_period_end_date" name="benefit_period_end_date" value="{{ old('benefit_period_end_date', is_string($history?->benefit_period_end_date ?? null) ? $history->benefit_period_end_date : ($history?->benefit_period_end_date?->format('Y-m-d') ?? '')) }}">
      </div>
    </div>

    {{-- 初療年月日 --}}
    <div>
      <label class="form-label-tab" for="first_care_date">初療年月日
        @error('first_care_date')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="first_care_date" name="first_care_date" value="{{ old('first_care_date', is_string($history?->first_care_date ?? null) ? $history->first_care_date : ($history?->first_care_date?->format('Y-m-d') ?? '')) }}">
      </div>
    </div>

    {{-- 傷病名（あんま・マッサージ） --}}
    <div>
      <label class="form-label-tab" for="injury_and_illness_name_id">傷病名（あんま・マッサージ）
        @error('injury_and_illness_name_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('disease_name_custom')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <select id="injury_and_illness_name_id" name="injury_and_illness_name_id">
          <option value="">╌╌╌</option>
          @foreach($diseaseNames ?? [] as $disease)
            <option value="{{ $disease->id }}" {{ old('injury_and_illness_name_id', $history?->injury_and_illness_name_id ?? '') == $disease->id ? 'selected' : '' }}>
              {{ $disease->illness_name }}
            </option>
          @endforeach
        </select>
        <div class="mt-1"><small>上記欄に記入無い場合、下記に入力する文字でマスターとして登録。</small></div>
        <input type="text" id="disease_name_custom" name="disease_name_custom" placeholder="入力されたデータをマスターとして新規登録。" value="{{ old('disease_name_custom', '') }}">
      </div>
    </div>

    {{-- 再同意有効期限 --}}
    <div>
      <label class="form-label-tab" for="reconsenting_expiry">再同意有効期限
        @error('reconsenting_expiry')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="reconsenting_expiry" name="reconsenting_expiry" value="{{ old('reconsenting_expiry', is_string($history?->reconsenting_expiry ?? null) ? $history->reconsenting_expiry : ($history?->reconsenting_expiry?->format('Y-m-d') ?? '')) }}">
      </div>
    </div>

    {{-- 請求区分 --}}
    <div>
      <label class="form-label-tab" for="bill_category_id">請求区分
        @error('bill_category_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <select id="bill_category_id" name="bill_category_id">
          <option value="">╌╌╌</option>
          @foreach($billingCategories ?? [] as $category)
            <option value="{{ $category->id }}" {{ old('bill_category_id', $history?->bill_category_id ?? '') == $category->id ? 'selected' : '' }}>
              {{ $category->bill_category }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    {{-- 転帰 --}}
    <div>
      <label class="form-label-tab" for="outcome_id">転帰
        @error('outcome_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <select id="outcome_id" name="outcome_id">
          <option value="">╌╌╌</option>
          @foreach($outcomes ?? [] as $outcome)
            <option value="{{ $outcome->id }}" {{ old('outcome_id', $history?->outcome_id ?? '') == $outcome->id ? 'selected' : '' }}>
              {{ $outcome->outcome }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    {{-- 症状1 --}}
    <div>
      <div class="form-label-tab">症状1
        @error('symptom1')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </div>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <div>
          <input type="checkbox" id="symptom1_muscle_paralysis" name="symptom1[]" value="筋麻痺" {{ (is_array(old('symptom1', $history?->symptom1 ?? [])) && in_array('筋麻痺', old('symptom1', $history?->symptom1 ?? []))) ? 'checked' : '' }}>
          <label for="symptom1_muscle_paralysis">筋麻痺・筋萎縮</label>
        </div>
        <div class="d-flex gap-3 flex-wrap mt-1">
          <div>
            <input type="checkbox" id="symptom1_left_upper" name="symptom1[]" value="左上肢" {{ (is_array(old('symptom1', $history?->symptom1 ?? [])) && in_array('左上肢', old('symptom1', $history?->symptom1 ?? []))) ? 'checked' : '' }}>
            <label for="symptom1_left_upper">左上肢</label>
          </div>
          <div>
            <input type="checkbox" id="symptom1_right_upper" name="symptom1[]" value="右上肢" {{ (is_array(old('symptom1', $history?->symptom1 ?? [])) && in_array('右上肢', old('symptom1', $history?->symptom1 ?? []))) ? 'checked' : '' }}>
            <label for="symptom1_right_upper">右上肢</label>
          </div>
          <div>
            <input type="checkbox" id="symptom1_left_lower" name="symptom1[]" value="左下肢" {{ (is_array(old('symptom1', $history?->symptom1 ?? [])) && in_array('左下肢', old('symptom1', $history?->symptom1 ?? []))) ? 'checked' : '' }}>
            <label for="symptom1_left_lower">左下肢</label>
          </div>
          <div>
            <input type="checkbox" id="symptom1_right_lower" name="symptom1[]" value="右下肢" {{ (is_array(old('symptom1', $history?->symptom1 ?? [])) && in_array('右下肢', old('symptom1', $history?->symptom1 ?? []))) ? 'checked' : '' }}>
            <label for="symptom1_right_lower">右下肢</label>
          </div>
        </div>
      </div>
    </div>

    {{-- 症状2 --}}
    <div>
      <div class="form-label-tab">症状2
        @error('symptom2')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </div>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <div>
          <input type="checkbox" id="symptom2_joint_disorder" name="symptom2_joint_disorder" value="1" {{ old('symptom2_joint_disorder', $history?->symptom2_joint_disorder ?? false) ? 'checked' : '' }}>
          <label for="symptom2_joint_disorder">関節拘縮</label>
        </div>
        <div class="d-flex gap-3 flex-wrap mt-2">
          <div><input type="checkbox" id="symptom2_right_shoulder" name="symptom2[]" value="右肩" {{ (is_array(old('symptom2', $history?->symptom2 ?? [])) && in_array('右肩', old('symptom2', $history?->symptom2 ?? []))) ? 'checked' : '' }}><label for="symptom2_right_shoulder">右肩</label></div>
          <div><input type="checkbox" id="symptom2_right_elbow" name="symptom2[]" value="右肘" {{ (is_array(old('symptom2', $history?->symptom2 ?? [])) && in_array('右肘', old('symptom2', $history?->symptom2 ?? []))) ? 'checked' : '' }}><label for="symptom2_right_elbow">右肘</label></div>
          <div><input type="checkbox" id="symptom2_right_wrist" name="symptom2[]" value="右手首" {{ (is_array(old('symptom2', $history?->symptom2 ?? [])) && in_array('右手首', old('symptom2', $history?->symptom2 ?? []))) ? 'checked' : '' }}><label for="symptom2_right_wrist">右手首</label></div>
          <div><input type="checkbox" id="symptom2_right_hip_joint" name="symptom2[]" value="右股関節" {{ (is_array(old('symptom2', $history?->symptom2 ?? [])) && in_array('右股関節', old('symptom2', $history?->symptom2 ?? []))) ? 'checked' : '' }}><label for="symptom2_right_hip_joint">右股関節</label></div>
          <div><input type="checkbox" id="symptom2_right_knee" name="symptom2[]" value="右膝" {{ (is_array(old('symptom2', $history?->symptom2 ?? [])) && in_array('右膝', old('symptom2', $history?->symptom2 ?? []))) ? 'checked' : '' }}><label for="symptom2_right_knee">右膝</label></div>
          <div><input type="checkbox" id="symptom2_right_ankle" name="symptom2[]" value="右足首" {{ (is_array(old('symptom2', $history?->symptom2 ?? [])) && in_array('右足首', old('symptom2', $history?->symptom2 ?? []))) ? 'checked' : '' }}><label for="symptom2_right_ankle">右足首</label></div>
        </div>
        <div class="d-flex gap-3 flex-wrap mt-2">
          <div><input type="checkbox" id="symptom2_left_shoulder" name="symptom2[]" value="左肩" {{ (is_array(old('symptom2', $history?->symptom2 ?? [])) && in_array('左肩', old('symptom2', $history?->symptom2 ?? []))) ? 'checked' : '' }}><label for="symptom2_left_shoulder">左肩</label></div>
          <div><input type="checkbox" id="symptom2_left_elbow" name="symptom2[]" value="左肘" {{ (is_array(old('symptom2', $history?->symptom2 ?? [])) && in_array('左肘', old('symptom2', $history?->symptom2 ?? []))) ? 'checked' : '' }}><label for="symptom2_left_elbow">左肘</label></div>
          <div><input type="checkbox" id="symptom2_left_wrist" name="symptom2[]" value="左手首" {{ (is_array(old('symptom2', $history?->symptom2 ?? [])) && in_array('左手首', old('symptom2', $history?->symptom2 ?? []))) ? 'checked' : '' }}><label for="symptom2_left_wrist">左手首</label></div>
          <div><input type="checkbox" id="symptom2_left_hip_joint" name="symptom2[]" value="左股関節" {{ (is_array(old('symptom2', $history?->symptom2 ?? [])) && in_array('左股関節', old('symptom2', $history?->symptom2 ?? []))) ? 'checked' : '' }}><label for="symptom2_left_hip_joint">左股関節</label></div>
          <div><input type="checkbox" id="symptom2_left_knee" name="symptom2[]" value="左膝" {{ (is_array(old('symptom2', $history?->symptom2 ?? [])) && in_array('左膝', old('symptom2', $history?->symptom2 ?? []))) ? 'checked' : '' }}><label for="symptom2_left_knee">左膝</label></div>
          <div><input type="checkbox" id="symptom2_left_ankle" name="symptom2[]" value="左足首" {{ (is_array(old('symptom2', $history?->symptom2 ?? [])) && in_array('左足首', old('symptom2', $history?->symptom2 ?? []))) ? 'checked' : '' }}><label for="symptom2_left_ankle">左足首</label></div>
        </div>
        <div class="mt-2">
          <input type="checkbox" id="symptom2_other" name="symptom2_other" value="1" {{ old('symptom2_other', $history?->symptom2_other ?? false) ? 'checked' : '' }}>
          <label for="symptom2_other">その他（</label>
          <input type="text" id="symptom2_other_text" name="symptom2_other_text" value="{{ old('symptom2_other_text', $history?->symptom2_other_text ?? '') }}" style="width: 200px;">
          <label>）</label>
        </div>
      </div>
    </div>

    {{-- 症状3 --}}
    <div>
      <label class="form-label-tab" for="symptom3">症状3
        @error('symptom3')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <div>
          <input type="checkbox" id="symptom3_other" name="symptom3_other" value="1" {{ old('symptom3_other', $history?->symptom3_other ?? false) ? 'checked' : '' }}>
          <label for="symptom3_other">その他</label>
        </div>
        <div class="mt-1"><small>（筋麻痺、筋萎縮又は関節拘縮のある名称部位以外の部位に施術を必要とする場合には記載下さい）</small></div>
        <textarea id="symptom3" name="symptom3" rows="3" style="width: 100%;">{{ old('symptom3', $history?->symptom3 ?? '') }}</textarea>
      </div>
    </div>

    {{-- 施術の種類1 --}}
    <div>
      <div class="form-label-tab">施術の種類1
        @error('treatment_type1')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </div>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <div>
          <input type="checkbox" id="treatment_type1_massage" name="treatment_type1[]" value="マッサージ" {{ (is_array(old('treatment_type1', $history?->treatment_type1 ?? [])) && in_array('マッサージ', old('treatment_type1', $history?->treatment_type1 ?? []))) ? 'checked' : '' }}>
          <label for="treatment_type1_massage">マッサージ</label>
        </div>
        <div class="d-flex gap-3 flex-wrap mt-1">
          <div><input type="checkbox" id="treatment_type1_left_upper" name="treatment_type1[]" value="左上肢" {{ (is_array(old('treatment_type1', $history?->treatment_type1 ?? [])) && in_array('左上肢', old('treatment_type1', $history?->treatment_type1 ?? []))) ? 'checked' : '' }}><label for="treatment_type1_left_upper">左上肢</label></div>
          <div><input type="checkbox" id="treatment_type1_left_lower" name="treatment_type1[]" value="左下肢" {{ (is_array(old('treatment_type1', $history?->treatment_type1 ?? [])) && in_array('左下肢', old('treatment_type1', $history?->treatment_type1 ?? []))) ? 'checked' : '' }}><label for="treatment_type1_left_lower">左下肢</label></div>
          <div><input type="checkbox" id="treatment_type1_right_upper" name="treatment_type1[]" value="右上肢" {{ (is_array(old('treatment_type1', $history?->treatment_type1 ?? [])) && in_array('右上肢', old('treatment_type1', $history?->treatment_type1 ?? []))) ? 'checked' : '' }}><label for="treatment_type1_right_upper">右上肢</label></div>
          <div><input type="checkbox" id="treatment_type1_right_lower" name="treatment_type1[]" value="右下肢" {{ (is_array(old('treatment_type1', $history?->treatment_type1 ?? [])) && in_array('右下肢', old('treatment_type1', $history?->treatment_type1 ?? []))) ? 'checked' : '' }}><label for="treatment_type1_right_lower">右下肢</label></div>
        </div>
      </div>
    </div>

    {{-- 施術の種類2 --}}
    <div>
      <div class="form-label-tab">施術の種類2
        @error('treatment_type2')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </div>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <div>
          <input type="checkbox" id="treatment_type2_corrective_hand" name="treatment_type2_corrective_hand" value="1" {{ old('treatment_type2_corrective_hand', $history?->treatment_type2_corrective_hand ?? false) ? 'checked' : '' }}>
          <label for="treatment_type2_corrective_hand">変形徒手矯正術</label>
        </div>
        <div class="d-flex gap-3 flex-wrap mt-2">
          <div><input type="checkbox" id="treatment_type2_right_upper" name="treatment_type2[]" value="右上肢" {{ (is_array(old('treatment_type2', $history?->treatment_type2 ?? [])) && in_array('右上肢', old('treatment_type2', $history?->treatment_type2 ?? []))) ? 'checked' : '' }}><label for="treatment_type2_right_upper">右上肢</label></div>
          <div><input type="checkbox" id="treatment_type2_left_upper" name="treatment_type2[]" value="左上肢" {{ (is_array(old('treatment_type2', $history?->treatment_type2 ?? [])) && in_array('左上肢', old('treatment_type2', $history?->treatment_type2 ?? []))) ? 'checked' : '' }}><label for="treatment_type2_left_upper">左上肢</label></div>
          <div><input type="checkbox" id="treatment_type2_right_lower" name="treatment_type2[]" value="右下肢" {{ (is_array(old('treatment_type2', $history?->treatment_type2 ?? [])) && in_array('右下肢', old('treatment_type2', $history?->treatment_type2 ?? []))) ? 'checked' : '' }}><label for="treatment_type2_right_lower">右下肢</label></div>
          <div><input type="checkbox" id="treatment_type2_left_lower" name="treatment_type2[]" value="左下肢" {{ (is_array(old('treatment_type2', $history?->treatment_type2 ?? [])) && in_array('左下肢', old('treatment_type2', $history?->treatment_type2 ?? []))) ? 'checked' : '' }}><label for="treatment_type2_left_lower">左下肢</label></div>
        </div>
      </div>
    </div>

    {{-- 往療の必要有無 --}}
    <div>
      <div class="form-label-tab">往療の必要有無
        @error('is_housecall_required')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('housecall_reason_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('housecall_reason_addendum')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </div>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <div class="d-flex gap-3">
          <div>
            <input type="radio" id="is_housecall_required_yes" name="is_housecall_required" value="1" {{ old('is_housecall_required', $history?->is_housecall_required ?? '') == '1' ? 'checked' : '' }}>
            <label for="is_housecall_required_yes">必要とする</label>
          </div>
          <div>
            <input type="radio" id="is_housecall_required_no" name="is_housecall_required" value="0" {{ old('is_housecall_required', $history?->is_housecall_required ?? '') == '0' ? 'checked' : '' }}>
            <label for="is_housecall_required_no">必要としない</label>
          </div>
        </div>
        <div class="mt-2">
          <label for="housecall_reason_id" class="small fw-semibold">往療を必要とする理由</label>
          <select id="housecall_reason_id" name="housecall_reason_id">
            <option value="">╌╌╌</option>
            @foreach($housecallReasons ?? [] as $reason)
              <option value="{{ $reason->id }}" {{ old('housecall_reason_id', $history?->housecall_reason_id ?? '') == $reason->id ? 'selected' : '' }}>
                {{ $reason->housecall_reason }}
              </option>
            @endforeach
          </select>
          <div class="mt-1">
            <small>↑「その他」を選択した場合はご入力（</small>
            <input type="text" id="housecall_reason_addendum" name="housecall_reason_addendum" value="{{ old('housecall_reason_addendum', $history?->housecall_reason_addendum ?? '') }}" style="width: 200px;">
            <small>）</small>
          </div>
        </div>
      </div>
    </div>

    {{-- 介護保険の要介護度 --}}
    <div>
      <label class="form-label-tab" for="care_level">介護保険の要介護度
        @error('care_level')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="care_level" name="care_level" value="{{ old('care_level', $history?->care_level ?? '') }}">
      </div>
    </div>

    {{-- 注意事項等 --}}
    <div>
      <label class="form-label-tab" for="notes">注意事項等
        @error('notes')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <textarea id="notes" name="notes" rows="3" style="width: 100%;">{{ old('notes', $history?->notes ?? '') }}</textarea>
        <div class="mt-1"><small>施術に当たって注意を要する事項等があれば記載下さい（任意）</small></div>
      </div>
    </div>

    {{-- 要加療期間 --}}
    <div>
      <label class="form-label-tab" for="therapy_period">要加療期間
        @error('therapy_period')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="therapy_period" name="therapy_period" placeholder="例：3ヶ月、6週間" value="{{ old('therapy_period', $history?->therapy_period ?? '') }}">
      </div>
    </div>

    {{-- 初回施術内容 --}}
    <div>
      <label class="form-label-tab" for="first_therapy_content_id">初回施術内容
        @error('first_therapy_content_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <select id="first_therapy_content_id" name="first_therapy_content_id">
          <option value="">╌╌╌</option>
          @foreach($initialTreatments ?? [] as $treatment)
            <option value="{{ $treatment->id }}" {{ old('first_therapy_content_id', $history?->first_therapy_content_id ?? '') == $treatment->id ? 'selected' : '' }}>
              {{ $treatment->therapy_content }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    {{-- 発病負傷経過 --}}
    <div>
      <label class="form-label-tab" for="condition_id">発病負傷経過
        @error('condition_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('disease_progress_custom')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <select id="condition_id" name="condition_id">
          <option value="">╌╌╌</option>
          @foreach($diseaseProgresses ?? [] as $progress)
            <option value="{{ $progress->id }}" {{ old('condition_id', $history?->condition_id ?? '') == $progress->id ? 'selected' : '' }}>
              {{ $progress->condition_name }}
            </option>
          @endforeach
        </select>
        <div class="mt-1"><small>上記欄に記入無い場合、下記に入力する文字でマスターとして登録。</small></div>
        <input type="text" id="disease_progress_custom" name="disease_progress_custom" placeholder="入力されたデータをマスターとして新規登録。" value="{{ old('disease_progress_custom', '') }}">
      </div>
    </div>

    {{-- 業務上外等区分 --}}
    <div>
      <label class="form-label-tab" for="work_scope_type_id">業務上外等区分
        @error('work_scope_type_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <select id="work_scope_type_id" name="work_scope_type_id">
          <option value="">╌╌╌</option>
          @foreach($workRelatedCategories ?? [] as $category)
            <option value="{{ $category->id }}" {{ old('work_scope_type_id', $history?->work_scope_type_id ?? '') == $category->id ? 'selected' : '' }}>
              {{ $category->work_scope_type }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    {{-- 発病 負傷年月日 --}}
    <div>
      <label class="form-label-tab" for="onset_and_injury_date">発病 負傷年月日
        @error('onset_and_injury_date')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="onset_and_injury_date" name="onset_and_injury_date" value="{{ old('onset_and_injury_date', is_string($history?->onset_and_injury_date ?? null) ? $history->onset_and_injury_date : ($history?->onset_and_injury_date?->format('Y-m-d') ?? '')) }}">
      </div>
    </div>

    <div class="mt-4 d-flex gap-2 justify-content-end">
      <a href="{{ $cancelRoute }}" class="btn-ex-main btn-ex-gray"><i class="nf nf-fa-caret_left me-1" style="transform: scale(1.2)"></i>戻る</a>
      <button type="submit" class="btn-ex-main btn-ex-green">{{ $submitLabel }}</button>
    </div>

  </div>
</div>
