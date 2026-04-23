<?php
// app/Models/HousecallReason.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HousecallReason extends Model
{
  use HasFactory;

  protected $connection = null;
  protected $table = 'housecall_reasons';

  protected $fillable = [
    'housecall_reason'
  ];
}
