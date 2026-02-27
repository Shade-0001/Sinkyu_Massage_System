<?php
// database/migrations/2026_02_27_000002_nullable_address3_in_clinic_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  protected $connection = 'sinkyu_massage_system_db';

  public function up(): void
  {
    Schema::connection('sinkyu_massage_system_db')->table('clinic_users', function (Blueprint $table) {
      $table->string('address_3')->nullable()->change();
    });
  }

  public function down(): void
  {
    Schema::connection('sinkyu_massage_system_db')->table('clinic_users', function (Blueprint $table) {
      $table->string('address_3')->nullable(false)->change();
    });
  }
};
