<x-app-layout>
  @php
    // モードに応じたパンくずリスト定義名を決定
    if ($mode === 'create') {
      $breadcrumbName = 'caremanagers.create';
    } elseif ($mode === 'edit') {
      $breadcrumbName = 'caremanagers.edit';
    } else { // duplicate
      $breadcrumbName = 'caremanagers.duplicate';
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
      $formAction = route('caremanagers.confirm');
    } else { // edit
      $formAction = route('caremanagers.edit.confirm', $careManager->id);
    }
  @endphp

  <form action="{{ $formAction }}" method="POST">
    @include('master.caremanagers.components.caremanagers_form', [
      'careManager' => $careManager,
      'submitLabel' => '登録確認へ',
      'cancelRoute' => route('caremanagers.index')
    ])
  </form>

  @push('scripts')
    <script src="{{ asset('js/utility.js') }}"></script>
    <script src="{{ asset('js/caremanagers.js') }}"></script>
  @endpush
</x-app-layout>
