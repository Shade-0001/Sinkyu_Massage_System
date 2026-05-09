<?php
//-- app/Providers/AppServiceProvider.php --//

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\App;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    App::setLocale('ja');

    // Bootstrap 5のページネーションビューを全体で使用する設定
    Paginator::useBootstrapFive();

    if (config('app.env') === 'production') {
      URL::forceScheme('https');
    }

    // パンくずリストの定義を読み込む
    require_once base_path('routes/breadcrumbs.php');
  }
}
