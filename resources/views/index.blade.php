<!-- resources/views/index.blade.php -->


<x-app-layout>
  @php
    $page_header_title = 'ホーム';
  @endphp

  <x-page-header
    :title="$page_header_title"
  />

  <ul class="list-unstyled ms-3 mt-3">
    <li class="mb-3">
      <a class="text-decoration-none text-reset d-flex align-items-center" href="{{ route('records.index') }}">
        <div class="d-flex align-items-center justify-content-center rounded text-white me-3 fs-2 flex-shrink-0" style="background-color:#e74c3c; width:3.5rem; height:3.5rem;"><i class="nf nf-fa-file_text_o"></i></div>
        <div>
          <div class="fs-5 fw-bold">実績データ</div>
          <div class="text-muted small">施術実績の入力・管理</div>
        </div>
      </a>
    </li>
    <li class="mb-3">
      <a class="text-decoration-none text-reset d-flex align-items-center" href="{{ route('reports.index') }}">
        <div class="d-flex align-items-center justify-content-center rounded text-white me-3 fs-2 flex-shrink-0" style="background-color:#e67e22; width:3.5rem; height:3.5rem;"><i class="nf nf-md-message_reply_text_outline"></i></div>
        <div>
          <div class="fs-5 fw-bold">報告書データ</div>
          <div class="text-muted small">各種報告書の作成・管理</div>
        </div>
      </a>
    </li>
    <li class="mb-3">
      <a class="text-decoration-none text-reset d-flex align-items-center" href="{{ route('schedules.index') }}">
        <div class="d-flex align-items-center justify-content-center rounded text-white me-3 fs-2 flex-shrink-0" style="background-color:#f1c40f; width:3.5rem; height:3.5rem;"><i class="nf nf-md-calendar_month_outline"></i></div>
        <div>
          <div class="fs-5 fw-bold">スケジュール</div>
          <div class="text-muted small">施術スケジュールの確認・管理</div>
        </div>
      </a>
    </li>
    <li class="mb-3">
      <a class="text-decoration-none text-reset d-flex align-items-center" href="{{ route('master.index') }}">
        <div class="d-flex align-items-center justify-content-center rounded text-white me-3 fs-2 flex-shrink-0" style="background-color:#2ecc71; width:3.5rem; height:3.5rem;"><i class="nf nf-fa-edit"></i></div>
        <div>
          <div class="fs-5 fw-bold">マスター登録</div>
          <div class="text-muted small">患者・医師・施術者等の登録・管理</div>
        </div>
      </a>
    </li>
    <li class="mb-3">
      <a class="text-decoration-none text-reset d-flex align-items-center" href="{{ route('prints.index') }}">
        <div class="d-flex align-items-center justify-content-center rounded text-white me-3 fs-2 flex-shrink-0" style="background-color:#3498db; width:3.5rem; height:3.5rem;"><i class="nf nf-md-printer_outline"></i></div>
        <div>
          <div class="fs-5 fw-bold">印刷メニュー</div>
          <div class="text-muted small">各種帳票の印刷</div>
        </div>
      </a>
    </li>
    <li class="mb-3">
      <a class="text-decoration-none text-reset d-flex align-items-center" href="{{ route('therapy-periods.index') }}">
        <div class="d-flex align-items-center justify-content-center rounded text-white me-3 fs-2 flex-shrink-0" style="background-color:#1a6bb5; width:3.5rem; height:3.5rem;"><i class="nf nf-fa-list"></i></div>
        <div>
          <div class="fs-5 fw-bold">要加療期間リスト</div>
          <div class="text-muted small">加療期間の一覧管理</div>
        </div>
      </a>
    </li>
    <li class="mb-3">
      <a class="text-decoration-none text-reset d-flex align-items-center" href="{{ route('deposits.index') }}">
        <div class="d-flex align-items-center justify-content-center rounded text-white me-3 fs-2 flex-shrink-0" style="background-color:#9b59b6; width:3.5rem; height:3.5rem;"><i class="nf nf-fa-yen"></i></div>
        <div>
          <div class="fs-5 fw-bold">入金管理</div>
          <div class="text-muted small">入金・支払いの管理</div>
        </div>
      </a>
    </li>
  </ul>
</x-app-layout>
