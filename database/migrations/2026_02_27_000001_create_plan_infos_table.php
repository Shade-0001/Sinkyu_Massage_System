<?php
// database/migrations/2026_02_27_000001_create_plan_infos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  protected $connection = 'sinkyu_massage_system_db';

  public function up(): void
  {
    Schema::connection('sinkyu_massage_system_db')->create('plan_infos', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->integer('clinic_user_id')->nullable();
      $table->date('evaluation_date')->nullable();
      $table->string('evaluator')->nullable();
      $table->string('respiration')->nullable();
      $table->string('meal_assistance_level')->nullable();
      $table->text('meal_assistance_note')->nullable();
      $table->string('mobility_level')->nullable();
      $table->text('mobility_note')->nullable();
      $table->string('grooming_level')->nullable();
      $table->text('grooming_note')->nullable();
      $table->string('toilet_level')->nullable();
      $table->text('toilet_note')->nullable();
      $table->string('bathing_level')->nullable();
      $table->text('bathing_note')->nullable();
      $table->string('flat_walking_level')->nullable();
      $table->text('flat_walking_note')->nullable();
      $table->string('stairs_level')->nullable();
      $table->text('stairs_note')->nullable();
      $table->string('dressing_level')->nullable();
      $table->text('dressing_note')->nullable();
      $table->string('defecation_level')->nullable();
      $table->text('defecation_note')->nullable();
      $table->string('urination_level')->nullable();
      $table->text('urination_note')->nullable();
      $table->text('communication')->nullable();
      $table->text('patient_family_request')->nullable();
      $table->text('treatment_purpose')->nullable();
      $table->text('rehabilitation_program')->nullable();
      $table->text('home_rehabilitation')->nullable();
      $table->text('improvement_changes')->nullable();
      $table->text('disability_notes')->nullable();
      $table->date('consent_date')->nullable();

      $table->foreign('clinic_user_id')
        ->references('id')
        ->on('clinic_users')
        ->onDelete('cascade');
    });
  }

  public function down(): void
  {
    Schema::connection('sinkyu_massage_system_db')->dropIfExists('plan_infos');
  }
};
