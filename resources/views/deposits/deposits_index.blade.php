<x-app-layout>
  <style>
    /* deposits/index height+scaleY collapse */
    .year-content,
    .month-content {
      transform-origin: top center;
      transform: scaleY(0);
      opacity: 0;
      height: 0;
      overflow: hidden;
      transition: height 0.3s ease, transform 0.3s ease, opacity 0.3s ease;
    }

    /* number型inputのスピナーを非表示 */
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }
    input[type="number"] {
      -moz-appearance: textfield;
    }

    /* テーブルヘッダーのフォントウェイト */
    .table thead th {
      font-weight: 500 !important;
    }
  </style>

  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('deposits.index')"
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

  <!-- 入金データ一覧表示エリア -->
  <div id="deposits-list-area" style="overflow-y: auto; overflow-x: hidden; border: 1px solid #dee2e6;">
    @foreach($depositsByYear as $year => $yearData)
      @php
        $hasDeposits = $yearData['has_deposits'];
        $count = $yearData['count'] ?? 0;
        $months = $yearData['months'];
        $collapseId = 'year-' . $year;
        $isYearExpanded = $hasDeposits && (string)$year === substr($scrollToYearMonth, 0, 4);
      @endphp

      @if (!$loop->first)
        <div class="mt-5"></div>
      @endif

      <div class="bg-gray-96 rounded-1 p-2 me-2 year-block" style="position: relative;">
        <!-- 年ヘッダー -->
        <div class="year-header d-flex align-items-center">
          @if($hasDeposits)
            <button class="btn-ex-sub btn-ex-blue btn-ex-xl btn-ex-sub-toggle-invert fs-2 gap-3 me-2"
                    type="button" data-toggle-year="{{ $collapseId }}"
                    aria-expanded="false">
              <span class="align-self-center ps-1">{{ $year }}</span>
              <div class="vr mx-2 align-self-center opacity-50" style="height: 1.5rem; width: 2px;"></div>
              <span class="fs-6 fw-medium align-self-center">{{ $count }}件</span>
              <span class="year-toggle-arrow d-inline-flex align-items-center align-self-center">
                <i class="nf nf-md-chevron_down fs-5 ps-2"></i>
              </span>
            </button>
          @else
            <div class="fs-2 fw-bold text-secondary opacity-75 ps-3">{{ $year }}</div>
            <div class="vr mx-4 align-self-center opacity-25" style="height: 2.4rem; width: 2px;"></div>
            <span class="fs-6 fw-medium text-secondary align-self-center me-3">該当データなし</span>
          @endif
          <hr class="year-top-hr" style="flex-grow: 1; border: none; border-top: 4px solid #000; margin: 0rem;">
        </div>

        <!-- 月別データ（scaleY展開） -->
        <div class="year-content" id="{{ $collapseId }}" data-year="{{ $year }}">
          @foreach($months as $item)
            @php
              $yearMonth = $item['year_month'];
              $monthCollapseId = "month-{$item['year']}-{$item['month']}";
              $monthCount = $item['count'] ?? 0;
            @endphp
            <div class="deposit-month-section ms-4 {{ $loop->first ? 'mt-4' : '' }}" data-year-month="{{ $yearMonth }}">
              @if($item['has_data'])
                <!-- 入金データあり -->
                <button class="btn-ex-sub btn-ex-blue btn-ex-lg btn-ex-sub-toggle-invert mb-1 gap-2"
                     type="button"
                     data-toggle-month="{{ $monthCollapseId }}"
                     aria-expanded="false">
                  {{ $item['year'] }}年{{ "\u{2000}" }}{{ $item['month'] }}月
                  <div class="vr mx-1 align-self-center opacity-75" style="height: 1.4rem;"></div>
                  <span class="fs-7 fw-medium">{{ $monthCount }}件</span>
                  <span class="year-toggle-arrow d-inline-flex align-items-center align-self-center">
                    <i class="nf nf-md-chevron_down ps-2"></i>
                  </span>
                </button>
                <div class="month-content" id="{{ $monthCollapseId }}" style="overflow-x: hidden;">
                  <div class="deposit-data-container" data-year-month="{{ $yearMonth }}">
                    <!-- データはAjaxで動的に読み込まれる -->
                  </div>
                </div>
              @else
                <!-- 入金データなし -->
                <div class="d-flex align-items-center gap-3">
                  <div class="fw-medium fs-5 mb-0 opacity-75">{{ $item['year'] }}年{{ "\u{2000}" }}{{ $item['month'] }}月</div>
                  <div class="vr align-self-center opacity-50" style="height: 1.6rem;"></div>
                  <span class="text-secondary fw-medium">該当データなし</span>
                </div>
              @endif
            </div>
            @if (!$loop->last)
              <hr class="border-secondary border-2 ms-4">
            @else
              <div class="mb-3"></div>
            @endif
          @endforeach
        </div>

        <!-- hr2：格納時はhr1と同位置・同幅、展開時に下部へ移動後に左端を広げる -->
        <hr class="year-bottom-hr" style="position: absolute; border: none; border-top: 4px solid #000; margin: 0; display: none;">
      </div>
    @endforeach
  </div>

  @push('scripts')
  <script>
    window.depositsConfig = {
      scrollToYearMonth: @json($scrollToYearMonth),
      getMonthDataUrl: '{{ route("deposits.getMonthData", ":yearMonth") }}',
      updateUrl: '{{ route("deposits.update", ":id") }}',
      csrfToken: '{{ csrf_token() }}'
    };
  </script>
  <script src="{{ asset('js/deposits.js') }}"></script>
  @endpush
</x-app-layout>
