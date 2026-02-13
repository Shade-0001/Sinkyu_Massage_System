{{-- resources/views/master/documents/components/documents_form.blade.php --}}

@csrf

<div class="mb-3">
  <label class="fw-semibold" for="document_category">文面カテゴリ</span></label><br>
  <select name="document_category" id="document_category">
    <option value="">╌╌╌</option>
    @foreach($categories as $category)
      <option value="{{ $category }}" {{ old('document_category', $item->document_category ?? '') == $category ? 'selected' : '' }}>
        {{ $category }}
      </option>
    @endforeach
  </select>
  @error('document_category')
    <div class="text-danger">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label class="fw-semibold" for="document_name">文面名称</label><br>
  <input type="text" name="document_name" id="document_name" value="{{ old('document_name', $item->document_name ?? '') }}" placeholder="文面名称を入力…">
  <div id="name-duplicate-error" class="text-danger" style="display: none;">既存の文面名称と重複。文面名称を変更が必要。</div>
  @error('document_name')
    <div class="text-danger">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label class="fw-semibold" for="content">本文</span></label><br>
  <textarea name="content" id="content" rows="6" maxlength="2000">{{ old('content', $item->content ?? '') }}</textarea>
  @error('content')
    <div class="text-danger">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label class="fw-semibold" for="font_size">フォントサイズ</label><br>
  <input type="number" name="font_size" id="font_size" value="{{ old('font_size', $item->font_size ?? 12) }}" style="width: 100px;">
  @error('font_size')
    <div class="text-danger">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label class="fw-semibold" for="line_height">行間隔</label><br>
  <input type="number" name="line_height" id="line_height" value="{{ old('line_height', $item->line_height ?? 7) }}" style="width: 100px;">
  @error('line_height')
    <div class="text-danger">{{ $message }}</div>
  @enderror
</div>

<button type="submit" id="submit-btn">{{ $submitLabel ?? '登録' }}</button>
<a href="{{ $cancelRoute ?? route('master.documents.index') }}">
  <button type="button">キャンセル</button>
</a>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
