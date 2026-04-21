<?php
//-- app/Models/SystemUser.php --//

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class SystemUser extends Authenticatable
{
  protected $table = 'user_accounts';

  protected $fillable = [
    'name',
    'login_id',
    'password',
    'plain_password',
    'is_admin',
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
