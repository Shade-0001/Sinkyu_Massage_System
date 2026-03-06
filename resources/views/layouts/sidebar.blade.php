<!-- resources/views/layouts/sidebar.blade.php -->


<aside id="sidebar" class="sidebar border-end border-secondary border-2 bg-body-secondary">
  <div class="border-bottom border-secondary border-2 px-3 py-2">
    <div class="d-flex flex-column gap-2">
      <div class="small text-dark-emphasis">ログインユーザー名<br>：<b>{{ Auth::user()->name }}</b></div>
      <div class="small text-dark-emphasis">
        @if(Auth::user()->last_login_at)
          前回ログイン日時<br>：{{ Auth::user()->last_login_at->format('Y/m/d H:i') }}
        @else
          前回ログイン：
        @endif
      </div>
    </div>
  </div>

  <nav class="p-0">
    <ul class="list-unstyled p-0 m-0">
      <li class="border-bottom border-secondary">
        <a href="{{ route('records.index') }}" class="sidebar-link"><i class="nf nf-fa-edit"></i> 実績データ</a>
      </li>
      <li class="border-bottom border-secondary">
        <a href="{{ route('reports.index') }}" class="sidebar-link"><i class="nf nf-fa-file_text_o"></i> 報告書データ</a>
      </li>
      <li class="border-bottom border-secondary">
        <a href="{{ route('schedules.index') }}" class="sidebar-link"><i class="nf nf-md-calendar_month_outline"></i> スケジュール</a>
      </li>
      <li class="border-bottom border-secondary">
        <div class="sidebar-link sidebar-submenu-toggle" data-target="master-submenu">
          <span><i class="nf nf-oct-gear"></i> マスター登録</span>
          <span class="submenu-arrow"><i class="nf nf-fa-chevron_right"></i></span>
        </div>
        <ul id="master-submenu" class="submenu">
          <li><a href="{{ route('clinic-users.index') }}" class="submenu-link">利用者</a></li>
          <li><a href="{{ route('doctors.index') }}" class="submenu-link">医師</a></li>
          <li><a href="{{ route('therapists.index') }}" class="submenu-link">施術者</a></li>
          <li><a href="{{ route('caremanagers.index') }}" class="submenu-link">ケアマネ</a></li>
          <li><a href="{{ route('clinic-info.index') }}" class="submenu-link">自社情報</a></li>
          <li><a href="{{ route('master.documents.index') }}" class="submenu-link">文面編集</a></li>
          <li><a href="{{ route('master.treatment-fees.index') }}" class="submenu-link">施術料金</a></li>
          <li><a href="{{ route('master.self-fees.index') }}" class="submenu-link">自費施術料金</a></li>
          <li><a href="{{ route('master.document-association.index') }}" class="submenu-link">登録済み標準文書の確認･関連付け</a></li>
        </ul>
      </li>
      <li class="border-bottom border-secondary">
        <div class="sidebar-link sidebar-submenu-toggle" data-target="submaster-submenu">
          <span><i class="nf nf-oct-gear"></i> サブマスター登録</span>
          <span class="submenu-arrow"><i class="nf nf-fa-chevron_right"></i></span>
        </div>
        <ul id="submaster-submenu" class="submenu">
          <li><a href="{{ route('submaster.medical-institutions') }}" class="submenu-link">医療機関名</a></li>
          <li><a href="{{ route('submaster.service-providers') }}" class="submenu-link">サービス事業者名</a></li>
          <li><a href="{{ route('submaster.conditions') }}" class="submenu-link">発病負傷経過（あんま・マッサージ）</a></li>
          <li><a href="{{ route('submaster.illnesses-massage') }}" class="submenu-link">傷病名（あんま・マッサージ）</a></li>
        </ul>
      </li>
      <li class="border-bottom border-secondary">
        <a href="{{ route('prints.index') }}" class="sidebar-link"><i class="nf nf-md-printer_outline"></i> 印刷メニュー</a>
      </li>
      <li class="border-bottom border-secondary">
        <a href="{{ route('therapy-periods.index') }}" class="sidebar-link"><i class="nf nf-fa-list"></i> 要加療期間リスト</a>
      </li>
      <li class="border-bottom border-secondary">
        <a href="{{ route('deposits.index') }}" class="sidebar-link"><i class="nf nf-fa-yen"></i> 入金管理</a>
      </li>
    </ul>
  </nav>
</aside>
