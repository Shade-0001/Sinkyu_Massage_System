<div class="border-bottom border-secondary border-2 px-3 py-2">
    <div class="d-flex flex-column gap-2">
      <div class="small text-light"><b>{{ Auth::user()->name }}</b>&nbsp;&nbsp;様</div>
      <div class="small text-light">
        @if(Auth::user()->last_login_at)
          <small>前回ログイン日時：</small><br><b>{{ Auth::user()->last_login_at->format('Y/m/d') }}&nbsp;&nbsp;{{ Auth::user()->last_login_at->format('H:i') }}</b>
        @endif
      </div>
    </div>
  </div>

  <nav class="p-0">
    <ul class="list-unstyled p-0 m-0">
      <li class="border-bottom border-secondary">
        <a href="{{ route('records.index') }}" class="sidebar-link text-light p-3 d-block text-decoration-none fw-medium text-nowrap user-select-none"><i class="nf nf-fa-edit me-1"></i> 実績データ</a>
      </li>
      <li class="border-bottom border-secondary">
        <a href="{{ route('reports.index') }}" class="sidebar-link text-light p-3 d-block text-decoration-none fw-medium text-nowrap user-select-none"><i class="nf nf-fa-file_text_o me-1"></i> 報告書データ</a>
      </li>
      <li class="border-bottom border-secondary">
        <a href="{{ route('schedules.index') }}" class="sidebar-link text-light p-3 d-block text-decoration-none fw-medium text-nowrap user-select-none"><i class="nf nf-md-calendar_month_outline me-1"></i> スケジュール</a>
      </li>
      <li class="border-bottom border-secondary">
        <div class="sidebar-link text-light p-3 sidebar-submenu-toggle fw-medium user-select-none d-flex justify-content-between align-items-center text-nowrap" data-target="master-submenu">
          <span><i class="nf nf-oct-gear me-1"></i> マスター登録</span>
          <span class="submenu-arrow"><i class="nf nf-md-chevron_down"></i></span>
        </div>
        <ul id="master-submenu" class="submenu">
          <li><a href="{{ route('clinic-users.index') }}" class="submenu-link d-block text-decoration-none fw-normal py-1 px-3 small text-white-50 user-select-none">利用者</a></li>
          <li><a href="{{ route('doctors.index') }}" class="submenu-link d-block text-decoration-none fw-normal py-1 px-3 small text-white-50 user-select-none">医師</a></li>
          <li><a href="{{ route('therapists.index') }}" class="submenu-link d-block text-decoration-none fw-normal py-1 px-3 small text-white-50 user-select-none">施術者</a></li>
          <li><a href="{{ route('caremanagers.index') }}" class="submenu-link d-block text-decoration-none fw-normal py-1 px-3 small text-white-50 user-select-none">ケアマネ</a></li>
          <li><a href="{{ route('clinic-info.index') }}" class="submenu-link d-block text-decoration-none fw-normal py-1 px-3 small text-white-50 user-select-none">自社情報</a></li>
          <li><a href="{{ route('master.documents.index') }}" class="submenu-link d-block text-decoration-none fw-normal py-1 px-3 small text-white-50 user-select-none">文面編集</a></li>
          <li><a href="{{ route('master.treatment-fees.index') }}" class="submenu-link d-block text-decoration-none fw-normal py-1 px-3 small text-white-50 user-select-none">施術料金</a></li>
          <li><a href="{{ route('master.self-fees.index') }}" class="submenu-link d-block text-decoration-none fw-normal py-1 px-3 small text-white-50 user-select-none">自費施術料金</a></li>
          <li><a href="{{ route('master.document-association.index') }}" class="submenu-link d-block text-decoration-none fw-normal py-1 px-3 small text-white-50 user-select-none">登録済み標準文書の確認･関連付け</a></li>
        </ul>
      </li>
      <li class="border-bottom border-secondary">
        <div class="sidebar-link text-light p-3 sidebar-submenu-toggle fw-medium user-select-none d-flex justify-content-between align-items-center text-nowrap" data-target="submaster-submenu">
          <span><i class="nf nf-oct-gear me-1"></i> サブマスター登録</span>
          <span class="submenu-arrow"><i class="nf nf-md-chevron_down"></i></span>
        </div>
        <ul id="submaster-submenu" class="submenu">
          <li><a href="{{ route('submaster.medical-institutions') }}" class="submenu-link d-block text-decoration-none fw-normal py-1 px-3 small text-white-50 user-select-none">医療機関名</a></li>
          <li><a href="{{ route('submaster.service-providers') }}" class="submenu-link d-block text-decoration-none fw-normal py-1 px-3 small text-white-50 user-select-none">サービス事業者名</a></li>
          <li><a href="{{ route('submaster.conditions') }}" class="submenu-link d-block text-decoration-none fw-normal py-1 px-3 small text-white-50 user-select-none">発病負傷経過（あんま・マッサージ）</a></li>
          <li><a href="{{ route('submaster.illnesses-massage') }}" class="submenu-link d-block text-decoration-none fw-normal py-1 px-3 small text-white-50 user-select-none">傷病名（あんま・マッサージ）</a></li>
        </ul>
      </li>
      <li class="border-bottom border-secondary">
        <a href="{{ route('prints.index') }}" class="sidebar-link text-light p-3 d-block text-decoration-none fw-medium text-nowrap user-select-none"><i class="nf nf-md-printer_outline me-1"></i> 印刷メニュー</a>
      </li>
      <li class="border-bottom border-secondary">
        <a href="{{ route('therapy-periods.index') }}" class="sidebar-link text-light p-3 d-block text-decoration-none fw-medium text-nowrap user-select-none"><i class="nf nf-fa-list me-1"></i> 要加療期間リスト</a>
      </li>
      <li class="border-bottom border-secondary">
        <a href="{{ route('deposits.index') }}" class="sidebar-link text-light p-3 d-block text-decoration-none fw-medium text-nowrap user-select-none"><i class="nf nf-fa-yen me-1"></i> 入金管理</a>
      </li>
    </ul>
  </nav>

{{-- サブメニュー描画前に展開状態を復元（フリッカー防止） --}}
<script>
  (function() {
    var states = JSON.parse(localStorage.getItem('submenuStates') || '{}');
    Object.keys(states).forEach(function(id) {
      if (!states[id]) return;
      var el = document.getElementById(id);
      if (!el) return;
      el.classList.add('open');
      el.style.maxHeight = 'none';
      var toggle = document.querySelector('[data-target="' + id + '"]');
      if (toggle) {
        var arrow = toggle.querySelector('.submenu-arrow');
        if (arrow) arrow.classList.add('rotated');
      }
    });
  })();
</script>
