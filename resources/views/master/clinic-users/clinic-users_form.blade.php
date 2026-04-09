@php
  // 呼び出し元が渡す変数一覧:
  // - action (string): フォーム送信先のURL
  // - sessionKey (string): セッションキーのプレフィックス（例: 'registration_data' / 'edit_data'）
  // - clinicUser (object|null): 編集時はモデル、登録時は null
  // - isEdit (bool): 編集モードかどうか（true = 編集）
  // - includeId (bool): hidden の id を含めるかどうか
  $get = function($field, $default = '') use ($sessionKey, $clinicUser) {
  $fromModel = isset($clinicUser) && isset($clinicUser->$field) ? $clinicUser->$field : $default;
  // 日付はモデルが Carbon の場合、表示用に Y-m-d に整形すること。
  // 必要に応じて呼び出し元で調整すること。
  return old($field, session($sessionKey . '.' . $field, $fromModel));
  };
@endphp

<form action="{{ $action }}" method="POST">
  @csrf
  @if(!empty($includeId) && isset($clinicUser->id))
  <input type="hidden" name="id" value="{{ $clinicUser->id }}">
  @endif

  <div class="row g-3 gx-3 align-content-start align-items-start" style="max-width: 600px;">

    {{-- 氏名 --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3">氏名
          @error('last_name')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
          @error('first_name')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
        </label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1">
          <div class="d-flex gap-2 align-items-center">
            <div>
              <label for="last_name" class="form-label small mb-1">姓</label>
              <input type="text" id="last_name" name="last_name" value="{{ $get('last_name') }}" @if(!empty($isEdit)) required @endif>
            </div>
            <div>
              <label for="first_name" class="form-label small mb-1">名</label>
              <input type="text" id="first_name" name="first_name" value="{{ $get('first_name') }}" @if(!empty($isEdit)) required @endif>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- フリガナ --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3">フリガナ
          @error('last_kana')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
          @error('first_kana')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
        </label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1">
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
    </div>

    {{-- 生年月日 --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="birthday">生年月日</label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
          <input type="date" id="birthday" name="birthday" value="{{ old('birthday', session($sessionKey . '.birthday', isset($clinicUser) && !empty($clinicUser->birthday) ? ($clinicUser->birthday instanceof \Carbon\Carbon ? $clinicUser->birthday->format('Y-m-d') : $clinicUser->birthday) : '')) }}">
        </div>
      </div>
    </div>

    {{-- 年齢 --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="age">年齢
          @error('age')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
        </label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
          <input type="number" id="age" name="age" value="{{ $get('age') }}" min="0" max="150" readonly style="cursor: default;" data-tooltip="生年月日から自動入力されます">
        </div>
      </div>
    </div>

    {{-- 性別 --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="gender_id">性別</label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
          @php $genderVal = old('gender_id', session($sessionKey . '.gender_id', isset($clinicUser) ? $clinicUser->gender_id ?? '' : '')); @endphp
          <select id="gender_id" name="gender_id">
            <option value="">╌╌╌</option>
            <option value="1" {{ $genderVal == '1' ? 'selected' : '' }}>男性</option>
            <option value="2" {{ $genderVal == '2' ? 'selected' : '' }}>女性</option>
          </select>
        </div>
      </div>
    </div>

    {{-- 郵便番号 --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="postal_code">郵便番号
          @error('postal_code')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
        </label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1">
          <div class="d-flex align-items-center">
            <input type="text" id="postal_code" name="postal_code" value="{{ $get('postal_code') }}" placeholder="000-0000" maxlength="8">
          </div>
          <div id="address-message" class="loading d-none mt-1"></div>
        </div>
      </div>
    </div>

    {{-- 都道府県 --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="address_1">都道府県
          @error('address_1')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
        </label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
          <input type="text" id="address_1" name="address_1" value="{{ $get('address_1') }}" readonly data-tooltip="郵便番号から自動入力されます">
        </div>
      </div>
    </div>

    {{-- 市区町村番地以下 --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="address_2">市区町村番地以下
          @error('address_2')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
        </label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
          <input type="text" id="address_2" name="address_2" value="{{ $get('address_2') }}">
        </div>
      </div>
    </div>

    {{-- アパート・マンション名等 --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="address_3">アパート・マンション名等
          @error('address_3')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
        </label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
          <input type="text" id="address_3" name="address_3" value="{{ $get('address_3') }}">
        </div>
      </div>
    </div>

    {{-- 電話番号 --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="phone">電話番号</label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
          <input type="text" id="phone" name="phone" value="{{ $get('phone') }}">
        </div>
      </div>
    </div>

    {{-- 携帯番号 --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="cell_phone">携帯番号</label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
          <input type="text" id="cell_phone" name="cell_phone" value="{{ $get('cell_phone') }}">
        </div>
      </div>
    </div>

    {{-- FAX番号 --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="fax">FAX番号</label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
          <input type="text" id="fax" name="fax" value="{{ $get('fax') }}">
        </div>
      </div>
    </div>

    {{-- メールアドレス --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="email">メールアドレス</label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
          <input type="email" id="email" name="email" value="{{ $get('email') }}">
        </div>
      </div>
    </div>

    {{-- 往診距離 --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="housecall_distance">往診距離（合計）</label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
          <input type="number" id="housecall_distance" name="housecall_distance" value="{{ $get('housecall_distance') }}" min="0">
        </div>
      </div>
    </div>

    {{-- 往診加算距離 --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="housecall_additional_distance">往診加算距離</label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1">
          <div class="d-flex align-items-center">
            <input type="number" id="housecall_additional_distance" name="housecall_additional_distance" value="{{ $get('housecall_additional_distance') }}" min="0">
          </div>
          <div class="small text-secondary mt-1">2㎞を超える場合の加算距離です。上記往診距離が2㎞以上の場合自動で入力されます</div>
        </div>
      </div>
    </div>

    {{-- 償還対象 --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="is_redeemed">償還対象</label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
          @php $redeemed = old('is_redeemed', session($sessionKey . '.is_redeemed', isset($clinicUser) ? $clinicUser->is_redeemed ?? '' : '')); @endphp
          <input type="checkbox" id="is_redeemed" name="is_redeemed" value="1" {{ $redeemed ? 'checked' : '' }}>
        </div>
      </div>
    </div>

    {{-- 申請書提出開始回数 --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="application_count">申請書提出開始回数<span class="fw-normal small ms-1">［大阪市のみ］</span></label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
          <input type="number" id="application_count" name="application_count" value="{{ $get('application_count') }}" min="0">
        </div>
      </div>
    </div>

    {{-- メモ --}}
    <div class="col-12">
      <div class="rounded-1 d-flex align-items-start overflow-hidden">
        <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="note">メモ</label>
        <div class="vr align-self-stretch"></div>
        <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1">
          <textarea id="note" name="note" rows="4" style="width: 100%;">{{ $get('note') }}</textarea>
        </div>
      </div>
    </div>

  </div>

  <div class="mt-4">
    <button type="submit">登録確認へ</button>
  </div>
</form>
