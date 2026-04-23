<?php
// app/Models/BillCategory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillCategory extends Model
{
  use HasFactory;

  protected $connection = null;
  protected $table = 'bill_categories';

  protected $fillable = [
    'bill_category'
  ];
}
