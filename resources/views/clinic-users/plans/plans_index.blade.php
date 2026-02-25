<!-- resources/views/clinic-users/plans/plans_index.blade.php -->

<x-app-layout>
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('clinic-users.plans.index')"
  />

  <br>

  @if(session('success'))
  <div class="alert alert-success">
    {{ session('success') }}
  </div>
  @endif

  @if($errors->any())
  <div class="alert alert-danger">
    <ul>
    @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
    </ul>
  </div>
  @endif

  <!-- 計画情報新規登録ボタン -->
  <a href="{{ route('clinic-users.plans.create', $id) }}">
  <button>計画情報新規登録</button>
  </a>

  <!-- 計画情報印刷ボタン -->
  <button type="button" id="printPlanInfos" data-print-url="{{ route('clinic-users.plans.print-history', $id) }}" style="margin-left: 10px;">計画情報印刷</button>
  <br><br>

  <!-- 計画情報一覧テーブル -->
  <table id="planInfoTable" class="table table-bordered table-striped">
  <thead>
    <tr>
    <th>評価日</th>
    <th>評価者</th>
    <th>聴衆者</th>
    <th>ADL合計</th>
    <th>データ登録日</th>
    <th>複製</th>
    <th>削除</th>
    </tr>
  </thead>
  <tbody>
    @forelse($planInfos as $planInfo)
    <tr>
      <td data-order="{{ $planInfo->assessment_date ? strtotime($planInfo->assessment_date) : 0 }}">
      <a href="{{ route('clinic-users.plans.edit', ['id' => $id, 'plan_id' => $planInfo->id]) }}">
        @if($planInfo->assessment_date)
          {{ \Carbon\Carbon::parse($planInfo->assessment_date)->format('Y/n/j') }}
        @endif
        [編集]
      </a>
      </td>
      <td>{{ $planInfo->assessor }}</td>
      <td>{{ $planInfo->audience }}</td>
      <td>{{ $planInfo->adl_total ?? '' }}</td>
      <td data-order="{{ strtotime($planInfo->created_at) }}">
      {{ \Carbon\Carbon::parse($planInfo->created_at)->format('Y/n/j') }}
      </td>
      <td>
      <a href="{{ route('clinic-users.plans.duplicate', ['id' => $id, 'plan_id' => $planInfo->id]) }}">[複製]</a>
      </td>
      <td>
      <form action="{{ route('clinic-users.plans.delete', ['id' => $id, 'plan_id' => $planInfo->id]) }}" method="POST" class="delete-form d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="delete-btn btn btn-link p-0">[削除]</button>
      </form>
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
  <script src="{{ asset('js/plans.js') }}"></script>
  @endpush
</x-app-layout>
