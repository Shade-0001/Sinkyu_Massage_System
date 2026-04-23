<?php
//-- app/Providers/AppServiceProvider.php --//

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

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
    // Bootstrap 5のページネーションビューを全体で使用する設定
    Paginator::useBootstrapFive();

    // パンくずリストの定義を読み込む
    require_once base_path('routes/breadcrumbs.php');
  }
}
