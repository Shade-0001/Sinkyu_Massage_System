<x-app-layout>
  @php
    // モードに応じたパンくずリスト定義名を決定
    if ($mode === 'create') {
      $breadcrumbName = 'doctors.create';
      $formAction = route('doctors.confirm');
    } elseif ($mode === 'edit') {
      $breadcrumbName = 'doctors.edit';
      $formAction = route('doctors.edit.confirm', $doctor->id);
    } else { // duplicate
      $breadcrumbName = 'doctors.duplicate';
      $formAction = route('doctors.duplicate.confirm');
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

  <form action="{{ $formAction }}" method="POST">
    @if($mode === 'duplicate')
      <input type="hidden" name="source_doctor_id" value="{{ old('source_doctor_id', $doctor->id) }}">
    @endif

    @include('master.doctors.components.doctors_form', [
      'doctor' => $doctor,
      'medicalInstitutions' => $medicalInstitutions,
      'submitLabel' => '登録確認へ',
      'cancelRoute' => route('doctors.index')
    ])
  </form>

  @push('scripts')
    <script src="{{ asset('js/utility.js') }}"></script>
    <script src="{{ asset('js/doctors.js') }}"></script>
  @endpush
</x-app-layout>
