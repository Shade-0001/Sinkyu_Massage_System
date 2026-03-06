<x-app-layout>
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('master.self-fees.index')"
  />

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <table id="selfPayFeesTable" class="table table-bordered table-striped">
    <thead>
      <tr>
        <th class="text-center" style="width: 10%;">ID</th>
        <th class="text-center" style="width: 50%;">名称</th>
        <th class="text-center" style="width: 15%;">金額（円）</th>
        <th class="text-center" style="width: 25%;">操作</th>
      </tr>
    </thead>
    <tbody>
      <!-- 新規登録行 -->
      <tr class="new-entry-row">
        <td>新規登録</td>
        <td>
          <input type="text" name="self_fee_name" value="" required class="form-control" form="form-new">
        </td>
        <td>
          <input type="number" name="amount" value="" min="0" step="1" required class="form-control" form="form-new">
        </td>
        <td class="text-center">
          <form action="{{ route('master.self-fees.store') }}" method="POST" id="form-new" class="d-inline">
            @csrf
            <button type="submit">新規登録</button>
          </form>
        </td>
      </tr>

      <!-- 既存データ行 -->
      @foreach($items as $item)
      <tr>
        <td>{{ $item->id }}</td>
        <td>
          <form action="{{ route('master.self-fees.update', $item->id) }}" method="POST" id="form-{{ $item->id }}">
            @csrf
            <input type="text" name="self_fee_name" value="{{ $item->self_fee_name }}" required class="form-control">
        </td>
        <td>
            <input type="number" name="amount" value="{{ $item->amount }}" min="0" step="1" required class="form-control">
          </form>
        </td>
        <td class="text-center">
          <button type="submit" form="form-{{ $item->id }}">更新</button>
          <form action="{{ route('master.self-fees.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('本当に削除する？');">
            @csrf
            @method('DELETE')
            <button type="submit">削除</button>
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
      
      var table = $('#selfPayFeesTable').DataTable({
        language: {
          url: '{{ asset('js/dataTables-ja.json') }}',
          paginate: {
            previous: '◂ 前へ',
            next: '次へ ▸'
          }
        },
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        columnDefs: [
          { orderable: false, targets: [3] }
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
