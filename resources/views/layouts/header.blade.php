<div class="d-flex align-items-center gap-3 fw-bold user-select-none" style="height: 2rem;">
  <!-- トグルボタン (menu02) -->
  <button id="sidebar-toggle" class="sidebar-toggle hamburger hamburger--menu02" type="button">
    <span class="hamburger-lines">
      <span class="hamburger-line hamburger-line--top"></span>
      <span class="hamburger-line hamburger-line--middle"></span>
      <span class="hamburger-line hamburger-line--bottom"></span>
    </span>
  </button>

  <div class="vr opacity-50"></div>

  <a href="{{route('index')}}" class="text-light">ホーム</a>

  <div class="vr opacity-50"></div>

  <form method="POST" action="{{ route('logout') }}" class="m-0">
  @csrf
  <a href="{{ route('logout') }}"
    onclick="event.preventDefault(); this.closest('form').submit();"
    class="fw-bold text-decoration-none text-light">
    {{ __('ログアウト') }}
  </a>
  </form>
</div>
