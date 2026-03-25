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
    <label for="name" class="form-label fw-bold">名前 <span class="text-danger">*</span></label>
    <input
      type="text"
      id="name"
      name="name"
      class="form-control @error('name') is-invalid @enderror"
      value="{{ old('name', $systemUser->name ?? '') }}"
      required
    >
    @error('name')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label for="login_id" class="form-label fw-bold">ログインID <span class="text-danger">*</span></label>
    <input
      type="text"
      id="login_id"
      name="login_id"
      class="form-control @error('login_id') is-invalid @enderror"
      value="{{ old('login_id', $systemUser->login_id ?? '') }}"
      required
    >
    @error('login_id')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label for="plain_password" class="form-label fw-bold">パスワード <span class="text-danger">*</span></label>
    @if($mode === 'edit')
    <div class="form-text mb-1 text-muted">現在のパスワードを入力してください（変更する場合は新しいパスワードを入力）</div>
    @endif
    <input
      type="text"
      id="plain_password"
      name="plain_password"
      class="form-control @error('plain_password') is-invalid @enderror"
      value="{{ old('plain_password', $mode === 'edit' ? ($systemUser->plain_password ?? '') : '') }}"
      required
    >
    @error('plain_password')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label for="is_admin" class="form-label fw-bold">権限 <span class="text-danger">*</span></label>
    <select
      id="is_admin"
      name="is_admin"
      class="form-select @error('is_admin') is-invalid @enderror"
      required
    >
      <option value="" disabled {{ old('is_admin', $systemUser->is_admin ?? '') === '' ? 'selected' : '' }}>選択してください</option>
      <option value="0" {{ old('is_admin', $systemUser->is_admin ?? '') === 0 || old('is_admin', $systemUser->is_admin ?? '') === '0' ? 'selected' : '' }}>通常</option>
      <option value="1" {{ old('is_admin', $systemUser->is_admin ?? '') === 1 || old('is_admin', $systemUser->is_admin ?? '') === '1' ? 'selected' : '' }} {{ !Auth::user()->is_admin ? 'disabled' : '' }}>管理者</option>
    </select>
    @error('is_admin')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn-custom btn-custom-blue">
      @if($mode === 'edit') 更新する
      @else 登録する
      @endif
    </button>
    <a href="{{ route('system-users.index') }}" class="btn-custom btn-custom-gray">一覧へ戻る</a>
  </div>

  </form>
</x-app-layout>
