<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('notices.index')"
  />

  <br>

  <a href="{{ route('notices.create') }}">
  <button class="btn-ex-main btn-ex-blue">新規登録</button>
  </a>

  <br><br>

  @if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <!-- お知らせ一覧テーブル -->
  <table id="noticesTable" class="table table-bordered table-striped">
  <thead>
    <tr>
    <th class="text-center">ID</th>
    <th class="text-center text-nowrap">作成日</th>
    <th class="text-center">タイトル</th>
    <th class="text-center">内容</th>
    <th class="text-center">編集</th>
    <th class="text-center">削除</th>
    </tr>
  </thead>
  <tbody>
    @foreach($notices as $notice)
    <tr>
      <td>{{ $notice->id }}</td>
      <td data-order="{{ $notice->created_at ? $notice->created_at->timestamp : 0 }}">
      {{ optional($notice->created_at)->format('Y/n/j') }}
      </td>
      <td>{{ $notice->title }}</td>
      <td>{{ $notice->content }}</td>
      <td>
        <a class="btn-ex-main btn-ex-blue btn-ex-sm" href="{{ route('notices.edit', ['id' => $notice->id]) }}">編集</a>
        <a class="btn-ex-main btn-ex-blue btn-ex-sm" href="{{ route('notices.duplicate', ['id' => $notice->id]) }}">複製</a>
      </td>
      <td>
      <form action="{{ route('notices.delete', ['id' => $notice->id]) }}" method="POST" class="delete-form d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="delete-btn btn-ex-main btn-ex-red btn-ex-sm">削除</button>
      </form>
      </td>
    </tr>
    @endforeach
  </tbody>
  </table>

  @push('scripts')
  <script>
    $(document).ready(function() {
      $('#noticesTable').DataTable({
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
          { orderable: false, targets: [4, 5] }
        ]
      });

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
