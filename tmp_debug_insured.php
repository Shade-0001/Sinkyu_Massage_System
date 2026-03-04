<?php
require 'vendor/autoload.php';
\ = require_once 'bootstrap/app.php';
\->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\ = DB::table('insurances')
  ->leftJoin('self_or_family', 'insurances.self_or_family_id', '=', 'self_or_family.id')
  ->where('insurances.clinic_user_id', 100)
  ->orderBy('insurances.created_at', 'desc')
  ->select('insurances.id', 'insurances.self_or_family_id', 'self_or_family.subject_type')
  ->first();

echo "insurance record: " . json_encode(\) . PHP_EOL;

\ = DB::table('clinic_users')
  ->leftJoin('gender', 'clinic_users.gender_id', '=', 'gender.id')
  ->where('clinic_users.id', 100)
  ->select('clinic_users.id', 'clinic_users.gender_id', 'gender.gender', 'clinic_users.birthday', 'clinic_users.postal_code', 'clinic_users.address_1')
  ->first();

echo "clinic_user: " . json_encode(\) . PHP_EOL;
