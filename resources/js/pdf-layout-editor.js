// PDFレイアウトエディター
class PdfLayoutEditor {
  constructor() {
    this.coordinates = {};
    this.originalCoordinates = {};
    this.currentFile = null;
    this.currentTemplate = null;
    this.selectedElement = null;
    this.isDragging = false;
    this.dragOffset = { x: 0, y: 0 };
    this.zoom = 1;
    this.gridSize = 5;
    this.showGrid = true;
    this.snapToGrid = false;

    this.init();
  }

  init() {
    this.setupEventListeners();
    this.updateZoomDisplay();
    this.renderGrid();
  }

  setupEventListeners() {
    // 読み込みボタン
    document.getElementById('loadBtn').addEventListener('click', () => this.loadCoordinates());

    // 保存ボタン
    document.getElementById('saveBtn').addEventListener('click', () => this.saveCoordinates());

    // JSON出力ボタン
    document.getElementById('exportBtn').addEventListener('click', () => this.showJsonModal());

    // リセットボタン
    document.getElementById('resetBtn').addEventListener('click', () => this.resetCoordinates());

    // ズームコントロール
    document.getElementById('zoomIn').addEventListener('click', () => this.adjustZoom(0.1));
    document.getElementById('zoomOut').addEventListener('click', () => this.adjustZoom(-0.1));
    document.getElementById('zoomReset').addEventListener('click', () => this.setZoom(1));

    // グリッド設定
    document.getElementById('showGrid').addEventListener('change', (e) => {
      this.showGrid = e.target.checked;
      this.renderGrid();
    });

    document.getElementById('snapToGrid').addEventListener('change', (e) => {
      this.snapToGrid = e.target.checked;
    });

    document.getElementById('gridSize').addEventListener('change', (e) => {
      this.gridSize = parseInt(e.target.value);
      this.renderGrid();
    });

    // 検索
    document.getElementById('searchElements').addEventListener('input', (e) => {
      this.filterElements(e.target.value);
    });

    // キャンバスのマウスイベント
    const canvas = document.getElementById('canvas');
    canvas.addEventListener('mousemove', (e) => this.handleCanvasMouseMove(e));
    canvas.addEventListener('mouseup', () => this.handleMouseUp());

    // JSONモーダル
    document.getElementById('closeJsonModal').addEventListener('click', () => this.hideJsonModal());
    document.getElementById('copyJsonBtn').addEventListener('click', () => this.copyJson());
    document.getElementById('downloadJsonBtn').addEventListener('click', () => this.downloadJson());
  }

  async loadCoordinates() {
    const select = document.getElementById('pdfTypeSelect');
    const option = select.options[select.selectedIndex];

    if (!option || !option.value) {
      alert('PDFタイプを選択してください');
      return;
    }

    const coordinatesFile = option.dataset.coordinates;
    const templateFile = option.dataset.template;

    try {
      const response = await fetch('/pdf-layout-editor/coordinates', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ coordinatesFile })
      });

      if (!response.ok) throw new Error('座標データの読み込みに失敗しました');

      this.coordinates = await response.json();
      this.originalCoordinates = JSON.parse(JSON.stringify(this.coordinates));
      this.currentFile = coordinatesFile;
      this.currentTemplate = templateFile;

      this.renderElements();
      this.renderElementsList();
      this.enableButtons();
      this.updateStatus('座標データを読み込みました');
    } catch (error) {
      console.error(error);
      alert(error.message);
    }
  }

  renderElements() {
    const container = document.getElementById('elementsContainer');
    container.innerHTML = '';

    Object.entries(this.coordinates).forEach(([key, data]) => {
      const element = this.createDraggableElement(key, data);
      container.appendChild(element);
    });
  }

  createDraggableElement(key, data) {
    const element = document.createElement('div');
    element.className = 'draggable-element';
    element.dataset.key = key;
    element.dataset.type = data.type || 'text';

    // 位置設定
    const x = data.x || 0;
    const y = data.y || 0;
    element.style.left = `${x}mm`;
    element.style.top = `${y}mm`;

    // 表示内容
    const label = data.label || key;
    element.innerHTML = `
      <div class="element-label">${label}</div>
      <div class="element-info">(${x.toFixed(1)}, ${y.toFixed(1)})</div>
    `;

    // イベントリスナー
    element.addEventListener('mousedown', (e) => this.handleElementMouseDown(e, key));
    element.addEventListener('click', () => this.selectElement(key));

    return element;
  }

  handleElementMouseDown(e, key) {
    e.stopPropagation();
    this.isDragging = true;
    this.selectedElement = key;

    const element = e.currentTarget;
    const rect = element.getBoundingClientRect();
    const canvas = document.getElementById('canvas');
    const canvasRect = canvas.getBoundingClientRect();

    this.dragOffset = {
      x: (e.clientX - rect.left) / this.zoom,
      y: (e.clientY - rect.top) / this.zoom
    };

    element.classList.add('dragging');
    this.selectElement(key);
  }

  handleCanvasMouseMove(e) {
    // マウス座標表示
    const canvas = document.getElementById('canvas');
    const rect = canvas.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / this.zoom * 0.264583).toFixed(2); // px to mm
    const y = ((e.clientY - rect.top) / this.zoom * 0.264583).toFixed(2);
    document.getElementById('mousePosition').textContent = `X: ${x}mm, Y: ${y}mm`;

    // ドラッグ中
    if (this.isDragging && this.selectedElement) {
      const newX = ((e.clientX - rect.left) / this.zoom - this.dragOffset.x) * 0.264583; // px to mm
      const newY = ((e.clientY - rect.top) / this.zoom - this.dragOffset.y) * 0.264583;

      let finalX = newX;
      let finalY = newY;

      // グリッドスナップ
      if (this.snapToGrid) {
        finalX = Math.round(newX / this.gridSize) * this.gridSize;
        finalY = Math.round(newY / this.gridSize) * this.gridSize;
      }

      // 座標更新
      this.coordinates[this.selectedElement].x = parseFloat(finalX.toFixed(2));
      this.coordinates[this.selectedElement].y = parseFloat(finalY.toFixed(2));

      // 要素位置更新
      const element = document.querySelector(`[data-key="${this.selectedElement}"]`);
      element.style.left = `${finalX}mm`;
      element.style.top = `${finalY}mm`;
      element.querySelector('.element-info').textContent = 
        `(${finalX.toFixed(1)}, ${finalY.toFixed(1)})`;

      // プロパティパネル更新
      this.updatePropertiesPanel();
    }
  }

  handleMouseUp() {
    if (this.isDragging) {
      this.isDragging = false;
      const element = document.querySelector(`[data-key="${this.selectedElement}"]`);
      if (element) element.classList.remove('dragging');
      this.updateStatus('座標を変更しました（未保存）');
    }
  }

  selectElement(key) {
    // 既存選択解除
    document.querySelectorAll('.draggable-element').forEach(el => {
      el.classList.remove('selected');
    });

    // 新規選択
    const element = document.querySelector(`[data-key="${key}"]`);
    if (element) {
      element.classList.add('selected');
      this.selectedElement = key;
      this.updatePropertiesPanel();
    }
  }

  updatePropertiesPanel() {
    const panel = document.getElementById('propertiesPanel');
    
    if (!this.selectedElement || !this.coordinates[this.selectedElement]) {
      panel.innerHTML = '<p class="empty-message">要素を選択してください</p>';
      return;
    }

    const data = this.coordinates[this.selectedElement];
    
    let html = `
      <div class="property-section">
        <h3>${data.label || this.selectedElement}</h3>
        <div class="property-group">
          <label>キー:</label>
          <input type="text" value="${this.selectedElement}" disabled class="form-input">
        </div>
        <div class="property-group">
          <label>タイプ:</label>
          <input type="text" value="${data.type || 'text'}" disabled class="form-input">
        </div>
        <div class="property-group">
          <label>X座標 (mm):</label>
          <input type="number" value="${data.x}" step="0.1" 
                 onchange="pdfEditor.updateCoordinate('x', this.value)" class="form-input">
        </div>
        <div class="property-group">
          <label>Y座標 (mm):</label>
          <input type="number" value="${data.y}" step="0.1" 
                 onchange="pdfEditor.updateCoordinate('y', this.value)" class="form-input">
        </div>
    `;

    // 追加プロパティ
    if (data.fontSize !== undefined) {
      html += `
        <div class="property-group">
          <label>フォントサイズ:</label>
          <input type="number" value="${data.fontSize}" step="0.5" 
                 onchange="pdfEditor.updateProperty('fontSize', parseFloat(this.value))" class="form-input">
        </div>
      `;
    }

    if (data.textAlign !== undefined) {
      html += `
        <div class="property-group">
          <label>テキスト配置:</label>
          <select onchange="pdfEditor.updateProperty('textAlign', this.value)" class="form-select">
            <option value="left" ${data.textAlign === 'left' ? 'selected' : ''}>左</option>
            <option value="center" ${data.textAlign === 'center' ? 'selected' : ''}>中央</option>
            <option value="right" ${data.textAlign === 'right' ? 'selected' : ''}>右</option>
          </select>
        </div>
      `;
    }

    if (data.letterSpacing !== undefined) {
      html += `
        <div class="property-group">
          <label>文字間隔:</label>
          <input type="number" value="${data.letterSpacing}" step="0.1" 
                 onchange="pdfEditor.updateProperty('letterSpacing', parseFloat(this.value))" class="form-input">
        </div>
      `;
    }

    html += '</div>';
    panel.innerHTML = html;
  }

  updateCoordinate(axis, value) {
    if (!this.selectedElement) return;

    const numValue = parseFloat(value);
    this.coordinates[this.selectedElement][axis] = numValue;

    // 要素位置更新
    const element = document.querySelector(`[data-key="${this.selectedElement}"]`);
    if (axis === 'x') {
      element.style.left = `${numValue}mm`;
    } else {
      element.style.top = `${numValue}mm`;
    }

    const x = this.coordinates[this.selectedElement].x;
    const y = this.coordinates[this.selectedElement].y;
    element.querySelector('.element-info').textContent = `(${x.toFixed(1)}, ${y.toFixed(1)})`;
  }

  updateProperty(key, value) {
    if (!this.selectedElement) return;
    this.coordinates[this.selectedElement][key] = value;
  }

  renderElementsList() {
    const list = document.getElementById('elementsList');
    list.innerHTML = '';

    Object.entries(this.coordinates).forEach(([key, data]) => {
      const item = document.createElement('div');
      item.className = 'element-list-item';
      item.innerHTML = `
        <div class="element-list-label">${data.label || key}</div>
        <div class="element-list-type">${data.type || 'text'}</div>
      `;
      item.addEventListener('click', () => {
        this.selectElement(key);
        // スクロールして表示
        const element = document.querySelector(`[data-key="${key}"]`);
        if (element) element.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
      list.appendChild(item);
    });
  }

  filterElements(query) {
    const items = document.querySelectorAll('.element-list-item');
    const lowerQuery = query.toLowerCase();

    items.forEach(item => {
      const text = item.textContent.toLowerCase();
      item.style.display = text.includes(lowerQuery) ? 'block' : 'none';
    });
  }

  async saveCoordinates() {
    if (!this.currentFile) {
      alert('保存するデータがありません');
      return;
    }

    if (!confirm('座標データを保存しますか？')) return;

    try {
      const response = await fetch('/pdf-layout-editor/save', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
          coordinatesFile: this.currentFile,
          coordinates: this.coordinates
        })
      });

      if (!response.ok) throw new Error('保存に失敗しました');

      const result = await response.json();
      this.originalCoordinates = JSON.parse(JSON.stringify(this.coordinates));
      this.updateStatus('保存完了');
      alert(result.message);
    } catch (error) {
      console.error(error);
      alert(error.message);
    }
  }

  resetCoordinates() {
    if (!confirm('変更を破棄して元に戻しますか？')) return;

    this.coordinates = JSON.parse(JSON.stringify(this.originalCoordinates));
    this.renderElements();
    this.updatePropertiesPanel();
    this.updateStatus('変更をリセットしました');
  }

  adjustZoom(delta) {
    this.setZoom(this.zoom + delta);
  }

  setZoom(value) {
    this.zoom = Math.max(0.25, Math.min(3, value));
    const canvas = document.getElementById('canvas');
    canvas.style.transform = `scale(${this.zoom})`;
    canvas.dataset.zoom = this.zoom;
    this.updateZoomDisplay();
    this.renderGrid();
  }

  updateZoomDisplay() {
    document.getElementById('zoomLevel').textContent = `${Math.round(this.zoom * 100)}%`;
  }

  renderGrid() {
    const canvas = document.getElementById('canvas');
    
    if (!this.showGrid) {
      canvas.style.backgroundImage = 'none';
      return;
    }

    const gridPx = this.gridSize / 0.264583; // mm to px
    canvas.style.backgroundSize = `${gridPx}px ${gridPx}px`;
    canvas.style.backgroundImage = `
      linear-gradient(to right, #e0e0e0 1px, transparent 1px),
      linear-gradient(to bottom, #e0e0e0 1px, transparent 1px)
    `;
  }

  showJsonModal() {
    const json = JSON.stringify(this.coordinates, null, 2);
    document.getElementById('jsonOutput').value = json;
    document.getElementById('jsonModal').style.display = 'flex';
  }

  hideJsonModal() {
    document.getElementById('jsonModal').style.display = 'none';
  }

  copyJson() {
    const textarea = document.getElementById('jsonOutput');
    textarea.select();
    document.execCommand('copy');
    alert('クリップボードにコピーしました');
  }

  downloadJson() {
    const json = JSON.stringify(this.coordinates, null, 2);
    const blob = new Blob([json], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = this.currentFile || 'coordinates.json';
    a.click();
    URL.revokeObjectURL(url);
  }

  enableButtons() {
    document.getElementById('saveBtn').disabled = false;
    document.getElementById('exportBtn').disabled = false;
    document.getElementById('resetBtn').disabled = false;
  }

  updateStatus(message) {
    document.getElementById('statusMessage').textContent = message;
  }
}

// 初期化
let pdfEditor;
document.addEventListener('DOMContentLoaded', () => {
  pdfEditor = new PdfLayoutEditor();
});
