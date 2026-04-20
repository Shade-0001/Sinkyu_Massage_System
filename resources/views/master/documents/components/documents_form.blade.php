{{-- resources/views/master/documents/components/documents_form.blade.php --}}

@csrf

<div class="d-flex flex-column gap-4 container ms-0">

  {{-- 文書カテゴリ --}}
  <div>
    <label class="form-label-tab" for="document_category">文書カテゴリ
      @error('document_category')<span class="text-danger ms-2">{{ $message }}</span>@enderror
    </label>
    <div class="form-field px-3 py-2">
      <div class="form-field-top"></div>
      <select name="document_category" id="document_category">
        <option value="">╌╌╌</option>
        @foreach($categories as $category)
          <option value="{{ $category }}" {{ old('document_category', $item->document_category ?? '') == $category ? 'selected' : '' }}>
            {{ $category }}
          </option>
        @endforeach
      </select>
    </div>
  </div>

  {{-- 文書名称 --}}
  <div>
    <label class="form-label-tab" for="document_name">文書名称
      @error('document_name')<span class="text-danger ms-2">{{ $message }}</span>@enderror
    </label>
    <div class="form-field px-3 py-2">
      <div class="form-field-top"></div>
      <input type="text" name="document_name" id="document_name" value="{{ old('document_name', $item->document_name ?? '') }}" placeholder="文書名称を入力…（任意）">
      <div id="name-duplicate-error" class="text-danger mt-1" style="display: none;">既存の文書名称と重複。文書名称を変更が必要。</div>
    </div>
  </div>

  {{-- 本文 --}}
  <div>
    <label class="form-label-tab" for="content">本文
      @error('content')<span class="text-danger ms-2">{{ $message }}</span>@enderror
    </label>
    <div class="form-field px-3 py-2">
      <div class="form-field-top"></div>
      <textarea name="content" id="content" rows="10" style="width: 100%;">{{ old('content', $item->content ?? '') }}</textarea>
    </div>
  </div>

  {{-- 患者情報 --}}
  <div>
    <label class="form-label-tab">患者情報</label>
    <div class="form-field px-3 py-2">
      <div class="form-field-top"></div>
      <div class="d-flex gap-4 align-items-center">
        <div class="form-check">
          <input class="form-check-input" type="radio" name="show_patient_info" id="patient_info_none" value="0"
            {{ old('show_patient_info', $item->show_patient_info ?? 0) == 0 ? 'checked' : '' }}>
          <label class="form-check-label" for="patient_info_none">なし</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="show_patient_info" id="patient_info_show" value="1"
            {{ old('show_patient_info', $item->show_patient_info ?? 0) == 1 ? 'checked' : '' }}>
          <label class="form-check-label" for="patient_info_show">あり</label>
        </div>
      </div>
    </div>
  </div>

  {{-- 患者氏名・傷病名（患者情報ありの場合のみ表示） --}}
  <div id="patient-info-fields" style="{{ old('show_patient_info', $item->show_patient_info ?? 0) == 1 ? '' : 'display:none;' }}">
    <div class="d-flex flex-column gap-4">
      <div>
        <label class="form-label-tab" for="patient_name">患者氏名</label>
        <div class="form-field px-3 py-2">
          <div class="form-field-top"></div>
          <input type="text" name="patient_name" id="patient_name" value="{{ old('patient_name', $item->patient_name ?? '') }}" placeholder="患者氏名を入力…" maxlength="100">
        </div>
      </div>
      <div>
        <label class="form-label-tab" for="patient_illness">傷病名</label>
        <div class="form-field px-3 py-2">
          <div class="form-field-top"></div>
          <input type="text" name="patient_illness" id="patient_illness" value="{{ old('patient_illness', $item->patient_illness ?? '') }}" placeholder="傷病名を入力…" maxlength="100">
        </div>
      </div>
    </div>
  </div>

  {{-- フォントサイズ --}}
  <div>
    <label class="form-label-tab" for="font_size">フォントサイズ（pt）
      @error('font_size')<span class="text-danger ms-2">{{ $message }}</span>@enderror
    </label>
    <div class="form-field px-3 py-2">
      <div class="form-field-top"></div>
      <input type="number" name="font_size" id="font_size" value="{{ old('font_size', $item->font_size ?? 12) }}" step="0.1" style="width: 100px;">
    </div>
  </div>

  {{-- 行間隔 --}}
  <div>
    <label class="form-label-tab" for="line_height">行間隔（mm）
      @error('line_height')<span class="text-danger ms-2">{{ $message }}</span>@enderror
    </label>
    <div class="form-field px-3 py-2">
      <div class="form-field-top"></div>
      <input type="number" name="line_height" id="line_height" value="{{ old('line_height', $item->line_height ?? 7) }}" step="0.1" style="width: 100px;">
    </div>
  </div>

  <div class="mt-4 d-flex gap-2 justify-content-end">
    <a href="{{ $cancelRoute ?? route('master.documents.index') }}" class="btn-ex-main btn-ex-gray"><i class="nf nf-fa-caret_left me-1" style="transform: scale(1.2)"></i>戻る</a>
    <button type="submit" id="submit-btn" class="btn-ex-main btn-ex-green">{{ $submitLabel ?? '登録' }}</button>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // 患者情報ラジオボタンの切り替え
  const patientInfoFields = document.getElementById('patient-info-fields');
  document.querySelectorAll('input[name="show_patient_info"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
      patientInfoFields.style.display = this.value === '1' ? '' : 'none';
    });
  });

  const nameInput = document.getElementById('document_name');
  const duplicateError = document.getElementById('name-duplicate-error');
  const submitBtn = document.getElementById('submit-btn');
  let typingTimer;
  const typingDelay = 500; // 500msの遅延
  const excludeId = {{ isset($item->id) ? $item->id : 'null' }};

  if (!nameInput || !duplicateError || !submitBtn) {
    return;
  }

  // 文面名称の入力時にリアルタイムチェック
  nameInput.addEventListener('input', function() {
    clearTimeout(typingTimer);

    const name = this.value.trim();

    // 空の場合はエラーを非表示
    if (name === '') {
      duplicateError.style.display = 'none';
      submitBtn.disabled = false;
      return;
    }

    // 入力が止まってから500ms後にチェック
    typingTimer = setTimeout(function() {
      checkDuplicateName(name, excludeId);
    }, typingDelay);
  });

  function checkDuplicateName(name, excludeId) {
    // CSRFトークンを取得
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                      document.querySelector('input[name="_token"]')?.value;

    if (!csrfToken) {
      return;
    }

    fetch('{{ route('master.documents.check-duplicate-name') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        document_name: name,
        exclude_id: excludeId
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.exists) {
        duplicateError.style.display = 'block';
        submitBtn.disabled = true;
      } else {
        duplicateError.style.display = 'none';
        submitBtn.disabled = false;
      }
    })
    .catch(error => {
      console.error('重複チェックエラー:', error);
    });
  }
});
</script>
