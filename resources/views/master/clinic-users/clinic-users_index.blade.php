<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('clinic-users.index')"
  />

  <br>

  <a href="{{ route('clinic-users.create') }}">
  <button class="btn-ex btn-ex-blue">利用者新規登録</button>
  </a>

  <br><br>

  <!-- 利用者一覧テーブル -->
  <table id="userTable" class="table table-bordered table-striped">
  <thead>
    <tr>
    <th class="text-center">ID</th>
    <th class="text-center">名前 / カナ</th>
    <th class="text-center">生年月日</th>
    <th class="text-center">住所 / TEL</th>
    <th class="text-center text-nowrap">データ登録日</th>
    <th class="text-center">編集</th>
    <th class="text-center">削除</th>
    </tr>
  </thead>
  <tbody>
    @foreach($clinicUsers as $user)
    <tr>
      <td>{{ $user->id }}</td>
      <td data-order="{{ $user->full_kana }}">
      {{ $user->last_name }}{{ "\u{2000}" }}{{ $user->first_name }}<br>
      {{ $user->full_kana }}<br>
      </td>
      <td>
      @if(!empty($user->birthday))
        {{ optional($user->birthday)->format('Y/n/j') }}
        （{{ $user->birthday ? \Carbon\Carbon::parse($user->birthday)->age : '' }}才）
      @endif
      </td>
      <td>
      @if(!empty($user->postal_code))
        〒{{ $user->postal_code }}<br>
      @endif
      {{ $user->address_1 }} {{ $user->address_2 }} {{ $user->address_3 }}<br>
      @if(!empty($user->phone))
        {{ $user->phone }}<br>
      @elseif(!empty($user->cell_phone))
        {{ $user->cell_phone }}<br>
      @endif
      </td>
      <td data-order="{{ $user->created_at ? $user->created_at->timestamp : 0 }}">
      {{ optional($user->created_at)->format('Y/n/j') }}{{ "\u{2000}" }}{{ optional($user->created_at)->format('H:i') }}
      </td>
      <td>
        <div class="d-flex flex-wrap gap-0" style="max-width: 350px;">
          <a class="btn-ex btn-ex-blue btn-ex-sm m-025" href="{{ route('clinic-users.edit', ['id' => $user->id]) }}">利用者情報</a>
          <a class="btn-ex btn-ex-blue btn-ex-sm m-025" href="{{ route('clinic-users.insurances.index', ['id' => $user->id]) }}">保険情報</a>
          <a class="btn-ex btn-ex-blue btn-ex-sm m-025" href="{{ route('clinic-users.plans.index', ['id' => $user->id]) }}">計画情報</a>
          <a class="btn-ex btn-ex-blue btn-ex-sm m-025" href="{{ route('clinic-users.consents-acupuncture.index', ['id' => $user->id]) }}">同意医師履歴（ＨＫ）</a>
          <a class="btn-ex btn-ex-blue btn-ex-sm m-025" href="{{ route('clinic-users.consents-massage.index', ['id' => $user->id]) }}">同意医師履歴（ＡＭ）</a>
        </div>
      </td>
      <td>
      <form action="{{ route('clinic-users.delete', ['id' => $user->id]) }}" method="POST" class="delete-form d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="delete-btn btn-ex btn-ex-red btn-ex-sm">削除</button>
      </form>
      </td>
    </tr>
    @endforeach
  </tbody>
  </table>

  @push('scripts')
  <script>
    $(document).ready(function() {
      $('#userTable').DataTable({
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
          { orderable: false, targets: [5, 6] }
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