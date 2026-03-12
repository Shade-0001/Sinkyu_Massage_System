<div class="px-3 d-flex align-items-center gap-3 fw-bold">
  <!-- サイドバートグルボタン -->
  <button id="sidebar-toggle" type="button" class="px-2 py-2 hover-highlight-30 rounded-1">
    <div id="sidebar-toggle-icon">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </button>

  <div class="vr opacity-50 my-1"></div>

  <a href="{{route('index')}}" class="text-decoration-none text-gray-90 py-1 px-2 rounded-1 hover-highlight-30 highlight-target-text-white">ホーム</a>

  <div class="vr opacity-50 my-1"></div>

  <div class="ms-auto d-flex align-self-stretch align-items-center gap-3">
    <div class="vr opacity-50 my-1"></div>
    <form method="POST" action="{{ route('logout') }}">
    @csrf
    <a href="{{ route('logout') }}"
      onclick="event.preventDefault(); localStorage.removeItem('sidebarState'); localStorage.removeItem('submenuStates'); this.closest('form').submit();"
      class="fw-bold text-decoration-none text-gray-90 py-1 px-2 rounded-1 hover-highlight-30 highlight-target-text-white">
      {{ __('ログアウト') }}
    </a>
    </form>
  </div>
</div>
