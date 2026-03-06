<x-app-layout>
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('master.treatment-fees.index')"
  />

  @if(session('success'))
    <div style="color: green;">{{ session('success') }}</div>
  @endif

  <!-- 新規登録ボタン -->
  <div style="margin-bottom: 10px;">
    <a href="{{ route('master.treatment-fees.create') }}">
      <button type="button">施術料金新規登録</button>
    </a>
  </div>

  <table id="treatmentFeesTable" class="table table-bordered table-striped">
    <thead>
      <tr>
        <th style="width: 10%;">編集</th>
        <th style="width: 30%;">対象期間</th>
        <th style="width: 15%;">初回</th>
        <th style="width: 15%;">通常</th>
        <th style="width: 20%;">データ登録日</th>
        <th style="width: 10%;">削除</th>
      </tr>
    </thead>
    <tbody>
      @foreach($items as $item)
      <tr>
        <td style="text-align: center;">
          <a href="{{ route('master.treatment-fees.edit', $item->id) }}">
            <button type="button">編集</button>
          </a>
        </td>
        <td>{{ $item->period_start }} ～ {{ $item->period_end }}</td>
        <td style="text-align: right;">{{ number_format($item->hari_first) }} 円</td>
        <td style="text-align: right;">{{ number_format($item->hari_normal) }} 円</td>
        <td>{{ $item->created_at }}</td>
        <td style="text-align: center;">
          <form action="{{ route('master.treatment-fees.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('本当に削除する？');">
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
      $('#treatmentFeesTable').DataTable({
        language: {
          url: '{{ asset('js/dataTables-ja.json') }}',
          paginate: {
            previous: '◂ 前へ',
            next: '次へ ▸'
          }
        },
        order: [[1, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        columnDefs: [
          { orderable: false, targets: [0, 5] }
        ]
      });
    });
  </script>
  @endpush
</x-app-layout>
