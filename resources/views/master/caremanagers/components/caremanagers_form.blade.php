{{-- resources/views/caremanagers/components/caremanagers_form.blade.php --}}

<div class="caremanager-form">
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
            <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $careManager->last_name ?? '') }}" placeholder="姓">
          </div>
          <div>
            <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $careManager->first_name ?? '') }}" placeholder="名">
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
            <input type="text" id="last_name_kana" name="last_name_kana" value="{{ old('last_name_kana', $careManager->last_name_kana ?? '') }}" placeholder="セイ">
          </div>
          <div>
            <input type="text" id="first_name_kana" name="first_name_kana" value="{{ old('first_name_kana', $careManager->first_name_kana ?? '') }}" placeholder="メイ">
          </div>
        </div>
      </div>
    </div>

    {{-- サービス事業者名 --}}
    <div>
      <label class="form-label-tab" for="service_providers_id">サービス事業者名
        @error('service_providers_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <select id="service_providers_id" name="service_providers_id">
          <option value="">╌╌╌</option>
          @foreach($serviceProviders as $provider)
            <option value="{{ $provider->id }}" {{ old('service_providers_id', $careManager->service_providers_id ?? '') == $provider->id ? 'selected' : '' }}>
              {{ $provider->service_provider_name }}
            </option>
          @endforeach
        </select>
        <div class="mt-1">
          <span class="small">上記選択にない場合、下記に入力する事でマスターとして登録します。</span><br>
          <span class="small">もしくは<a href="#">こちらから</a>登録してください。</span>
        </div>
        <input type="text" id="service_provider_name_custom" name="service_provider_name_custom" placeholder="入力されたデータをマスターとして新規登録。" value="{{ old('service_provider_name_custom') }}">
      </div>
    </div>

    {{-- 郵便番号 --}}
    <div>
      <label class="form-label-tab" for="postal_code">郵便番号
        @error('postal_code')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <div class="small mb-1">郵便番号を入力すると住所が自動で入力されます <a href="https://www.post.japanpost.jp/zipcode/" target="_blank">[日本郵便HPへ]</a></div>
        <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $careManager->postal_code ?? '') }}" placeholder="000-0000" maxlength="8">
        <div id="caremanager-address-message" class="loading d-none mt-1"></div>
      </div>
    </div>

    {{-- 都道府県 --}}
    <div>
      <label class="form-label-tab" for="address_1">都道府県
        @error('address_1')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="address_1" name="address_1" value="{{ old('address_1', $careManager->address_1 ?? '') }}" readonly data-tooltip="郵便番号から自動入力されます">
      </div>
    </div>

    {{-- 市区町村番地以下 --}}
    <div>
      <label class="form-label-tab" for="address_2">市区町村番地以下
        @error('address_2')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="address_2" name="address_2" value="{{ old('address_2', $careManager->address_2 ?? '') }}">
      </div>
    </div>

    {{-- アパート・マンション名等 --}}
    <div>
      <label class="form-label-tab" for="address_3">アパート・マンション名等
        @error('address_3')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="address_3" name="address_3" value="{{ old('address_3', $careManager->address_3 ?? '') }}">
      </div>
    </div>

    {{-- 電話番号 --}}
    <div>
      <label class="form-label-tab" for="phone">電話番号
        @error('phone')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="phone" name="phone" value="{{ old('phone', $careManager->phone ?? '') }}" placeholder="03-1234-5678">
      </div>
    </div>

    {{-- 携帯番号 --}}
    <div>
      <label class="form-label-tab" for="cell_phone">携帯番号
        @error('cell_phone')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="cell_phone" name="cell_phone" value="{{ old('cell_phone', $careManager->cell_phone ?? '') }}" placeholder="03-1234-5678">
      </div>
    </div>

    {{-- FAX番号 --}}
    <div>
      <label class="form-label-tab" for="fax">FAX番号
        @error('fax')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="text" id="fax" name="fax" value="{{ old('fax', $careManager->fax ?? '') }}" placeholder="03-1234-5679">
      </div>
    </div>

    {{-- メールアドレス --}}
    <div>
      <label class="form-label-tab" for="email">メールアドレス
        @error('email')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input type="email" id="email" name="email" value="{{ old('email', $careManager->email ?? '') }}" placeholder="yamada@google.co.jp">
      </div>
    </div>

    {{-- メモ --}}
    <div>
      <label class="form-label-tab" for="note">メモ
        @error('note')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <textarea id="note" name="note" rows="4" style="width: 100%;">{{ old('note', $careManager->note ?? '') }}</textarea>
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
