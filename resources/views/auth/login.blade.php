<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ログイン｜鍼灸マッサージ管理システム v1.0.0</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  @vite(['resources/css/app.css'])
</head>
<body class="bg-secondary-subtle d-flex flex-column vh-100">

    <div class="h-25"></div>

    <div class="d-flex flex-column align-items-center">
      <h1 class="text-center fs-4 fw-bold mb-5 opacity-90">鍼灸マッサージ管理システム｜v1.0.0</h1>
      <div style="width: 100%; max-width: 350px;">
        <div class="card bg-white rounded-1 shadow-sm">
          <div class="card-body p-4">
            @if ($errors->any())
              <div class="alert alert-danger py-2 mb-3">
                <ul class="mb-0 ps-3 small">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
              @csrf

              <div class="mb-3">
                <label for="login_id" class="form-label small fw-medium text-dark">ユーザーID</label>
                <input id="login_id" type="text" name="login_id"
                  class="form-control bg-light rounded-1 @error('login_id') is-invalid @enderror"
                  value="{{ old('login_id') }}" required autofocus>
                @error('login_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label for="password" class="form-label small fw-medium text-dark">パスワード</label>
                <input id="password" type="password" name="password"
                  class="form-control bg-light rounded-1 @error('password') is-invalid @enderror"
                  required>
                @error('password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="remember" id="remember">
                  <label class="form-check-label small" for="remember">ログイン状態を保持</label>
                </div>
              </div>

              <div class="d-flex justify-content-center py-3">
                <button type="submit" class="btn-ex btn-ex-blue w-50">ログイン</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    localStorage.removeItem('sidebarState');
    localStorage.removeItem('submenuStates');
  </script>
</body>
</html>
