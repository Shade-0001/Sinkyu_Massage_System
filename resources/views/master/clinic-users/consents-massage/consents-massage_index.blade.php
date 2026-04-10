<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('clinic-users.consents-massage.index')"
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

  <!-- 同意医師履歴新規登録ボタン -->
  <a href="{{ route('clinic-users.consents-massage.create', $id) }}">
    <button class="btn-ex-main btn-ex-blue">同意医師履歴新規登録</button>
  </a>

  <!-- 同意医師履歴印刷ボタン -->
  <button type="button" id="printConsentingHistory" class="btn-ex-main btn-ex-blue ms-2" data-print-url="{{ route('clinic-users.consents-massage.print-history', $id) }}">同意医師履歴印刷</button>
  <br><br>

  <!-- 同意医師履歴一覧テーブル -->
  <table id="consentingTable" class="table table-bordered">
  <thead>
    <tr>
    <th class="text-center">同意医師名</th>
    <th class="text-center">同意日</th>
    <th class="text-center">同意開始日</th>
    <th class="text-center">同意終了日</th>
    <th class="text-center">データ登録日</th>
    <th class="text-center">操作</th>
    </tr>
  </thead>
  <tbody>
    @forelse($consentingHistories as $history)
    <tr>
      <td>
      @if($history->consentingDoctor)
        {{ $history->consentingDoctor->doctor_name }}
      @else
        同意医師未設定
      @endif
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
      <td class="text-center">
      <div class="d-flex flex-wrap justify-content-center gap-1">
        <a class="btn-ex-main btn-ex-blue btn-ex-sm" href="{{ route('clinic-users.consents-massage.edit', ['id' => $id, 'history_id' => $history->id]) }}">編集</a>
        <a class="btn-ex-main btn-ex-blue btn-ex-sm" href="{{ route('clinic-users.consents-massage.duplicate', ['id' => $id, 'history_id' => $history->id]) }}">複製</a>
        <form action="{{ route('clinic-users.consents-massage.delete', ['id' => $id, 'history_id' => $history->id]) }}" method="POST" class="delete-form d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="delete-btn btn-ex-main btn-ex-red btn-ex-sm">削除</button>
        </form>
      </div>
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="6" class="text-center">データがありません</td>
    </tr>
    @endforelse
  </tbody>
  </table>

  @push('scripts')
  <script src="{{ asset('js/utility.js') }}"></script>
  <script src="{{ asset('js/consents-massage.js') }}"></script>
  @endpush
</x-app-layout>
