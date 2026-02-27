<?php
// database/seeders/RecordSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecordSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('records')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    $clinicUserIds    = DB::connection('sinkyu_massage_system_db')->table('clinic_users')->pluck('id')->toArray();
    $therapistIds     = DB::connection('sinkyu_massage_system_db')->table('therapists')->pluck('id')->toArray();
    $billCategoryIds  = DB::connection('sinkyu_massage_system_db')->table('bill_categories')->pluck('id')->toArray();
    $therapyContents  = DB::connection('sinkyu_massage_system_db')->table('therapy_contents')->get()->groupBy('therapy_type');

    // therapy_type別のcontent IDリスト
    $massageContentIds = $therapyContents->get(2, collect())->pluck('id')->toArray();
    $acuContentIds     = $therapyContents->get(1, collect())->pluck('id')->toArray();

    $data = [];
    foreach ($clinicUserIds as $userId) {
      // 1人あたり約20件の施術記録
      $recordCount = rand(10, 30);
      for ($i = 0; $i < $recordCount; $i++) {
        $therapyType   = rand(1, 2);
        $contentIds    = $therapyType === 2 ? $massageContentIds : $acuContentIds;
        $contentId     = !empty($contentIds) ? $contentIds[array_rand($contentIds)] : null;

        $date       = date('Y-m-d', strtotime('-' . rand(0, 730) . ' days'));
        $startHour  = rand(9, 17);
        $startMin   = rand(0, 1) * 30;
        $duration   = [30, 45, 60][rand(0, 2)];
        $endMinutes = $startHour * 60 + $startMin + $duration;
        $startTime  = sprintf('%02d:%02d', $startHour, $startMin);
        $endTime    = sprintf('%02d:%02d', intdiv($endMinutes, 60), $endMinutes % 60);

        $data[] = [
          'clinic_user_id'     => $userId,
          'date'               => $date,
          'start_time'         => $startTime,
          'end_time'           => $endTime,
          'therapy_type'       => $therapyType,
          'therapy_category'   => rand(1, 2),
          'insurance_category' => rand(1, 3),
          'housecall_distance' => round(rand(10, 150) / 10, 1),
          'therapy_days'       => rand(1, 30),
          'consent_expiry'     => date('Y-m-d', strtotime('+' . rand(1, 12) . ' months')),
          'therapy_content_id' => $contentId,
          'self_fee_id'        => null,
          'bill_category_id'   => $billCategoryIds[array_rand($billCategoryIds)],
          'therapist_id'       => $therapistIds[array_rand($therapistIds)],
          'abstract'           => null,
          'created_at'         => now(),
          'updated_at'         => now(),
        ];
      }
    }

    // withoutEventsで saved イベントを発火させずにinsert
    \App\Models\Record::withoutEvents(function () use ($data) {
      foreach (array_chunk($data, 500) as $chunk) {
        DB::connection('sinkyu_massage_system_db')->table('records')->insert($chunk);
      }
    });
  }
}
