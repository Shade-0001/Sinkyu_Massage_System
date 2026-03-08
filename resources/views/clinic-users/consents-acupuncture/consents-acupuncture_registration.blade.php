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
      {{ $history->consentingDoctor->last_name }}{{ "\u{2000}" }}{{ $history->consentingDoctor->first_name }}
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
  <script>
    (function () {
      // 同意日の月末（N ヶ月後）を YYYY-MM-DD で返す
      function endOfMonthAfter(dateStr, months) {
        const d = new Date(dateStr);
        // N ヶ月後の翌月1日の前日 = N ヶ月後の月末
        const end = new Date(d.getFullYear(), d.getMonth() + months + 1, 0);
        const yyyy = end.getFullYear();
        const mm = String(end.getMonth() + 1).padStart(2, '0');
        const dd = String(end.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
      }

      document.getElementById('consenting_date').addEventListener('change', function () {
        const val = this.value;
        if (!val) return;

        // 同意日と同じ日を入力
        document.getElementById('consenting_start_date').value = val;
        document.getElementById('benefit_period_start_date').value = val;

        // 同意日の6ヶ月後の月末を入力
        const endDate = endOfMonthAfter(val, 6);
        document.getElementById('consenting_end_date').value = endDate;
        document.getElementById('benefit_period_end_date').value = endDate;
        document.getElementById('reconsenting_expiry').value = endDate;
      });
    })();
  </script>
  @endpush
</x-app-layout>
