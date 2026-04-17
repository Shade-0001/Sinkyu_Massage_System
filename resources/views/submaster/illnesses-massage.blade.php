<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('submaster.illnesses-massage')"
  />

  <table id="illnessesMassageTable" class="table table-bordered">
    <thead>
      <tr>
        <th class="text-center" style="width: 10%;">ID</th>
        <th class="text-center" style="width: 50%;">名称</th>
        <th class="text-center" style="max-width: 140px;">操作</th>
      </tr>
    </thead>
    <tbody>
      <!-- 新規登録行 -->
      <tr class="new-entry-row">
        <td class="fw-medium">新規登録</td>
        <td class="fw-medium">
          <input type="text" name="illness_name" value="" required class="form-control" form="form-new">
        </td>
        <td class="text-center fw-medium">
          <form action="{{ route('submaster.illnesses-massage.store') }}" method="POST" id="form-new" class="d-inline">
            @csrf
            <button type="submit" class="btn-ex-main btn-ex-blue btn-ex-sm">新規登録</button>
          </form>
        </td>
      </tr>

      <!-- 既存データ行 -->
      @foreach($items as $item)
      <tr>
        <td class="fw-medium">{{ $item->id }}</td>
        <td class="fw-medium">
          <form action="{{ route('submaster.illnesses-massage.update', $item->id) }}" method="POST" id="form-{{ $item->id }}">
            @csrf
            <input type="text" name="illness_name" value="{{ $item->illness_name }}" required class="form-control">
          </form>
        </td>
        <td class="text-center fw-medium">
          <button type="submit" form="form-{{ $item->id }}" class="btn-ex-main btn-ex-blue btn-ex-sm">更新</button>
          <form action="{{ route('submaster.illnesses-massage.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('本当に削除する？');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-ex-main btn-ex-red btn-ex-sm">削除</button>
          </form>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  @push('scripts')
  <script>
    $(document).ready(function() {
      var newEntryRow = $('.new-entry-row').detach();
      
      // DataTables 初期化（定義ファイル：resources/js/app.js）
      var table = initDataTable('#illnessesMassageTable', {
        order: [[0, 'desc']],
        columnDefs: [
          { orderable: false, targets: [2] }
        ],
        drawCallback: function() {
          $(this.api().table().body()).prepend(newEntryRow);
        }
      });
      
      $(table.table().body()).prepend(newEntryRow);
    });
  </script>
  @endpush
</x-app-layout>
