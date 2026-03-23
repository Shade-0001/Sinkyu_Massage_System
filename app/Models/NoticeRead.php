<?php
// app/Models/NoticeRead.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoticeRead extends Model
{
  protected $connection = 'sinkyu_massage_system_db';
  protected $table = 'notice_reads';

  protected $fillable = [
    'user_id',
    'notice_id',
  ];
}
