<x-app-layout>
  @php
    // モードに応じたパンくずリスト定義名を決定
    if (($mode ?? 'create') === 'create') {
      $breadcrumbName = 'master.treatment-fees.create';
    } else { // edit
      $breadcrumbName = 'master.treatment-fees.edit';
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
    if (($mode ?? 'create') === 'create') {
      $formAction = route('master.treatment-fees.store');
      $submitLabel = '登録確認へ';
    } else { // edit
      $formAction = route('master.treatment-fees.update', $item->id);
      $submitLabel = '更新';
    }
  @endphp

  <form action="{{ $formAction }}" method="POST">
    @include('master.treatment-fees.components.treatment-fees_form', [
      'item' => $item ?? (object)[],
      'submitLabel' => $submitLabel,
      'cancelRoute' => route('master.treatment-fees.index')
    ])
  </form>

  @push('scripts')
  <script src="{{ asset('js/utility.js') }}"></script>
  @endpush
</x-app-layout>
