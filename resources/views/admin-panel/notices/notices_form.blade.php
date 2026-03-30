<x-app-layout>
  @section('title', $page_header_title)
  @php
    $breadcrumb_key = match($mode) {
      'edit'      => 'notices.edit',
      'duplicate' => 'notices.duplicate',
      default     => 'notices.create',
    };
    $action = match($mode) {
      'edit'      => route('notices.update', ['id' => $notice->id]),
      'duplicate' => route('notices.duplicate.store'),
      default     => route('notices.store'),
    };
  @endphp

  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate($breadcrumb_key)"
  />

  <br>

  @if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  <form action="{{ $action }}" method="POST">
  @csrf

  <div class="mb-3">
    <label for="title" class="form-label fw-bold">タイトル <span class="text-danger">*</span></label>
    <input
      type="text"
      id="title"
      name="title"
      class="form-control @error('title') is-invalid @enderror"
      value="{{ old('title', $notice->title ?? '') }}"
      required
    >
    @error('title')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label for="content" class="form-label fw-bold">内容 <span class="text-danger">*</span></label>
    <textarea
      id="content"
      name="content"
      class="form-control @error('content') is-invalid @enderror"
      rows="10"
      required
    >{{ old('content', $notice->content ?? '') }}</textarea>
    @error('content')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn-ex btn-ex-blue">
      @if($mode === 'edit') 更新する
      @elseif($mode === 'duplicate') 複製して登録する
      @else 登録する
      @endif
    </button>
    <a href="{{ route('notices.index') }}" class="btn-ex btn-ex-gray">一覧へ戻る</a>
  </div>

  </form>
</x-app-layout>
