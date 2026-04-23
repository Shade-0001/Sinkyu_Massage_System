<?php
// app/Models/Notice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
  use HasFactory;

  protected $connection = null;
  protected $table = 'notices';

  protected $fillable = [
    'title',
    'content',
  ];

  public function reads()
  {
    return $this->hasMany(NoticeRead::class, 'notice_id');
  }

  public function isReadBy(int $userId): bool
  {
    return $this->reads()->where('user_id', $userId)->exists();
  }
}
