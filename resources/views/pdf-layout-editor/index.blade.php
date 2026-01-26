<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PDFレイアウトエディター</title>
  @vite(['resources/css/pdf-layout-editor.css', 'resources/js/pdf-layout-editor.js'])
</head>
<body>
  <div id="app" class="layout-editor">
    <!-- ヘッダー -->
    <header class="editor-header">
      <h1>PDFレイアウトエディター</h1>
      <div class="header-controls">
        <select id="pdfTypeSelect" class="form-select">
          <option value="">PDFタイプを選択...</option>
          @foreach($pdfTypes as $key => $type)
            <option value="{{ $key }}" 
                    data-coordinates="{{ $type['coordinatesFile'] }}"
                    data-template="{{ $type['templateFile'] ?? '' }}">
              {{ $type['name'] ?? $key }}
            </option>
          @endforeach
        </select>
        <button id="loadBtn" class="btn btn-primary">読み込み</button>
        <button id="saveBtn" class="btn btn-success" disabled>保存</button>
        <button id="exportBtn" class="btn btn-info" disabled>JSON出力</button>
        <button id="resetBtn" class="btn btn-warning" disabled>リセット</button>
      </div>
    </header>

    <!-- メインコンテンツ -->
    <div class="editor-main">
      <!-- サイドバー：要素リスト -->
      <aside class="editor-sidebar">
        <h2>描画要素一覧</h2>
        <div class="search-box">
          <input type="text" id="searchElements" placeholder="要素を検索..." class="form-input">
        </div>
        <div id="elementsList" class="elements-list">
          <p class="empty-message">PDFタイプを読み込んでください</p>
        </div>
      </aside>

      <!-- キャンバスエリア -->
      <main class="editor-canvas-wrapper">
        <div class="canvas-toolbar">
          <div class="zoom-controls">
            <button id="zoomOut" class="btn-icon" title="縮小">−</button>
            <span id="zoomLevel" class="zoom-level">100%</span>
            <button id="zoomIn" class="btn-icon" title="拡大">+</button>
            <button id="zoomReset" class="btn-icon" title="リセット">⊙</button>
          </div>
          <div class="grid-controls">
            <label>
              <input type="checkbox" id="showGrid" checked> グリッド表示
            </label>
            <label>
              <input type="checkbox" id="snapToGrid"> グリッドにスナップ
            </label>
            <label>
              グリッド間隔:
              <input type="number" id="gridSize" value="5" min="1" max="20" class="grid-size-input">
            </label>
          </div>
        </div>
        
        <div id="canvasContainer" class="canvas-container">
          <div id="canvas" class="canvas" data-zoom="1">
            <!-- 背景PDF画像（オプション） -->
            <div id="pdfBackground" class="pdf-background"></div>
            
            <!-- ドラッグ可能な要素がここに追加される -->
            <div id="elementsContainer" class="elements-container"></div>
          </div>
        </div>
      </main>

      <!-- プロパティパネル -->
      <aside class="editor-properties">
        <h2>プロパティ</h2>
        <div id="propertiesPanel" class="properties-panel">
          <p class="empty-message">要素を選択してください</p>
        </div>
      </aside>
    </div>

    <!-- ステータスバー -->
    <footer class="editor-footer">
      <div id="statusBar" class="status-bar">
        <span id="statusMessage">準備完了</span>
        <span id="mousePosition" class="mouse-pos">X: 0, Y: 0</span>
      </div>
    </footer>

    <!-- JSONモーダル -->
    <div id="jsonModal" class="modal" style="display: none;">
      <div class="modal-content">
        <div class="modal-header">
          <h3>JSON出力</h3>
          <button class="modal-close" id="closeJsonModal">&times;</button>
        </div>
        <div class="modal-body">
          <textarea id="jsonOutput" class="json-output" readonly></textarea>
        </div>
        <div class="modal-footer">
          <button id="copyJsonBtn" class="btn btn-primary">クリップボードにコピー</button>
          <button id="downloadJsonBtn" class="btn btn-success">ダウンロード</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
