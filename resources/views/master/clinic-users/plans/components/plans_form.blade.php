{{-- resources/views/clinic-users/plans/components/plans_form.blade.php --}}

<div class="plan-info-form">
  @csrf

  <div class="d-flex flex-column gap-4 container ms-0">

    {{-- 評価日 --}}
    <div>
      <label class="form-label-tab" for="assessment_date">評価日
        @error('assessment_date')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="assessment_date" name="assessment_date" value="{{ old('assessment_date', is_string($planInfo?->assessment_date ?? null) ? $planInfo->assessment_date : ($planInfo?->assessment_date?->format('Y-m-d') ?? '')) }}">
      </div>
    </div>

    {{-- 評価者 --}}
    <div>
      <label class="form-label-tab" for="assessor">評価者
        @error('assessor')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="assessor" name="assessor" value="{{ old('assessor', $planInfo?->assessor ?? '') }}">
      </div>
    </div>

    {{-- 聴衆者 --}}
    <div>
      <label class="form-label-tab" for="audience">聴衆者
        @error('audience')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="audience" name="audience" value="{{ old('audience', $planInfo?->audience ?? '') }}">
      </div>
    </div>

    {{-- 食事介助 --}}
    <div>
      <label class="form-label-tab" for="eating_assistance_level_id">食事介助
        @error('eating_assistance_level_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('eating_assistance_note')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
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
      </div>
    </div>

    {{-- 起居移動 --}}
    <div>
      <label class="form-label-tab" for="moving_assistance_level_id">起居移動
        @error('moving_assistance_level_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('moving_assistance_note')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
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
      </div>
    </div>

    {{-- 整容 --}}
    <div>
      <label class="form-label-tab" for="personal_grooming_assistance_level_id">整容
        @error('personal_grooming_assistance_level_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('personal_grooming_assistance_note')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
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
      </div>
    </div>

    {{-- トイレ --}}
    <div>
      <label class="form-label-tab" for="using_toilet_assistance_level_id">トイレ
        @error('using_toilet_assistance_level_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('using_toilet_assistance_note')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
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
      </div>
    </div>

    {{-- 入浴 --}}
    <div>
      <label class="form-label-tab" for="bathing_assistance_level_id">入浴
        @error('bathing_assistance_level_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('bathing_assistance_note')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
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
      </div>
    </div>

    {{-- 平地歩行 --}}
    <div>
      <label class="form-label-tab" for="walking_assistance_level_id">平地歩行
        @error('walking_assistance_level_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('walking_assistance_note')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
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
      </div>
    </div>

    {{-- 階段昇降 --}}
    <div>
      <label class="form-label-tab" for="using_stairs_assistance_level_id">階段昇降
        @error('using_stairs_assistance_level_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('using_stairs_assistance_note')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
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
      </div>
    </div>

    {{-- 更衣 --}}
    <div>
      <label class="form-label-tab" for="changing_clothes_assistance_level_id">更衣
        @error('changing_clothes_assistance_level_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('changing_clothes_assistance_note')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
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
      </div>
    </div>

    {{-- 排便 --}}
    <div>
      <label class="form-label-tab" for="defecation_assistance_level_id">排便
        @error('defecation_assistance_level_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('defecation_assistance_note')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
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
      </div>
    </div>

    {{-- 排尿 --}}
    <div>
      <label class="form-label-tab" for="urination_assistance_level_id">排尿
        @error('urination_assistance_level_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('urination_assistance_note')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
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
      </div>
    </div>

    {{-- コミュニケーション --}}
    <div>
      <label class="form-label-tab" for="communication_note">コミュニケーション
        @error('communication_note')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <textarea id="communication_note" name="communication_note" rows="3" style="width: 100%;">{{ old('communication_note', $planInfo?->communication_note ?? '') }}</textarea>
      </div>
    </div>

    {{-- ご本人・ご家族の希望 --}}
    <div>
      <label class="form-label-tab" for="wish_of_user_and_familiy">ご本人・ご家族の希望
        @error('wish_of_user_and_familiy')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <textarea id="wish_of_user_and_familiy" name="wish_of_user_and_familiy" rows="3" style="width: 100%;">{{ old('wish_of_user_and_familiy', $planInfo?->wish_of_user_and_familiy ?? '') }}</textarea>
      </div>
    </div>

    {{-- 治療目的 --}}
    <div>
      <label class="form-label-tab" for="care_purpose">治療目的
        @error('care_purpose')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <textarea id="care_purpose" name="care_purpose" rows="3" style="width: 100%;">{{ old('care_purpose', $planInfo?->care_purpose ?? '') }}</textarea>
      </div>
    </div>

    {{-- リハビリテーションプログラム --}}
    <div>
      <label class="form-label-tab" for="rehabilitation_program">リハビリテーションプログラム
        @error('rehabilitation_program')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <textarea id="rehabilitation_program" name="rehabilitation_program" rows="3" style="width: 100%;">{{ old('rehabilitation_program', $planInfo?->rehabilitation_program ?? '') }}</textarea>
      </div>
    </div>

    {{-- 自宅でのリハビリテーション --}}
    <div>
      <label class="form-label-tab" for="home_rehabilitation">自宅でのリハビリテーション
        @error('home_rehabilitation')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <textarea id="home_rehabilitation" name="home_rehabilitation" rows="3" style="width: 100%;">{{ old('home_rehabilitation', $planInfo?->home_rehabilitation ?? '') }}</textarea>
      </div>
    </div>

    {{-- 前回計画書作成時からの改善・変化 --}}
    <div>
      <label class="form-label-tab" for="change_since_previous_planning">前回計画書作成時からの改善・変化
        @error('change_since_previous_planning')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <textarea id="change_since_previous_planning" name="change_since_previous_planning" rows="3" style="width: 100%;">{{ old('change_since_previous_planning', $planInfo?->change_since_previous_planning ?? '') }}</textarea>
      </div>
    </div>

    {{-- 障害・注意事項 --}}
    <div>
      <label class="form-label-tab" for="note">障害・注意事項
        @error('note')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <textarea id="note" name="note" rows="3" style="width: 100%;">{{ old('note', $planInfo?->note ?? '') }}</textarea>
      </div>
    </div>

    {{-- 本人・家族同意日 --}}
    <div>
      <label class="form-label-tab" for="user_and_family_consent_date">本人・家族同意日
        @error('user_and_family_consent_date')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="user_and_family_consent_date" name="user_and_family_consent_date" value="{{ old('user_and_family_consent_date', is_string($planInfo?->user_and_family_consent_date ?? null) ? $planInfo->user_and_family_consent_date : ($planInfo?->user_and_family_consent_date?->format('Y-m-d') ?? '')) }}">
      </div>
    </div>

    <div class="mt-4 d-flex gap-2 justify-content-end">
      <a href="{{ $cancelRoute }}" class="btn-ex-main btn-ex-gray"><i class="nf nf-fa-caret_left me-1" style="transform: scale(1.2)"></i>戻る</a>
      <button type="submit" class="btn-ex-main btn-ex-green">{{ $submitLabel }}</button>
    </div>

  </div>
</div>
