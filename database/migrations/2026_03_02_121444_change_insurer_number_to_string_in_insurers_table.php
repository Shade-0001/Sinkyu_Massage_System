<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  protected $connection = 'sinkyu_massage_system_db';

  public function up(): void
  {
    Schema::connection('sinkyu_massage_system_db')->table('insurers', function (Blueprint $table) {
      $table->string('insurer_number', 20)->change();
    });
  }

  public function down(): void
  {
    Schema::connection('sinkyu_massage_system_db')->table('insurers', function (Blueprint $table) {
      $table->integer('insurer_number')->change();
    });
  }
};
