<!-- resources/views/clinic-users/consents-massage/consents-massage_registration.blade.php -->

<x-app-layout>
  @php
    // モードに応じたパンくずリスト定義名を決定
    if ($mode === 'create') {
      $breadcrumbName = 'clinic-users.consents-massage.create';
    } elseif ($mode === 'edit') {
      $breadcrumbName = 'clinic-users.consents-massage.edit';
    } else { // duplicate
      $breadcrumbName = 'clinic-users.consents-massage.duplicate';
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
      $formAction = route('clinic-users.consents-massage.confirm', $id);
    } elseif ($mode === 'edit') {
      $formAction = route('clinic-users.consents-massage.edit.confirm', [$id, $history_id]);
    } else { // duplicate
      $formAction = route('clinic-users.consents-massage.duplicate.confirm', [$id, $history_id]);
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
    @include('clinic-users.consents-massage.components.consents-massage_form', [
      'history' => $history ?? null,
      'submitLabel' => '登録確認へ',
      'cancelRoute' => route('clinic-users.consents-massage.index', $id)
    ])
  </form>

  @push('scripts')
  <script src="{{ asset('js/utility.js') }}"></script>
  @endpush
</x-app-layout>
