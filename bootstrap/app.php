<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\TrimStrings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/index');

        // セッション有効性チェックミドルウェアをwebグループに追加
        $middleware->web(append: [
            \App\Http\Middleware\CheckSessionValidity::class,
        ]);

        // contentフィールドはトリム対象外（全角スペースを保持するため）
        TrimStrings::except(['content']);

        // ミドルウェアエイリアス登録
        $middleware->alias([
            'admin' => \App\Http\Middleware\CheckAdminPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
