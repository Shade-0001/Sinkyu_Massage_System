<!-- resources/views/clinic-users-info/cui-consenting-doctor-history-acupuncture/ccdha-index.blade.php -->


<x-app-layout>
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('clinic-users.consents-acupuncture.index')"
  />

  <br>

  @if(session('success'))
  <div class="alert alert-success">
    {{ session('success') }}
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

  <!-- 同意医師履歴新規登録ボタン -->
  <a href="{{ route('clinic-users.consents-acupuncture.registration', $id) }}">
  <button>同意医師履歴新規登録</button>
  </a>

  <!-- 同意医師履歴印刷ボタン -->
  <button type="button" id="printConsentingHistory" data-print-url="{{ route('clinic-users.consents-acupuncture.print-history', $id) }}" style="margin-left: 10px;">同意医師履歴印刷</button>
  <br><br>

  <!-- 同意医師履歴一覧テーブル -->
  <table id="consentingTable" class="table table-bordered table-striped">
  <thead>
    <tr>
    <th>同意医師名</th>
    <th>同意日</th>
    <th>同意開始日</th>
    <th>同意終了日</th>
    <th>データ登録日</th>
    <th>複製</th>
    <th>削除</th>
    </tr>
  </thead>
  <tbody>
    @forelse($consentingHistories as $history)
    <tr>
      <td>
      <a href="{{ route('clinic-users.consents-acupuncture.edit', ['id' => $id, 'history_id' => $history->id]) }}">
        @if($history->consentingDoctor)
          {{ $history->consentingDoctor->last_name }}\u{2000}{{ $history->consentingDoctor->first_name }}
        @else
          同意医師未設定
        @endif
        [編集]
      </a>
      </td>
      <td data-order="{{ $history->consenting_date ? strtotime($history->consenting_date) : 0 }}">
      @if($history->consenting_date)
        {{ \Carbon\Carbon::parse($history->consenting_date)->format('Y/n/j') }}
      @endif
      </td>
      <td data-order="{{ $history->consenting_start_date ? strtotime($history->consenting_start_date) : 0 }}">
      @if($history->consenting_start_date)
        {{ \Carbon\Carbon::parse($history->consenting_start_date)->format('Y/n/j') }}
      @endif
      </td>
      <td data-order="{{ $history->consenting_end_date ? strtotime($history->consenting_end_date) : 0 }}">
      @if($history->consenting_end_date)
        {{ \Carbon\Carbon::parse($history->consenting_end_date)->format('Y/n/j') }}
      @endif
      </td>
      <td data-order="{{ strtotime($history->created_at) }}">
      {{ \Carbon\Carbon::parse($history->created_at)->format('Y/n/j') }}
      </td>
      <td>
      <a href="{{ route('clinic-users.consents-acupuncture.duplicate', ['id' => $id, 'history_id' => $history->id]) }}">[複製]</a>
      </td>
      <td>
      <form action="{{ route('clinic-users.consents-acupuncture.delete', ['id' => $id, 'history_id' => $history->id]) }}" method="POST" class="delete-form d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="delete-btn btn btn-link p-0">[削除]</button>
      </form>
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="7" class="text-center">データがありません</td>
    </tr>
    @endforelse
  </tbody>
  </table>

  @push('scripts')
  <script src="{{ asset('js/utility.js') }}"></script>
  <script src="{{ asset('js/consents-acupuncture.js') }}"></script>
  @endpush
</x-app-layout>
