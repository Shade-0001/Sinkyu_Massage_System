{{-- resources/views/doctors-info/components/doctor-form.blade.php --}}

<div class="doctor-form">
  @csrf

  <div class="d-flex flex-column gap-4 container ms-0">

    {{-- 氏名 --}}
    <div>
      <div class="form-label-tab">氏名 <span class="text-danger">*</span>
        @error('last_name')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('first_name')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </div>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <div class="d-flex gap-2 align-items-center">
          <div>
            <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $doctor->last_name ?? '') }}" placeholder="姓">
          </div>
          <div>
            <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $doctor->first_name ?? '') }}" placeholder="名">
          </div>
        </div>
      </div>
    </div>

    {{-- フリガナ --}}
    <div>
      <div class="form-label-tab">フリガナ
        @error('last_name_kana')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('first_name_kana')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </div>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <div class="d-flex gap-2 align-items-center">
          <div>
            <input type="text" id="last_name_kana" name="last_name_kana" value="{{ old('last_name_kana', $doctor->last_name_kana ?? '') }}" placeholder="セイ">
          </div>
          <div>
            <input type="text" id="first_name_kana" name="first_name_kana" value="{{ old('first_name_kana', $doctor->first_name_kana ?? '') }}" placeholder="メイ">
          </div>
        </div>
      </div>
    </div>

    {{-- 医療機関 --}}
    <div>
      <label class="form-label-tab" for="medical_institutions_id">医療機関
        @error('medical_institutions_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <select id="medical_institutions_id" name="medical_institutions_id" onchange="updateMedicalInstitutionFields()">
          <option value="">╌╌╌</option>
          @foreach($medicalInstitutions as $institution)
            <option value="{{ $institution->id }}"
              {{ old('medical_institutions_id', (isset($doctor) && $doctor->medical_institutions_id == $institution->id) ? $institution->id : '') == $institution->id ? 'selected' : '' }}>
              {{ $institution->medical_institution_name }}
            </option>
          @endforeach
        </select>
        <div class="mt-1 small">▼ 選択項目に無い場合は下記フィールドに入力してください</div>
        @error('new_medical_institution_name')<span class="text-danger">{{ $message }}</span>@enderror
        <input type="text" id="new_medical_institution_name" name="new_medical_institution_name" value="{{ old('new_medical_institution_name', '') }}" oninput="clearMedicalInstitutionSelect()">
      </div>
    </div>

    {{-- 郵便番号 --}}
    <div>
      <label class="form-label-tab" for="postal_code">郵便番号
        @error('postal_code')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $doctor->postal_code ?? '') }}" placeholder="000-0000" maxlength="8">
        <div id="doctor-address-message" class="loading d-none mt-1"></div>
      </div>
    </div>

    {{-- 都道府県 --}}
    <div>
      <label class="form-label-tab" for="address_1">都道府県
        @error('address_1')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="address_1" name="address_1" value="{{ old('address_1', $doctor->address_1 ?? '') }}" readonly data-tooltip="郵便番号から自動入力されます">
      </div>
    </div>

    {{-- 市区町村番地以下 --}}
    <div>
      <label class="form-label-tab" for="address_2">市区町村番地以下
        @error('address_2')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="address_2" name="address_2" value="{{ old('address_2', $doctor->address_2 ?? '') }}">
      </div>
    </div>

    {{-- アパート・マンション名等 --}}
    <div>
      <label class="form-label-tab" for="address_3">アパート・マンション名等
        @error('address_3')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="address_3" name="address_3" value="{{ old('address_3', $doctor->address_3 ?? '') }}">
      </div>
    </div>

    {{-- 電話番号 --}}
    <div>
      <label class="form-label-tab" for="phone">電話番号
        @error('phone')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="phone" name="phone" value="{{ old('phone', $doctor->phone ?? '') }}">
      </div>
    </div>

    {{-- 携帯番号 --}}
    <div>
      <label class="form-label-tab" for="cell_phone">携帯番号
        @error('cell_phone')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="cell_phone" name="cell_phone" value="{{ old('cell_phone', $doctor->cell_phone ?? '') }}">
      </div>
    </div>

    {{-- FAX番号 --}}
    <div>
      <label class="form-label-tab" for="fax">FAX番号
        @error('fax')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="fax" name="fax" value="{{ old('fax', $doctor->fax ?? '') }}">
      </div>
    </div>

    {{-- メールアドレス --}}
    <div>
      <label class="form-label-tab" for="email">メールアドレス
        @error('email')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="email" id="email" name="email" value="{{ old('email', $doctor->email ?? '') }}">
      </div>
    </div>

    {{-- メモ --}}
    <div>
      <label class="form-label-tab" for="note">メモ
        @error('note')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <textarea id="note" name="note" rows="4" style="width: 100%;">{{ old('note', $doctor->note ?? '') }}</textarea>
      </div>
    </div>

    <div class="mt-4 d-flex gap-2 justify-content-end">
      <a href="{{ $cancelRoute }}" class="btn-ex-main btn-ex-gray"><i class="nf nf-fa-caret_left me-1" style="transform: scale(1.2)"></i>戻る</a>
      <button type="submit" class="btn-ex-main btn-ex-green">{{ $submitLabel }}</button>
    </div>

  </div>
</div>
