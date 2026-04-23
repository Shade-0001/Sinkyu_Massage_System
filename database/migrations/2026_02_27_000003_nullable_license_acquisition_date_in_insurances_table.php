<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::connection(env('DB_CONNECTION', 'mysql'))->table('insurances', function (Blueprint $table) {
      $table->date('license_acquisition_date')->nullable()->change();
    });
  }

  public function down(): void
  {
    Schema::connection(env('DB_CONNECTION', 'mysql'))->table('insurances', function (Blueprint $table) {
      $table->date('license_acquisition_date')->nullable(false)->change();
    });
  }
};
