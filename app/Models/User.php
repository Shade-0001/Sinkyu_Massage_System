<?php
//-- app/Models/User.php --//

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
  /** @use HasFactory<\Database\Factories\UserFactory> */
  use HasFactory, Notifiable;

  // ログイン機能でsystem_usersテーブルを使用
  protected $table = 'system_users';

  /**
   * The attributes that are mass assignable.
   *
   * @var list<string>
   */
  protected $fillable = [
    'login_id',
    'password',
    'is_admin',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var list<string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'password'     => 'hashed',
      'last_login_at' => 'datetime',
      'is_admin'     => 'integer',
    ];
  }

  // login_id を使ってログイン
  public function username(): string
  {
    return 'login_id';
  }
}
