<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('clinic-users.insurances.index')"
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

  <!-- 保険情報新規登録ボタン -->
  <a href="{{ route('clinic-users.insurances.create', $id) }}">
    <button class="btn-ex-main btn-ex-blue">保険情報新規登録</button>
  </a>

  <!-- 医療保険履歴印刷ボタン -->
  <button type="button" id="printInsuranceHistory" class="btn-ex-main btn-ex-blue ms-2">医療保険履歴印刷</button>
  <br><br>

  <!-- 保険情報一覧テーブル -->
  <table id="insuranceTable" class="table table-bordered">
  <thead>
    <tr>
    <th class="text-center">保険区分</th>
    <th class="text-center">被保険番号</th>
    <th class="text-center">資格取得日</th>
    <th class="text-center">有効期限</th>
    <th class="text-center">データ登録日</th>
    <th class="text-center">操作</th>
    </tr>
  </thead>
  <tbody>
    @forelse($insurances as $insurance)
    <tr>
      <td class="fw-medium">{{ $insurance->insurer?->insurer_category ?? '保険' }}</td>
      <td class="fw-medium">{{ $insurance->insured_number }}</td>
      <td data-order="{{ $insurance->license_acquisition_date ? $insurance->license_acquisition_date->timestamp : 0 }}">
      @if($insurance->license_acquisition_date)
        {{ $insurance->license_acquisition_date->format('Y/n/j') }}
      @endif
      </td>
      <td data-order="{{ $insurance->expiry_date ? $insurance->expiry_date->timestamp : 0 }}">
      @if($insurance->expiry_date)
        {{ $insurance->expiry_date->format('Y/n/j') }}
      @endif
      </td>
      <td data-order="{{ $insurance->created_at->timestamp }}">
      {{ $insurance->created_at->format('Y/n/j') }}
      </td>
      <td class="text-center fw-medium">
      <div class="d-flex flex-wrap justify-content-center gap-1">
        <a class="btn-ex-main btn-ex-blue btn-ex-sm" href="{{ route('clinic-users.insurances.edit', ['id' => $id, 'insurance_id' => $insurance->id]) }}">編集</a>
        <a class="btn-ex-main btn-ex-blue btn-ex-sm" href="{{ route('clinic-users.insurances.duplicate', ['id' => $id, 'insurance_id' => $insurance->id]) }}">複製</a>
        <form action="{{ route('clinic-users.insurances.delete', ['id' => $id, 'insurance_id' => $insurance->id]) }}" method="POST" class="delete-form d-inline">
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
  <script>
    $(document).ready(function() {
      // データがない場合はDataTablesを初期化しない
      const hasData = $('#insuranceTable tbody tr').length > 0 &&
                      !$('#insuranceTable tbody tr:first td[colspan]').length;

      if (hasData) {
        // DataTables 初期化（定義ファイル：resources/js/app.js）
        initDataTable('#insuranceTable', {
          order: [[4, 'desc']], // データ登録日の降順
          columnDefs: [
            { orderable: false, targets: [5] } // 操作列はソート無効
          ]
        });
      }

      // 削除確認
      $(document).on('submit', '.delete-form', function(e) {
        e.preventDefault();
        if (confirm('一度削除したデータは元に戻せません。\n削除してもよろしいですか？')) {
          this.submit();
        }
      });

      // 医療保険履歴印刷
      $('#printInsuranceHistory').on('click', function() {
        const url = '{{ route('clinic-users.insurances.print-history', $id) }}';
        window.open(url, '_blank');
      });
    });
  </script>
  @endpush
</x-app-layout>

