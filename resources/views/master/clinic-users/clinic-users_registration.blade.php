<x-app-layout>
  @section('title', $page_header_title)
  @php
    // モードに応じたパンくずリスト定義名を決定
    if ($mode === 'create') {
      $breadcrumbName = 'clinic-users.create';
    } elseif ($mode === 'edit') {
      $breadcrumbName = 'clinic-users.edit';
    } else { // duplicate
      $breadcrumbName = 'clinic-users.duplicate';
    }
  @endphp

  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate($breadcrumbName)"
  />

  @if(session('error'))
  <div class="alert alert-danger">
    {{ session('error') }}
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

  @php
    // モードに応じたフォームの送信先を設定
    if ($mode === 'create') {
      $formAction = route('clinic-users.confirm');
      $sessionKey = 'registration_data';
      $isEdit = false;
      $includeId = false;
    } else { // edit
      $formAction = route('clinic-users.edit.confirm', ['id' => $clinicUser->id]);
      $sessionKey = 'edit_data';
      $isEdit = true;
      $includeId = true;
    }
  @endphp

  @include('master.clinic-users.clinic-users_form', [
  'action' => $formAction,
  'sessionKey' => $sessionKey,
  'clinicUser' => $clinicUser,
  'isEdit' => $isEdit,
  'includeId' => $includeId,
  ])

  @push('scripts')
    <script src="{{ asset('js/utility.js') }}"></script>
    <script src="{{ asset('js/clinic-users.js') }}"></script>
  @endpush
</x-app-layout>
