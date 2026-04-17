<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('caremanagers.index')"
  />

  <a href="{{ route('caremanagers.create') }}">
    <button class="btn-ex-main btn-ex-blue">ケアマネ新規登録</button>
  </a>

  <!-- ケアマネ一覧テーブル -->
  <table id="careManagerTable" class="table table-bordered">
    <thead>
      <tr>
        <th class="text-center">名前 / カナ</th>
        <th class="text-center">サービス事業者名</th>
        <th class="text-center">住所 / TEL</th>
        <th class="text-center">データ登録日</th>
        <th class="text-center">操作</th>
      </tr>
    </thead>
    <tbody>
      @foreach($careManagers as $careManager)
      <tr>
        <td class="fw-medium">
          {{ $careManager->last_name }}{{ "\u{2000}" }}{{ $careManager->first_name }}<br>
          {{ $careManager->last_name_kana }}{{ "\u{2000}" }}{{ $careManager->first_name_kana }}
        </td>
        <td class="fw-medium">
          {{ $careManager->service_provider_name ?? '-' }}
        </td>
        <td class="fw-medium">
          @if(!empty($careManager->postal_code))
            〒{{ $careManager->postal_code }}<br>
          @endif
          {{ $careManager->address_1 }} {{ $careManager->address_2 }} {{ $careManager->address_3 }}
          @if(!empty($careManager->phone))
            <br>TEL: {{ $careManager->phone }}
          @endif
        </td>
        <td data-order="{{ $careManager->created_at ? strtotime($careManager->created_at) : 0 }}">
          {{ $careManager->created_at ? \Carbon\Carbon::parse($careManager->created_at)->format('Y/n/j') : '' }}<br>
          {{ $careManager->created_at ? \Carbon\Carbon::parse($careManager->created_at)->format('H:i') : '' }}
        </td>
        <td class="text-center fw-medium">
        <div class="d-flex flex-wrap justify-content-center gap-1">
          <a class="btn-ex-main btn-ex-blue btn-ex-sm" href="{{ route('caremanagers.edit', $careManager->id) }}">編集</a>
          <form action="{{ route('caremanagers.delete', ['id' => $careManager->id]) }}" method="POST" class="delete-form d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="delete-btn btn-ex-main btn-ex-red btn-ex-sm">削除</button>
          </form>
        </div>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  @if($careManagers->isEmpty())
  <p class="text-center">データがありません</p>
  @endif

  @push('scripts')
  <script>
    $(document).ready(function() {
      // テーブルの存在確認
      if ($('#careManagerTable').length) {
        // DataTablesが既に初期化されている場合は破棄
        if ($.fn.DataTable.isDataTable('#careManagerTable')) {
          $('#careManagerTable').DataTable().destroy();
        }
        
        // DataTables 初期化（定義ファイル：resources/js/app.js）
        initDataTable('#careManagerTable', {
          order: [[3, 'desc']],
          columnDefs: [
            { orderable: false, targets: [4] }
          ]
        });
      }

      // 削除確認
      $(document).on('submit', '.delete-form', function(e) {
        e.preventDefault();
        if (confirm('一度削除したデータは元に戻せません。\n削除してもよろしいですか？')) {
          this.submit();
        }
      });
    });
  </script>
  @endpush
</x-app-layout>
