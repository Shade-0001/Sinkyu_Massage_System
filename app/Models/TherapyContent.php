<?php
// app/Models/TherapyContent.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TherapyContent extends Model
{
  use HasFactory;

  protected $connection = null;
  protected $table = 'therapy_contents';

  protected $fillable = [
    'therapy_content'
  ];
}
