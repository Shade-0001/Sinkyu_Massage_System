<x-app-layout>
  @section('title', $page_header_title)
  @php
    // モードに応じたパンくずリスト定義名を決定
    if ($mode === 'create') {
      $breadcrumbName = 'clinic-users.insurances.create';
    } elseif ($mode === 'edit') {
      $breadcrumbName = 'clinic-users.insurances.edit';
    } else { // duplicate
      $breadcrumbName = 'clinic-users.insurances.duplicate';
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
      $formAction = route('clinic-users.insurances.confirm', $userId);
      $isEdit = false;
    } elseif ($mode === 'edit') {
      $formAction = route('clinic-users.insurances.edit.confirm', [$userId, $insurance->id]);
      $isEdit = true;
    } else { // duplicate
      $formAction = route('clinic-users.insurances.duplicate.confirm', [$userId, $insurance->id]);
      $isEdit = true;
    }
  @endphp

  <form action="{{ $formAction }}" method="POST">
    @include('master.clinic-users.insurances.components.insurances_form', [
      'isEdit' => $isEdit,
      'insurance' => $insurance,
      'insurers' => $insurers ?? null,
      'submitLabel' => '登録確認へ',
      'cancelRoute' => route('clinic-users.insurances.index', $userId)
    ])
  </form>

  @push('scripts')
    <script src="{{ asset('js/utility.js') }}"></script>
    <script src="{{ asset('js/insurances.js') }}"></script>
  @endpush
</x-app-layout>
