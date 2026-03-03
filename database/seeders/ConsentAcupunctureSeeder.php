<?php
// database/seeders/ConsentAcupunctureSeeder.php

namespace Database\Seeders;

use App\Models\ConsentAcupuncture;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsentAcupunctureSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('consents_acupuncture')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    $clinicUserIds   = DB::connection('sinkyu_massage_system_db')->table('clinic_users')->pluck('id')->toArray();
    $conditionIds    = DB::connection('sinkyu_massage_system_db')->table('conditions')->pluck('id')->toArray();
    $doctorIds       = DB::connection('sinkyu_massage_system_db')->table('doctors')->pluck('id')->toArray();
    $billCategoryIds = DB::connection('sinkyu_massage_system_db')->table('bill_categories')->pluck('id')->toArray();
    $outcomeIds      = DB::connection('sinkyu_massage_system_db')->table('outcomes')->pluck('id')->toArray();
    $workScopeIds    = DB::connection('sinkyu_massage_system_db')->table('work_scope_types')->pluck('id')->toArray();
    $housecallIds    = DB::connection('sinkyu_massage_system_db')->table('housecall_reasons')->pluck('id')->toArray();
    // 鍼灸系の therapy_content_id: therapy_type=1 のもの (id=1〜6)
    $acuContentIds   = DB::connection('sinkyu_massage_system_db')
      ->table('therapy_contents')->where('therapy_type', 1)->pluck('id')->toArray();

    // すでにマッサージ同意書がある利用者IDを取得
    $hasMassageIds = DB::connection('sinkyu_massage_system_db')
      ->table('consents_massage')->pluck('clinic_user_id')->toArray();

    // マッサージ同意書がない利用者（必ず鍼灸を付与する）
    $mustTargetIds = array_values(array_diff($clinicUserIds, $hasMassageIds));

    // マッサージ同意書がある利用者から約40%を鍼灸にも付与（両方持つケース）
    $mayTargetIds = array_values(array_intersect($clinicUserIds, $hasMassageIds));
    shuffle($mayTargetIds);
    $optionalIds = array_slice($mayTargetIds, 0, (int) round(count($mayTargetIds) * 0.4));

    $targetIds = array_merge($mustTargetIds, $optionalIds);

    $data = [];
    foreach ($targetIds as $userId) {
      $row = ConsentAcupuncture::factory()->make()->getAttributes();
      $row['clinic_user_id']         = $userId;
      $row['consenting_doctor_id']   = $doctorIds[array_rand($doctorIds)];
      $row['bill_category_id']       = $billCategoryIds[array_rand($billCategoryIds)];
      $row['outcome_id']             = $outcomeIds[array_rand($outcomeIds)];
      $row['work_scope_type_id']     = $workScopeIds[array_rand($workScopeIds)];
      $row['housecall_reason_id']    = $housecallIds[array_rand($housecallIds)];
      $row['first_therapy_content_id'] = !empty($acuContentIds) ? $acuContentIds[array_rand($acuContentIds)] : null;
      $row['condition_id']             = $conditionIds[array_rand($conditionIds)];
      $row['created_at'] = now();
      $row['updated_at'] = now();
      $data[] = $row;
    }

    foreach (array_chunk($data, 100) as $chunk) {
      DB::connection('sinkyu_massage_system_db')->table('consents_acupuncture')->insert($chunk);
    }
  }
}
