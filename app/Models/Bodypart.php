<?php
// app/Models/Bodypart.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bodypart extends Model
{
  use HasFactory;

  protected $connection = null;
  protected $table = 'bodyparts';

  protected $fillable = ['bodypart'];

  public function consentsMassage()
  {
    return $this->belongsToMany(
      ConsentMassage::class,
      'bodyparts-consents_massage',
      'symtom_1_bodyparts_id',
      'consents_massage_id'
    );
  }
}
