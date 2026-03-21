<x-app-layout>
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('system-users.index')"
  />

  <br>

  <a href="{{ route('system-users.create') }}">
  <button class="btn-custom btn-custom-blue">新規登録</button>
  </a>

  <br><br>

  @if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <!-- システム管理ユーザー一覧テーブル -->
  <table id="systemUsersTable" class="table table-bordered table-striped">
  <thead>
    <tr>
    <th class="text-center">ID</th>
    <th class="text-center">名前</th>
    <th class="text-center">ログインID</th>
    <th class="text-center">パスワード</th>
    <th class="text-center">編集</th>
    </tr>
  </thead>
  <tbody>
    @foreach($systemUsers as $systemUser)
    <tr>
      <td>{{ $systemUser->id }}</td>
      <td>{{ $systemUser->name }}</td>
      <td>{{ $systemUser->login_id }}</td>
      <td>{{ $systemUser->plain_password ? '●●●●●' : '―' }}</td>
      <td>
        <a class="btn-custom btn-custom-blue btn-custom-sm" href="{{ route('system-users.edit', ['id' => $systemUser->id]) }}">編集</a>
      </td>
    </tr>
    @endforeach
  </tbody>
  </table>

  @push('scripts')
  <script>
    $(document).ready(function() {
      $('#systemUsersTable').DataTable({
        language: {
          url: '{{ asset('js/dataTables-ja.json') }}',
          paginate: {
          previous: '◂ 前へ',
          next: '次へ ▸'
          }
        },
        order: [[0, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        columnDefs: [
          { orderable: false, targets: [4] }
        ]
      });
    });
  </script>
  @endpush
</x-app-layout>
