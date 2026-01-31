<?php
// app/Models/ConsentAcupuncture.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsentAcupuncture extends Model
{
  use HasFactory;

  protected $connection = 'sinkyu_massage_system_db';
  protected $table = 'consents_acupuncture';

  protected $fillable = [
    'clinic_user_id',
    'consenting_doctor_name',
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
    'therapy_period_start_date',
    'therapy_period_end_date',
    'first_therapy_content_id',
    'condition',
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
    'therapy_period_start_date' => 'date',
    'therapy_period_end_date' => 'date',
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

  // TODO: bodypartsとのリレーションは中間テーブル作成後に実装
  // 現状では consents_acupuncture 用の中間テーブルが存在しないため、
  // 必要に応じて bodyparts-consents_acupuncture テーブルを作成する必要がある
}
