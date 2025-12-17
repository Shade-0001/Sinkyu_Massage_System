<x-app-layout>
<div class="container-fluid mt-4">
  <h4 class="mb-4">はり・きゅう療養費支給申請書 - 座標調整ツール</h4>

  <div class="row">
    <!-- 左側: 設定パネル -->
    <div class="col-md-3">
      <div class="card">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">フィールド設定</h5>
        </div>
        <div class="card-body p-0" style="max-height: 80vh; overflow-y: auto;">
          <!-- 利用者選択 -->
          <div class="p-3 border-bottom bg-light">
            <label for="clinic-user-select" class="form-label mb-2">プレビュー利用者：</label>
            <select id="clinic-user-select" class="form-control">
              @foreach($clinicUsers as $user)
                <option value="{{ $user->id }}">
                  {{ $user->last_name }} {{ $user->first_name }} ({{ $user->last_kana }} {{ $user->first_kana }})
                </option>
              @endforeach
            </select>
          </div>

          <div id="field-settings">
            <!-- JavaScriptで動的に生成 -->
          </div>

          <div class="mt-4">
            <div id="save-status" class="alert alert-success" style="display: none; padding: 8px; margin-bottom: 10px; font-size: 0.9em;">
              自動保存済み
            </div>
            <button id="btn-reset" class="btn btn-secondary btn-block">
              元に戻す
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 右側: PDFプレビュー -->
    <div class="col-md-9">
      <div class="card">
        <div class="card-header bg-info text-white">
          <h5 class="mb-0">
            PDFプレビュー
            <span id="preview-loading" class="badge badge-light ml-2" style="display: none;">更新中...</span>
            <span id="save-indicator" class="badge badge-success ml-2" style="display: none;">保存中...</span>
          </h5>
        </div>
        <div class="card-body">
          <div id="pdf-preview" style="width: 100%; height: 80vh; border: 1px solid #ddd; position: relative;">
            <iframe id="pdf-iframe" style="width: 100%; height: 100%; border: none;"></iframe>
            <div id="preview-overlay" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 10; align-items: center; justify-content: center;">
              <div class="spinner-border text-info" role="status">
                <span class="sr-only"></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.field-group {
  margin-bottom: 10px;
  padding: 10px;
  border: 1px solid #e0e0e0;
  border-radius: 5px;
  background-color: #f9f9f9;
}

.field-header {
  margin-bottom: 0;
  padding: 5px;
  color: #333;
  font-weight: bold;
  border-radius: 3px;
  transition: background-color 0.2s;
}

.field-header:hover {
  background-color: #e8e8e8;
}

.toggle-icon {
  display: inline-block;
  width: 15px;
  font-size: 12px;
  transition: transform 0.2s;
}

.field-controls {
  margin-top: 10px;
  padding-left: 20px;
  overflow: hidden;
  transition: max-height 0.3s ease;
  max-height: 0;
}

.field-controls.show {
  max-height: 500px;
}

.coordinate-input {
  margin-bottom: 10px;
}

.coordinate-input label {
  display: inline-block;
  width: 100px;
  font-weight: normal;
  font-size: 0.9em;
}

.coordinate-input input {
  width: 80px;
  display: inline-block;
}

.btn-adjust {
  width: 30px;
  height: 30px;
  padding: 0;
  font-size: 16px;
  line-height: 1;
}

.btn-group-sm {
  margin-top: 5px;
}

.btn-group-sm .btn {
  font-size: 0.85em;
  padding: 4px 8px;
}

.btn-group-sm .btn.active {
  background-color: #007bff;
  color: white;
  border-color: #007bff;
}
</style>

<script>
let coordinates = {};
let originalCoordinates = {};

// 初期化
document.addEventListener('DOMContentLoaded', function() {
  loadCoordinates();

  // イベントリスナー
  document.getElementById('btn-reset').addEventListener('click', resetCoordinates);
  document.getElementById('clinic-user-select').addEventListener('change', function() {
    previewPdf();
  });
});

// 座標読み込み
function loadCoordinates() {
  fetch('/prints/get-coordinates')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        coordinates = data.coordinates;
        originalCoordinates = JSON.parse(JSON.stringify(data.coordinates));
        renderFieldSettings();
        // 初回プレビュー表示
        previewPdf();
      }
    })
    .catch(error => {
      console.error('座標読み込みエラー:', error);
      alert('座標設定の読み込みに失敗しました');
    });
}

// フィールド設定UI生成
function renderFieldSettings() {
  const container = document.getElementById('field-settings');
  container.innerHTML = '';

  console.log('Total fields:', Object.keys(coordinates).length);

  Object.keys(coordinates).forEach(key => {
    const field = coordinates[key];
    const div = document.createElement('div');
    div.className = 'field-group';

    div.innerHTML = `
      <h6 class="field-header" onclick="toggleField('${key}')" style="cursor: pointer; user-select: none;">
        <span class="toggle-icon" id="toggle-${key}">▶</span> ${field.label || key}
      </h6>

      <div class="field-controls" id="controls-${key}">
        <div class="coordinate-input">
          <label>X座標:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'x', -0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'x', -0.5)"
                  ontouchend="stopLongPress()">←</button>
          <input type="number" step="0.5" value="${field.x}"
                 onchange="updateCoordinate('${key}', 'x', this.value)"
                 class="form-control form-control-sm" data-property="x">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'x', 0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'x', 0.5)"
                  ontouchend="stopLongPress()">→</button>
        </div>

        <div class="coordinate-input">
          <label>Y座標:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'y', -0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'y', -0.5)"
                  ontouchend="stopLongPress()">↑</button>
          <input type="number" step="0.5" value="${field.y}"
                 onchange="updateCoordinate('${key}', 'y', this.value)"
                 class="form-control form-control-sm" data-property="y">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'y', 0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'y', 0.5)"
                  ontouchend="stopLongPress()">↓</button>
        </div>

        <div class="coordinate-input">
          <label>フォントサイズ:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'fontSize', -0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'fontSize', -0.5)"
                  ontouchend="stopLongPress()">−</button>
          <input type="number" step="0.5" value="${field.fontSize}"
                 onchange="updateCoordinate('${key}', 'fontSize', this.value)"
                 class="form-control form-control-sm" data-property="fontSize">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'fontSize', 0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'fontSize', 0.5)"
                  ontouchend="stopLongPress()">+</button>
        </div>

        <div class="coordinate-input">
          <label>文字間隔:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'letterSpacing', -0.1)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'letterSpacing', -0.1)"
                  ontouchend="stopLongPress()">−</button>
          <input type="number" step="0.1" value="${field.letterSpacing || 0}"
                 onchange="updateCoordinate('${key}', 'letterSpacing', this.value)"
                 class="form-control form-control-sm" data-property="letterSpacing">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'letterSpacing', 0.1)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'letterSpacing', 0.1)"
                  ontouchend="stopLongPress()">+</button>
        </div>

        <div class="coordinate-input">
          <label>テキスト配置:</label>
          <div class="btn-group btn-group-sm d-flex" role="group">
            <button type="button" class="btn btn-outline-secondary flex-fill ${field.textAlign === 'left' || !field.textAlign ? 'active' : ''}"
                    onclick="updateCoordinate('${key}', 'textAlign', 'left')"
                    title="左揃え">左</button>
            <button type="button" class="btn btn-outline-secondary flex-fill ${field.textAlign === 'center' ? 'active' : ''}"
                    onclick="updateCoordinate('${key}', 'textAlign', 'center')"
                    title="中央揃え">中央</button>
            <button type="button" class="btn btn-outline-secondary flex-fill ${field.textAlign === 'right' ? 'active' : ''}"
                    onclick="updateCoordinate('${key}', 'textAlign', 'right')"
                    title="右揃え">右</button>
          </div>
        </div>

        ${field.ellipseWidth !== undefined ? `
        <div class="coordinate-input">
          <label>楕円幅:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'ellipseWidth', -0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'ellipseWidth', -0.5)"
                  ontouchend="stopLongPress()">−</button>
          <input type="number" step="0.5" value="${field.ellipseWidth || 8}"
                 onchange="updateCoordinate('${key}', 'ellipseWidth', this.value)"
                 class="form-control form-control-sm" data-property="ellipseWidth">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'ellipseWidth', 0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'ellipseWidth', 0.5)"
                  ontouchend="stopLongPress()">+</button>
        </div>
        ` : ''}

        ${field.ellipseHeight !== undefined ? `
        <div class="coordinate-input">
          <label>楕円高さ:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'ellipseHeight', -0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'ellipseHeight', -0.5)"
                  ontouchend="stopLongPress()">−</button>
          <input type="number" step="0.5" value="${field.ellipseHeight || 5}"
                 onchange="updateCoordinate('${key}', 'ellipseHeight', this.value)"
                 class="form-control form-control-sm" data-property="ellipseHeight">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'ellipseHeight', 0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'ellipseHeight', 0.5)"
                  ontouchend="stopLongPress()">+</button>
        </div>
        ` : ''}

        ${field.circleRadius !== undefined ? `
        <div class="coordinate-input">
          <label>○半径:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'circleRadius', -0.1)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'circleRadius', -0.1)"
                  ontouchend="stopLongPress()">−</button>
          <input type="number" step="0.1" value="${field.circleRadius || 1.2}"
                 onchange="updateCoordinate('${key}', 'circleRadius', this.value)"
                 class="form-control form-control-sm" data-property="circleRadius">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'circleRadius', 0.1)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'circleRadius', 0.1)"
                  ontouchend="stopLongPress()">+</button>
        </div>
        ` : ''}

        ${field.doubleCircleInnerRadius !== undefined ? `
        <div class="coordinate-input">
          <label>◎内円半径:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'doubleCircleInnerRadius', -0.05)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'doubleCircleInnerRadius', -0.05)"
                  ontouchend="stopLongPress()">−</button>
          <input type="number" step="0.05" value="${field.doubleCircleInnerRadius || 0.4}"
                 onchange="updateCoordinate('${key}', 'doubleCircleInnerRadius', this.value)"
                 class="form-control form-control-sm" data-property="doubleCircleInnerRadius">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'doubleCircleInnerRadius', 0.05)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'doubleCircleInnerRadius', 0.05)"
                  ontouchend="stopLongPress()">+</button>
        </div>
        ` : ''}
      </div>
    `;

    container.appendChild(div);
  });
}

// 座標更新
function updateCoordinate(key, property, value) {
  // テキスト配置の場合は文字列、その他は数値に変換
  if (property === 'textAlign') {
    coordinates[key][property] = value;
  } else {
    coordinates[key][property] = parseFloat(value);
  }
  
  // テキスト配置更新の場合はボタンのアクティブ状態を更新
  if (property === 'textAlign') {
    const controls = document.getElementById('controls-' + key);
    if (controls) {
      const buttons = controls.querySelectorAll('.btn-group-sm .btn');
      buttons.forEach(btn => btn.classList.remove('active'));
      
      const activeIndex = ['left', 'center', 'right'].indexOf(value);
      if (activeIndex >= 0 && buttons[activeIndex]) {
        buttons[activeIndex].classList.add('active');
      }
    }
  }
  
  autoPreview();
  autoSave();
}

// 微調整ボタン
function adjustValue(key, property, delta) {
  const currentValue = coordinates[key][property] || 0;
  
  // delta の精度に応じて丸め桁を決定
  let roundDigits = 1; // デフォルトは小数第1位
  if (Math.abs(delta) === 0.05) {
    roundDigits = 2; // ±0.05 の場合は小数第2位
  } else if (Math.abs(delta) === 0.1) {
    roundDigits = 1; // ±0.1 の場合は小数第1位
  }
  
  const multiplier = Math.pow(10, roundDigits);
  const newValue = Math.round((currentValue + delta) * multiplier) / multiplier;
  coordinates[key][property] = newValue;

  // 該当のinput要素を data-property 属性で探して更新
  const controls = document.getElementById('controls-' + key);
  if (controls) {
    const input = controls.querySelector(`input[data-property="${property}"]`);
    if (input) {
      input.value = newValue;
    }
  }

  autoPreview();
  autoSave();
}

// 自動プレビュー（デバウンス付き）
let previewTimeout = null;
function autoPreview() {
  clearTimeout(previewTimeout);
  previewTimeout = setTimeout(() => {
    previewPdf();
  }, 500); // 500ms後にプレビュー更新
}

// 自動保存（デバウンス付き）
let saveTimeout = null;
function autoSave() {
  clearTimeout(saveTimeout);
  saveTimeout = setTimeout(() => {
    saveCoordinates(true); // 自動保存フラグ
  }, 1000); // 1秒後に自動保存
}

// 長押し処理
let longPressInterval = null;
let longPressTimeout = null;

function startLongPress(key, property, delta) {
  // 即座に1回実行
  adjustValue(key, property, delta);

  // 100ms後から連続実行開始
  longPressTimeout = setTimeout(() => {
    longPressInterval = setInterval(() => {
      adjustValue(key, property, delta);
    }, 1); // 5msごとに実行
  }, 100);
}

function stopLongPress() {
  if (longPressTimeout) {
    clearTimeout(longPressTimeout);
    longPressTimeout = null;
  }
  if (longPressInterval) {
    clearInterval(longPressInterval);
    longPressInterval = null;
  }
}

// 保存
function saveCoordinates(isAuto = false) {
  const saveIndicator = document.getElementById('save-indicator');
  const saveStatus = document.getElementById('save-status');

  // 保存中インジケーター表示
  if (isAuto) {
    saveIndicator.style.display = 'inline-block';
  }

  fetch('/prints/save-coordinates', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ coordinates })
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        originalCoordinates = JSON.parse(JSON.stringify(coordinates));

        if (isAuto) {
          // 自動保存時は控えめな通知
          saveIndicator.style.display = 'none';
          saveStatus.style.display = 'block';
          setTimeout(() => {
            saveStatus.style.display = 'none';
          }, 2000);
        }
      } else {
        if (isAuto) {
          saveIndicator.style.display = 'none';
        }
        console.error('保存失敗:', data.message);
      }
    })
    .catch(error => {
      if (isAuto) {
        saveIndicator.style.display = 'none';
      }
      console.error('保存エラー:', error);
    });
}

// プレビュー
function previewPdf() {
  const iframe = document.getElementById('pdf-iframe');
  const loadingBadge = document.getElementById('preview-loading');
  const overlay = document.getElementById('preview-overlay');
  const clinicUserSelect = document.getElementById('clinic-user-select');
  const clinicUserId = clinicUserSelect ? clinicUserSelect.value : null;

  // ローディング表示
  loadingBadge.style.display = 'inline-block';
  overlay.style.display = 'flex';

  fetch('/prints/preview-pdf', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
      coordinates,
      clinic_user_id: clinicUserId
    })
  })
    .then(response => response.blob())
    .then(blob => {
      const url = URL.createObjectURL(blob);
      iframe.src = url;

      // ローディング非表示
      loadingBadge.style.display = 'none';
      overlay.style.display = 'none';
    })
    .catch(error => {
      console.error('プレビューエラー:', error);
      alert('プレビュー表示に失敗しました');

      // ローディング非表示
      loadingBadge.style.display = 'none';
      overlay.style.display = 'none';
    });
}

// フィールドの展開/折りたたみ
function toggleField(key) {
  const controls = document.getElementById('controls-' + key);
  const toggle = document.getElementById('toggle-' + key);

  if (controls.classList.contains('show')) {
    // 格納
    controls.style.maxHeight = controls.scrollHeight + 'px';
    setTimeout(() => {
      controls.style.maxHeight = '0';
    }, 10);
    controls.classList.remove('show');
    toggle.textContent = '▶';
  } else {
    // 展開
    controls.classList.add('show');
    controls.style.maxHeight = controls.scrollHeight + 'px';
    toggle.textContent = '▼';

    // アニメーション完了後にmax-heightをnoneに設定（リサイズ対応）
    controls.addEventListener('transitionend', function handler() {
      if (controls.classList.contains('show')) {
        controls.style.maxHeight = 'none';
      }
      controls.removeEventListener('transitionend', handler);
    });
  }
}

// リセット
function resetCoordinates() {
  if (!confirm('変更を破棄して元に戻しますか？')) return;

  coordinates = JSON.parse(JSON.stringify(originalCoordinates));
  renderFieldSettings();
  previewPdf();
}
</script>
</x-app-layout>
