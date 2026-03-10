<x-app-layout>
  @php
    $page_header_title = 'ホーム';
  @endphp

  <x-page-header :title="$page_header_title"/>

  <div class="mt-2 mx-1 user-select-none" style="max-width: 1200px;">
    <div id="home-menu" class="d-flex flex-column">
      <div class="flex-fill">
        <a class="d-flex mb-3 bg-light rounded-2" href="{{ route('records.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white m-2 me-3 fs-2 flex-shrink-0" style="background-color:#e74c3c; aspect-ratio: 1/1;"><i class="nf nf-fa-edit px-4" style="font-size: 3rem;"></i></div>
          <div>
            <div class="home-menu-label text-white text-nowrap mt-3">実績データ</div>
            <div class="text-light-emphasis fw-medium mt-1 mb-3 text-nowrap">施術実績の入力･管理</div>
          </div>
        </a>
        <a class="d-flex mb-3 bg-gray-95 rounded-2 hover-bg-white" href="{{ route('reports.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white m-2 me-3 fs-2 flex-shrink-0" style="background-color:#e67e22; aspect-ratio: 1/1;"><i class="nf nf-fa-file_text_o px-4" style="font-size: 3rem;"></i></div>
          <div>
            <div class="home-menu-label text-white text-nowrap mt-3">報告書データ</div>
            <div class="text-light-emphasis fw-medium mt-1 mb-3 text-nowrap">各種報告書の入力･管理</div>
          </div>
        </a>
        <a class="d-flex mb-3 bg-white rounded-2" href="{{ route('schedules.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white m-2 me-3 fs-2 flex-shrink-0" style="background-color:#f1c40f; aspect-ratio: 1/1;"><i class="nf nf-md-calendar_month_outline px-4" style="font-size: 3rem;"></i></div>
          <div>
            <div class="home-menu-label text-white text-nowrap mt-3">スケジュール</div>
            <div class="text-light-emphasis fw-medium mt-1 mb-3 text-nowrap">施術スケジュールの確認･管理</div>
          </div>
        </a>
        <a class="d-flex mb-3 bg-white rounded-2" href="{{ route('master.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white m-2 me-3 fs-2 flex-shrink-0" style="background-color:#8bc34a; aspect-ratio: 1/1;"><i class="nf nf-oct-gear px-4" style="font-size: 3rem;"></i></div>
          <div>
            <div class="home-menu-label text-white text-nowrap mt-3">マスター登録</div>
            <div class="text-light-emphasis fw-medium mt-1 mb-3 text-nowrap">利用者･医師･施術者などの登録･管理</div>
          </div>
        </a>
      </div>

      <div class="home-menu-vr d-none flex-fill justify-content-center">
        <div class="vr h-100"></div>
      </div>

      <div class="flex-fill">
        <a class="d-flex mb-3 bg-white rounded-2" href="{{ route('submaster.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white m-2 me-3 fs-2 flex-shrink-0" style="background-color:#27ae60; aspect-ratio: 1/1;"><i class="nf nf-oct-gear px-4" style="font-size: 3rem;"></i></div>
          <div>
            <div class="home-menu-label text-white text-nowrap mt-3">サブマスター登録</div>
            <div class="text-light-emphasis fw-medium mt-1 mb-3 text-nowrap">医療機関･サービス事業者などの登録･管理</div>
          </div>
        </a>
        <a class="d-flex mb-3 bg-white rounded-2" href="{{ route('prints.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white m-2 me-3 fs-2 flex-shrink-0" style="background-color:#3498db; aspect-ratio: 1/1;"><i class="nf nf-md-printer_outline px-4" style="font-size: 3rem;"></i></div>
          <div>
            <div class="home-menu-label text-white text-nowrap mt-3">印刷メニュー</div>
            <div class="text-light-emphasis fw-medium mt-1 mb-3 text-nowrap">各種文書のPDF出力</div>
          </div>
        </a>
        <a class="d-flex mb-3 bg-white rounded-2" href="{{ route('therapy-periods.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white m-2 me-3 fs-2 flex-shrink-0" style="background-color:#1a6bb5; aspect-ratio: 1/1;"><i class="nf nf-fa-list px-4" style="font-size: 3rem;"></i></div>
          <div>
            <div class="home-menu-label text-white text-nowrap mt-3">要加療期間リスト</div>
            <div class="text-light-emphasis fw-medium mt-1 mb-3 text-nowrap">要加療期間の確認･管理</div>
          </div>
        </a>
        <a class="d-flex mb-3 bg-white rounded-2" href="{{ route('deposits.index') }}">
          <div class="d-flex align-items-center justify-content-center rounded text-white m-2 me-3 fs-2 flex-shrink-0" style="background-color:#9b59b6; aspect-ratio: 1/1;"><i class="nf nf-fa-yen px-4" style="font-size: 3rem;"></i></div>
          <div>
            <div class="home-menu-label text-white text-nowrap mt-3">入金管理</div>
            <div class="text-light-emphasis fw-medium mt-1 mb-3 text-nowrap">入金情報の入力･管理</div>
          </div>
        </a>
      </div>
    </div>
  </div>


</x-app-layout>
