<?php
// app/Models/Plan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
  use HasFactory;

  protected $connection = 'sinkyu_massage_system_db';
  protected $table = 'plans';

  protected $fillable = [
    'clinic_user_id',
    'assessment_date',
    'assessor',
    'audience',
    'eating_assistance_level_id',
    'eating_assistance_note',
    'moving_assistance_level_id',
    'moving_assistance_note',
    'personal_grooming_assistance_level_id',
    'personal_grooming_assistance_note',
    'using_toilet_assistance_level_id',
    'using_toilet_assistance_note',
    'bathing_assistance_level_id',
    'bathing_assistance_note',
    'walking_assistance_level_id',
    'walking_assistance_note',
    'using_stairs_assistance_level_id',
    'using_stairs_assistance_note',
    'changing_clothes_assistance_level_id',
    'changing_clothes_assistance_note',
    'defecation_assistance_level_id',
    'defecation_assistance_note',
    'urination_assistance_level_id',
    'urination_assistance_note',
    'communication_note',
    'wish_of_user_and_familiy',
    'care_purpose',
    'rehabilitation_program',
    'home_rehabilitation',
    'change_since_previous_planning',
    'note',
    'user_and_family_consent_date'
  ];

  protected $casts = [
    'assessment_date' => 'date',
    'user_and_family_consent_date' => 'date'
  ];

  public function clinicUser()
  {
    return $this->belongsTo(ClinicUser::class, 'clinic_user_id');
  }

  // 介助レベルリレーション
  public function eatingAssistanceLevel()
  {
    return $this->belongsTo(AssistanceLevel::class, 'eating_assistance_level_id');
  }

  public function movingAssistanceLevel()
  {
    return $this->belongsTo(AssistanceLevel::class, 'moving_assistance_level_id');
  }

  public function personalGroomingAssistanceLevel()
  {
    return $this->belongsTo(AssistanceLevel::class, 'personal_grooming_assistance_level_id');
  }

  public function usingToiletAssistanceLevel()
  {
    return $this->belongsTo(AssistanceLevel::class, 'using_toilet_assistance_level_id');
  }

  public function bathingAssistanceLevel()
  {
    return $this->belongsTo(AssistanceLevel::class, 'bathing_assistance_level_id');
  }

  public function walkingAssistanceLevel()
  {
    return $this->belongsTo(AssistanceLevel::class, 'walking_assistance_level_id');
  }

  public function usingStairsAssistanceLevel()
  {
    return $this->belongsTo(AssistanceLevel::class, 'using_stairs_assistance_level_id');
  }

  public function changingClothesAssistanceLevel()
  {
    return $this->belongsTo(AssistanceLevel::class, 'changing_clothes_assistance_level_id');
  }

  public function defecationAssistanceLevel()
  {
    return $this->belongsTo(AssistanceLevel::class, 'defecation_assistance_level_id');
  }

  public function urinationAssistanceLevel()
  {
    return $this->belongsTo(AssistanceLevel::class, 'urination_assistance_level_id');
  }

  /**
   * バーセルインデックスに基づく ADL 合計スコアを算出
   * 1項目も入力されていない場合は null を返す
   */
  public function getAdlTotalAttribute(): ?int
  {
    $scoreMap = [
      'eating_assistance_level_id'              => [2 => 0, 3 => 5,  8 => 10],
      'moving_assistance_level_id'              => [2 => 0, 4 => 5,  6 => 10, 8 => 15],
      'personal_grooming_assistance_level_id'   => [1 => 0, 8 => 5],
      'using_toilet_assistance_level_id'        => [2 => 0, 3 => 5,  8 => 10],
      'bathing_assistance_level_id'             => [1 => 0, 8 => 5],
      'walking_assistance_level_id'             => [5 => 0, 7 => 5,  6 => 10, 8 => 15],
      'using_stairs_assistance_level_id'        => [2 => 0, 3 => 5,  8 => 10],
      'changing_clothes_assistance_level_id'    => [5 => 0, 3 => 5,  8 => 10],
      'defecation_assistance_level_id'          => [2 => 0, 1 => 5,  8 => 10],
      'urination_assistance_level_id'           => [2 => 0, 1 => 5,  9 => 10],
    ];

    $total  = 0;
    $hasAny = false;

    foreach ($scoreMap as $field => $scores) {
      $levelId = $this->$field;
      if ($levelId !== null && isset($scores[$levelId])) {
        $total += $scores[$levelId];
        $hasAny = true;
      }
    }

    return $hasAny ? $total : null;
  }
}
