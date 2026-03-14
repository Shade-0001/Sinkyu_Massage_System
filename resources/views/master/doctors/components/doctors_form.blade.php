{{-- resources/views/doctors-info/components/doctor-form.blade.php --}}

<div class="doctor-form">
  @csrf

  <div class="mb-3">
    <label class="fw-semibold">氏名 <span class="text-danger">*</span></label>
    <br>
    <div class="d-flex gap-2 align-items-center">
      <div>
        <label for="last_name" class="form-label small mb-1">姓</label>
        @error('last_name')
          <span class="text-danger ms-2">{{ $message }}</span>
        @enderror
        <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $doctor->last_name ?? '') }}">
      </div>
      <div>
        <label for="first_name" class="form-label small mb-1">名</label>
        @error('first_name')
          <span class="text-danger ms-2">{{ $message }}</span>
        @enderror
        <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $doctor->first_name ?? '') }}">
      </div>
    </div>
  </div>

  <div class="mb-3">
    <label class="fw-semibold">氏名</label>
    <br>
    <div class="d-flex gap-2 align-items-center">
      <div>
        <label for="last_name_kana" class="form-label small mb-1">セイ</label>
        @error('last_name_kana')
          <span class="text-danger ms-2">{{ $message }}</span>
        @enderror
        <input type="text" id="last_name_kana" name="last_name_kana" value="{{ old('last_name_kana', $doctor->last_name_kana ?? '') }}">
      </div>
      <div>
        <label for="first_name_kana" class="form-label small mb-1">メイ</label>
        @error('first_name_kana')
          <span class="text-danger ms-2">{{ $message }}</span>
        @enderror
        <input type="text" id="first_name_kana" name="first_name_kana" value="{{ old('first_name_kana', $doctor->first_name_kana ?? '') }}">
      </div>
    </div>
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="medical_institutions_id">医療機関</label>
    @error('medical_institutions_id')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <select id="medical_institutions_id" name="medical_institutions_id" onchange="updateMedicalInstitutionFields()">
      <option value="">╌╌╌</option>
      @foreach($medicalInstitutions as $institution)
        <option value="{{ $institution->id }}"
          {{ old('medical_institutions_id', (isset($doctor) && $doctor->medical_institutions_id == $institution->id) ? $institution->id : '') == $institution->id ? 'selected' : '' }}>
          {{ $institution->medical_institution_name }}
        </option>
      @endforeach
    </select>
  </div>

  <div>▼ 選択項目に無い場合は下記フィールドに入力してください</div>

  <div class="mb-3">
    @error('new_medical_institution_name')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <input type="text" id="new_medical_institution_name" name="new_medical_institution_name" value="{{ old('new_medical_institution_name', '') }}" placeholder="" oninput="clearMedicalInstitutionSelect()" data-tooltip="">
  </div>

  <br>

  <div class="mb-3">
    <label class="fw-semibold" for="postal_code">郵便番号</label>
    @error('postal_code')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $doctor->postal_code ?? '') }}" placeholder="000-0000" maxlength="8">
    <div id="doctor-address-message" class="loading d-none mt-1"></div>
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="address_1">都道府県</label>
    @error('address_1')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="address_1" name="address_1" value="{{ old('address_1', $doctor->address_1 ?? '') }}" readonly data-tooltip="郵便番号から自動入力されます">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="address_2">市区町村番地以下</label>
    @error('address_2')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="address_2" name="address_2" value="{{ old('address_2', $doctor->address_2 ?? '') }}">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="address_3">アパート・マンション名等</label>
    @error('address_3')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="address_3" name="address_3" value="{{ old('address_3', $doctor->address_3 ?? '') }}">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="phone">電話番号</label>
    @error('phone')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="phone" name="phone" value="{{ old('phone', $doctor->phone ?? '') }}">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="cell_phone">携帯番号</label>
    @error('cell_phone')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="cell_phone" name="cell_phone" value="{{ old('cell_phone', $doctor->cell_phone ?? '') }}">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="fax">FAX番号</label>
    @error('fax')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="fax" name="fax" value="{{ old('fax', $doctor->fax ?? '') }}">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="email">メールアドレス</label>
    @error('email')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="email" id="email" name="email" value="{{ old('email', $doctor->email ?? '') }}">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="note">メモ</label>
    @error('note')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <textarea id="note" name="note" rows="4">{{ old('note', $doctor->note ?? '') }}</textarea>
  </div>

  <button type="submit">{{ $submitLabel }}</button>
  <a href="{{ $cancelRoute }}">
    <button type="button">キャンセル</button>
  </a>
</div>
