<?php
// database/seeders/BodypartConsentMassageSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BodypartConsentMassageSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('bodyparts-consents_massage')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    $consentIds  = DB::connection('sinkyu_massage_system_db')->table('consents_massage')->pluck('id')->toArray();
    $bodypartIds = DB::connection('sinkyu_massage_system_db')->table('bodyparts')->pluck('id')->toArray();

    if (empty($consentIds) || empty($bodypartIds)) {
      return;
    }

    $data = [];
    foreach ($consentIds as $consentId) {
      shuffle($bodypartIds);
      $data[] = [
        'consents_massage_id'        => $consentId,
        'symtom_1_bodyparts_id'      => $bodypartIds[0],
        'symtom_2_bodyparts_id'      => $bodypartIds[1] ?? null,
        'therapy_type_1_bodyparts_id' => $bodypartIds[2] ?? null,
        'therapy_type_2_bodyparts_id' => $bodypartIds[3] ?? null,
        'created_at'                 => now(),
        'updated_at'                 => now(),
      ];
    }

    foreach (array_chunk($data, 100) as $chunk) {
      DB::connection('sinkyu_massage_system_db')->table('bodyparts-consents_massage')->insert($chunk);
    }
  }
}
