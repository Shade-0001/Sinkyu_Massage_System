<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('therapists.index')"
  />

  <br>

  <a href="{{ route('therapists.create') }}">
  <button>施術者新規登録</button>
  </a>

  <br><br>

  <!-- 施術者一覧テーブル -->
  <table id="therapistTable" class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>施術者名 [編集] / カナ</th>
        <th>資格・免許番号・交付日</th>
        <th>住所 / TEL</th>
        <th>データ登録日</th>
        <th>削除</th>
      </tr>
    </thead>
    <tbody>
      @foreach($therapists as $therapist)
      <tr>
        <td>
          <a href="{{ route('therapists.edit', $therapist->id) }}">{{ $therapist->last_name }}{{ "\u{2000}" }}{{ $therapist->first_name }}［編集］</a><br>
          {{ $therapist->last_name_kana }}{{ "\u{2000}" }}{{ $therapist->first_name_kana }}
        </td>
        <td>
          @if(!empty($therapist->license_hari_code_number) || !empty($therapist->license_hari_issued_date))
            <strong>はり:</strong>
            {{ $therapist->license_hari_code_number }}
            @if(!empty($therapist->license_hari_issued_date))
              （{{ \Carbon\Carbon::parse($therapist->license_hari_issued_date)->format('Y/n/j') }}）
            @endif
            <br>
          @endif
          @if(!empty($therapist->license_kyu_code_number) || !empty($therapist->license_kyu_issued_date))
            <strong>きゅう:</strong>
            {{ $therapist->license_kyu_code_number }}
            @if(!empty($therapist->license_kyu_issued_date))
              （{{ \Carbon\Carbon::parse($therapist->license_kyu_issued_date)->format('Y/n/j') }}）
            @endif
            <br>
          @endif
          @if(!empty($therapist->license_massage_code_number) || !empty($therapist->license_massage_issued_date))
            <strong>あん摩・マッサージ:</strong>
            {{ $therapist->license_massage_code_number }}
            @if(!empty($therapist->license_massage_issued_date))
              （{{ \Carbon\Carbon::parse($therapist->license_massage_issued_date)->format('Y/n/j') }}）
            @endif
          @endif
          @if(empty($therapist->license_hari_code_number) && empty($therapist->license_kyu_code_number) && empty($therapist->license_massage_code_number))
            -
          @endif
        </td>
        <td>
          @if(!empty($therapist->postal_code))
            〒{{ $therapist->postal_code }}<br>
          @endif
          {{ $therapist->address_1 }} {{ $therapist->address_2 }} {{ $therapist->address_3 }}
          @if(!empty($therapist->phone))
            <br>TEL: {{ $therapist->phone }}
          @endif
        </td>
        <td data-order="{{ $therapist->created_at ? strtotime($therapist->created_at) : 0 }}">
          {{ $therapist->created_at ? \Carbon\Carbon::parse($therapist->created_at)->format('Y/n/j') : '' }}<br>
          {{ $therapist->created_at ? \Carbon\Carbon::parse($therapist->created_at)->format('H:i') : '' }}
        </td>
        <td>
          <form action="{{ route('therapists.delete', ['id' => $therapist->id]) }}" method="POST" class="delete-form d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="delete-btn btn btn-link p-0">削除</button>
          </form>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  @if($therapists->isEmpty())
  <p class="text-center">データがありません</p>
  @endif

  @push('scripts')
  <script>
    $(document).ready(function() {
      // テーブルの存在確認
      if ($('#therapistTable').length) {
        // DataTablesが既に初期化されている場合は破棄
        if ($.fn.DataTable.isDataTable('#therapistTable')) {
          $('#therapistTable').DataTable().destroy();
        }
        
        initDataTable('#therapistTable', {
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
