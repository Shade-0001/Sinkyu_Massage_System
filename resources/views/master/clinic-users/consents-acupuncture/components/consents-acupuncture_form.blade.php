{{-- resources/views/clinic-users/consents-acupuncture/components/consents-acupuncture_form.blade.php --}}

<div class="consenting-form">
  @csrf

  <div class="d-flex flex-column gap-4 container ms-0">

    {{-- 同意医師 --}}
    <div>
      <label class="form-label-tab" for="consenting_doctor_id">同意医師
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

    {{-- 病名（はり・きゅう） --}}
    <div>
      <label class="form-label-tab" for="illness_name_acupuncture_id">病名（はり・きゅう）
        @error('illness_name_acupuncture_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('illness_name_acupuncture_addendum')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <select id="illness_name_acupuncture_id" name="illness_name_acupuncture_id">
          <option value="">╌╌╌</option>
          @foreach($diseaseNames ?? [] as $disease)
            <option value="{{ $disease->id }}" {{ old('illness_name_acupuncture_id', $history?->illness_name_acupuncture_id ?? '') == $disease->id ? 'selected' : '' }}>
              {{ $disease->illness_name }}
            </option>
          @endforeach
        </select>
        <div class="mt-1"><small>上記に無い場合は下に入力してマスター登録できます。</small></div>
        <input type="text" id="illness_name_acupuncture_addendum" name="illness_name_acupuncture_addendum" placeholder="その他の時の病名（入力でマスター登録）" value="{{ old('illness_name_acupuncture_addendum', $history?->illness_name_acupuncture_addendum ?? '') }}">
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
            <small>↑「その他」を選択した場合はご入力</small>
            <input type="text" id="housecall_reason_addendum" name="housecall_reason_addendum" value="{{ old('housecall_reason_addendum', $history?->housecall_reason_addendum ?? '') }}" style="width: 200px;">
          </div>
        </div>
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
        @error('condition_custom')<span class="text-danger ms-2">{{ $message }}</span>@enderror
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
        <div class="mt-1"><small>上記欄に記入無い場合は下に入力してマスター登録できます。</small></div>
        <input type="text" id="condition_custom" name="condition_custom" placeholder="発病負傷経過（入力でマスター登録）" value="{{ old('condition_custom', '') }}">
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
      <button type="submit" class="btn-ex-main btn-ex-blue">{{ $submitLabel }}</button>
    </div>

  </div>
</div>
