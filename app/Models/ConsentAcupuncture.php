<?php
// app/Models/ConsentAcupuncture.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsentAcupuncture extends Model
{
  use HasFactory;

  protected $connection = null;
  protected $table = 'consents_acupuncture';

  protected $fillable = [
    'clinic_user_id',
    'consenting_doctor_id',
    'consenting_date',
    'consenting_start_date',
    'consenting_end_date',
    'benefit_period_start_date',
    'benefit_period_end_date',
    'first_care_date',
    'reconsenting_expiry',
    'bill_category_id',
    'outcome_id',
    'illness_name_acupuncture_id',
    'illness_name_acupuncture_addendum',
    'is_housecall_required',
    'housecall_reason_id',
    'housecall_reason_addendum',
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
    'is_housecall_required' => 'boolean'
  ];

  public function clinicUser()
  {
    return $this->belongsTo(ClinicUser::class, 'clinic_user_id');
  }

  public function workScopeType()
  {
    return $this->belongsTo(WorkScopeType::class, 'work_scope_type_id');
  }

  public function consentingDoctor()
  {
    return $this->belongsTo(Doctor::class, 'consenting_doctor_id');
  }

  // 注: 鍼灸同意書では部位（bodyparts）のリレーションは使用しない
  // マッサージ同意書とは異なり、鍼灸同意書では illness_name_acupuncture_id フィールドで
  // 傷病名を管理するため、bodyparts との中間テーブルは不要
}
