<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('clinic-users.index')"
  />

  <a href="{{ route('clinic-users.create') }}">
    <button class="btn-ex-main btn-ex-blue mb-3">利用者新規登録</button>
  </a>

  <!-- 利用者一覧テーブル -->
  <table id="userTable" class="table table-bordered">
  <thead>
    <tr>
    <th class="text-center">ID</th>
    <th class="text-center">名前 / カナ</th>
    <th class="text-center">生年月日</th>
    <th class="text-center">住所 / TEL</th>
    <th class="text-center">登録日時</th>
    <th class="text-center" style="max-width: 350px;">編集</th>
    <th class="text-center">削除</th>
    </tr>
  </thead>
  <tbody>
    @foreach($clinicUsers as $user)
    <tr>
      <td class="fw-medium">{{ $user->id }}</td>
      <td class="fw-medium" data-order="{{ $user->full_kana }}">
      {{ $user->last_name }}{{ "\u{2000}" }}{{ $user->first_name }}<br>
      {{ $user->full_kana }}<br>
      </td>
      <td class="fw-medium">
      @if(!empty($user->birthday))
        {{ optional($user->birthday)->format('Y/n/j') }}
        （{{ $user->birthday ? \Carbon\Carbon::parse($user->birthday)->age : '' }}才）
      @endif
      </td>
      <td class="fw-medium">
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
      <td class="fw-medium" data-order="{{ $user->created_at ? $user->created_at->timestamp : 0 }}">
      {{ optional($user->created_at)->format('Y/n/j') }}{{ "\u{2000}" }}{{ optional($user->created_at)->format('H:i') }}
      </td>
      <td class="fw-medium" style="max-width: 350px;">
        <div class="d-flex flex-wrap gap-0">
          <a class="btn-ex-main btn-ex-blue btn-ex-sm m-025" href="{{ route('clinic-users.edit', ['id' => $user->id]) }}">利用者情報</a>
          <a class="btn-ex-main btn-ex-blue btn-ex-sm m-025" href="{{ route('clinic-users.insurances.index', ['id' => $user->id]) }}">保険情報</a>
          <a class="btn-ex-main btn-ex-blue btn-ex-sm m-025" href="{{ route('clinic-users.plans.index', ['id' => $user->id]) }}">計画情報</a>
          <a class="btn-ex-main btn-ex-blue btn-ex-sm m-025" href="{{ route('clinic-users.consents-acupuncture.index', ['id' => $user->id]) }}">同意医師履歴（ＨＫ）</a>
          <a class="btn-ex-main btn-ex-blue btn-ex-sm m-025" href="{{ route('clinic-users.consents-massage.index', ['id' => $user->id]) }}">同意医師履歴（ＡＭ）</a>
        </div>
      </td>
      <td class="fw-medium text-center">
      <form action="{{ route('clinic-users.delete', ['id' => $user->id]) }}" method="POST" class="delete-form d-inline">
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
      // DataTables 初期化（定義ファイル：resources/js/app.js）
      initDataTable('#userTable', {
        order: [[0, 'desc']],
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