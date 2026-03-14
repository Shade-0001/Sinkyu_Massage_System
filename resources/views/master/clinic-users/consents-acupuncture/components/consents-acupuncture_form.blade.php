{{-- resources/views/clinic-users/consents-acupuncture/components/consents-acupuncture_form.blade.php --}}

<div class="consenting-form">
  @csrf

  <div class="mb-3">
    <label class="fw-semibold" for="consenting_doctor_id">同意医師</label><br>
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
    @error('consenting_doctor_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="consenting_date">同意日</label><br>
    <input type="date" id="consenting_date" name="consenting_date" value="{{ old('consenting_date', is_string($history?->consenting_date ?? null) ? $history->consenting_date : ($history?->consenting_date?->format('Y-m-d') ?? '')) }}">
    @error('consenting_date')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="consenting_start_date">同意開始年月日</label><br>
    <input type="date" id="consenting_start_date" name="consenting_start_date" value="{{ old('consenting_start_date', is_string($history?->consenting_start_date ?? null) ? $history->consenting_start_date : ($history?->consenting_start_date?->format('Y-m-d') ?? '')) }}">
    @error('consenting_start_date')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="consenting_end_date">同意終了年月日</label><br>
    <input type="date" id="consenting_end_date" name="consenting_end_date" value="{{ old('consenting_end_date', is_string($history?->consenting_end_date ?? null) ? $history->consenting_end_date : ($history?->consenting_end_date?->format('Y-m-d') ?? '')) }}">
    @error('consenting_end_date')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="benefit_period_start_date">支給期間 開始</label><br>
    <input type="date" id="benefit_period_start_date" name="benefit_period_start_date" value="{{ old('benefit_period_start_date', is_string($history?->benefit_period_start_date ?? null) ? $history->benefit_period_start_date : ($history?->benefit_period_start_date?->format('Y-m-d') ?? '')) }}">
    @error('benefit_period_start_date')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="benefit_period_end_date">支給期間 終了</label><br>
    <input type="date" id="benefit_period_end_date" name="benefit_period_end_date" value="{{ old('benefit_period_end_date', is_string($history?->benefit_period_end_date ?? null) ? $history->benefit_period_end_date : ($history?->benefit_period_end_date?->format('Y-m-d') ?? '')) }}">
    @error('benefit_period_end_date')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="first_care_date">初療年月日</label><br>
    <input type="date" id="first_care_date" name="first_care_date" value="{{ old('first_care_date', is_string($history?->first_care_date ?? null) ? $history->first_care_date : ($history?->first_care_date?->format('Y-m-d') ?? '')) }}">
    @error('first_care_date')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="reconsenting_expiry">再同意有効期限</label><br>
    <input type="date" id="reconsenting_expiry" name="reconsenting_expiry" value="{{ old('reconsenting_expiry', is_string($history?->reconsenting_expiry ?? null) ? $history->reconsenting_expiry : ($history?->reconsenting_expiry?->format('Y-m-d') ?? '')) }}">
    @error('reconsenting_expiry')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="illness_name_acupuncture_id">病名（はり・きゅう）</label><br>
    <select id="illness_name_acupuncture_id" name="illness_name_acupuncture_id">
      <option value="">╌╌╌</option>
      @foreach($diseaseNames ?? [] as $disease)
        <option value="{{ $disease->id }}" {{ old('illness_name_acupuncture_id', $history?->illness_name_acupuncture_id ?? '') == $disease->id ? 'selected' : '' }}>
          {{ $disease->illness_name }}
        </option>
      @endforeach
    </select>
    <div class="mt-1">
      <small>上記に無い場合は下に入力してマスター登録できます。</small>
    </div>
    <input type="text" id="illness_name_acupuncture_addendum" name="illness_name_acupuncture_addendum" placeholder="その他の時の病名（入力でマスター登録）" value="{{ old('illness_name_acupuncture_addendum', $history?->illness_name_acupuncture_addendum ?? '') }}">
    @error('illness_name_acupuncture_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
    @error('illness_name_acupuncture_addendum')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="bill_category_id">請求区分</label><br>
    <select id="bill_category_id" name="bill_category_id">
      <option value="">╌╌╌</option>
      @foreach($billingCategories ?? [] as $category)
        <option value="{{ $category->id }}" {{ old('bill_category_id', $history?->bill_category_id ?? '') == $category->id ? 'selected' : '' }}>
          {{ $category->bill_category }}
        </option>
      @endforeach
    </select>
    @error('bill_category_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="outcome_id">転帰</label><br>
    <select id="outcome_id" name="outcome_id">
      <option value="">╌╌╌</option>
      @foreach($outcomes ?? [] as $outcome)
        <option value="{{ $outcome->id }}" {{ old('outcome_id', $history?->outcome_id ?? '') == $outcome->id ? 'selected' : '' }}>
          {{ $outcome->outcome }}
        </option>
      @endforeach
    </select>
    @error('outcome_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold">往療の必要有無</label><br>
    <div>
      <input type="radio" id="is_housecall_required_yes" name="is_housecall_required" value="1" {{ old('is_housecall_required', $history?->is_housecall_required ?? '') == '1' ? 'checked' : '' }}>
      <label for="is_housecall_required_yes">必要とする</label>

      <input type="radio" id="is_housecall_required_no" name="is_housecall_required" value="0" {{ old('is_housecall_required', $history?->is_housecall_required ?? '') == '0' ? 'checked' : '' }}>
      <label for="is_housecall_required_no">必要としない</label>
    </div>
    <div class="mt-2">
      <label class="fw-semibold" for="housecall_reason_id">往療を必要とする理由</label><br>
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
    @error('is_housecall_required')
      <div class="text-danger">{{ $message }}</div>
    @enderror
    @error('housecall_reason_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
    @error('housecall_reason_addendum')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="therapy_period">要加療期間</label><br>
    <input type="text" id="therapy_period" name="therapy_period" placeholder="例：3ヶ月、6週間" value="{{ old('therapy_period', $history?->therapy_period ?? '') }}">
    @error('therapy_period')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="first_therapy_content_id">初回施術内容</label><br>
    <select id="first_therapy_content_id" name="first_therapy_content_id">
      <option value="">╌╌╌</option>
      @foreach($initialTreatments ?? [] as $treatment)
        <option value="{{ $treatment->id }}" {{ old('first_therapy_content_id', $history?->first_therapy_content_id ?? '') == $treatment->id ? 'selected' : '' }}>
          {{ $treatment->therapy_content }}
        </option>
      @endforeach
    </select>
    @error('first_therapy_content_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="condition_id">発病負傷経過</label><br>
    <select id="condition_id" name="condition_id">
      <option value="">╌╌╌</option>
      @foreach($diseaseProgresses ?? [] as $progress)
        <option value="{{ $progress->id }}" {{ old('condition_id', $history?->condition_id ?? '') == $progress->id ? 'selected' : '' }}>
          {{ $progress->condition_name }}
        </option>
      @endforeach
    </select>
    <div class="mt-1">
      <small>上記欄に記入無い場合は下に入力してマスター登録できます。</small>
    </div>
    <input type="text" id="condition_custom" name="condition_custom" placeholder="発病負傷経過（入力でマスター登録）" value="{{ old('condition_custom', '') }}">
    @error('condition_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
    @error('condition_custom')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="work_scope_type_id">業務上外等区分</label><br>
    <select id="work_scope_type_id" name="work_scope_type_id">
      <option value="">╌╌╌</option>
      @foreach($workRelatedCategories ?? [] as $category)
        <option value="{{ $category->id }}" {{ old('work_scope_type_id', $history?->work_scope_type_id ?? '') == $category->id ? 'selected' : '' }}>
          {{ $category->work_scope_type }}
        </option>
      @endforeach
    </select>
    @error('work_scope_type_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="onset_and_injury_date">発病 負傷年月日</label><br>
    <input type="date" id="onset_and_injury_date" name="onset_and_injury_date" value="{{ old('onset_and_injury_date', is_string($history?->onset_and_injury_date ?? null) ? $history->onset_and_injury_date : ($history?->onset_and_injury_date?->format('Y-m-d') ?? '')) }}">
    @error('onset_and_injury_date')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <button type="submit">{{ $submitLabel }}</button>
    <a href="{{ $cancelRoute }}">
    <button type="button">キャンセル</button>
    </a>
  </div>
</div>
