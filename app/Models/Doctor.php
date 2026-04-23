<?php
// app/Models/Doctor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
  use HasFactory;

  protected $connection = null;
  protected $table = 'doctors';

  protected $fillable = [
    'last_name',
    'first_name',
    'last_name_kana',
    'first_name_kana',
    'medical_institutions_id',
    'postal_code',
    'address_1',
    'address_2',
    'address_3',
    'phone',
    'cell_phone',
    'fax',
    'email',
    'note'
  ];

  /**
   * 医師名（姓名フルネーム）を取得するアクセサー
   */
  public function getDoctorNameAttribute()
  {
    $name = trim(($this->last_name ?? '') . "\u{2000}" . ($this->first_name ?? ''));
    return $name ?: null;
  }
}
