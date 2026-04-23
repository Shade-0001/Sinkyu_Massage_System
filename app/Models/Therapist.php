<?php
// app/Models/Therapist.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Therapist extends Model
{
  use HasFactory;

  protected $connection = null;
  protected $table = 'therapists';

  protected $fillable = [
    'therapist_name',
    'furigana',
    'postal_code',
    'address_1',
    'address_2',
    'address_3',
    'phone',
    'cell_phone',
    'fax',
    'email',
    'license_hari_id',
    'license_hari_number',
    'license_hari_issued_date',
    'license_kyu_id',
    'license_kyu_number',
    'license_kyu_issued_date',
    'license_massage_id',
    'license_massage_number',
    'license_massage_issued_date',
    'member_number',
    'note'
  ];
}
