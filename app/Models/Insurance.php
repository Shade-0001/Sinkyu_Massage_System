<?php
// app/Models/Insurance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insurance extends Model
{
  use HasFactory;

  protected $connection = null;
  protected $table = 'insurances';

  protected $fillable = [
  'clinic_user_id',
  'insurers_id',
  'insurance_type_1_id',
  'insurance_type_2_id',
  'insurance_type_3_id',
  'self_or_family_id',
  'insured_number',
  'code_number',
  'account_number',
  'locality_code',
  'recipient_code',
  'license_acquisition_date',
  'certification_date',
  'issue_date',
  'expenses_borne_ratio_id',
  'expiry_date',
  'is_redeemed',
  'insured_name',
  'relationship_with_clinic_user_id',
  'is_healthcare_subsidized',
  'public_funds_payer_code',
  'public_funds_recipient_code',
  'locality_code_family',
  'recipient_code_family'
  ];

  protected $casts = [
  'license_acquisition_date' => 'date',
  'certification_date' => 'date',
  'issue_date' => 'date',
  'expiry_date' => 'date',
  'is_redeemed' => 'boolean',
  'is_healthcare_subsidized' => 'boolean'
  ];

  // アクセサ: ID から文字列値へのマッピング
  public function getInsuranceType1Attribute()
  {
  $map = [1 => '社･国･組', 2 => '公費', 3 => '後期', 4 => '退職'];
  return $map[$this->insurance_type_1_id] ?? '';
  }

  public function getInsuranceType2Attribute()
  {
  $map = [1 => '単独', 2 => '２併', 3 => '３併'];
  return $map[$this->insurance_type_2_id] ?? '';
  }

  public function getInsuranceType3Attribute()
  {
  $map = [1 => '本外', 2 => '三外', 3 => '家外', 4 => '高外9', 5 => '高外8'];
  return $map[$this->insurance_type_3_id] ?? '';
  }

  public function getInsuredPersonTypeAttribute()
  {
  $map = [1 => '本人', 2 => '六歳', 3 => '家族', 4 => '高齢１', 5 => '高齢', 6 => '高齢７'];
  return $map[$this->self_or_family_id] ?? '';
  }

  public function getSymbolAttribute()
  {
  return $this->code_number;
  }

  public function getNumberAttribute()
  {
  return $this->account_number;
  }

  public function getQualificationDateAttribute()
  {
  return $this->license_acquisition_date;
  }

  public function getExpirationDateAttribute()
  {
  return $this->expiry_date;
  }

  public function getReimbursementTargetAttribute()
  {
  return $this->is_redeemed;
  }

  public function getInsuredPersonNameAttribute()
  {
  return $this->insured_name;
  }

  public function getRelationshipAttribute()
  {
  $map = [1 => '本人', 2 => '家族'];
  return $map[$this->relationship_with_clinic_user_id] ?? '';
  }

  public function getCopaymentRateAttribute()
  {
  $map = [1 => '1割', 2 => '2割', 3 => '3割'];
  return $map[$this->expenses_borne_ratio_id] ?? '';
  }

  public function getMedicalAssistanceTargetAttribute()
  {
  return $this->is_healthcare_subsidized;
  }

  public function getPublicBurdenNumberAttribute()
  {
  return $this->public_funds_payer_code;
  }

  public function getPublicRecipientNumberAttribute()
  {
  return $this->public_funds_recipient_code;
  }

  public function getMunicipalCodeAttribute()
  {
  return $this->locality_code;
  }

  public function getRecipientNumberAttribute()
  {
  return $this->recipient_code;
  }

  public function clinicUser()
  {
  return $this->belongsTo(ClinicUser::class, 'clinic_user_id');
  }

  public function insurer()
  {
  return $this->belongsTo(Insurer::class, 'insurers_id');
  }
}
