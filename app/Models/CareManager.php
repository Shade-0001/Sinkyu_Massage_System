<?php
//-- app/Models/CareManager.php --//

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareManager extends Model
{
  use HasFactory;

  protected $connection = null;
  protected $table = 'caremanagers';

  protected $fillable = [
    'last_name',
    'first_name',
    'last_name_kana',
    'first_name_kana',
    'service_providers_id',
    'postal_code',
    'address_1',
    'address_2',
    'address_3',
    'phone',
    'cell_phone',
    'fax',
    'email',
    'note'
  ];
}
