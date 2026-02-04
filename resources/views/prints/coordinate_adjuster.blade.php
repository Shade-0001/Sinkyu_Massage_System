{{--
  PDFレイアウト調整ツール

  【アーキテクチャ概要】
  このツールは複数のPDFタイプ（療養費支給申請書、施術料金領収書など）の座標調整を
  一元的に管理するためのシステム。

  【ファイル構成】
  storage/app/config/
    - pdf_types.json              : PDFタイプのマスター定義（名前、座標ファイル、テンプレート、サービスクラス）
    - *_coordinates.json          : 各PDFタイプの座標設定

  storage/app/templates/
    - acupuncture_and_massage/    : 鍼灸・マッサージ関連のPDFテンプレート
    - others_1/                   : その他のPDFテンプレート1
    - others_2/                   : その他のPDFテンプレート2

  app/Http/Controllers/
    - PrintsController.php        : PDFタイプ設定を一元管理し、座標取得/保存/プレビューを処理

  app/Services/Print/
    - *PdfService.php             : 各PDFタイプのPDF生成サービス

  public/js/
    - coordinate-adjuster_*.js    : 座標調整ツールのJS（カテゴリ、データ、フィールド、UI、コア）

  【新しいPDFタイプを追加する手順】
  1. storage/app/config/pdf_types.json に新しいタイプのエントリを追加
  2. storage/app/config/ に座標設定JSONファイルを作成
  3. storage/app/templates/ の適切なサブフォルダにPDFテンプレートを配置
  4. app/Services/Print/ にPDF生成サービスクラスを作成
  5. （必要に応じて）coordinate-adjuster_ui.js の shouldIncludeField 関数にフィルタリング条件を追加

  【既存PDFタイプに新しいフィールドを追加する手順】【重要】
  1. public/js/coordinate-adjuster_fields.js にフィールド定義を追加
  2. storage/app/config/*_coordinates.json に座標情報を追加
  3. public/js/coordinate-adjuster_categories.js にカテゴリ定義を追加 ← 【必須：忘れるとUIに表示されない】
  4. app/Services/Print/Traits/*FormFieldsTrait.php に描画ロジックを追加
--}}
<x-app-layout>
<div class="container-fluid mt-4">
  <h4 class="mb-4">PDFレイアウト調整ツール｜{{ $pdfTypeName }}</h4>

  <div class="row">
    <!-- 左側: 設定パネル -->
    <div class="col-md-3">
      <div class="card">
        <div class="card-header bg-secondary text-white">
          <h5 class="mb-0">フィールド設定</h5>
        </div>
        <div class="card-body p-0" style="max-height: 80vh; overflow-y: auto;">
          <!-- PDFタイプ選択（pdf_types.jsonから動的生成） -->
          <div class="p-3 border-bottom bg-light">
            <label for="pdf-type-select" class="form-label mb-2">PDFタイプ</label>
            <select id="pdf-type-select" class="form-control">
              @foreach($pdfTypes as $typeKey => $typeConfig)
                <option value="{{ $typeKey }}" {{ $pdfType === $typeKey ? 'selected' : '' }}>{{ $typeConfig['name'] }}</option>
              @endforeach
            </select>
          </div>

          <!-- 利用者選択 -->
          <div class="p-3 border-bottom bg-light">
            <label for="clinic-user-select" class="form-label mb-2">プレビュー利用者</label>
            <select id="clinic-user-select" class="form-control">
              @foreach($clinicUsers as $user)
                <option value="{{ $user->id }}" {{ ($selectedClinicUserId && $selectedClinicUserId == $user->id) ? 'selected' : '' }}>
                  {{ $user->last_name }} {{ $user->first_name }} ({{ $user->last_kana }} {{ $user->first_kana }})
                </option>
              @endforeach
            </select>
          </div>

          <!-- 年月選択 -->
          <div class="p-3 border-bottom bg-light">
            <label for="year-month-select" class="form-label mb-2">プレビュー年月</label>
            <select id="year-month-select" class="form-control">
              @foreach($yearMonths as $ym)
                <option value="{{ $ym['value'] }}" {{ $ym['value'] === $currentYearMonth ? 'selected' : '' }}>
                  {{ $ym['label'] }}
                </option>
              @endforeach
            </select>
          </div>

          <!-- サンプル表示オプション -->
          <div class="p-3 border-bottom" style="background-color: #f8f9fa;">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="show-sample-data">
              <label class="form-check-label" for="show-sample-data">
                サンプルデータ表示
              </label>
            </div>
          </div>

          <div id="field-settings">
            <!-- JavaScriptで動的に生成 -->
          </div>

          <div class="mt-4">
            <div id="save-status" class="alert alert-success" style="display: none; padding: 8px; margin-bottom: 10px; font-size: 0.9em;">
              自動保存済み
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 右側: PDFプレビュー -->
    <div class="col-md-9">
      <div class="card">
        <div class="card-header bg-secondary text-white">
          <h5 class="mb-0">
            PDFプレビュー
            <span id="preview-loading" class="badge badge-light ml-2" style="display: none;">更新中･･･</span>
            <span id="save-indicator" class="badge badge-success ml-2" style="display: none;">保存中･･･</span>
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

<link rel="stylesheet" href="{{ asset('css/coordinate_adjuster.css') }}">

<script>
// PHP変数をJavaScriptに渡す
window.coordinateAdjusterData = {
  currentPdfType: '{{ $pdfType }}',
  pdfTypes: @json($pdfTypes),
  masterData: {
    genders: @json($masterData['genders']),
    relationships: @json($masterData['relationships']),
    insurance_types_1: @json($masterData['insurance_types_1']),
    insurance_types_3: @json($masterData['insurance_types_3'])
  },
  treatmentFees: @json($treatmentFees ?? null),
  csrfToken: '{{ csrf_token() }}'
};
</script>
<!-- 座標調整ツール：分割されたJSファイルを読み込み -->
<script src="{{ asset('js/coordinate-adjuster_categories.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/coordinate-adjuster_data.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/coordinate-adjuster_fields.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/coordinate-adjuster_ui.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/coordinate-adjuster_core.js') }}?v={{ time() }}"></script>
</x-app-layout>
