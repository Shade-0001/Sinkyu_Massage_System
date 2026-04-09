@php
  // 呼び出し元が渡す変数一覧:
  // - action (string): フォーム送信先のURL
  // - sessionKey (string): セッションキーのプレフィックス（例: 'registration_data' / 'edit_data'）
  // - clinicUser (object|null): 編集時はモデル、登録時は null
  // - isEdit (bool): 編集モードかどうか（true = 編集）
  // - includeId (bool): hidden の id を含めるかどうか
  /** @var \App\Models\ClinicUser|null $clinicUser */
  $clinicUser = $clinicUser ?? null;
  $get = function($field, $default = '') use ($sessionKey, $clinicUser) {
  $fromModel = isset($clinicUser) && isset($clinicUser->$field) ? $clinicUser->$field : $default;
  return old($field, session($sessionKey . '.' . $field, $fromModel));
  };
@endphp

<form action="{{ $action }}" method="POST">
  @csrf
  @if(!empty($includeId) && isset($clinicUser->id))
  <input type="hidden" name="id" value="{{ $clinicUser->id }}">
  @endif

  <div class="d-flex flex-column gap-3 container ms-0">

    {{-- 氏名 --}}
    <div>
      <div class="form-label-tab">氏名
        @error('last_name')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('first_name')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </div>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <div class="d-flex gap-2 align-items-center">
          <div>
            <label for="last_name" class="form-label small mb-1 d-block">姓：</label>
            <input type="text" id="last_name" name="last_name" value="{{ $get('last_name') }}" @if(!empty($isEdit)) required @endif>
          </div>
          <div>
            <label for="first_name" class="form-label small mb-1 d-block">名：</label>
            <input type="text" id="first_name" name="first_name" value="{{ $get('first_name') }}" @if(!empty($isEdit)) required @endif>
          </div>
        </div>
      </div>
    </div>

    {{-- フリガナ --}}
    <div>
      <div class="form-label-tab">フリガナ
        @error('last_kana')<span class="text-danger ms-2">{{ $message }}</span>@enderror
        @error('first_kana')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </div>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <div class="d-flex gap-2 align-items-center">
          <div>
            <label for="last_kana" class="form-label small mb-1">セイ</label>
            <input type="text" id="last_kana" name="last_kana" value="{{ $get('last_kana') }}">
          </div>
          <div>
            <label for="first_kana" class="form-label small mb-1">メイ</label>
            <input type="text" id="first_kana" name="first_kana" value="{{ $get('first_kana') }}">
          </div>
        </div>
      </div>
    </div>

    {{-- 生年月日 --}}
    <div>
      <label class="form-label-tab" for="birthday">生年月日</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="date" id="birthday" name="birthday" value="{{ old('birthday', session($sessionKey . '.birthday', isset($clinicUser) && !empty($clinicUser->birthday) ? ($clinicUser->birthday instanceof \Carbon\Carbon ? $clinicUser->birthday->format('Y-m-d') : $clinicUser->birthday) : '')) }}">
      </div>
    </div>

    {{-- 年齢 --}}
    <div>
      <label class="form-label-tab" for="age">年齢
        @error('age')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="number" id="age" name="age" value="{{ $get('age') }}" min="0" max="150" readonly style="cursor: default;" data-tooltip="生年月日から自動入力されます">
      </div>
    </div>

    {{-- 性別 --}}
    <div>
      <label class="form-label-tab" for="gender_id">性別</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        @php $genderVal = old('gender_id', session($sessionKey . '.gender_id', isset($clinicUser) ? $clinicUser->gender_id ?? '' : '')); @endphp
        <select id="gender_id" name="gender_id">
          <option value="">╌╌╌</option>
          <option value="1" {{ $genderVal == '1' ? 'selected' : '' }}>男性</option>
          <option value="2" {{ $genderVal == '2' ? 'selected' : '' }}>女性</option>
        </select>
      </div>
    </div>

    {{-- 郵便番号 --}}
    <div>
      <label class="form-label-tab" for="postal_code">郵便番号
        @error('postal_code')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="postal_code" name="postal_code" value="{{ $get('postal_code') }}" placeholder="000-0000" maxlength="8">
        <div id="address-message" class="loading d-none mt-1"></div>
      </div>
    </div>

    {{-- 都道府県 --}}
    <div>
      <label class="form-label-tab" for="address_1">都道府県
        @error('address_1')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="address_1" name="address_1" value="{{ $get('address_1') }}" readonly data-tooltip="郵便番号から自動入力されます">
      </div>
    </div>

    {{-- 市区町村番地以下 --}}
    <div>
      <label class="form-label-tab" for="address_2">市区町村番地以下
        @error('address_2')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="address_2" name="address_2" value="{{ $get('address_2') }}">
      </div>
    </div>

    {{-- アパート・マンション名等 --}}
    <div>
      <label class="form-label-tab" for="address_3">アパート・マンション名等
        @error('address_3')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="address_3" name="address_3" value="{{ $get('address_3') }}">
      </div>
    </div>

    {{-- 電話番号 --}}
    <div>
      <label class="form-label-tab" for="phone">電話番号</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="phone" name="phone" value="{{ $get('phone') }}">
      </div>
    </div>

    {{-- 携帯番号 --}}
    <div>
      <label class="form-label-tab" for="cell_phone">携帯番号</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="cell_phone" name="cell_phone" value="{{ $get('cell_phone') }}">
      </div>
    </div>

    {{-- FAX番号 --}}
    <div>
      <label class="form-label-tab" for="fax">FAX番号</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="fax" name="fax" value="{{ $get('fax') }}">
      </div>
    </div>

    {{-- メールアドレス --}}
    <div>
      <label class="form-label-tab" for="email">メールアドレス</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="email" id="email" name="email" value="{{ $get('email') }}">
      </div>
    </div>

    {{-- 往診距離 --}}
    <div>
      <label class="form-label-tab" for="housecall_distance">往診距離（合計）</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="number" id="housecall_distance" name="housecall_distance" value="{{ $get('housecall_distance') }}" min="0">
      </div>
    </div>

    {{-- 往診加算距離 --}}
    <div>
      <label class="form-label-tab" for="housecall_additional_distance">往診加算距離</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="number" id="housecall_additional_distance" name="housecall_additional_distance" value="{{ $get('housecall_additional_distance') }}" min="0" readonly style="cursor: default;" data-tooltip="往診距離から自動入力されます">
      </div>
    </div>

    {{-- 償還対象 --}}
    <div>
      <label class="form-label-tab" for="is_redeemed">償還対象</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        @php $redeemed = old('is_redeemed', session($sessionKey . '.is_redeemed', isset($clinicUser) ? $clinicUser->is_redeemed ?? '' : '')); @endphp
        <input type="checkbox" id="is_redeemed" name="is_redeemed" value="1" {{ $redeemed ? 'checked' : '' }}>
      </div>
    </div>

    {{-- 申請書提出開始回数 --}}
    <div>
      <label class="form-label-tab" for="application_count">申請書提出開始回数<span class="fw-normal ms-1">［大阪市のみ］</span></label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="number" id="application_count" name="application_count" value="{{ $get('application_count') }}" min="0">
      </div>
    </div>

    {{-- メモ --}}
    <div>
      <label class="form-label-tab" for="note">メモ</label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <textarea id="note" name="note" rows="4" style="width: 100%;">{{ $get('note') }}</textarea>
      </div>
    </div>

    <div class="mt-4 text-end">
      <button type="submit" class="btn-ex-main btn-ex-blue">登録確認へ</button>
    </div>
  </div>
</form>
