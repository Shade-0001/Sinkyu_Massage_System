<!-- resources/views/deposits/deposits_index.blade.php -->

<x-app-layout>
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('deposits.index')"
  />

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

  <!-- 入金データ一覧表示エリア -->
  <div id="deposits-list-area" style="max-height: 75vh; overflow-y: auto; overflow-x: hidden; border: 1px solid #dee2e6; padding: 1rem;">
    @foreach($depositsByYear as $year => $yearData)
      @php
        $hasDeposits = $yearData['has_deposits'];
        $count = $yearData['count'] ?? 0;
        $months = $yearData['months'];
        $collapseId = 'year-' . $year;
      @endphp

      <!-- 年ヘッダー（折り畳み・展開ボタン） -->
      @if($hasDeposits)
        {{-- データがある年：クリック可能 --}}
        <div class="year-header mb-2">
          <button
            class="btn btn-link text-decoration-none p-0 fw-bold fs-4 text-dark d-flex align-items-center"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#{{ $collapseId }}"
            aria-expanded="false"
            aria-controls="{{ $collapseId }}"
          >
            <span class="toggle-icon">▶</span>
            <span>［ {{ $year }} ］</span>
            <div class="vr ms-3 me-4" style="height: 1.8rem; position: relative; top: 0.1rem;"></div>
            <span style="font-size: 0.95rem; font-weight: normal;">{{ $count }}件</span>
          </button>
        </div>
      @else
        {{-- データがない年：クリック不可、「該当データなし」を表示 --}}
        <div class="year-header mb-2">
          <div class="fw-bold fs-4 text-secondary d-flex align-items-center">
            <span style="visibility: hidden;">▶</span>
            <span>［ {{ $year }} ］</span>
            <div class="vr ms-3 me-4" style="height: 1.8rem; position: relative; top: 0.1rem;"></div>
            <span style="font-size: 0.95rem; font-weight: normal;">該当データなし</span>
          </div>
        </div>
      @endif

      <!-- 月別データ（折り畳み可能） -->
      <div class="collapse" id="{{ $collapseId }}" data-year="{{ $year }}">
        @foreach($months as $item)
          @php
            $yearMonth = $item['year_month'];
            $hasData = $item['has_data'] ?? false;
          @endphp
          @if($hasData)
            {{-- データがある月：展開/格納機能あり --}}
            <div class="deposit-month-section my-2 ms-4" data-year-month="{{ $yearMonth }}" data-has-data="true">
              <div class="fw-bold fs-5 d-flex align-items-center">
                <span class="month-toggle-icon me-2">▸</span>
                <span>{{ $item['year'] }}年 {{ sprintf('%02d', $item['month']) }}月</span>
              </div>
              <div class="deposit-data-container" data-year-month="{{ $yearMonth }}">
                <!-- データはAjaxで動的に読み込まれる -->
              </div>
            </div>
          @else
            {{-- データがない月：展開/格納機能なし、「該当データなし」を表示 --}}
            <div class="my-1 ms-4">
              <div class="fw-bold fs-5 d-flex align-items-center">
                <span class="me-2" style="visibility: hidden;">▸</span>
                <span class="text-secondary fw-medium">{{ $item['year'] }}年 {{ sprintf('%02d', $item['month']) }}月</span>
                <div class="vr ms-3 me-4" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
                <span class="text-secondary" style="font-size: 0.9rem; font-weight: normal;">該当データなし</span>
              </div>
            </div>
          @endif
          <hr class="my-0">
        @endforeach
      </div>
    @endforeach
  </div>

  @push('styles')
  <style>
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

    /* 年ヘッダーのクリック可能スタイル */
    .year-header button {
      cursor: pointer;
    }

    /* データがある月のクリック可能スタイル */
    .deposit-month-section[data-has-data="true"] {
      cursor: pointer;
    }
  </style>
  @endpush

  @push('scripts')
  <script>
    // PHP変数をJavaScriptに渡す
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
