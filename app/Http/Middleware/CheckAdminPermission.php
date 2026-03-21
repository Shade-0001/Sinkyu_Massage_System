<?php
//-- app/Http/Middleware/CheckAdminPermission.php --//

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminPermission
{
  public function handle(Request $request, Closure $next): Response
  {
    if (Auth::check() && Auth::user()->is_admin !== 1) {
      abort(403, '管理者権限を有するユーザーのみアクセス可能です。');
    }

    return $next($request);
  }
}
