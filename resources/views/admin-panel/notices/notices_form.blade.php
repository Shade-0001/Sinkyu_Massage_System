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

  <div class="d-flex flex-column gap-4 container ms-0">

    {{-- タイトル --}}
    <div>
      <label class="form-label-tab" for="title">タイトル <span class="text-danger">*</span>
        @error('title')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input
          type="text"
          id="title"
          name="title"
          value="{{ old('title', $notice->title ?? '') }}"
          required
        >
      </div>
    </div>

    {{-- 内容 --}}
    <div>
      <label class="form-label-tab" for="content">内容 <span class="text-danger">*</span>
        @error('content')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <textarea
          id="content"
          name="content"
          rows="10"
          style="width: 100%;"
          required
        >{{ old('content', $notice->content ?? '') }}</textarea>
      </div>
    </div>

    <div class="mt-4 d-flex gap-2 justify-content-end">
      <a href="{{ route('notices.index') }}" class="btn-ex-main btn-ex-gray"><i class="nf nf-fa-caret_left me-1" style="transform: scale(1.2)"></i>戻る</a>
      <button type="submit" class="btn-ex-main btn-ex-green">
        @if($mode === 'edit') 更新する
        @elseif($mode === 'duplicate') 複製して登録する
        @else 登録する
        @endif
      </button>
    </div>

  </div>

  </form>
</x-app-layout>
