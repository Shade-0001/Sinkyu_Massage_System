<x-app-layout>
  <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 40vh;">
    <h2 class="mb-3">セッションの有効期限切れ</h2>
    <p class="text-muted">ページの有効期限が切れました。再度お試しください。</p>
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="btn-ex-main btn-ex-gray mt-2">戻る</a>
  </div>
</x-app-layout>
