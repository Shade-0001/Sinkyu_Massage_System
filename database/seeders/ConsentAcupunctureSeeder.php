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

    $conditionIds    = DB::connection('sinkyu_massage_system_db')->table('conditions')->pluck('id')->toArray();
    $doctorIds       = DB::connection('sinkyu_massage_system_db')->table('doctors')->pluck('id')->toArray();
    $billCategoryIds = DB::connection('sinkyu_massage_system_db')->table('bill_categories')->pluck('id')->toArray();
    $outcomeIds      = DB::connection('sinkyu_massage_system_db')->table('outcomes')->pluck('id')->toArray();
    $workScopeIds    = DB::connection('sinkyu_massage_system_db')->table('work_scope_types')->pluck('id')->toArray();
    $housecallIds    = DB::connection('sinkyu_massage_system_db')->table('housecall_reasons')->pluck('id')->toArray();
    // 鍼灸系の therapy_content_id: therapy_type=1 のもの (id=1〜6)
    $acuContentIds   = DB::connection('sinkyu_massage_system_db')
      ->table('therapy_contents')->where('therapy_type', 1)->pluck('id')->toArray();
    // illnesses_acupuncture: ID1~6（固定）とそれ以外（ダミー）を分けて取得
    $illnessFixedIds = DB::connection('sinkyu_massage_system_db')->table('illnesses_acupuncture')->where('id', '<=', 6)->pluck('id')->toArray();
    $illnessDummyIds = DB::connection('sinkyu_massage_system_db')->table('illnesses_acupuncture')->where('id', '>', 6)->pluck('id')->toArray();

    // ConsentMassageSeederが書き出したグループキャッシュを読み込む
    $cacheFile = storage_path('app/consent_group_cache.json');
    $groupCache = file_exists($cacheFile) ? json_decode(file_get_contents($cacheFile), true) : null;

    // 両方組＋HKのみ組が対象（合計75%）
    $targetIds = array_merge($groupCache['both'] ?? [], $groupCache['hk_only'] ?? []);

    // 末尾10人はPhase2（3〜6月）を確実にカバーする同意書日付に固定
    $allUserIds = DB::connection('sinkyu_massage_system_db')->table('clinic_users')->orderBy('id')->pluck('id')->toArray();
    $lastTenIds = array_slice($allUserIds, -10);

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
      // illnesses_acupuncture: ID1~6を80%、それ以外を20%の確率で選択
      if (!empty($illnessFixedIds) && (empty($illnessDummyIds) || mt_rand(1, 100) <= 80)) {
        $row['illness_name_acupuncture_id'] = $illnessFixedIds[array_rand($illnessFixedIds)];
      } elseif (!empty($illnessDummyIds)) {
        $row['illness_name_acupuncture_id'] = $illnessDummyIds[array_rand($illnessDummyIds)];
      }
      $row['condition_id'] = $conditionIds[array_rand($conditionIds)];
      // 末尾10人はPhase2（3〜6月）を確実にカバーするconsenting_dateに上書き
      if (in_array($userId, $lastTenIds, true)) {
        $consentingDate = (new \DateTime())->setTimestamp(rand(strtotime('2026-01-01'), strtotime('2026-03-01')));
        $endDate        = (clone $consentingDate)->modify('+6 months');
        $row['consenting_date']           = $consentingDate->format('Y-m-d');
        $row['consenting_start_date']     = $consentingDate->format('Y-m-d');
        $row['consenting_end_date']       = $endDate->format('Y-m-d');
        $row['benefit_period_start_date'] = $consentingDate->format('Y-m-d');
        $row['benefit_period_end_date']   = $endDate->format('Y-m-d');
        $row['first_care_date']           = $consentingDate->format('Y-m-d');
        $row['reconsenting_expiry']       = $endDate->format('Y-m-d');
      }
      $row['created_at'] = now();
      $row['updated_at'] = now();
      $data[] = $row;
    }

    foreach (array_chunk($data, 100) as $chunk) {
      DB::connection('sinkyu_massage_system_db')->table('consents_acupuncture')->insert($chunk);
    }
  }
}
