<?php
// app/Models/ClinicUser.php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicUser extends Model
{
  use HasFactory;

  protected $connection = 'sinkyu_massage_system_db';
  protected $table = 'clinic_users';
  
  protected $fillable = [
    'last_name',
    'first_name',
    'last_kana',
    'first_kana',
    'birthday',
    'age',
    'gender_id',
    'postal_code',
    'address_1',
    'address_2',
    'address_3',
    'phone',
    'cell_phone',
    'fax',
    'email',
    'housecall_distance',
    'housecall_additional_distance',
    'is_redeemed',
    'application_count',
    'note'
  ];

  /**
   * フルネームを取得
   */
  public function getFullNameAttribute(): string
  {
    return $this->last_name . $this->first_name;
  }

  /**
   * フルネーム（カナ）を取得
   */
  public function getFullKanaAttribute(): string
  {
    return $this->last_kana . "\u{2000}" . $this->first_kana;
  }

  protected $casts = [
  'birthday' => 'date',
  'is_redeemed' => 'boolean'
  ];
}