{{-- resources/views/therapists/components/therapists_form.blade.php --}}

<div class="therapist-form">
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
            <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $therapist->last_name ?? '') }}" placeholder="姓">
          </div>
          <div>
            <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $therapist->first_name ?? '') }}" placeholder="名">
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
            <input type="text" id="last_name_kana" name="last_name_kana" value="{{ old('last_name_kana', $therapist->last_name_kana ?? '') }}" placeholder="セイ">
          </div>
          <div>
            <input type="text" id="first_name_kana" name="first_name_kana" value="{{ old('first_name_kana', $therapist->first_name_kana ?? '') }}" placeholder="メイ">
          </div>
        </div>
      </div>
    </div>

    {{-- 郵便番号 --}}
    <div>
      <label class="form-label-tab" for="postal_code">郵便番号
        @error('postal_code')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $therapist->postal_code ?? '') }}" placeholder="000-0000" maxlength="8">
        <div id="therapist-address-message" class="loading d-none mt-1"></div>
      </div>
    </div>

    {{-- 都道府県 --}}
    <div>
      <label class="form-label-tab" for="address_1">都道府県
        @error('address_1')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="address_1" name="address_1" value="{{ old('address_1', $therapist->address_1 ?? '') }}" readonly data-tooltip="郵便番号から自動入力されます">
      </div>
    </div>

    {{-- 市区町村番地以下 --}}
    <div>
      <label class="form-label-tab" for="address_2">市区町村番地以下
        @error('address_2')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="address_2" name="address_2" value="{{ old('address_2', $therapist->address_2 ?? '') }}">
      </div>
    </div>

    {{-- アパート・マンション名等 --}}
    <div>
      <label class="form-label-tab" for="address_3">アパート・マンション名等
        @error('address_3')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="address_3" name="address_3" value="{{ old('address_3', $therapist->address_3 ?? '') }}">
      </div>
    </div>

    {{-- 電話番号 --}}
    <div>
      <label class="form-label-tab" for="phone">電話番号
        @error('phone')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="phone" name="phone" value="{{ old('phone', $therapist->phone ?? '') }}">
      </div>
    </div>

    {{-- 携帯番号 --}}
    <div>
      <label class="form-label-tab" for="cell_phone">携帯番号
        @error('cell_phone')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="cell_phone" name="cell_phone" value="{{ old('cell_phone', $therapist->cell_phone ?? '') }}">
      </div>
    </div>

    {{-- FAX番号 --}}
    <div>
      <label class="form-label-tab" for="fax">FAX番号
        @error('fax')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="fax" name="fax" value="{{ old('fax', $therapist->fax ?? '') }}">
      </div>
    </div>

    {{-- メールアドレス --}}
    <div>
      <label class="form-label-tab" for="email">メールアドレス
        @error('email')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="email" id="email" name="email" value="{{ old('email', $therapist->email ?? '') }}">
      </div>
    </div>

    {{-- 資格（はり） --}}
    <h4>資格（はり）</h4>

    <div>
      <label class="form-label-tab" for="license_hari_code_number">免許証記号番号
        @error('license_hari_code_number')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="number" id="license_hari_code_number" name="license_hari_code_number" @if(old('license_hari_code_number', $therapist->license_hari_code_number ?? null) !== null) value="{{ old('license_hari_code_number', $therapist->license_hari_code_number) }}" @endif>
      </div>
    </div>

    <div>
      <label class="form-label-tab" for="license_hari_issued_date">免許証交付年月日
        @error('license_hari_issued_date')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="license_hari_issued_date" name="license_hari_issued_date" value="{{ old('license_hari_issued_date', $therapist->license_hari_issued_date ?? '') }}">
      </div>
    </div>

    {{-- 資格（きゅう） --}}
    <h4>資格（きゅう）</h4>

    <div>
      <label class="form-label-tab" for="license_kyu_code_number">免許証記号番号
        @error('license_kyu_code_number')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="number" id="license_kyu_code_number" name="license_kyu_code_number" @if(old('license_kyu_code_number', $therapist->license_kyu_code_number ?? null) !== null) value="{{ old('license_kyu_code_number', $therapist->license_kyu_code_number) }}" @endif>
      </div>
    </div>

    <div>
      <label class="form-label-tab" for="license_kyu_issued_date">免許証交付年月日
        @error('license_kyu_issued_date')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="license_kyu_issued_date" name="license_kyu_issued_date" value="{{ old('license_kyu_issued_date', $therapist->license_kyu_issued_date ?? '') }}">
      </div>
    </div>

    {{-- 資格（あん摩・マッサージ） --}}
    <h4>資格（あん摩・マッサージ）</h4>

    <div>
      <label class="form-label-tab" for="license_massage_code_number">免許証記号番号
        @error('license_massage_code_number')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="number" id="license_massage_code_number" name="license_massage_code_number" @if(old('license_massage_code_number', $therapist->license_massage_code_number ?? null) !== null) value="{{ old('license_massage_code_number', $therapist->license_massage_code_number) }}" @endif>
      </div>
    </div>

    <div>
      <label class="form-label-tab" for="license_massage_issued_date">免許証交付年月日
        @error('license_massage_issued_date')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="license_massage_issued_date" name="license_massage_issued_date" value="{{ old('license_massage_issued_date', $therapist->license_massage_issued_date ?? '') }}">
      </div>
    </div>

    {{-- 大阪市発行の会員番号 --}}
    <div>
      <label class="form-label-tab" for="member_number">大阪市発行の会員番号
        @error('member_number')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="number" id="member_number" name="member_number" @if(old('member_number', $therapist->member_number ?? null) !== null) value="{{ old('member_number', $therapist->member_number) }}" @endif>
      </div>
    </div>

    {{-- メモ --}}
    <div>
      <label class="form-label-tab" for="note">メモ
        @error('note')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <textarea id="note" name="note" rows="4" style="width: 100%;">{{ old('note', $therapist->note ?? '') }}</textarea>
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
