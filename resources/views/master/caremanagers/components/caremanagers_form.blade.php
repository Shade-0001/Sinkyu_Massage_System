{{-- resources/views/caremanagers/components/caremanagers_form.blade.php --}}

<div class="caremanager-form">
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
        <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $careManager->last_name ?? '') }}">
      </div>
      <div>
        <label for="first_name" class="form-label small mb-1">名</label>
        @error('first_name')
          <span class="text-danger ms-2">{{ $message }}</span>
        @enderror
        <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $careManager->first_name ?? '') }}">
      </div>
    </div>
  </div>

  <div class="mb-3">
    <label class="fw-semibold">フリガナ</label>
    <br>
    <div class="d-flex gap-2 align-items-center">
      <div>
        <label for="last_name_kana" class="form-label small mb-1">セイ</label>
        @error('last_name_kana')
          <span class="text-danger ms-2">{{ $message }}</span>
        @enderror
        <input type="text" id="last_name_kana" name="last_name_kana" value="{{ old('last_name_kana', $careManager->last_name_kana ?? '') }}">
      </div>
      <div>
        <label for="first_name_kana" class="form-label small mb-1">メイ</label>
        @error('first_name_kana')
          <span class="text-danger ms-2">{{ $message }}</span>
        @enderror
        <input type="text" id="first_name_kana" name="first_name_kana" value="{{ old('first_name_kana', $careManager->first_name_kana ?? '') }}">
      </div>
    </div>
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="service_providers_id">サービス事業者名</label>
    @error('service_providers_id')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <select id="service_providers_id" name="service_providers_id">
      <option value="">╌╌╌</option>
      @foreach($serviceProviders as $provider)
        <option value="{{ $provider->id }}" {{ old('service_providers_id', $careManager->service_providers_id ?? '') == $provider->id ? 'selected' : '' }}>
          {{ $provider->service_provider_name }}
        </option>
      @endforeach
    </select>
    <br>
    <span class="small">上記選択にない場合、下記に入力する事でマスターとして登録します。</span><br>
    <span class="small">もしくは<a href="#">こちらから</a>登録してください。</span>
    <br>
    <input type="text" id="service_provider_name_custom" name="service_provider_name_custom" placeholder="入力されたデータをマスターとして新規登録。" value="{{ old('service_provider_name_custom') }}">
  </div>

  <br>

  <div class="mb-3">
    <label class="fw-semibold" for="postal_code">住所</label>
    @error('postal_code')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <span class="small">郵便番号を入力すると住所が自動で入力されます <a href="https://www.post.japanpost.jp/zipcode/" target="_blank">[日本郵便HPへ]</a></span>
    <br>
    <label for="postal_code" class="small">郵便番号</label><br>
    <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $careManager->postal_code ?? '') }}" placeholder="000-0000" maxlength="8">
    <div id="caremanager-address-message" class="loading d-none mt-1"></div>
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="address_1">都道府県</label>
    @error('address_1')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="address_1" name="address_1" value="{{ old('address_1', $careManager->address_1 ?? '') }}" readonly data-tooltip="郵便番号から自動入力されます">
  </div>

  <div class="mb-3">
    <label for="address_2" class="small">市区町村番地以下</label>
    @error('address_2')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="address_2" name="address_2" value="{{ old('address_2', $careManager->address_2 ?? '') }}">
  </div>

  <div class="mb-3">
    <label for="address_3" class="small">アパート・マンション名等</label>
    @error('address_3')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="address_3" name="address_3" value="{{ old('address_3', $careManager->address_3 ?? '') }}">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="phone">電話番号</label>
    @error('phone')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="phone" name="phone" value="{{ old('phone', $careManager->phone ?? '') }}" placeholder="03-1234-5678">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="cell_phone">携帯番号</label>
    @error('cell_phone')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="cell_phone" name="cell_phone" value="{{ old('cell_phone', $careManager->cell_phone ?? '') }}" placeholder="03-1234-5678">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="fax">FAX番号</label>
    @error('fax')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="fax" name="fax" value="{{ old('fax', $careManager->fax ?? '') }}" placeholder="03-1234-5679">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="email">メールアドレス</label>
    @error('email')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="email" id="email" name="email" value="{{ old('email', $careManager->email ?? '') }}" placeholder="yamada@google.co.jp">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="note">メモ</label>
    @error('note')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <textarea id="note" name="note" rows="4">{{ old('note', $careManager->note ?? '') }}</textarea>
  </div>

  <button type="submit">{{ $submitLabel }}</button>
  <a href="{{ $cancelRoute }}">
    <button type="button">キャンセル</button>
  </a>
</div>
