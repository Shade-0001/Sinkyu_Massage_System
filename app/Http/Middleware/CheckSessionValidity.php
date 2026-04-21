<?php
//-- app/Http/Middleware/CheckSessionValidity.php --//

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionValidity
{
    // アイドルタイムアウト: 60分
    public const IDLE_TIMEOUT = 60 * 60;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $remember = $request->session()->get('remember_login', false);

            // 「ログイン状態を保持」の場合はタイムアウト無効
            if (!$remember) {
                // タブ/ウィンドウ全閉じ検知：セッションCookieが消えていたらログアウト
                if (!$request->cookie('tab_alive')) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect()->route('login');
                }

                // アイドルタイムアウト（1時間）
                $lastActivity = $request->session()->get('last_activity');
                if ($lastActivity && time() - $lastActivity > self::IDLE_TIMEOUT) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect()->route('login')->with('timeout', true);
                }

                $request->session()->put('last_activity', time());
            }
        }

        return $next($request);
    }
}
