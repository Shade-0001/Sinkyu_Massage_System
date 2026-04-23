<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  protected $connection = null;

  public function up(): void
  {
    Schema::connection(env('DB_CONNECTION', 'mysql'))->table('insurers', function (Blueprint $table) {
      $table->string('insurer_number', 20)->change();
    });
  }

  public function down(): void
  {
    Schema::connection(env('DB_CONNECTION', 'mysql'))->table('insurers', function (Blueprint $table) {
      $table->integer('insurer_number')->change();
    });
  }
};
