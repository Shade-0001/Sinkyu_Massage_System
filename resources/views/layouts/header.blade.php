<div class="px-3 d-flex align-items-center gap-3 fw-bold">
  <!-- サイドバートグルボタン -->
  <button id="sidebar-toggle" type="button">
    <span></span>
    <span></span>
    <span></span>
  </button>

  <div class="vr opacity-50 my-2"></div>

  <a href="{{route('index')}}" class="text-decoration-none text-light py-3">ホーム</a>

  <div class="vr opacity-50 my-2"></div>

  <form method="POST" action="{{ route('logout') }}">
  @csrf
  <a href="{{ route('logout') }}"
    onclick="event.preventDefault(); this.closest('form').submit();"
    class="fw-bold text-decoration-none text-light">
    {{ __('ログアウト') }}
  </a>
  </form>
</div>
