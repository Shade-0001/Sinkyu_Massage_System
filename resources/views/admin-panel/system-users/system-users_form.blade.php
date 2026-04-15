<x-app-layout>
  @section('title', $page_header_title)
  @php
    $breadcrumb_key = $mode === 'edit' ? 'system-users.edit' : 'system-users.create';
    $action = $mode === 'edit'
      ? route('system-users.update', ['id' => $systemUser->id])
      : route('system-users.store');
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

    {{-- 名前 --}}
    <div>
      <label class="form-label-tab" for="name">名前 <span class="text-danger">*</span>
        @error('name')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input
          type="text"
          id="name"
          name="name"
          value="{{ old('name', $systemUser->name ?? '') }}"
          required
        >
      </div>
    </div>

    {{-- ログインID --}}
    <div>
      <label class="form-label-tab" for="login_id">ログインID <span class="text-danger">*</span>
        @error('login_id')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <input
          type="text"
          id="login_id"
          name="login_id"
          value="{{ old('login_id', $systemUser->login_id ?? '') }}"
          required
        >
      </div>
    </div>

    {{-- パスワード --}}
    <div>
      <label class="form-label-tab" for="plain_password">パスワード <span class="text-danger">*</span>
        @error('plain_password')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        @if($mode === 'edit')
        <div class="small mb-1 text-muted">現在のパスワードを入力してください（変更する場合は新しいパスワードを入力）</div>
        @endif
        <input
          type="text"
          id="plain_password"
          name="plain_password"
          value="{{ old('plain_password', $mode === 'edit' ? ($systemUser->plain_password ?? '') : '') }}"
          required
        >
      </div>
    </div>

    {{-- 権限 --}}
    <div>
      <label class="form-label-tab" for="is_admin">権限 <span class="text-danger">*</span>
        @error('is_admin')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      </label>
      <div class="form-field px-3 py-2">
        <div class="form-field-top"></div>
        <select
          id="is_admin"
          name="is_admin"
          required
        >
          <option value="" disabled {{ old('is_admin', $systemUser->is_admin ?? '') === '' ? 'selected' : '' }}>選択してください</option>
          <option value="0" {{ old('is_admin', $systemUser->is_admin ?? '') === 0 || old('is_admin', $systemUser->is_admin ?? '') === '0' ? 'selected' : '' }}>通常</option>
          <option value="1" {{ old('is_admin', $systemUser->is_admin ?? '') === 1 || old('is_admin', $systemUser->is_admin ?? '') === '1' ? 'selected' : '' }} {{ !Auth::user()->is_admin ? 'disabled' : '' }}>管理者</option>
        </select>
      </div>
    </div>

    <div class="mt-4 text-end">
      <button type="submit" class="btn-ex-main btn-ex-blue">
        @if($mode === 'edit') 更新する
        @else 登録する
        @endif
      </button>
      <a href="{{ route('system-users.index') }}" class="btn-ex-main btn-ex-gray">一覧へ戻る</a>
    </div>

  </div>

  </form>
</x-app-layout>
