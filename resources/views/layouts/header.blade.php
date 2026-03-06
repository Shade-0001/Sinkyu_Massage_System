<!-- resources/views/layouts/header.blade.php -->


<div class="d-flex align-items-center gap-3 fw-bold user-select-none" style="height: 2rem;">
  <!-- トグルボタン -->
  <button id="sidebar-toggle" class="sidebar-toggle">☰</button>

  <div class="divider-vertical"></div>

  <a href="{{route('index')}}">ホーム</a>

  <div class="divider-vertical"></div>

  <form method="POST" action="{{ route('logout') }}" class="m-0">
  @csrf
  <a href="{{ route('logout') }}"
    onclick="event.preventDefault(); this.closest('form').submit();"
    class="fw-bold text-decoration-none">
    {{ __('ログアウト') }}
  </a>
  </form>
</div>
