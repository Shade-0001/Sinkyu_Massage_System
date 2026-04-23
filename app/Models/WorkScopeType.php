<?php
// app/Models/WorkScopeType.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkScopeType extends Model
{
  use HasFactory;

  protected $connection = null;
  protected $table = 'work_scope_types';

  protected $fillable = [
    'work_scope_type'
  ];
}
