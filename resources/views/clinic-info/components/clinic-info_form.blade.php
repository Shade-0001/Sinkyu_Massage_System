{{-- resources/views/clinic-info/components/clinic-info_form.blade.php --}}

<div class="clinic-info-form">
  @csrf

  <div class="mb-3">
    <label class="fw-semibold" for="clinic_name">事業所名</label>
    @error('clinic_name')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="clinic_name" name="clinic_name" value="{{ old('clinic_name', $companyInfo->clinic_name ?? '') }}">
  </div>

  <div class="mb-3">
    <label class="fw-semibold">代表者氏名</label>
    <br>
    <label for="owner_last_name" class="small fw-medium">姓</label>
    @error('owner_last_name')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="owner_last_name" name="owner_last_name" value="{{ old('owner_last_name', $companyInfo->owner_last_name ?? '') }}">
  </div>

  <div class="mb-3">
    <label for="owner_first_name" class="small fw-medium">名</label>
    @error('owner_first_name')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="owner_first_name" name="owner_first_name" value="{{ old('owner_first_name', $companyInfo->owner_first_name ?? '') }}">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="owner_birthday">代表者生年月日</label>
    @error('owner_birthday')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="date" id="owner_birthday" name="owner_birthday" value="{{ old('owner_birthday', $companyInfo->owner_birthday ?? '') }}">
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
    <label for="postal_code" class="small fw-medium">郵便番号</label><br>
    <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $companyInfo->postal_code ?? '') }}" placeholder="000-0000" maxlength="8">
    <div id="clinic-info-address-message" class="loading d-none mt-1"></div>
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="address_1">都道府県</label>
    @error('address_1')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="address_1" name="address_1" value="{{ old('address_1', $companyInfo->address_1 ?? '') }}" readonly data-tooltip="郵便番号から自動入力されます">
  </div>

  <div class="mb-3">
    <label for="address_2" class="small fw-medium">市区町村番地以下</label>
    @error('address_2')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="address_2" name="address_2" value="{{ old('address_2', $companyInfo->address_2 ?? '') }}">
  </div>

  <div class="mb-3">
    <label for="address_3" class="small fw-medium">アパート・マンション名等</label>
    @error('address_3')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="address_3" name="address_3" value="{{ old('address_3', $companyInfo->address_3 ?? '') }}">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="phone">電話番号</label>
    @error('phone')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="phone" name="phone" value="{{ old('phone', $companyInfo->phone ?? '') }}" placeholder="03-1234-5678">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="cellphone">携帯番号</label>
    @error('cellphone')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="cellphone" name="cellphone" value="{{ old('cellphone', $companyInfo->cellphone ?? '') }}" placeholder="090-1234-5678">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="freephone">フリーダイヤル</label>
    @error('freephone')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="freephone" name="freephone" value="{{ old('freephone', $companyInfo->freephone ?? '') }}" placeholder="0120-123-456">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="fax">FAX番号</label>
    @error('fax')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="fax" name="fax" value="{{ old('fax', $companyInfo->fax ?? '') }}" placeholder="03-1234-5679">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="email">メールアドレス</label>
    @error('email')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="email" id="email" name="email" value="{{ old('email', $companyInfo->email ?? '') }}" placeholder="yamada@google.co.jp">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="website_url">ホームページURL</label>
    @error('website_url')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="website_url" name="website_url" value="{{ old('website_url', $companyInfo->website_url ?? '') }}" placeholder="https://example.co.jp">
  </div>

  <br>

  <div class="mb-3">
    <label class="fw-semibold" for="business_hours_start">営業時間</label>
    @error('business_hours_start')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="time" id="business_hours_start" name="business_hours_start" value="{{ old('business_hours_start', $companyInfo->business_hours_start ?? '') }}">
    ～
    <input type="time" id="business_hours_end" name="business_hours_end" value="{{ old('business_hours_end', $companyInfo->business_hours_end ?? '') }}">
  </div>

  <div class="mb-3">
    <label class="fw-semibold">定休日</label><br>
    <input type="checkbox" id="closed_day_monday" name="closed_day_monday" value="1" {{ old('closed_day_monday', $companyInfo->closed_day_monday ?? 0) ? 'checked' : '' }}>
    <label for="closed_day_monday">月</label>

    <input type="checkbox" id="closed_day_tuesday" name="closed_day_tuesday" value="1" {{ old('closed_day_tuesday', $companyInfo->closed_day_tuesday ?? 0) ? 'checked' : '' }}>
    <label for="closed_day_tuesday">火</label>

    <input type="checkbox" id="closed_day_wednesday" name="closed_day_wednesday" value="1" {{ old('closed_day_wednesday', $companyInfo->closed_day_wednesday ?? 0) ? 'checked' : '' }}>
    <label for="closed_day_wednesday">水</label>

    <input type="checkbox" id="closed_day_thursday" name="closed_day_thursday" value="1" {{ old('closed_day_thursday', $companyInfo->closed_day_thursday ?? 0) ? 'checked' : '' }}>
    <label for="closed_day_thursday">木</label>

    <input type="checkbox" id="closed_day_friday" name="closed_day_friday" value="1" {{ old('closed_day_friday', $companyInfo->closed_day_friday ?? 0) ? 'checked' : '' }}>
    <label for="closed_day_friday">金</label>

    <input type="checkbox" id="closed_day_saturday" name="closed_day_saturday" value="1" {{ old('closed_day_saturday', $companyInfo->closed_day_saturday ?? 0) ? 'checked' : '' }}>
    <label for="closed_day_saturday">土</label>

    <input type="checkbox" id="closed_day_sunday" name="closed_day_sunday" value="1" {{ old('closed_day_sunday', $companyInfo->closed_day_sunday ?? 0) ? 'checked' : '' }}>
    <label for="closed_day_sunday">日</label>
  </div>

  <br>

  <div class="mb-3">
    <label class="fw-semibold" for="bank_account_type">振込先銀行</label><br>
    <label for="bank_account_type">預金種類</label>
    @error('bank_account_type')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <select id="bank_account_type" name="bank_account_type">
      <option value="">╌╌╌</option>
      @foreach($bankAccountTypes as $type)
        <option value="{{ $type }}" {{ old('bank_account_type', $companyInfo->bank_account_type ?? '') == $type ? 'selected' : '' }}>
          {{ $type }}
        </option>
      @endforeach
    </select>
  </div>

  <div class="mb-3">
    <label for="bank_name">銀行名</label>
    @error('bank_name')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name', $companyInfo->bank_name ?? '') }}">
  </div>

  <div class="mb-3">
    <label for="bank_branch_name">支店名</label>
    @error('bank_branch_name')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="bank_branch_name" name="bank_branch_name" value="{{ old('bank_branch_name', $companyInfo->bank_branch_name ?? '') }}">
  </div>

  <div class="mb-3">
    <label for="bank_account_name">口座名義</label>
    @error('bank_account_name')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="bank_account_name" name="bank_account_name" value="{{ old('bank_account_name', $companyInfo->bank_account_name ?? '') }}">
  </div>

  <div class="mb-3">
    <label for="bank_account_name_kana">口座名義（カナ）</label>
    @error('bank_account_name_kana')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="bank_account_name_kana" name="bank_account_name_kana" value="{{ old('bank_account_name_kana', $companyInfo->bank_account_name_kana ?? '') }}">
  </div>

  <div class="mb-3">
    <label for="bank_code">銀行コード</label>
    @error('bank_code')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="bank_code" name="bank_code" value="{{ old('bank_code', $companyInfo->bank_code ?? '') }}" placeholder="0001">
  </div>

  <div class="mb-3">
    <label for="bank_branch_code">支店コード</label>
    @error('bank_branch_code')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="bank_branch_code" name="bank_branch_code" value="{{ old('bank_branch_code', $companyInfo->bank_branch_code ?? '') }}" placeholder="001">
  </div>

  <div class="mb-3">
    <label for="bank_account_number">口座番号</label>
    @error('bank_account_number')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $companyInfo->bank_account_number ?? '') }}" placeholder="0123456">
  </div>

  <br>

  <div class="mb-3">
    <label class="fw-semibold" for="health_center_registerd_location">保健所登録分</label>
    @error('health_center_registerd_location')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <select id="health_center_registerd_location" name="health_center_registerd_location">
      <option value="">╌╌╌</option>
      @foreach($healthCenterLocations as $location)
        <option value="{{ $location }}" {{ old('health_center_registerd_location', $companyInfo->health_center_registerd_location ?? '') == $location ? 'selected' : '' }}>
          {{ $location }}
        </option>
      @endforeach
    </select>
  </div>

  <br>

  <div class="mb-3">
    <label class="fw-semibold">はり師免許</label><br>
    <label for="license_hari_number">はり師免許番号</label>
    @error('license_hari_number')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="license_hari_number" name="license_hari_number" value="{{ old('license_hari_number', $companyInfo->license_hari_number ?? '') }}" placeholder="123456">
  </div>

  <div class="mb-3">
    <label for="license_hari_issued_date">はり師免許交付年月日</label>
    @error('license_hari_issued_date')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="date" id="license_hari_issued_date" name="license_hari_issued_date" value="{{ old('license_hari_issued_date', $companyInfo->license_hari_issued_date ?? '') }}">
  </div>

  <br>

  <div class="mb-3">
    <label class="fw-semibold">きゅう師免許</label><br>
    <label for="license_kyu_number">きゅう師免許番号</label>
    @error('license_kyu_number')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="license_kyu_number" name="license_kyu_number" value="{{ old('license_kyu_number', $companyInfo->license_kyu_number ?? '') }}" placeholder="123456">
  </div>

  <div class="mb-3">
    <label for="license_kyu_issued_date">きゅう師免許交付年月日</label>
    @error('license_kyu_issued_date')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="date" id="license_kyu_issued_date" name="license_kyu_issued_date" value="{{ old('license_kyu_issued_date', $companyInfo->license_kyu_issued_date ?? '') }}">
  </div>

  <br>

  <div class="mb-3">
    <label class="fw-semibold">あん摩・マッサージ師免許</label><br>
    <label for="license_massage_number">あん摩・マッサージ師免許番号</label>
    @error('license_massage_number')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="license_massage_number" name="license_massage_number" value="{{ old('license_massage_number', $companyInfo->license_massage_number ?? '') }}" placeholder="123456">
  </div>

  <div class="mb-3">
    <label for="license_massage_issued_date">あん摩・マッサージ師免許交付年月日</label>
    @error('license_massage_issued_date')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="date" id="license_massage_issued_date" name="license_massage_issued_date" value="{{ old('license_massage_issued_date', $companyInfo->license_massage_issued_date ?? '') }}">
  </div>

  <br>

  <div class="mb-3">
    <label class="fw-semibold" for="billing_prefecture">請求先都道府県</label>
    @error('billing_prefecture')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <select id="billing_prefecture" name="billing_prefecture">
      <option value="">╌╌╌</option>
      @foreach($prefectures as $prefecture)
        <option value="{{ $prefecture }}" {{ old('billing_prefecture', $companyInfo->billing_prefecture ?? '') == $prefecture ? 'selected' : '' }}>
          {{ $prefecture }}
        </option>
      @endforeach
    </select>
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="therapist_number">施術者付与（登録）番号</label>
    @error('therapist_number')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="therapist_number" name="therapist_number" value="{{ old('therapist_number', $companyInfo->therapist_number ?? '') }}" placeholder="123456">
  </div>

  <div class="mb-3">
    <label class="fw-semibold" for="medical_institution_number">施術機関番号</label>
    @error('medical_institution_number')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="text" id="medical_institution_number" name="medical_institution_number" value="{{ old('medical_institution_number', $companyInfo->medical_institution_number ?? '') }}" placeholder="123456">
  </div>

  <div class="mb-3">
    <label class="fw-semibold">領収書発行時の領収金額の四捨五入</label>
    @error('should_round_amount')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    <input type="radio" id="should_round_amount_0" name="should_round_amount" value="0" {{ old('should_round_amount', $companyInfo->should_round_amount ?? 0) == 0 ? 'checked' : '' }}>
    <label for="should_round_amount_0">四捨五入しない</label>

    <input type="radio" id="should_round_amount_1" name="should_round_amount" value="1" {{ old('should_round_amount', $companyInfo->should_round_amount ?? 0) == 1 ? 'checked' : '' }}>
    <label for="should_round_amount_1">1桁目を四捨五入する</label>
  </div>

  <div class="mb-3">
    <label class="fw-semibold">申請書等の書式選択</label>
    @error('document_formats')
      <span class="text-danger ms-2">{{ $message }}</span>
    @enderror
    <br>
    @foreach($documentFormats as $format)
      <input type="radio" id="document_format_{{ str_replace(['2013', ' '], ['', '_'], strtolower($format)) }}" name="document_formats" value="{{ $format }}" {{ old('document_formats', $companyInfo->document_formats ?? '') == $format ? 'checked' : '' }}>
      <label for="document_format_{{ str_replace(['2013', ' '], ['', '_'], strtolower($format)) }}">{{ $format }}</label>
    @endforeach
  </div>

  <button type="submit">{{ $submitLabel }}</button>
  <a href="{{ $cancelRoute }}">
    <button type="button">キャンセル</button>
  </a>
</div>
