<div class="px-3 d-flex align-items-center gap-3 fw-bold">
  <!-- サイドバートグルボタン -->
  <button id="sidebar-toggle" type="button" class="my-2 px-5 hover-highlight-30 rounded-1">
    <span></span>
    <span></span>
    <span></span>
  </button>

  <div class="vr opacity-50 my-1"></div>

  <a href="{{route('index')}}" class="text-decoration-none text-gray-90 py-1 px-3 hover-highlight-30 rounded-1">ホーム</a>

  <div class="vr opacity-50 my-1"></div>

  <form method="POST" action="{{ route('logout') }}">
  @csrf
  <a href="{{ route('logout') }}"
    onclick="event.preventDefault(); localStorage.removeItem('sidebarState'); localStorage.removeItem('submenuStates'); this.closest('form').submit();"
    class="fw-bold text-decoration-none text-gray-90 py-1 px-3 hover-highlight-30 rounded-1">
    {{ __('ログアウト') }}
  </a>
  </form>
</div>
