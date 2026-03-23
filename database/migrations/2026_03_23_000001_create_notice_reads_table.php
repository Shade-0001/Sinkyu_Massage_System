<?php
// database/migrations/2026_03_23_000001_create_notice_reads_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  protected $connection = 'sinkyu_massage_system_db';

  public function up(): void
  {
    Schema::connection('sinkyu_massage_system_db')->create('notice_reads', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id');
      $table->unsignedBigInteger('notice_id');
      $table->timestamps();
      $table->unique(['user_id', 'notice_id']);
    });
  }

  public function down(): void
  {
    Schema::connection('sinkyu_massage_system_db')->dropIfExists('notice_reads');
  }
};
