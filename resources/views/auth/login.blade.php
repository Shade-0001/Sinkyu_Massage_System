<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ログイン</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-secondary-subtle d-flex align-items-center justify-content-center min-vh-100">

  <div style="width: 100%; max-width: 250px;">
    <div class="card bg-white rounded-2 shadow-sm">
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
            <label for="login_id" class="form-label small fw-medium">ユーザーID</label>
            <input id="login_id" type="text" name="login_id"
              class="form-control @error('login_id') is-invalid @enderror"
              value="{{ old('login_id') }}" required autofocus>
            @error('login_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="password" class="form-label small fw-medium">パスワード</label>
            <input id="password" type="password" name="password"
              class="form-control @error('password') is-invalid @enderror"
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

          <div class="d-grid">
            <button type="submit" class="btn btn-primary fw-bold">ログイン</button>
          </div>
        </form>

      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
