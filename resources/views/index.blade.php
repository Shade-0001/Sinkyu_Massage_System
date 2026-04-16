<x-app-layout>
  @php
    $page_header_title = 'ホーム';
  @endphp
  @section('title', $page_header_title)

  @push('styles')
  <style>
    .home-menu-label {
      font-family: "M PLUS Rounded 1c", sans-serif;
      font-size: 2rem;
      letter-spacing: 0.2rem;
      -webkit-text-stroke: 7px #444;
      paint-order: stroke fill;
    }

    .home-menu-item {
      width: 100%;
    }

    /* サイドバー格納時：lg以上で2列 */
    @media (min-width: 992px) {
      html[data-sidebar="closed"] .home-menu-item {
        width: 50%;
      }
    }

    /* サイドバー展開時：xl以上で2列 */
    @media (min-width: 1200px) {
      html[data-sidebar="open"] .home-menu-item {
        width: 50%;
      }
    }
  </style>
  @endpush

  <x-page-header :title="$page_header_title"/>

  <div class="mt-2 mx-1 user-select-none" style="max-width: 1200px;">
    <div id="home-menu" class="d-flex flex-wrap">

      <div class="home-menu-item">
        <a class="d-flex justify-content-start btn-ex-main bg-gray-98 rounded-2 gap-3" href="{{ route('records.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white fs-2 flex-shrink-0 border-bevel-3" style="background-color:#E74C3C; aspect-ratio: 1/1;">
            <i class="nf nf-fa-edit px-4 text-gray-95 highlight-target-text-white" style="font-size: 3rem;"></i>
          </div>
          <div class="d-flex flex-column gap-3">
            <div class="home-menu-label text-gray-95 highlight-target-text-white text-nowrap fw-lighter">実績データ</div>
            <div class="text-gray-30 fw-medium text-nowrap">施術実績の入力･管理</div>
          </div>
        </a>
      </div>

      <div class="home-menu-item">
        <a class="d-flex justify-content-start btn-ex-main bg-gray-98 rounded-2 gap-3" href="{{ route('reports.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white fs-2 flex-shrink-0 border-bevel-3" style="background-color:#E67E22; aspect-ratio: 1/1;">
            <i class="nf nf-fa-file_text_o px-4 text-gray-95 highlight-target-text-white" style="font-size: 3rem;"></i>
          </div>
          <div class="d-flex flex-column gap-3">
            <div class="home-menu-label text-gray-95 highlight-target-text-white text-nowrap fw-lighter">報告書データ</div>
            <div class="text-gray-30 fw-medium text-nowrap">各種報告書の入力･管理</div>
          </div>
        </a>
      </div>

      <div class="home-menu-item">
        <a class="d-flex justify-content-start btn-ex-main bg-gray-98 rounded-2 gap-3" href="{{ route('schedules.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white fs-2 flex-shrink-0 border-bevel-3" style="background-color:#EDBE00; aspect-ratio: 1/1;">
            <i class="nf nf-md-calendar_month_outline px-4 text-gray-95 highlight-target-text-white" style="font-size: 3rem;"></i>
          </div>
          <div class="d-flex flex-column gap-3">
            <div class="home-menu-label text-gray-95 highlight-target-text-white text-nowrap fw-lighter">スケジュール</div>
            <div class="text-gray-30 fw-medium text-nowrap">施術スケジュールの確認･管理</div>
          </div>
        </a>
      </div>

      <div class="home-menu-item">
        <a class="d-flex justify-content-start btn-ex-main bg-gray-98 rounded-2 gap-3" href="{{ route('master.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white fs-2 flex-shrink-0 border-bevel-3" style="background-color:#8FDC00; aspect-ratio: 1/1;">
            <i class="nf nf-oct-gear px-4 text-gray-95 highlight-target-text-white" style="font-size: 3rem;"></i>
          </div>
          <div class="d-flex flex-column gap-3">
            <div class="home-menu-label text-gray-95 highlight-target-text-white text-nowrap fw-lighter">マスター登録</div>
            <div class="text-gray-30 fw-medium text-nowrap">利用者･医師･施術者などの登録･管理</div>
          </div>
        </a>
      </div>

      <div class="home-menu-item">
        <a class="d-flex justify-content-start btn-ex-main bg-gray-98 rounded-2 gap-3" href="{{ route('submaster.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white fs-2 flex-shrink-0 border-bevel-3" style="background-color:#1BAD50; aspect-ratio: 1/1;">
            <i class="nf nf-oct-gear px-4 text-gray-95 highlight-target-text-white" style="font-size: 3rem;"></i>
          </div>
          <div class="d-flex flex-column gap-3">
            <div class="home-menu-label text-gray-95 highlight-target-text-white text-nowrap fw-lighter">サブマスター登録</div>
            <div class="text-gray-30 fw-medium text-nowrap">医療機関･サービス事業者などの登録･管理</div>
          </div>
        </a>
      </div>

      <div class="home-menu-item">
        <a class="d-flex justify-content-start btn-ex-main bg-gray-98 rounded-2 gap-3" href="{{ route('prints.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white fs-2 flex-shrink-0 border-bevel-3" style="background-color:#00CAC0; aspect-ratio: 1/1;">
            <i class="nf nf-md-printer_outline px-4 text-gray-95 highlight-target-text-white" style="font-size: 3rem;"></i>
          </div>
          <div class="d-flex flex-column gap-3">
            <div class="home-menu-label text-gray-95 highlight-target-text-white text-nowrap fw-lighter">印刷メニュー</div>
            <div class="text-gray-30 fw-medium text-nowrap">各種文書のPDF出力</div>
          </div>
        </a>
      </div>

      <div class="home-menu-item">
        <a class="d-flex justify-content-start btn-ex-main bg-gray-98 rounded-2 gap-3" href="{{ route('therapy-periods.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white fs-2 flex-shrink-0 border-bevel-3" style="background-color:#1F91CE; aspect-ratio: 1/1;">
            <i class="nf nf-fa-list px-4 text-gray-95 highlight-target-text-white" style="font-size: 3rem;"></i>
          </div>
          <div class="d-flex flex-column gap-3">
            <div class="home-menu-label text-gray-95 highlight-target-text-white text-nowrap fw-lighter">要加療期間リスト</div>
            <div class="text-gray-30 fw-medium text-nowrap">要加療期間の確認･管理</div>
          </div>
        </a>
      </div>

      <div class="home-menu-item">
        <a class="d-flex justify-content-start btn-ex-main bg-gray-98 rounded-2 gap-3" href="{{ route('deposits.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white fs-2 flex-shrink-0 border-bevel-3" style="background-color:#B164E4; aspect-ratio: 1/1;">
            <i class="nf nf-fa-yen px-4 text-gray-95 highlight-target-text-white" style="font-size: 3rem;"></i>
          </div>
          <div class="d-flex flex-column gap-3">
            <div class="home-menu-label text-gray-95 highlight-target-text-white text-nowrap fw-lighter">入金管理</div>
            <div class="text-gray-30 fw-medium text-nowrap">入金情報の入力･管理</div>
          </div>
        </a>
      </div>

    </div>
  </div>

</x-app-layout>
