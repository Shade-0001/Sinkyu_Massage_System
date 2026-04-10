<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
  />

  <br>

  @if($errors->any())
  <div class="alert alert-danger">
    <ul>
    @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
    </ul>
  </div>
  @endif

  <!-- 要加療期間リストテーブル -->
  <table id="therapyPeriodsTable" class="table table-bordered">
  <thead>
    <tr>
    <th>利用者名</th>
    <th>区分</th>
    <th>要加療期間</th>
    <th>同意期間</th>
    <th>医師名</th>
    <th>医療機関名</th>
    <th>編集</th>
    </tr>
  </thead>
  <tbody>
    @forelse($therapyPeriods as $period)
    <tr>
      <td>
      <a href="{{ route('clinic-users.index', ['search_name' => $period->last_name . "\u{2000}" . $period->first_name]) }}">
        {{ $period->last_name }}{{ "\u{2000}" }}{{ $period->first_name }}
      </a>
      </td>
      <td>{{ $period->category }}</td>
      <td>{{ $period->therapy_period }}</td>
      <td>
      @if($period->consenting_start_date && $period->consenting_end_date)
        {{ \Carbon\Carbon::parse($period->consenting_start_date)->format('Y/n/j') }} ~ {{ \Carbon\Carbon::parse($period->consenting_end_date)->format('Y/n/j') }}
      @endif
      </td>
      <td>
      @if($period->doctor_id)
        <a href="{{ route('doctors.index', ['search_name' => $period->consenting_doctor_name]) }}">
        {{ $period->consenting_doctor_name }}
        </a>
      @else
        {{ $period->consenting_doctor_name }}
      @endif
      </td>
      <td>{{ $period->medical_institution_name }}</td>
      <td>
      @if($period->category === 'あんま・マッサージ')
        <a href="{{ route('clinic-users.consents-massage.edit', ['id' => $period->clinic_user_id, 'history_id' => $period->consent_id]) }}">
        <button type="button">編集</button>
        </a>
      @else
        <a href="{{ route('clinic-users.consents-acupuncture.edit', ['id' => $period->clinic_user_id, 'history_id' => $period->consent_id]) }}">
        <button type="button">編集</button>
        </a>
      @endif
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="7" class="text-center">データがありません</td>
    </tr>
    @endforelse
  </tbody>
  </table>

  @push('scripts')
  <script src="{{ asset('js/utility.js') }}"></script>
  <script>
  $(document).ready(function() {
    // データがある場合のみDataTableを初期化
    @if($therapyPeriods->count() > 0)
    // DataTables 初期化（定義ファイル：resources/js/app.js）
    initDataTable('#therapyPeriodsTable', {
      order: [[3, 'desc']], // 同意終了日が新しい順
      pageLength: 25
    });
    @endif
  });
  </script>
  @endpush
</x-app-layout>
