<?php
// database/seeders/SelfFeeSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SelfFeeSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('self_fees')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    DB::connection('sinkyu_massage_system_db')->table('self_fees')->insert([
      ['self_fee_name' => 'マッサージ（30分）',       'amount' => 3000, 'created_at' => now(), 'updated_at' => now()],
      ['self_fee_name' => 'マッサージ（60分）',       'amount' => 5500, 'created_at' => now(), 'updated_at' => now()],
      ['self_fee_name' => 'マッサージ（90分）',       'amount' => 8000, 'created_at' => now(), 'updated_at' => now()],
      ['self_fee_name' => '鍼灸（初回）',             'amount' => 5000, 'created_at' => now(), 'updated_at' => now()],
      ['self_fee_name' => '鍼灸（2回目以降）',       'amount' => 4000, 'created_at' => now(), 'updated_at' => now()],
      ['self_fee_name' => '鍼灸+マッサージセット',   'amount' => 7500, 'created_at' => now(), 'updated_at' => now()],
      ['self_fee_name' => '訪問マッサージ（30分）',   'amount' => 3500, 'created_at' => now(), 'updated_at' => now()],
      ['self_fee_name' => '訪問マッサージ（60分）',   'amount' => 6000, 'created_at' => now(), 'updated_at' => now()],
      ['self_fee_name' => '訪問鍼灸',                 'amount' => 4500, 'created_at' => now(), 'updated_at' => now()],
      ['self_fee_name' => '温熱療法オプション',       'amount' => 1000, 'created_at' => now(), 'updated_at' => now()],
    ]);
  }
}
