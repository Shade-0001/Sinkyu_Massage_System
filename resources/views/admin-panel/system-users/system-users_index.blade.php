<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('system-users.index')"
  />

  <br>

  <a href="{{ route('system-users.create') }}">
  <button class="btn-ex-main btn-ex-blue">新規登録</button>
  </a>

  <br><br>

  @if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <!-- ユーザーアカウント一覧テーブル -->
  <table id="systemUsersTable" class="table table-bordered table-striped">
  <thead>
    <tr>
    <th class="text-center">ID</th>
    <th class="text-center">権限</th>
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
      <td>{{ $systemUser->is_admin ? '管理者' : '通常' }}</td>
      <td>{{ $systemUser->name }}</td>
      <td>{{ $systemUser->login_id }}</td>
      <td>{{ $systemUser->plain_password ? '●●●●●' : '―' }}</td>
      <td>
        <button
          class="btn-ex-main btn-ex-blue btn-ex-sm edit-btn"
          data-id="{{ $systemUser->id }}"
          data-name="{{ $systemUser->name }}"
        >編集</button>
      </td>
    </tr>
    @endforeach
  </tbody>
  </table>

  <!-- パスワード確認モーダル -->
  <div class="modal fade" id="passwordCheckModal" tabindex="-1" aria-labelledby="passwordCheckModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="passwordCheckModalLabel">パスワード確認</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
      <p id="modal-user-name" class="mb-3"></p>
      <div class="mb-3">
      <label for="modal-password" class="form-label fw-bold">パスワード <span class="text-danger">*</span></label>
      <input type="password" id="modal-password" class="form-control" placeholder="パスワードを入力">
      <div id="modal-password-error" class="text-danger mt-1 d-none">パスワードが正しくありません。</div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn-ex-main btn-ex-gray" data-bs-dismiss="modal">キャンセル</button>
      <button type="button" id="modal-confirm-btn" class="btn-ex-main btn-ex-blue">確認して編集</button>
    </div>
    </div>
  </div>
  </div>

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
        pageLength: 100,
        dom: 'tp',
        columnDefs: [
          { orderable: false, targets: [5] }
        ]
      });

      let targetEditUrl = '';

      // 編集ボタン押下 → モーダル表示
      $(document).on('click', '.edit-btn', function() {
        const id   = $(this).data('id');
        const name = $(this).data('name');
        targetEditUrl = '{{ url('admin-panel/system-users') }}/' + id + '/edit';
        $('#modal-user-name').text('「' + name + '」のパスワードを入力してください。');
        $('#modal-password').val('');
        $('#modal-password-error').addClass('d-none');
        $('#modal-confirm-btn').prop('disabled', false);
        const modal = new bootstrap.Modal(document.getElementById('passwordCheckModal'));
        modal.show();
        setTimeout(() => $('#modal-password').focus(), 300);
      });

      // Enterキーで確認
      $('#modal-password').on('keydown', function(e) {
        if (e.key === 'Enter') $('#modal-confirm-btn').trigger('click');
      });

      // 確認ボタン押下 → Ajax検証
      $('#modal-confirm-btn').on('click', function() {
        const password = $('#modal-password').val();
        if (!password) {
          $('#modal-password-error').text('パスワードを入力してください。').removeClass('d-none');
          return;
        }
        $('#modal-confirm-btn').prop('disabled', true);

        $.ajax({
          url: '{{ route('system-users.verify-password') }}',
          method: 'POST',
          data: {
            _token: '{{ csrf_token() }}',
            edit_url: targetEditUrl,
            password: password,
          },
          success: function(res) {
            if (res.success) {
              window.location.href = res.redirect;
            } else {
              $('#modal-password-error').text('パスワードが正しくありません。').removeClass('d-none');
              $('#modal-confirm-btn').prop('disabled', false);
              $('#modal-password').val('').focus();
            }
          },
          error: function() {
            $('#modal-password-error').text('エラーが発生しました。再度お試しください。').removeClass('d-none');
            $('#modal-confirm-btn').prop('disabled', false);
          }
        });
      });
    });
  </script>
  @endpush
</x-app-layout>
