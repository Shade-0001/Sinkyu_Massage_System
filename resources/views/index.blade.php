<x-app-layout>
  @php
    $page_header_title = 'ホーム';
  @endphp
  @section('title', $page_header_title)

  <x-page-header :title="$page_header_title"/>

  <div class="mt-2 mx-1 user-select-none" style="max-width: 1200px;">
    <div id="home-menu" class="d-flex flex-column">
      <div class="flex-fill">
        <a class="d-flex mb-3 bg-gray-98 rounded-2 border-bevel-light hover-bg-white hover-highlight-20" href="{{ route('records.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white m-2 me-3 fs-2 flex-shrink-0 border-bevel-3" style="background-color:#E74C3C; aspect-ratio: 1/1;"><i class="nf nf-md-square_edit_outline px-4 text-gray-95 highlight-target-text-white" style="font-size: 3rem;"></i></div>
          <div>
            <div class="home-menu-label text-gray-95 highlight-target-text-white text-nowrap mt-3">実績データ</div>
            <div class="text-gray-30 fw-medium mt-1 mb-3 text-nowrap">施術実績の入力･管理</div>
          </div>
        </a>
        <a class="d-flex mb-3 bg-gray-98 rounded-2 border-bevel-light hover-bg-white hover-highlight-20" href="{{ route('reports.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white m-2 me-3 fs-2 flex-shrink-0 border-bevel-3" style="background-color:#E67E22; aspect-ratio: 1/1;"><i class="nf nf-md-file_document px-4 text-gray-95 highlight-target-text-white" style="font-size: 3rem;"></i></div>
          <div>
            <div class="home-menu-label text-gray-95 highlight-target-text-white text-nowrap mt-3">報告書データ</div>
            <div class="text-gray-30 fw-medium mt-1 mb-3 text-nowrap">各種報告書の入力･管理</div>
          </div>
        </a>
        <a class="d-flex mb-3 bg-gray-98 rounded-2 border-bevel-light hover-bg-white hover-highlight-20" href="{{ route('schedules.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white m-2 me-3 fs-2 flex-shrink-0 border-bevel-3" style="background-color:#EDBE00; aspect-ratio: 1/1;"><i class="nf nf-md-calendar_month_outline px-4 text-gray-95 highlight-target-text-white" style="font-size: 3rem;"></i></div>
          <div>
            <div class="home-menu-label text-gray-95 highlight-target-text-white text-nowrap mt-3">スケジュール</div>
            <div class="text-gray-30 fw-medium mt-1 mb-3 text-nowrap">施術スケジュールの確認･管理</div>
          </div>
        </a>
        <a class="d-flex mb-3 bg-gray-98 rounded-2 border-bevel-light hover-bg-white hover-highlight-20" href="{{ route('master.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white m-2 me-3 fs-2 flex-shrink-0 border-bevel-3" style="background-color:#8FDC00; aspect-ratio: 1/1;"><i class="nf nf-fa-gear px-4 text-gray-95 highlight-target-text-white" style="font-size: 3rem;"></i></div>
          <div>
            <div class="home-menu-label text-gray-95 highlight-target-text-white text-nowrap mt-3">マスター登録</div>
            <div class="text-gray-30 fw-medium mt-1 mb-3 text-nowrap">利用者･医師･施術者などの登録･管理</div>
          </div>
        </a>
      </div>

      <div class="home-menu-vr d-none flex-fill justify-content-center">
        <div class="vr h-100 border-secondary opacity-25"></div>
      </div>

      <div class="flex-fill">
        <a class="d-flex mb-3 bg-gray-98 rounded-2 border-bevel-light hover-bg-white hover-highlight-20" href="{{ route('submaster.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white m-2 me-3 fs-2 flex-shrink-0 border-bevel-3" style="background-color:#1BAD50; aspect-ratio: 1/1;"><i class="nf nf-fa-gear px-4 text-gray-95 highlight-target-text-white" style="font-size: 3rem;"></i></div>
          <div>
            <div class="home-menu-label text-gray-95 highlight-target-text-white text-nowrap mt-3">サブマスター登録</div>
            <div class="text-gray-30 fw-medium mt-1 mb-3 text-nowrap">医療機関･サービス事業者などの登録･管理</div>
          </div>
        </a>
        <a class="d-flex mb-3 bg-gray-98 rounded-2 border-bevel-light hover-bg-white hover-highlight-20" href="{{ route('prints.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white m-2 me-3 fs-2 flex-shrink-0 border-bevel-3" style="background-color:#00CAC0; aspect-ratio: 1/1;"><i class="nf nf-md-printer px-4 text-gray-95 highlight-target-text-white" style="font-size: 3rem;"></i></div>
          <div>
            <div class="home-menu-label text-gray-95 highlight-target-text-white text-nowrap mt-3">印刷メニュー</div>
            <div class="text-gray-30 fw-medium mt-1 mb-3 text-nowrap">各種文書のPDF出力</div>
          </div>
        </a>
        <a class="d-flex mb-3 bg-gray-98 rounded-2 border-bevel-light hover-bg-white hover-highlight-20" href="{{ route('therapy-periods.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white m-2 me-3 fs-2 flex-shrink-0 border-bevel-3" style="background-color:#1F91CE; aspect-ratio: 1/1;"><i class="nf nf-fa-list px-4 text-gray-95 highlight-target-text-white" style="font-size: 3rem;"></i></div>
          <div>
            <div class="home-menu-label text-gray-95 highlight-target-text-white text-nowrap mt-3">要加療期間リスト</div>
            <div class="text-gray-30 fw-medium mt-1 mb-3 text-nowrap">要加療期間の確認･管理</div>
          </div>
        </a>
        <a class="d-flex mb-3 bg-gray-98 rounded-2 border-bevel-light hover-bg-white hover-highlight-20" href="{{ route('deposits.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white m-2 me-3 fs-2 flex-shrink-0 border-bevel-3" style="background-color:#B164E4; aspect-ratio: 1/1;"><i class="nf nf-fa-yen px-4 text-gray-95 highlight-target-text-white" style="font-size: 3rem;"></i></div>
          <div>
            <div class="home-menu-label text-gray-95 highlight-target-text-white text-nowrap mt-3">入金管理</div>
            <div class="text-gray-30 fw-medium mt-1 mb-3 text-nowrap">入金情報の入力･管理</div>
          </div>
        </a>
      </div>
    </div>
  </div>


</x-app-layout>
