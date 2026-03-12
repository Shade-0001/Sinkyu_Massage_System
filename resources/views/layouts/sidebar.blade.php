<div class="border-bottom border-secondary border-3 px-3 py-2 text-nowrap">
    <div class="d-flex flex-column gap-2 text-gray-85">
      <div class="small"><b>{{ Auth::user()->name }}</b>&nbsp;&nbsp;様</div>
      <div class="small">
        @if(Auth::user()->last_login_at)
          <small>前回ログイン日時：</small><br>
          <b class="">{{ Auth::user()->last_login_at->format('Y/m/d') }}&nbsp;&nbsp;{{ Auth::user()->last_login_at->format('H:i') }}</b>
        @endif
      </div>
    </div>
  </div>

  <nav>
    <ul class="list-unstyled">
      <li class="border-bottom border-2 border-secondary">
        <a href="{{ route('records.index') }}" class="sidebar-link text-gray-90 hover-highlight-30 highlight-target-text-white p-3 d-block text-decoration-none fw-medium text-nowrap user-select-none"><i class="nf nf-fa-edit me-2"></i>実績データ</a>
      </li>
      <li class="border-bottom border-2 border-secondary">
        <a href="{{ route('reports.index') }}" class="sidebar-link text-gray-90 hover-highlight-30 highlight-target-text-white p-3 d-block text-decoration-none fw-medium text-nowrap user-select-none"><i class="nf nf-fa-file_text_o me-2"></i>報告書データ</a>
      </li>
      <li class="border-bottom border-2 border-secondary">
        <a href="{{ route('schedules.index') }}" class="sidebar-link text-gray-90 hover-highlight-30 highlight-target-text-white p-3 d-block text-decoration-none fw-medium text-nowrap user-select-none"><i class="nf nf-md-calendar_month_outline me-2"></i>スケジュール</a>
      </li>
      <li class="border-bottom border-1 border-secondary">
        <div class="sidebar-link text-gray-90 hover-highlight-30 highlight-target-text-white p-3 sidebar-submenu-toggle fw-medium user-select-none d-flex justify-content-between align-items-center text-nowrap" data-target="master-submenu">
          <span><i class="nf nf-oct-gear me-2"></i>マスター登録</span>
          <span class="submenu-arrow"><i class="nf nf-md-chevron_down"></i></span>
        </div>
        <ul id="master-submenu" class="submenu bg-gray-20 list-unstyled overflow-hidden border-top border-1 border-secondary">
          <li class="border-bottom border-secondary"><a href="{{ route('clinic-users.index') }}" class="submenu-link text-gray-85 hover-highlight-30 highlight-target-text-white small py-2 px-3 d-flex align-items-center text-decoration-none fw-normal user-select-none"><span class="me-2" style="font-size: 0.5rem">◆</span>利用者</a></li>
          <li class="border-bottom border-secondary"><a href="{{ route('doctors.index') }}" class="submenu-link text-gray-85 hover-highlight-30 highlight-target-text-white small py-2 px-3 d-block text-decoration-none fw-normal user-select-none">医師</a></li>
          <li class="border-bottom border-secondary"><a href="{{ route('therapists.index') }}" class="submenu-link text-gray-85 hover-highlight-30 highlight-target-text-white small py-2 px-3 d-block text-decoration-none fw-normal user-select-none">施術者</a></li>
          <li class="border-bottom border-secondary"><a href="{{ route('caremanagers.index') }}" class="submenu-link text-gray-85 hover-highlight-30 highlight-target-text-white small py-2 px-3 d-block text-decoration-none fw-normal user-select-none">ケアマネ</a></li>
          <li class="border-bottom border-secondary"><a href="{{ route('clinic-info.index') }}" class="submenu-link text-gray-85 hover-highlight-30 highlight-target-text-white small py-2 px-3 d-block text-decoration-none fw-normal user-select-none">自社情報</a></li>
          <li class="border-bottom border-secondary"><a href="{{ route('master.documents.index') }}" class="submenu-link text-gray-85 hover-highlight-30 highlight-target-text-white small py-2 px-3 d-block text-decoration-none fw-normal user-select-none">文面編集</a></li>
          <li class="border-bottom border-secondary"><a href="{{ route('master.treatment-fees.index') }}" class="submenu-link text-gray-85 hover-highlight-30 highlight-target-text-white small py-2 px-3 d-block text-decoration-none fw-normal user-select-none">施術料金</a></li>
          <li class="border-bottom border-secondary"><a href="{{ route('master.self-fees.index') }}" class="submenu-link text-gray-85 hover-highlight-30 highlight-target-text-white small py-2 px-3 d-block text-decoration-none fw-normal user-select-none">自費施術料金</a></li>
          <li class="border-bottom border-secondary border-1"><a href="{{ route('master.document-association.index') }}" class="submenu-link text-gray-85 hover-highlight-30 highlight-target-text-white small py-2 px-3 d-block text-decoration-none fw-normal user-select-none">登録済み標準文書の確認･関連付け</a></li>
        </ul>
      </li>
      <li class="border-bottom border-1 border-secondary">
        <div class="sidebar-link text-gray-85 hover-highlight-30 highlight-target-text-white p-3 sidebar-submenu-toggle fw-medium user-select-none d-flex justify-content-between align-items-center text-nowrap" data-target="submaster-submenu">
          <span><i class="nf nf-oct-gear me-2"></i>サブマスター登録</span>
          <span class="submenu-arrow"><i class="nf nf-md-chevron_down"></i></span>
        </div>
        <ul id="submaster-submenu" class="submenu bg-gray-20 list-unstyled overflow-hidden border-top border-1 border-secondary">
          <li class="border-bottom border-1 border-secondary"><a href="{{ route('submaster.medical-institutions') }}" class="submenu-link text-gray-85 hover-highlight-30 highlight-target-text-white small py-2 px-3 d-block text-decoration-none fw-normal user-select-none">医療機関名</a></li>
          <li class="border-bottom border-1 border-secondary"><a href="{{ route('submaster.service-providers') }}" class="submenu-link text-gray-85 hover-highlight-30 highlight-target-text-white small py-2 px-3 d-block text-decoration-none fw-normal user-select-none">サービス事業者名</a></li>
          <li class="border-bottom border-1 border-secondary"><a href="{{ route('submaster.conditions') }}" class="submenu-link text-gray-85 hover-highlight-30 highlight-target-text-white small py-2 px-3 d-block text-decoration-none fw-normal user-select-none">発病負傷経過（あんま･マッサージ）</a></li>
          <li class="border-bottom border-1 border-secondary"><a href="{{ route('submaster.illnesses-massage') }}" class="submenu-link text-gray-85 hover-highlight-30 highlight-target-text-white small py-2 px-3 d-block text-decoration-none fw-normal user-select-none">傷病名（あんま･マッサージ）</a></li>
        </ul>
      </li>
      <li class="border-bottom border-2 border-secondary">
        <a href="{{ route('prints.index') }}" class="sidebar-link text-gray-85 hover-highlight-30 highlight-target-text-white p-3 d-block text-decoration-none fw-medium text-nowrap user-select-none"><i class="nf nf-md-printer_outline me-2"></i>印刷メニュー</a>
      </li>
      <li class="border-bottom border-2 border-secondary">
        <a href="{{ route('therapy-periods.index') }}" class="sidebar-link text-gray-85 hover-highlight-30 highlight-target-text-white p-3 d-block text-decoration-none fw-medium text-nowrap user-select-none"><i class="nf nf-fa-list me-2"></i>要加療期間リスト</a>
      </li>
      <li class="border-bottom border-2 border-secondary">
        <a href="{{ route('deposits.index') }}" class="sidebar-link text-gray-85 hover-highlight-30 highlight-target-text-white p-3 d-block text-decoration-none fw-medium text-nowrap user-select-none"><i class="nf nf-fa-yen me-2"></i>入金管理</a>
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
