<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('clinic-users.plans.index')"
  />

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
    <button class="btn-ex-main btn-ex-blue">計画情報新規登録</button>
  </a>

  <!-- 計画情報一覧表印刷ボタン -->
  <a href="{{ route('clinic-users.plans.print-history', $id) }}">
    <button class="btn-ex-main btn-ex-blue">計画情報一覧表印刷</button>
  </a>

  <br><br>

  <!-- 計画情報一覧テーブル -->
  <table id="planInfoTable" class="table table-bordered">
  <thead>
    <tr>
    <th class="text-center">評価日</th>
    <th class="text-center">評価者</th>
    <th class="text-center">聴衆者</th>
    <th class="text-center">ADL合計</th>
    <th class="text-center">データ登録日</th>
    <th class="text-center">操作</th>
    </tr>
  </thead>
  <tbody>
    @forelse($planInfos as $planInfo)
    <tr>
      <td data-order="{{ $planInfo->assessment_date ? strtotime($planInfo->assessment_date) : 0 }}">
      @if($planInfo->assessment_date)
        {{ \Carbon\Carbon::parse($planInfo->assessment_date)->format('Y/n/j') }}
      @endif
      </td>
      <td>{{ $planInfo->assessor }}</td>
      <td>{{ $planInfo->audience }}</td>
      <td>{{ $planInfo->adl_total ?? '' }}</td>
      <td data-order="{{ strtotime($planInfo->created_at) }}">
      {{ \Carbon\Carbon::parse($planInfo->created_at)->format('Y/n/j') }}
      </td>
      <td class="text-center">
      <div class="d-flex flex-wrap justify-content-center gap-1">
        <a class="btn-ex-main btn-ex-blue btn-ex-sm" href="{{ route('clinic-users.plans.edit', ['id' => $id, 'plan_id' => $planInfo->id]) }}">編集</a>
        <a class="btn-ex-main btn-ex-blue btn-ex-sm" href="{{ route('clinic-users.plans.duplicate', ['id' => $id, 'plan_id' => $planInfo->id]) }}">複製</a>
        <form action="{{ route('clinic-users.plans.delete', ['id' => $id, 'plan_id' => $planInfo->id]) }}" method="POST" class="delete-form d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="delete-btn btn-ex-main btn-ex-red btn-ex-sm">削除</button>
        </form>
      </div>
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="6" class="text-center">データがありません</td>
    </tr>
    @endforelse
  </tbody>
  </table>

  @push('scripts')
  <script src="{{ asset('js/utility.js') }}"></script>
  <script src="{{ asset('js/plans.js') }}"></script>
  @endpush
</x-app-layout>
