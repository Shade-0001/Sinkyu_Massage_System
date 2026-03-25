<?php
// app/Http/Controllers/UserSearchController.php

namespace App\Http\Controllers;

use App\Models\ClinicUser;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
  public function index()
  {
    $users = ClinicUser::orderBy('last_kana', 'asc')
      ->orderBy('first_kana', 'asc')
      ->get();

    $maxClinicUserId = $users->max('id') ?? 1;
    $clinicUserIdLength = strlen((string) $maxClinicUserId);

    return view('user-search', compact('users', 'clinicUserIdLength'));
  }
}
