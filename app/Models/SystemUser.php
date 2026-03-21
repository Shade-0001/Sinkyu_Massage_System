<?php
//-- app/Models/SystemUser.php --//

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemUser extends Model
{
  protected $table = 'system_users';

  protected $fillable = [
    'name',
    'login_id',
    'password',
    'plain_password',
  ];

  protected $hidden = [
    'password',
    'remember_token',
  ];

  protected function casts(): array
  {
    return [
      'password' => 'hashed',
      'last_login_at' => 'datetime',
    ];
  }
}
