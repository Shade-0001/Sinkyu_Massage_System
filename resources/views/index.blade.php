<!-- resources/views/index.blade.php -->


<x-app-layout>
  @php
    $page_header_title = 'ホーム';
  @endphp

  <x-page-header
    :title="$page_header_title"
  />

  <div class="mt-2 mx-1 row-gap-4 user-select-none" style="display:grid; grid-template-columns:1fr 1fr; grid-template-rows:repeat(4, auto); grid-auto-flow:column;">
    <a class="text-decoration-none text-reset d-flex " href="{{ route('records.index') }}">
      <div class="d-flex align-items-center justify-content-center rounded text-white me-3 fs-2 flex-shrink-0" style="background-color:#e74c3c; aspect-ratio: 1/1;"><i class="nf nf-fa-edit" style="font-size: 3rem;"></i></div>
      <div>
        <div class="fs-2 text-outline-black mt-2">実績データ</div>
        <div class="text-light-emphasis fw-medium mt-1 mb-3">施術実績の入力・管理</div>
      </div>
    </a>
    <a class="text-decoration-none text-reset d-flex " href="{{ route('reports.index') }}">
      <div class="d-flex align-items-center justify-content-center rounded text-white me-3 fs-2 flex-shrink-0" style="background-color:#e67e22; aspect-ratio: 1/1;"><i class="nf nf-fa-file_text_o" style="font-size: 3rem;"></i></div>
      <div>
        <div class="fs-2 text-outline-black mt-1">報告書データ</div>
        <div class="text-light-emphasis fw-medium mt-1 mb-3">各種報告書の作成・管理</div>
      </div>
    </a>
    <a class="text-decoration-none text-reset d-flex " href="{{ route('schedules.index') }}">
      <div class="d-flex align-items-center justify-content-center rounded text-white me-3 fs-2 flex-shrink-0" style="background-color:#f1c40f; aspect-ratio: 1/1;"><i class="nf nf-md-calendar_month_outline" style="font-size: 3rem;"></i></div>
      <div>
        <div class="fs-2 text-outline-black mt-1">スケジュール</div>
        <div class="text-light-emphasis fw-medium mt-1 mb-3">施術スケジュールの確認・管理</div>
      </div>
    </a>
    <a class="text-decoration-none text-reset d-flex " href="{{ route('master.index') }}">
      <div class="d-flex align-items-center justify-content-center rounded text-white me-3 fs-2 flex-shrink-0" style="background-color:#8bc34a; aspect-ratio: 1/1;"><i class="nf nf-oct-gear" style="font-size: 3rem;"></i></div>
      <div>
        <div class="fs-2 text-outline-black mt-1">マスター登録</div>
        <div class="text-light-emphasis fw-medium mt-1 mb-3">患者・医師・施術者等の登録・管理</div>
      </div>
    </a>
    <a class="text-decoration-none text-reset d-flex " href="{{ route('submaster.index') }}">
      <div class="d-flex align-items-center justify-content-center rounded text-white me-3 fs-2 flex-shrink-0" style="background-color:#27ae60; aspect-ratio: 1/1;"><i class="nf nf-oct-gear" style="font-size: 3rem;"></i></div>
      <div>
        <div class="fs-2 text-outline-black mt-1">サブマスター登録</div>
        <div class="text-light-emphasis fw-medium mt-1 mb-3">医療機関・サービス提供者等の登録・管理</div>
      </div>
    </a>
    <a class="text-decoration-none text-reset d-flex " href="{{ route('prints.index') }}">
      <div class="d-flex align-items-center justify-content-center rounded text-white me-3 fs-2 flex-shrink-0" style="background-color:#3498db; aspect-ratio: 1/1;"><i class="nf nf-md-printer_outline" style="font-size: 3rem;"></i></div>
      <div>
        <div class="fs-2 text-outline-black mt-1">印刷メニュー</div>
        <div class="text-light-emphasis fw-medium mt-1 mb-3">各種帳票の印刷</div>
      </div>
    </a>
    <a class="text-decoration-none text-reset d-flex " href="{{ route('therapy-periods.index') }}">
      <div class="d-flex align-items-center justify-content-center rounded text-white me-3 fs-2 flex-shrink-0" style="background-color:#1a6bb5; aspect-ratio: 1/1;"><i class="nf nf-fa-list" style="font-size: 3rem;"></i></div>
      <div>
        <div class="fs-2 text-outline-black mt-1">要加療期間リスト</div>
        <div class="text-light-emphasis fw-medium mt-1 mb-3">加療期間の一覧管理</div>
      </div>
    </a>
    <a class="text-decoration-none text-reset d-flex " href="{{ route('deposits.index') }}">
      <div class="d-flex align-items-center justify-content-center rounded text-white me-3 fs-2 flex-shrink-0" style="background-color:#9b59b6; aspect-ratio: 1/1;"><i class="nf nf-fa-yen" style="font-size: 3rem;"></i></div>
      <div>
        <div class="fs-2 text-outline-black mt-1">入金管理</div>
        <div class="text-light-emphasis fw-medium mt-1 mb-3">入金・支払いの管理</div>
      </div>
    </a>
  </div>
</x-app-layout>
