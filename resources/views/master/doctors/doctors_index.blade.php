<x-app-layout>
  @section('title', '医師')
  <x-page-header
    title="医師"
    :breadcrumbs="App\Support\Breadcrumbs::generate('doctors.index')"
  />

  <a href="{{ route('doctors.create') }}">
  <button>医師新規登録</button>
  </a>

  <br><br>

  <!-- 医師一覧テーブル -->
  <table id="doctorTable" class="table table-bordered table-striped">
  <thead>
    <tr>
    <th>名前 [編集] / カナ</th>
    <th>医療機関名</th>
    <th>住所 / TEL</th>
    <th>データ登録日</th>
    <th>複製</th>
    <th>削除</th>
    </tr>
  </thead>
  <tbody>
    @foreach($doctors as $doctor)
    <tr>
      <td>
      <a href="{{ route('doctors.edit', $doctor->id) }}">{{ $doctor->last_name }}{{ "\u{2000}" }}{{ $doctor->first_name }}［編集］</a><br>
      {{ $doctor->last_name_kana }}{{ "\u{2000}" }}{{ $doctor->first_name_kana }}
      </td>
      <td>
      {{ $doctor->medical_institution_name }}
      </td>
      <td>
      @if(!empty($doctor->postal_code))
        〒{{ $doctor->postal_code }}<br>
      @endif
      {{ $doctor->address_1 }} {{ $doctor->address_2 }} {{ $doctor->address_3 }}
      @if(!empty($doctor->phone))
        <br>TEL: {{ $doctor->phone }}
      @endif
      </td>
      <td data-order="{{ $doctor->created_at ? strtotime($doctor->created_at) : 0 }}">
      {{ $doctor->created_at ? \Carbon\Carbon::parse($doctor->created_at)->format('Y/n/j') : '' }}<br>
      {{ $doctor->created_at ? \Carbon\Carbon::parse($doctor->created_at)->format('H:i') : '' }}
      </td>
      <td>
      <a href="{{ route('doctors.duplicate', $doctor->id) }}">複製</a>
      </td>
      <td>
      <form action="{{ route('doctors.delete', ['id' => $doctor->id]) }}" method="POST" class="delete-form d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="delete-btn btn btn-link p-0">削除</button>
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
      initDataTable('#doctorTable', {
        order: [[3, 'desc']],
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
