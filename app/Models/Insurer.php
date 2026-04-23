<?php
// app/Models/Insurer.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insurer extends Model
{
  use HasFactory;

  protected $connection = null;
  protected $table = 'insurers';

  protected $fillable = [
  'insurer_number',
  'insurer_name',
  'postal_code',
  'address',
  'recipient_name'
  ];

  public function getInsurerCategoryAttribute(): string
  {
    $number = $this->insurer_number ?? '';
    $prefix = (int) substr($number, 0, 2);

    if ($prefix === 6) return '協会けんぽ';
    if ($prefix >= 13 && $prefix <= 19) return '組合健保';
    if ($prefix >= 31 && $prefix <= 34) return '国民健康保険';
    if ($prefix === 39) return '後期高齢者医療';
    if ($prefix === 67) return '国保組合';
    if ($prefix >= 72 && $prefix <= 75) return '共済組合';
    if ($prefix === 2) return '船員保険';

    return '保険';
  }
}
