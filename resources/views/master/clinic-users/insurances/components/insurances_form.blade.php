{{-- resources/views/clinic-users-info/cui-insurances-info/components/insurance-form.blade.php --}}

<div class="insurance-form">
  @csrf

  <div class="d-flex flex-column gap-4 container ms-0">

    {{-- 保険種別１ --}}
    <div>
      <div class="form-label-tab">保険種別１
        @error('insurance_type_1')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </div>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        @php
          $type1Map = [1 => '社･国･組', 2 => '公費', 3 => '後期', 4 => '退職'];
          $currentType1 = old('insurance_type_1', (isset($insurance) && $insurance && $insurance->insurance_type_1_id) ? $type1Map[$insurance->insurance_type_1_id] : '');
        @endphp
        <div class="d-flex gap-3 flex-wrap">
          <div>
            <input type="radio" id="insurance_type_1_company_national_union" name="insurance_type_1" value="社･国･組" {{ $currentType1 == '社･国･組' ? 'checked' : '' }}>
            <label for="insurance_type_1_company_national_union">社･国･組</label>
          </div>
          <div>
            <input type="radio" id="insurance_type_1_public_expense" name="insurance_type_1" value="公費" {{ $currentType1 == '公費' ? 'checked' : '' }}>
            <label for="insurance_type_1_public_expense">公費</label>
          </div>
          <div>
            <input type="radio" id="insurance_type_1_latter_period" name="insurance_type_1" value="後期" {{ $currentType1 == '後期' ? 'checked' : '' }}>
            <label for="insurance_type_1_latter_period">後期</label>
          </div>
          <div>
            <input type="radio" id="insurance_type_1_retirement" name="insurance_type_1" value="退職" {{ $currentType1 == '退職' ? 'checked' : '' }}>
            <label for="insurance_type_1_retirement">退職</label>
          </div>
        </div>
      </div>
    </div>

    {{-- 保険種別２ --}}
    <div>
      <div class="form-label-tab">保険種別２
        @error('insurance_type_2')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </div>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        @php
          $type2Map = [1 => '単独', 2 => '２併', 3 => '３併'];
          $currentType2 = old('insurance_type_2', (isset($insurance) && $insurance && $insurance->insurance_type_2_id) ? $type2Map[$insurance->insurance_type_2_id] : '');
        @endphp
        <div class="d-flex gap-3 flex-wrap">
          <div>
            <input type="radio" id="insurance_type_2_single" name="insurance_type_2" value="単独" {{ $currentType2 == '単独' ? 'checked' : '' }}>
            <label for="insurance_type_2_single">単独</label>
          </div>
          <div>
            <input type="radio" id="insurance_type_2_dual_combination" name="insurance_type_2" value="２併" {{ $currentType2 == '２併' ? 'checked' : '' }}>
            <label for="insurance_type_2_dual_combination">２併</label>
          </div>
          <div>
            <input type="radio" id="insurance_type_2_triple_combination" name="insurance_type_2" value="３併" {{ $currentType2 == '３併' ? 'checked' : '' }}>
            <label for="insurance_type_2_triple_combination">３併</label>
          </div>
        </div>
      </div>
    </div>

    {{-- 保険種別３ --}}
    <div>
      <div class="form-label-tab">保険種別３
        @error('insurance_type_3')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </div>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        @php
          $type3Map = [1 => '本外', 2 => '三外', 3 => '家外', 4 => '高外9', 5 => '高外8'];
          $currentType3 = old('insurance_type_3', (isset($insurance) && $insurance && $insurance->insurance_type_3_id) ? $type3Map[$insurance->insurance_type_3_id] : '');
        @endphp
        <div class="d-flex gap-3 flex-wrap">
          <div>
            <input type="radio" id="insurance_type_3_main_external" name="insurance_type_3" value="本外" {{ $currentType3 == '本外' ? 'checked' : '' }}>
            <label for="insurance_type_3_main_external">本外</label>
          </div>
          <div>
            <input type="radio" id="insurance_type_3_three_external" name="insurance_type_3" value="三外" {{ $currentType3 == '三外' ? 'checked' : '' }}>
            <label for="insurance_type_3_three_external">三外</label>
          </div>
          <div>
            <input type="radio" id="insurance_type_3_home_external" name="insurance_type_3" value="家外" {{ $currentType3 == '家外' ? 'checked' : '' }}>
            <label for="insurance_type_3_home_external">家外</label>
          </div>
          <div>
            <input type="radio" id="insurance_type_3_high_external_9" name="insurance_type_3" value="高外9" {{ $currentType3 == '高外9' ? 'checked' : '' }}>
            <label for="insurance_type_3_high_external_9">高外9</label>
          </div>
          <div>
            <input type="radio" id="insurance_type_3_high_external_8" name="insurance_type_3" value="高外8" {{ $currentType3 == '高外8' ? 'checked' : '' }}>
            <label for="insurance_type_3_high_external_8">高外8</label>
          </div>
        </div>
      </div>
    </div>

    {{-- 本人・家族 --}}
    <div>
      <div class="form-label-tab">本人・家族
        @error('insured_person_type')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </div>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        @php
          $selfOrFamilyMap = [1 => '本人', 2 => '六歳', 3 => '家族', 4 => '高齢１', 5 => '高齢', 6 => '高齢７'];
          $currentSelfOrFamily = old('insured_person_type', (isset($insurance) && $insurance && $insurance->self_or_family_id) ? $selfOrFamilyMap[$insurance->self_or_family_id] : '');
        @endphp
        <div class="d-flex gap-3 flex-wrap">
          <div>
            <input type="radio" id="insured_person_type_self" name="insured_person_type" value="本人" {{ $currentSelfOrFamily == '本人' ? 'checked' : '' }}>
            <label for="insured_person_type_self">本人</label>
          </div>
          <div>
            <input type="radio" id="insured_person_type_six_years_old" name="insured_person_type" value="六歳" {{ $currentSelfOrFamily == '六歳' ? 'checked' : '' }}>
            <label for="insured_person_type_six_years_old">六歳</label>
          </div>
          <div>
            <input type="radio" id="insured_person_type_family" name="insured_person_type" value="家族" {{ $currentSelfOrFamily == '家族' ? 'checked' : '' }}>
            <label for="insured_person_type_family">家族</label>
          </div>
          <div>
            <input type="radio" id="insured_person_type_elderly_1" name="insured_person_type" value="高齢１" {{ $currentSelfOrFamily == '高齢１' ? 'checked' : '' }}>
            <label for="insured_person_type_elderly_1">高齢１</label>
          </div>
          <div>
            <input type="radio" id="insured_person_type_elderly" name="insured_person_type" value="高齢" {{ $currentSelfOrFamily == '高齢' ? 'checked' : '' }}>
            <label for="insured_person_type_elderly">高齢</label>
          </div>
          <div>
            <input type="radio" id="insured_person_type_elderly_7" name="insured_person_type" value="高齢７" {{ $currentSelfOrFamily == '高齢７' ? 'checked' : '' }}>
            <label for="insured_person_type_elderly_7">高齢７</label>
          </div>
        </div>
      </div>
    </div>

    {{-- 被保険者番号 --}}
    <div>
      <label class="form-label-tab" for="insured_number">被保険者番号</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="insured_number" name="insured_number" value="{{ old('insured_number', ($insurance->insured_number ?? '')) }}">
        @error('insured_number')<div class="text-danger mt-1">{{ $message }}</div>@enderror
      </div>
    </div>

    {{-- 記号 --}}
    <div>
      <label class="form-label-tab" for="code_number">記号</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="code_number" name="code_number" value="{{ old('code_number', ($insurance->code_number ?? '')) }}">
      </div>
    </div>

    {{-- 番号 --}}
    <div>
      <label class="form-label-tab" for="account_number">番号</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="account_number" name="account_number" value="{{ old('account_number', ($insurance->account_number ?? '')) }}">
      </div>
    </div>

    {{-- 区市町村番号 --}}
    <div>
      <label class="form-label-tab" for="locality_code">区市町村番号</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="locality_code" name="locality_code" value="{{ old('locality_code', ($insurance->locality_code ?? '')) }}">
      </div>
    </div>

    {{-- 受給者番号 --}}
    <div>
      <label class="form-label-tab" for="recipient_code">受給者番号</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="recipient_code" name="recipient_code" value="{{ old('recipient_code', ($insurance->recipient_code ?? '')) }}">
      </div>
    </div>

    {{-- 資格取得年月日 --}}
    <div>
      <label class="form-label-tab" for="license_acquisition_date">資格取得年月日</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="license_acquisition_date" name="license_acquisition_date" value="{{ old('license_acquisition_date', ($insurance && $insurance->license_acquisition_date) ? $insurance->license_acquisition_date->format('Y-m-d') : '') }}">
      </div>
    </div>

    {{-- 認定年月日 --}}
    <div>
      <label class="form-label-tab" for="certification_date">認定年月日</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="certification_date" name="certification_date" value="{{ old('certification_date', ($insurance && $insurance->certification_date) ? $insurance->certification_date->format('Y-m-d') : '') }}">
      </div>
    </div>

    {{-- 発行（交付）年月日 --}}
    <div>
      <label class="form-label-tab" for="issue_date">発行（交付）年月日</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="issue_date" name="issue_date" value="{{ old('issue_date', ($insurance && $insurance->issue_date) ? $insurance->issue_date->format('Y-m-d') : '') }}">
      </div>
    </div>

    {{-- 一部負担金の割合 --}}
    <div>
      <label class="form-label-tab" for="expenses_borne_ratio">一部負担金の割合</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        @php
          $copaymentMap = [1 => '1割', 2 => '2割', 3 => '3割'];
          $currentCopayment = old('expenses_borne_ratio', (isset($insurance) && $insurance && $insurance->expenses_borne_ratio_id) ? $copaymentMap[$insurance->expenses_borne_ratio_id] : '');
        @endphp
        <select id="expenses_borne_ratio" name="expenses_borne_ratio">
          <option value="">╌╌╌</option>
          <option value="1割" {{ $currentCopayment == '1割' ? 'selected' : '' }}>1割</option>
          <option value="2割" {{ $currentCopayment == '2割' ? 'selected' : '' }}>2割</option>
          <option value="3割" {{ $currentCopayment == '3割' ? 'selected' : '' }}>3割</option>
        </select>
      </div>
    </div>

    {{-- 有効期限 --}}
    <div>
      <label class="form-label-tab" for="expiry_date">有効期限</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date', ($insurance && $insurance->expiry_date) ? $insurance->expiry_date->format('Y-m-d') : '') }}">
      </div>
    </div>

    {{-- 償還対象 --}}
    <div>
      <label class="form-label-tab" for="is_redeemed">償還対象</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="checkbox" id="is_redeemed" name="is_redeemed" value="1" {{ old('is_redeemed', $insurance->is_redeemed ?? false) ? 'checked' : '' }}>
      </div>
    </div>

    {{-- 被保険者氏名 --}}
    <div>
      <label class="form-label-tab" for="insured_name">被保険者氏名</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="insured_name" name="insured_name" value="{{ old('insured_name', $insurance->insured_name ?? '') }}">
      </div>
    </div>

    {{-- 利用者との続柄 --}}
    <div>
      <label class="form-label-tab" for="relationship_with_clinic_user">利用者との続柄</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        @php
          $relationshipMap = [1 => '本人', 2 => '家族'];
          $currentRelationship = old('relationship_with_clinic_user', (isset($insurance) && $insurance && $insurance->relationship_with_clinic_user_id) ? $relationshipMap[$insurance->relationship_with_clinic_user_id] : '');
        @endphp
        <select id="relationship_with_clinic_user" name="relationship_with_clinic_user">
          <option value="">╌╌╌</option>
          <option value="本人" {{ $currentRelationship == '本人' ? 'selected' : '' }}>本人</option>
          <option value="家族" {{ $currentRelationship == '家族' ? 'selected' : '' }}>家族</option>
        </select>
      </div>
    </div>

    {{-- 医療助成対象 --}}
    <div>
      <label class="form-label-tab" for="is_healthcare_subsidized">医療助成対象</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="checkbox" id="is_healthcare_subsidized" name="is_healthcare_subsidized" value="1" {{ old('is_healthcare_subsidized', $insurance->is_healthcare_subsidized ?? false) ? 'checked' : '' }}>
      </div>
    </div>

    <div id="medical-assistance-fields">

      {{-- 公費負担者番号 --}}
      <div>
        <label class="form-label-tab" for="public_funds_payer_code">公費負担者番号</label>
        <div class="form-field px-3 py-2">
          <div class="form-field-top"></div>
          <input type="text" id="public_funds_payer_code" name="public_funds_payer_code" value="{{ old('public_funds_payer_code', $insurance->public_funds_payer_code ?? '') }}" data-tooltip="医療助成対象がチェック状態の場合に入力可能です">
        </div>
      </div>

      {{-- 公費受給者番号 --}}
      <div class="mt-4">
        <label class="form-label-tab" for="public_funds_recipient_code">公費受給者番号</label>
        <div class="form-field px-3 py-2">
          <div class="form-field-top"></div>
          <input type="text" id="public_funds_recipient_code" name="public_funds_recipient_code" value="{{ old('public_funds_recipient_code', $insurance->public_funds_recipient_code ?? '') }}" data-tooltip="医療助成対象がチェック状態の場合に入力可能です">
        </div>
      </div>

      {{-- 区市町村番号（家族） --}}
      <div class="mt-4">
        <label class="form-label-tab" for="locality_code_family">区市町村番号（家族）</label>
        <div class="form-field px-3 py-2">
          <div class="form-field-top"></div>
          <input type="text" id="locality_code_family" name="locality_code_family" value="{{ old('locality_code_family', ($insurance->locality_code_family ?? '')) }}" data-tooltip="利用者との続柄で『家族』が選択されている場合のみ入力可能です">
        </div>
      </div>

      {{-- 受給者番号（家族） --}}
      <div class="mt-4">
        <label class="form-label-tab" for="recipient_code_family">受給者番号（家族）</label>
        <div class="form-field px-3 py-2">
          <div class="form-field-top"></div>
          <input type="text" id="recipient_code_family" name="recipient_code_family" value="{{ old('recipient_code_family', ($insurance->recipient_code_family ?? '')) }}" data-tooltip="利用者との続柄で『家族』が選択されている場合のみ入力可能です">
        </div>
      </div>

    </div>

    {{-- 保険者情報 --}}
    <div>
      <label class="form-label-tab" for="selected_insurer">保険者情報</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <select id="selected_insurer" name="selected_insurer" onchange="updateInsurerFields()">
          <option value="">登録済みデータから選択［保険者名称｜保険者番号｜保険者住所｜提出先名称］</option>
          @foreach($insurers as $insurer)
            <option value="{{ $insurer->id }}"
              {{ old('selected_insurer', $isEdit && isset($insurance) && $insurance->insurers_id == $insurer->id ? $insurer->id : '') == $insurer->id ? 'selected' : '' }}
              data-number="{{ $insurer->insurer_number }}"
              data-name="{{ $insurer->insurer_name }}"
              data-postal="{{ $insurer->postal_code }}"
              data-address="{{ $insurer->address }}"
              data-recipient="{{ $insurer->recipient_name }}">
            {{ $insurer->insurer_name }}｜{{ $insurer->insurer_number }}｜{{ $insurer->postal_code }}｜{{ $insurer->address }}｜{{ $insurer->recipient_name }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    <h6>▼ 保険者情報新規登録</h6>

    {{-- 保険者番号 --}}
    <div>
      <label class="form-label-tab" for="new_insurer_number">保険者番号
        @error('new_insurer_number')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="new_insurer_number" name="new_insurer_number" value="{{ old('new_insurer_number', '') }}">
        <div id="insurer_number_warning" class="text-danger mt-1" style="display: none;">保険者番号は6桁または8桁の数字を入力してください</div>
      </div>
    </div>

    {{-- 保険者名称 --}}
    <div>
      <label class="form-label-tab" for="new_insurer_name">保険者名称</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="new_insurer_name" name="new_insurer_name" value="{{ old('new_insurer_name', '') }}">
      </div>
    </div>

    {{-- 郵便番号（保険者） --}}
    <div>
      <label class="form-label-tab" for="new_postal_code">郵便番号</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="new_postal_code" name="new_postal_code" placeholder="000-0000" maxlength="8" value="{{ old('new_postal_code', '') }}">
        <div id="new-address-message" class="loading d-none mt-1"></div>
      </div>
    </div>

    {{-- 住所（保険者） --}}
    <div>
      <label class="form-label-tab" for="new_address">住所</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="new_address" name="new_address" value="{{ old('new_address', '') }}">
      </div>
    </div>

    {{-- 提出先名称 --}}
    <div>
      <label class="form-label-tab" for="new_recipient_name">提出先名称</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="new_recipient_name" name="new_recipient_name" value="{{ old('new_recipient_name', '') }}">
      </div>
    </div>

    <div class="mt-4 text-end">
      <button type="submit" class="btn-ex-main btn-ex-blue">{{ $submitLabel }}</button>
      <a href="{{ $cancelRoute }}">
        <button type="button" class="btn-ex-main btn-ex-gray">キャンセル</button>
      </a>
    </div>

  </div>
</div>
