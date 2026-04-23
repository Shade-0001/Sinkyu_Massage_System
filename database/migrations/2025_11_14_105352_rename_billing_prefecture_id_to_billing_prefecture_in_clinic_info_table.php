<?php
//-- database/migrations/2025_11_14_105352_rename_billing_prefecture_id_to_billing_prefecture_in_clinic_info_table.php --//

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::connection(env('DB_CONNECTION', 'mysql'))->table('clinic_info', function (Blueprint $table) {
      // カラム名を変更してstring型に変換
      $table->renameColumn('billing_prefecture_id', 'billing_prefecture');
    });

    Schema::connection(env('DB_CONNECTION', 'mysql'))->table('clinic_info', function (Blueprint $table) {
      // string型に変更
      $table->string('billing_prefecture')->nullable()->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::connection(env('DB_CONNECTION', 'mysql'))->table('clinic_info', function (Blueprint $table) {
      // int型に戻す
      $table->integer('billing_prefecture')->nullable()->change();
    });

    Schema::connection(env('DB_CONNECTION', 'mysql'))->table('clinic_info', function (Blueprint $table) {
      // カラム名を元に戻す
      $table->renameColumn('billing_prefecture', 'billing_prefecture_id');
    });
  }
};
