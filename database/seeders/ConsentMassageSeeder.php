<?php
// database/seeders/ConsentMassageSeeder.php

namespace Database\Seeders;

use App\Models\ConsentMassage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsentMassageSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('consents_massage')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    $clinicUserIds    = DB::connection('sinkyu_massage_system_db')->table('clinic_users')->pluck('id')->toArray();
    $doctorIds        = DB::connection('sinkyu_massage_system_db')->table('doctors')->pluck('id')->toArray();
    $billCategoryIds  = DB::connection('sinkyu_massage_system_db')->table('bill_categories')->pluck('id')->toArray();
    $outcomeIds       = DB::connection('sinkyu_massage_system_db')->table('outcomes')->pluck('id')->toArray();
    $conditionIds     = DB::connection('sinkyu_massage_system_db')->table('conditions')->pluck('id')->toArray();
    $workScopeIds     = DB::connection('sinkyu_massage_system_db')->table('work_scope_types')->pluck('id')->toArray();
    $housecallIds     = DB::connection('sinkyu_massage_system_db')->table('housecall_reasons')->pluck('id')->toArray();
    $illnessIds       = DB::connection('sinkyu_massage_system_db')->table('illnesses_massage')->pluck('id')->toArray();
    // マッサージ系の therapy_content_id: therapy_type=2 のもの (id=7〜11)
    $massageContentIds = DB::connection('sinkyu_massage_system_db')
      ->table('therapy_contents')->where('therapy_type', 2)->pluck('id')->toArray();

    // 両方50%・AMのみ25%・HKのみ25% の割り当て
    shuffle($clinicUserIds);
    $total       = count($clinicUserIds);
    $bothCount   = (int) round($total * 0.50);
    $amOnlyCount = (int) round($total * 0.25);

    $bothIds   = array_slice($clinicUserIds, 0,                    $bothCount);
    $amOnlyIds = array_slice($clinicUserIds, $bothCount,           $amOnlyCount);
    $hkOnlyIds = array_slice($clinicUserIds, $bothCount + $amOnlyCount);

    // ConsentAcupunctureSeederと共有するためキャッシュファイルに書き出す
    file_put_contents(storage_path('app/consent_group_cache.json'), json_encode([
      'both'    => $bothIds,
      'am_only' => $amOnlyIds,
      'hk_only' => $hkOnlyIds,
    ]));

    $targetIds = array_merge($bothIds, $amOnlyIds);

    $data = [];
    foreach ($targetIds as $userId) {
      $row = ConsentMassage::factory()->make()->getAttributes();
      $row['clinic_user_id']           = $userId;
      $row['consenting_doctor_id']     = $doctorIds[array_rand($doctorIds)];
      $row['bill_category_id']         = $billCategoryIds[array_rand($billCategoryIds)];
      $row['outcome_id']               = $outcomeIds[array_rand($outcomeIds)];
      $row['condition_id']             = $conditionIds[array_rand($conditionIds)];
      $row['work_scope_type_id']       = $workScopeIds[array_rand($workScopeIds)];
      $row['housecall_reason_id']      = $housecallIds[array_rand($housecallIds)];
      $row['injury_and_illness_name_id'] = !empty($illnessIds) ? $illnessIds[array_rand($illnessIds)] : null;
      $row['first_therapy_content_id'] = !empty($massageContentIds) ? $massageContentIds[array_rand($massageContentIds)] : null;
      $row['created_at'] = now();
      $row['updated_at'] = now();
      $data[] = $row;
    }

    foreach (array_chunk($data, 100) as $chunk) {
      DB::connection('sinkyu_massage_system_db')->table('consents_massage')->insert($chunk);
    }
  }
}
