<x-app-layout>
  @section('title', $page_header_title)
  @php
    // モードに応じたパンくずリスト定義名を決定
    if (($mode ?? 'create') === 'create') {
      $breadcrumbName = 'master.documents.create';
    } elseif ($mode === 'edit') {
      $breadcrumbName = 'master.documents.edit';
    } else { // duplicate
      $breadcrumbName = 'master.documents.duplicate';
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
      $formAction = route('master.documents.store');
      $submitLabel = '登録';
      $item = (object)[];
    } elseif ($mode === 'duplicate') {
      $formAction = route('master.documents.duplicate.store');
      $submitLabel = '複製登録';
      $item = $document;
    } else { // edit
      $formAction = route('master.documents.update', $document->id);
      $submitLabel = '更新';
      $item = $document;
    }
  @endphp

  <form action="{{ $formAction }}" method="POST">
    @include('master.documents.components.documents_form', [
      'item' => $item,
      'categories' => $categories,
      'submitLabel' => $submitLabel,
      'cancelRoute' => route('master.documents.index')
    ])
  </form>

  @push('scripts')
  <script src="{{ asset('js/utility.js') }}"></script>
  @endpush
</x-app-layout>
