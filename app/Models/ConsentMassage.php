<?php
// app/Models/ConsentMassage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ConsentMassage extends Model
{
  use HasFactory;

  protected $connection = null;
  protected $table = 'consents_massage';

  protected $fillable = [
    'clinic_user_id',
    'consenting_doctor_id',
    'consenting_date',
    'consenting_start_date',
    'consenting_end_date',
    'benefit_period_start_date',
    'benefit_period_end_date',
    'first_care_date',
    'injury_and_illness_name_id',
    'reconsenting_expiry',
    'bill_category_id',
    'outcome_id',
    'is_symptom_1',
    'is_symptom_2',
    'symtom_2_addendum',
    'is_symptom_3',
    'symtom_3_addendum',
    'is_therapy_type_1',
    'is_therapy_type_2',
    'is_housecall_required',
    'housecall_reason_id',
    'housecall_reason_addendum',
    'care_level',
    'notes',
    'therapy_period',
    'first_therapy_content_id',
    'condition_id',
    'work_scope_type_id',
    'onset_and_injury_date'
  ];

  protected $casts = [
    'consenting_date' => 'date',
    'consenting_start_date' => 'date',
    'consenting_end_date' => 'date',
    'benefit_period_start_date' => 'date',
    'benefit_period_end_date' => 'date',
    'first_care_date' => 'date',
    'reconsenting_expiry' => 'date',
    'onset_and_injury_date' => 'date',
    'is_symptom_1' => 'boolean',
    'is_symptom_2' => 'boolean',
    'is_symptom_3' => 'boolean',
    'is_therapy_type_1' => 'boolean',
    'is_therapy_type_2' => 'boolean',
    'is_housecall_required' => 'boolean'
  ];

  public function clinicUser()
  {
    return $this->belongsTo(ClinicUser::class, 'clinic_user_id');
  }

  // bodypartsとのリレーション
  public function symptom1Bodyparts()
  {
    return $this->belongsToMany(
      Bodypart::class,
      'bodyparts-consents_massage',
      'consents_massage_id',
      'symtom_1_bodyparts_id'
    );
  }

  public function symptom2Bodyparts()
  {
    return $this->belongsToMany(
      Bodypart::class,
      'bodyparts-consents_massage',
      'consents_massage_id',
      'symtom_2_bodyparts_id'
    );
  }

  public function treatmentType1Bodyparts()
  {
    return $this->belongsToMany(
      Bodypart::class,
      'bodyparts-consents_massage',
      'consents_massage_id',
      'therapy_type_1_bodyparts_id'
    );
  }

  public function treatmentType2Bodyparts()
  {
    return $this->belongsToMany(
      Bodypart::class,
      'bodyparts-consents_massage',
      'consents_massage_id',
      'therapy_type_2_bodyparts_id'
    );
  }

  public function consentingDoctor()
  {
    return $this->belongsTo(Doctor::class, 'consenting_doctor_id');
  }

  /**
   * モデルの「起動」メソッド
   */
  protected static function boot()
  {
    parent::boot();

    // 削除時に関連するbodyparts-consents_massageレコードも削除
    static::deleting(function ($consentMassage) {
      \DB::table('bodyparts-consents_massage')
        ->where('consents_massage_id', $consentMassage->id)
        ->delete();
    });
  }
}
