<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('consents_massage', function (Blueprint $table) {
      $table->dropColumn(['therapy_period_start_date', 'therapy_period_end_date']);
    });

    Schema::table('consents_acupuncture', function (Blueprint $table) {
      $table->dropColumn(['therapy_period_start_date', 'therapy_period_end_date']);
    });
  }

  public function down(): void
  {
    Schema::table('consents_massage', function (Blueprint $table) {
      $table->date('therapy_period_start_date')->nullable()->after('therapy_period');
      $table->date('therapy_period_end_date')->nullable()->after('therapy_period_start_date');
    });

    Schema::table('consents_acupuncture', function (Blueprint $table) {
      $table->date('therapy_period_start_date')->nullable()->after('therapy_period');
      $table->date('therapy_period_end_date')->nullable()->after('therapy_period_start_date');
    });
  }
};
