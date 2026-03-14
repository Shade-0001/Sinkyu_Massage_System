{{-- resources/views/clinic-users/plans/components/plans_form.blade.php --}}

<div class="plan-info-form">
  @csrf

  <div class="mb-3">
    <label class="fw-semibold" for="assessment_date">評価日</label><br>
    <input type="date" id="assessment_date" name="assessment_date" value="{{ old('assessment_date', is_string($planInfo?->assessment_date ?? null) ? $planInfo->assessment_date : ($planInfo?->assessment_date?->format('Y-m-d') ?? '')) }}">
    @error('assessment_date')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="assessor">評価者</label><br>
    <input type="text" id="assessor" name="assessor" value="{{ old('assessor', $planInfo?->assessor ?? '') }}">
    @error('assessor')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="audience">聴衆者</label><br>
    <input type="text" id="audience" name="audience" value="{{ old('audience', $planInfo?->audience ?? '') }}">
    @error('audience')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="eating_assistance_level_id">食事介助</label><br>
    <select id="eating_assistance_level_id" name="eating_assistance_level_id">
      <option value="">╌╌╌</option>
      @foreach($assistanceLevels as $level)
        @if(in_array($level->id, $adlLevelMapping['eating']))
          <option value="{{ $level->id }}" {{ old('eating_assistance_level_id', $planInfo?->eating_assistance_level_id ?? '') == $level->id ? 'selected' : '' }}>
            {{ $level->assistance_level }}
          </option>
        @endif
      @endforeach
    </select>
    <input type="text" id="eating_assistance_note" name="eating_assistance_note" value="{{ old('eating_assistance_note', $planInfo?->eating_assistance_note ?? '') }}" class="mt-1">
    @error('eating_assistance_level_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
    @error('eating_assistance_note')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="moving_assistance_level_id">起居移動</label><br>
    <select id="moving_assistance_level_id" name="moving_assistance_level_id">
      <option value="">╌╌╌</option>
      @foreach($assistanceLevels as $level)
        @if(in_array($level->id, $adlLevelMapping['moving']))
          <option value="{{ $level->id }}" {{ old('moving_assistance_level_id', $planInfo?->moving_assistance_level_id ?? '') == $level->id ? 'selected' : '' }}>
            {{ $level->assistance_level }}
          </option>
        @endif
      @endforeach
    </select>
    <input type="text" id="moving_assistance_note" name="moving_assistance_note" value="{{ old('moving_assistance_note', $planInfo?->moving_assistance_note ?? '') }}" class="mt-1">
    @error('moving_assistance_level_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
    @error('moving_assistance_note')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="personal_grooming_assistance_level_id">整容</label><br>
    <select id="personal_grooming_assistance_level_id" name="personal_grooming_assistance_level_id">
      <option value="">╌╌╌</option>
      @foreach($assistanceLevels as $level)
        @if(in_array($level->id, $adlLevelMapping['personal_grooming']))
          <option value="{{ $level->id }}" {{ old('personal_grooming_assistance_level_id', $planInfo?->personal_grooming_assistance_level_id ?? '') == $level->id ? 'selected' : '' }}>
            {{ $level->assistance_level }}
          </option>
        @endif
      @endforeach
    </select>
    <input type="text" id="personal_grooming_assistance_note" name="personal_grooming_assistance_note" value="{{ old('personal_grooming_assistance_note', $planInfo?->personal_grooming_assistance_note ?? '') }}" class="mt-1">
    @error('personal_grooming_assistance_level_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
    @error('personal_grooming_assistance_note')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="using_toilet_assistance_level_id">トイレ</label><br>
    <select id="using_toilet_assistance_level_id" name="using_toilet_assistance_level_id">
      <option value="">╌╌╌</option>
      @foreach($assistanceLevels as $level)
        @if(in_array($level->id, $adlLevelMapping['using_toilet']))
          <option value="{{ $level->id }}" {{ old('using_toilet_assistance_level_id', $planInfo?->using_toilet_assistance_level_id ?? '') == $level->id ? 'selected' : '' }}>
            {{ $level->assistance_level }}
          </option>
        @endif
      @endforeach
    </select>
    <input type="text" id="using_toilet_assistance_note" name="using_toilet_assistance_note" value="{{ old('using_toilet_assistance_note', $planInfo?->using_toilet_assistance_note ?? '') }}" class="mt-1">
    @error('using_toilet_assistance_level_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
    @error('using_toilet_assistance_note')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="bathing_assistance_level_id">入浴</label><br>
    <select id="bathing_assistance_level_id" name="bathing_assistance_level_id">
      <option value="">╌╌╌</option>
      @foreach($assistanceLevels as $level)
        @if(in_array($level->id, $adlLevelMapping['bathing']))
          <option value="{{ $level->id }}" {{ old('bathing_assistance_level_id', $planInfo?->bathing_assistance_level_id ?? '') == $level->id ? 'selected' : '' }}>
            {{ $level->assistance_level }}
          </option>
        @endif
      @endforeach
    </select>
    <input type="text" id="bathing_assistance_note" name="bathing_assistance_note" value="{{ old('bathing_assistance_note', $planInfo?->bathing_assistance_note ?? '') }}" class="mt-1">
    @error('bathing_assistance_level_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
    @error('bathing_assistance_note')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="walking_assistance_level_id">平地歩行</label><br>
    <select id="walking_assistance_level_id" name="walking_assistance_level_id">
      <option value="">╌╌╌</option>
      @foreach($assistanceLevels as $level)
        @if(in_array($level->id, $adlLevelMapping['walking']))
          <option value="{{ $level->id }}" {{ old('walking_assistance_level_id', $planInfo?->walking_assistance_level_id ?? '') == $level->id ? 'selected' : '' }}>
            {{ $level->assistance_level }}
          </option>
        @endif
      @endforeach
    </select>
    <input type="text" id="walking_assistance_note" name="walking_assistance_note" value="{{ old('walking_assistance_note', $planInfo?->walking_assistance_note ?? '') }}" class="mt-1">
    @error('walking_assistance_level_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
    @error('walking_assistance_note')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="using_stairs_assistance_level_id">階段昇降</label><br>
    <select id="using_stairs_assistance_level_id" name="using_stairs_assistance_level_id">
      <option value="">╌╌╌</option>
      @foreach($assistanceLevels as $level)
        @if(in_array($level->id, $adlLevelMapping['using_stairs']))
          <option value="{{ $level->id }}" {{ old('using_stairs_assistance_level_id', $planInfo?->using_stairs_assistance_level_id ?? '') == $level->id ? 'selected' : '' }}>
            {{ $level->assistance_level }}
          </option>
        @endif
      @endforeach
    </select>
    <input type="text" id="using_stairs_assistance_note" name="using_stairs_assistance_note" value="{{ old('using_stairs_assistance_note', $planInfo?->using_stairs_assistance_note ?? '') }}" class="mt-1">
    @error('using_stairs_assistance_level_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
    @error('using_stairs_assistance_note')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="changing_clothes_assistance_level_id">更衣</label><br>
    <select id="changing_clothes_assistance_level_id" name="changing_clothes_assistance_level_id">
      <option value="">╌╌╌</option>
      @foreach($assistanceLevels as $level)
        @if(in_array($level->id, $adlLevelMapping['changing_clothes']))
          <option value="{{ $level->id }}" {{ old('changing_clothes_assistance_level_id', $planInfo?->changing_clothes_assistance_level_id ?? '') == $level->id ? 'selected' : '' }}>
            {{ $level->assistance_level }}
          </option>
        @endif
      @endforeach
    </select>
    <input type="text" id="changing_clothes_assistance_note" name="changing_clothes_assistance_note" value="{{ old('changing_clothes_assistance_note', $planInfo?->changing_clothes_assistance_note ?? '') }}" class="mt-1">
    @error('changing_clothes_assistance_level_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
    @error('changing_clothes_assistance_note')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="defecation_assistance_level_id">排便</label><br>
    <select id="defecation_assistance_level_id" name="defecation_assistance_level_id">
      <option value="">╌╌╌</option>
      @foreach($assistanceLevels as $level)
        @if(in_array($level->id, $adlLevelMapping['defecation']))
          <option value="{{ $level->id }}" {{ old('defecation_assistance_level_id', $planInfo?->defecation_assistance_level_id ?? '') == $level->id ? 'selected' : '' }}>
            {{ $level->assistance_level }}
          </option>
        @endif
      @endforeach
    </select>
    <input type="text" id="defecation_assistance_note" name="defecation_assistance_note" value="{{ old('defecation_assistance_note', $planInfo?->defecation_assistance_note ?? '') }}" class="mt-1">
    @error('defecation_assistance_level_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
    @error('defecation_assistance_note')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="urination_assistance_level_id">排尿</label><br>
    <select id="urination_assistance_level_id" name="urination_assistance_level_id">
      <option value="">╌╌╌</option>
      @foreach($assistanceLevels as $level)
        @if(in_array($level->id, $adlLevelMapping['urination']))
          <option value="{{ $level->id }}" {{ old('urination_assistance_level_id', $planInfo?->urination_assistance_level_id ?? '') == $level->id ? 'selected' : '' }}>
            {{ $level->assistance_level }}
          </option>
        @endif
      @endforeach
    </select>
    <input type="text" id="urination_assistance_note" name="urination_assistance_note" value="{{ old('urination_assistance_note', $planInfo?->urination_assistance_note ?? '') }}" class="mt-1">
    @error('urination_assistance_level_id')
      <div class="text-danger">{{ $message }}</div>
    @enderror
    @error('urination_assistance_note')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="communication_note">コミュニケーション</label><br>
    <textarea id="communication_note" name="communication_note" rows="3">{{ old('communication_note', $planInfo?->communication_note ?? '') }}</textarea>
    @error('communication_note')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="wish_of_user_and_familiy">ご本人・ご家族の希望</label><br>
    <textarea id="wish_of_user_and_familiy" name="wish_of_user_and_familiy" rows="3">{{ old('wish_of_user_and_familiy', $planInfo?->wish_of_user_and_familiy ?? '') }}</textarea>
    @error('wish_of_user_and_familiy')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="care_purpose">治療目的</label><br>
    <textarea id="care_purpose" name="care_purpose" rows="3">{{ old('care_purpose', $planInfo?->care_purpose ?? '') }}</textarea>
    @error('care_purpose')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="rehabilitation_program">リハビリテーションプログラム</label><br>
    <textarea id="rehabilitation_program" name="rehabilitation_program" rows="3">{{ old('rehabilitation_program', $planInfo?->rehabilitation_program ?? '') }}</textarea>
    @error('rehabilitation_program')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="home_rehabilitation">自宅でのリハビリテーション</label><br>
    <textarea id="home_rehabilitation" name="home_rehabilitation" rows="3">{{ old('home_rehabilitation', $planInfo?->home_rehabilitation ?? '') }}</textarea>
    @error('home_rehabilitation')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="change_since_previous_planning">前回計画書作成時からの改善・変化</label><br>
    <textarea id="change_since_previous_planning" name="change_since_previous_planning" rows="3">{{ old('change_since_previous_planning', $planInfo?->change_since_previous_planning ?? '') }}</textarea>
    @error('change_since_previous_planning')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="note">障害・注意事項</label><br>
    <textarea id="note" name="note" rows="3">{{ old('note', $planInfo?->note ?? '') }}</textarea>
    @error('note')
      <div class="text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="user_and_family_consent_date">本人・家族同意日</label><br>
    <input type="date" id="user_and_family_consent_date" name="user_and_family_consent_date" value="{{ old('user_and_family_consent_date', is_string($planInfo?->user_and_family_consent_date ?? null) ? $planInfo->user_and_family_consent_date : ($planInfo?->user_and_family_consent_date?->format('Y-m-d') ?? '')) }}">
    @error('user_and_family_consent_date')
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
