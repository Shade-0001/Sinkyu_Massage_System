<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  protected $connection = null;

  public function up(): void
  {
    Schema::connection(env('DB_CONNECTION', 'mysql'))->table('consents_acupuncture', function (Blueprint $table) {
      $table->renameColumn('condition', 'condition_id');
    });
  }

  public function down(): void
  {
    Schema::connection(env('DB_CONNECTION', 'mysql'))->table('consents_acupuncture', function (Blueprint $table) {
      $table->renameColumn('condition_id', 'condition');
    });
  }
};
