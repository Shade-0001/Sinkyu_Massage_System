<?php
//-- app/Http/Controllers/Auth/AuthenticatedSessionController.php --//

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
  /**
   * Display the login view.
   */
  public function create(): View
  {
    return view('auth.login');
  }

  /**
   * Handle an incoming authentication request.
   */
  public function store(LoginRequest $request): RedirectResponse
  {
    Log::info('Login attempt started', [
      'login_id' => $request->input('login_id'),
      'remember' => $request->input('remember'),
      'ip' => $request->ip()
    ]);

    try {
      $request->authenticate();
      Log::info('Authentication successful');
    } catch (\Exception $e) {
      Log::error('Authentication failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      throw $e;
    }

    // 前回ログイン日時を更新
    $user = Auth::user();
    $user->last_login_at = now();
    $user->save();

    $request->session()->regenerate();

    // セッションに「ログイン状態を保持」フラグを保存
    $rememberLogin = $request->boolean('remember');
    $request->session()->put('remember_login', $rememberLogin);

    if ($rememberLogin) {
      // ログイン状態を保持する場合: 72時間（4320分）・タイムアウト無効
      config(['session.lifetime' => 4320]);
      Log::info('Session configured with remember: 72 hours (4320 minutes)');
    } else {
      // ログイン状態を保持しない場合: アイドルタイムアウト（ミドルウェアで制御）
      config(['session.lifetime' => 120]);
      $request->session()->put('last_activity', time());
      Log::info('Session configured without remember: idle timeout enabled');
    }

    Log::info('Session regenerated, redirecting to index');

    return redirect()->route('home');
  }

  /**
   * Destroy an authenticated session.
   */
  public function destroy(Request $request): RedirectResponse
  {
    Auth::guard('web')->logout();

    $request->session()->invalidate();

    $request->session()->regenerateToken();

    return redirect()->route('login');
  }

}
