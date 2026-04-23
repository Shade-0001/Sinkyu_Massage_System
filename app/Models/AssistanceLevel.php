<?php
// app/Models/AssistanceLevel.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssistanceLevel extends Model
{
  use HasFactory;

  protected $connection = null;
  protected $table = 'assistance_levels';

  protected $fillable = [
    'assistance_level'
  ];
}
