<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ログイン</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;700&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Noto Sans JP', sans-serif;
      background-color: #1a1a2e;
      background-image: radial-gradient(ellipse at top, #16213e 0%, #0f0e17 100%);
      min-height: 100vh;
    }
    .login-card {
      border: 1px solid rgba(255, 255, 255, 0.1);
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }
    .login-card .card-header {
      background: rgba(255, 255, 255, 0.07);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .form-control {
      background-color: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #e0e0e0;
    }
    .form-control:focus {
      background-color: rgba(255, 255, 255, 0.12);
      border-color: rgba(99, 179, 237, 0.6);
      color: #ffffff;
      box-shadow: 0 0 0 0.25rem rgba(99, 179, 237, 0.15);
    }
    .form-control::placeholder {
      color: rgba(255, 255, 255, 0.3);
    }
    .form-label {
      color: #b0bec5;
      font-size: 0.875rem;
      font-weight: 500;
    }
    .form-check-label {
      color: #90a4ae;
      font-size: 0.875rem;
    }
    .btn-login {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      font-weight: 500;
      letter-spacing: 0.05em;
      transition: opacity 0.2s ease, transform 0.1s ease;
    }
    .btn-login:hover {
      opacity: 0.9;
      transform: translateY(-1px);
    }
    .btn-login:active {
      transform: translateY(0);
    }
    .app-title {
      color: #e0e0e0;
      font-weight: 700;
      letter-spacing: 0.03em;
    }
    .app-subtitle {
      color: #78909c;
      font-size: 0.8rem;
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">

  <div class="container" style="max-width: 420px;">

    <!-- ロゴ・タイトル -->
    <div class="text-center mb-4">
      <div class="mb-2">
        <span style="font-size: 2.5rem;">💆</span>
      </div>
      <h1 class="app-title h4 mb-1">Sinkyu Massage System</h1>
      <p class="app-subtitle mb-0">鍼灸マッサージ管理システム</p>
    </div>

    <!-- ログインカード -->
    <div class="card login-card shadow-lg rounded-3">
      <div class="card-header py-3 text-center">
        <h2 class="h6 mb-0 text-light fw-semibold">ログイン</h2>
      </div>
      <div class="card-body p-4">

        @if ($errors->any())
          <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <ul class="mb-0 ps-3">
              @foreach ($errors->all() as $error)
                <li style="font-size: 0.875rem;">{{ $error }}</li>
              @endforeach
            </ul>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        <form method="POST" action="{{ route('login') }}" id="loginForm">
          @csrf

          <!-- ユーザーID -->
          <div class="mb-3">
            <label for="login_id" class="form-label">ユーザーID</label>
            <input
              id="login_id"
              type="text"
              name="login_id"
              class="form-control @error('login_id') is-invalid @enderror"
              value="{{ old('login_id') }}"
              placeholder="IDを入力"
              required
              autofocus
            >
            @error('login_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- パスワード -->
          <div class="mb-3">
            <label for="password" class="form-label">パスワード</label>
            <input
              id="password"
              type="password"
              name="password"
              class="form-control @error('password') is-invalid @enderror"
              placeholder="パスワードを入力"
              required
            >
            @error('password')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- ログイン状態を保持 -->
          <div class="mb-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="remember" id="remember">
              <label class="form-check-label" for="remember">ログイン状態を保持</label>
            </div>
          </div>

          <!-- ログインボタン -->
          <div class="d-grid">
            <button type="submit" class="btn btn-login btn-primary py-2 text-white">
              ログイン
            </button>
          </div>
        </form>

      </div>
    </div>

    <p class="text-center mt-4 text-secondary" style="font-size: 0.75rem;">
      &copy; {{ date('Y') }} Sinkyu Massage System. All rights reserved.
    </p>

  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
