<?php
// app/Models/Illness.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Illness extends Model
{
  use HasFactory;

  protected $connection = null;
  protected $table = 'illnesses_massage';

  protected $fillable = [
    'illness_name'
  ];
}
