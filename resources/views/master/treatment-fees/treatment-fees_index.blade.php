<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('master.treatment-fees.index')"
  />

  @if(session('success'))
    <div class="text-success">{{ session('success') }}</div>
  @endif

  <!-- 新規登録ボタン -->
  <div class="mb-2">
    <a href="{{ route('master.treatment-fees.create') }}">
      <button type="button">施術料金新規登録</button>
    </a>
  </div>

  <table id="treatmentFeesTable" class="table table-bordered table-sm small">
    <thead class="table-secondary">
      <tr>
        <th rowspan="2" class="align-middle text-center">対象期間</th>
        <th colspan="3" class="text-center">初回</th>
        <th colspan="3" class="text-center">通常</th>
        <th rowspan="2" class="align-middle text-center">データ登録日</th>
        <th rowspan="2" class="align-middle text-center">操作</th>
      </tr>
      <tr>
        <th class="text-center" style="background-color: rgba(31,145,206,0.2);">はり・きゅう</th>
        <th class="text-center" style="background-color: rgba(230,126,34,0.2);">あんま・マッサージ</th>
        <th class="text-center" style="background-color: rgba(27,173,80,0.2);">往療料</th>
        <th class="text-center" style="background-color: rgba(31,145,206,0.2);">はり・きゅう</th>
        <th class="text-center" style="background-color: rgba(230,126,34,0.2);">あんま・マッサージ</th>
        <th class="text-center" style="background-color: rgba(27,173,80,0.2);">往療料</th>
      </tr>
    </thead>
    <tbody class="fw-medium">
      @foreach($items as $item)
      <tr>
        {{-- 対象期間 3行 --}}
        <td class="text-center align-middle text-nowrap">
          {{ \Carbon\Carbon::parse($item->period_start)->format('Y/m/d') }}<br>
          ┃<br>
          {{ \Carbon\Carbon::parse($item->period_end)->format('Y/m/d') }}
        </td>

        {{-- 初回：はり・きゅう --}}
        <td class="align-top" style="background-color: rgba(31,145,206,0.1);">
          <div>はり：{{ number_format($item->hari_first) }}</div>
          <div>きゅう：{{ number_format($item->kyu_first) }}</div>
          <div>はりきゅう併用：{{ number_format($item->hari_and_kyu_first) }}</div>
          <div>電療料（電気針）：{{ number_format($item->hari_and_elec_needle_first) }}</div>
          <div>電療料（電気温灸器）：{{ number_format($item->kyu_and_elec_moxa_heater_first) }}</div>
          <div>電療料（電気光線器具）：{{ number_format($item->hari_and_kyu_elec_ray_first) }}</div>
        </td>

        {{-- 初回：あんま・マッサージ --}}
        <td class="align-top" style="background-color: rgba(230,126,34,0.1);">
          <div>マッサージ躯幹：{{ number_format($item->massage_trunk_first) }}</div>
          <div>マッサージ右上肢：{{ number_format($item->massage_upper_limb_r_first) }}</div>
          <div>マッサージ左上肢：{{ number_format($item->massage_upper_limb_l_first) }}</div>
          <div>マッサージ右下肢：{{ number_format($item->massage_lower_limb_r_first) }}</div>
          <div>マッサージ左下肢：{{ number_format($item->massage_lower_limb_l_first) }}</div>
          <div>変形徒手矯正術：{{ number_format($item->manual_correction_first) }}</div>
          <div>温罨法：{{ number_format($item->fomentation_first) }}</div>
          <div>温罨法・電気光線器具：{{ number_format($item->fomentation_and_elec_ray_first) }}</div>
        </td>

        {{-- 初回：往療料 --}}
        <td class="align-top" style="background-color: rgba(27,173,80,0.1);">
          <div>往診料（4km以内）：{{ number_format($item->housecall_max_2km_first) }}</div>
          <div>往診料（4km超過）：{{ number_format($item->housecall_additional_max_4km_first) }}</div>
        </td>

        {{-- 通常：はり・きゅう --}}
        <td class="align-top" style="background-color: rgba(31,145,206,0.1);">
          <div>はり：{{ number_format($item->hari_normal) }}</div>
          <div>きゅう：{{ number_format($item->kyu_normal) }}</div>
          <div>はりきゅう併用：{{ number_format($item->hari_and_kyu_normal) }}</div>
          <div>電療料（電気針）：{{ number_format($item->hari_and_elec_needle_normal) }}</div>
          <div>電療料（電気温灸器）：{{ number_format($item->kyu_and_elec_moxa_heater_normal) }}</div>
          <div>電療料（電気光線器具）：{{ number_format($item->hari_and_kyu_elec_ray_normal) }}</div>
        </td>

        {{-- 通常：あんま・マッサージ --}}
        <td class="align-top" style="background-color: rgba(230,126,34,0.1);">
          <div>マッサージ躯幹：{{ number_format($item->massage_trunk_normal) }}</div>
          <div>マッサージ右上肢：{{ number_format($item->massage_upper_limb_r_normal) }}</div>
          <div>マッサージ左上肢：{{ number_format($item->massage_upper_limb_l_normal) }}</div>
          <div>マッサージ右下肢：{{ number_format($item->massage_lower_limb_r_normal) }}</div>
          <div>マッサージ左下肢：{{ number_format($item->massage_lower_limb_l_normal) }}</div>
          <div>変形徒手矯正術：{{ number_format($item->manual_correction_normal) }}</div>
          <div>温罨法：{{ number_format($item->fomentation_normal) }}</div>
          <div>温罨法・電気光線器具：{{ number_format($item->fomentation_and_elec_ray_normal) }}</div>
        </td>

        {{-- 通常：往療料 --}}
        <td class="align-top" style="background-color: rgba(27,173,80,0.1);">
          <div>往診料（4km以内）：{{ number_format($item->housecall_max_2km_normal) }}</div>
          <div>往診料（4km超過）：{{ number_format($item->housecall_additional_max_4km_normal) }}</div>
        </td>

        <td class="text-center align-middle text-nowrap">
          {{ \Carbon\Carbon::parse($item->created_at)->format('Y/m/d') }}<br>
          {{ \Carbon\Carbon::parse($item->created_at)->format('H:i:s') }}
        </td>

        {{-- 操作 --}}
        <td class="text-center align-middle">
          <a href="{{ route('master.treatment-fees.edit', $item->id) }}" class="d-block mb-1">
            <button type="button" class="btn btn-sm btn-outline-primary w-100">編集</button>
          </a>
          <form action="{{ route('master.treatment-fees.destroy', $item->id) }}" method="POST" onsubmit="return confirm('本当に削除する？');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger w-100">削除</button>
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
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        columnDefs: [
          { orderable: false, targets: [1, 2, 3, 4, 5, 6, 8] }
        ]
      });
    });
  </script>
  @endpush
</x-app-layout>
