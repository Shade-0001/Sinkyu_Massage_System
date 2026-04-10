<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('clinic-info.index')"
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

  <form action="{{ route('clinic-info.confirm') }}" method="POST">
    @include('master.clinic-info.components.clinic-info_form', [
      'companyInfo' => $companyInfo,
      'prefectures' => $prefectures,
      'bankAccountTypes' => $bankAccountTypes,
      'healthCenterLocations' => $healthCenterLocations,
      'documentFormats' => $documentFormats,
      'submitLabel' => '登録確認へ',
      'cancelRoute' => route('master.index')
    ])
  </form>

  @push('scripts')
    <script src="{{ asset('js/utility.js') }}"></script>
    <script src="{{ asset('js/clinic-info.js') }}"></script>
  @endpush
</x-app-layout>
