<?php
// app/Models/Condition.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Condition extends Model
{
  use HasFactory;

  protected $connection = null;
  protected $table = 'conditions';

  protected $fillable = [
    'condition_name'
  ];
}
