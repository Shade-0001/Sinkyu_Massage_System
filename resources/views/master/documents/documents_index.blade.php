<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('master.documents.index')"
  />

  <!-- 新規登録ボタン -->
  <div style="margin-bottom: 15px;">
    <button type="button" id="newDocumentBtn">
      新規登録
    </button>
  </div>

  <table id="documentsTable" class="table table-bordered">
    <thead>
      <tr>
        <th style="width: 12%;">文書カテゴリ</th>
        <th style="width: 35%;">文面名称</th>
        <th style="width: 13%;">登録日時</th>
        <th style="width: 10%;">プレビュー</th>
        <th style="width: 10%;">編集</th>
        <th style="width: 10%;">複製</th>
        <th style="width: 10%;">削除</th>
      </tr>
    </thead>
    <tbody>
      @foreach($items as $item)
      <tr>
        <td>{{ $item->document_category }}</td>
        <td>{{ $item->document_name }}</td>
        <td>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') : '' }}</td>
        <td style="text-align: center;">
          <button type="button" class="preview-btn" data-id="{{ $item->id }}">プレビュー</button>
        </td>
        <td style="text-align: center;">
          <button type="button" class="edit-btn" data-id="{{ $item->id }}">編集</button>
        </td>
        <td style="text-align: center;">
          <button type="button" class="duplicate-btn" data-id="{{ $item->id }}">複製</button>
        </td>
        <td style="text-align: center;">
          <button type="button" class="delete-btn" data-id="{{ $item->id }}">削除</button>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  @push('scripts')
  <script>
    $(document).ready(function() {
      // DataTables 初期化（定義ファイル：resources/js/app.js）
      var table = initDataTable('#documentsTable', {
        order: [[2, 'desc']],
        columnDefs: [
          { orderable: false, targets: [3, 4, 5, 6] }
        ]
      });

      // 新規登録ボタン
      $('#newDocumentBtn').on('click', function() {
        window.location.href = '{{ route('master.documents.create') }}';
      });

      // 編集ボタン
      $('.edit-btn').on('click', function() {
        var id = $(this).data('id');
        window.location.href = '/master/documents/' + id + '/edit';
      });

      // 複製ボタン
      $('.duplicate-btn').on('click', function() {
        var id = $(this).data('id');
        window.location.href = '/master/documents/' + id + '/duplicate';
      });

      // プレビューボタン
      $('.preview-btn').on('click', function() {
        var id = $(this).data('id');
        const url = '/master/documents/' + id + '/preview';
        const windowName = 'DocumentPreviewPDF_' + new Date().getTime();
        const w = Math.round(screen.width * 0.5);
        const h = Math.round(screen.height * 0.90);
        const left = Math.round((screen.width - w) / 2);
        const top = Math.round((screen.height - h) / 2);
        const windowFeatures = `popup=yes,width=${w},height=${h},left=${left},top=${top},menubar=yes,toolbar=yes,location=yes,status=yes,scrollbars=yes,resizable=yes`;
        window.open(url, windowName, windowFeatures);
      });

      // 削除ボタン
      $('.delete-btn').on('click', function() {
        var id = $(this).data('id');
        if(confirm('一度削除したデータは元に戻せない。\n削除してもよい？')) {
          var form = $('<form>', {
            'method': 'POST',
            'action': '/master/documents/' + id
          });
          var csrfToken = $('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
          });
          var methodField = $('<input>', {
            'type': 'hidden',
            'name': '_method',
            'value': 'DELETE'
          });
          form.append(csrfToken).append(methodField);
          $('body').append(form);
          form.submit();
        }
      });
    });
  </script>
  @endpush
</x-app-layout>
