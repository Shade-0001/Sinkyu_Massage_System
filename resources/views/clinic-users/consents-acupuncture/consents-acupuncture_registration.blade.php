<!-- resources/views/clinic-users/consents-acupuncture/consents-acupuncture_registration.blade.php -->

<x-app-layout>
  @php
    // モードに応じたパンくずリスト定義名を決定
    if ($mode === 'create') {
      $breadcrumbName = 'clinic-users.consents-acupuncture.registration';
    } elseif ($mode === 'edit') {
      $breadcrumbName = 'clinic-users.consents-acupuncture.edit';
    } else { // duplicate
      $breadcrumbName = 'clinic-users.consents-acupuncture.duplicate';
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
      $formAction = route('clinic-users.consents-acupuncture.confirm', $id);
    } elseif ($mode === 'edit') {
      $formAction = route('clinic-users.consents-acupuncture.edit.confirm', [$id, $history_id ?? $history->id]);
    } else { // duplicate
      $formAction = route('clinic-users.consents-acupuncture.duplicate.confirm', [$id, $history_id ?? $history->id]);
    }
  @endphp

  @if($mode === 'duplicate')
  <div class="alert alert-warning">
    <strong>複製元の履歴:</strong>
    @if($history->consentingDoctor)
      {{ $history->consentingDoctor->last_name }}\u{2000}{{ $history->consentingDoctor->first_name }}
    @else
      同意医師未設定
    @endif
    （同意日: {{ $history->consenting_date?->format('Y年m月d日') ?? '未設定' }}）
  </div>
  @endif

  <form action="{{ $formAction }}" method="POST">
    @include('clinic-users.consents-acupuncture.components.consents-acupuncture_form', [
      'history' => $history ?? null,
      'submitLabel' => '登録確認へ',
      'cancelRoute' => route('clinic-users.consents-acupuncture.index', $id)
    ])
  </form>

  @push('scripts')
  <script src="{{ asset('js/utility.js') }}"></script>
  @endpush
</x-app-layout>
