<x-app-layout>
  <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 40vh;">
    <h2 class="mb-3">403</h2>
    <p class="text-muted">{{ $exception->getMessage() ?: '管理者権限を有するユーザーのみアクセス可能です。' }}</p>
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('index') }}" class="btn-custom btn-custom-gray mt-2">戻る</a>
  </div>
</x-app-layout>
