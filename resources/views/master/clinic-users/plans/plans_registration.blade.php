<x-app-layout>
  @php
    // モードに応じたパンくずリスト定義名を決定
    if ($mode === 'create') {
      $breadcrumbName = 'clinic-users.plans.create';
    } elseif ($mode === 'edit') {
      $breadcrumbName = 'clinic-users.plans.edit';
    } else { // duplicate
      $breadcrumbName = 'clinic-users.plans.duplicate';
    }
  @endphp

  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate($breadcrumbName)"
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

  @php
    // モードに応じたフォームの送信先を設定
    if ($mode === 'create') {
      $formAction = route('clinic-users.plans.confirm', $id);
    } elseif ($mode === 'edit') {
      $formAction = route('clinic-users.plans.edit.confirm', [$id, $plan_id]);
    } else { // duplicate
      $formAction = route('clinic-users.plans.duplicate.confirm', [$id, $plan_id]);
    }
  @endphp

  @if($mode === 'duplicate')
  <div class="alert alert-warning">
    <strong>複製元の計画書:</strong>
    評価日: {{ $planInfo->assessment_date?->format('Y年m月d日') ?? '未設定' }}
    評価者: {{ $planInfo->assessor ?? '未設定' }}
  </div>
  @endif

  <form action="{{ $formAction }}" method="POST">
    @include('master.clinic-users.plans.components.plans_form', [
      'planInfo' => $planInfo ?? null,
      'assistanceLevels' => $assistanceLevels,
      'submitLabel' => '登録確認へ',
      'cancelRoute' => route('clinic-users.plans.index', $id)
    ])
  </form>

  @push('scripts')
  <script src="{{ asset('js/utility.js') }}"></script>
  @endpush
</x-app-layout>
