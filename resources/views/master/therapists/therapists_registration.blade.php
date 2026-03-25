<x-app-layout>
  @section('title', $page_header_title)
  @php
    // モードに応じたパンくずリスト定義名を決定
    if ($mode === 'create') {
      $breadcrumbName = 'therapists.create';
    } elseif ($mode === 'edit') {
      $breadcrumbName = 'therapists.edit';
    } else { // duplicate
      $breadcrumbName = 'therapists.duplicate';
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
      $formAction = route('therapists.confirm');
    } else { // edit
      $formAction = route('therapists.edit.confirm', $therapist->id);
    }
  @endphp

  <form action="{{ $formAction }}" method="POST">
    @include('master.therapists.components.therapists_form', [
      'therapist' => $therapist,
      'submitLabel' => '登録確認へ',
      'cancelRoute' => route('therapists.index')
    ])
  </form>

  @push('scripts')
    <script src="{{ asset('js/utility.js') }}"></script>
    <script src="{{ asset('js/therapists.js') }}"></script>
  @endpush
</x-app-layout>
