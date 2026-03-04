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

    $clinicUserIds   = DB::connection('sinkyu_massage_system_db')->table('clinic_users')->pluck('id')->toArray();
    $therapistIds    = DB::connection('sinkyu_massage_system_db')->table('therapists')->pluck('id')->toArray();
    $billCategoryIds = DB::connection('sinkyu_massage_system_db')->table('bill_categories')->pluck('id')->toArray();
    $therapyContents = DB::connection('sinkyu_massage_system_db')->table('therapy_contents')->get()->groupBy('therapy_type');

    $massageContentIds = $therapyContents->get(2, collect())->pluck('id')->toArray();
    $acuContentIds     = $therapyContents->get(1, collect())->pluck('id')->toArray();

    // clinic_info から定休日・営業時間を取得
    $clinicInfo = DB::connection('sinkyu_massage_system_db')->table('clinic_info')->first();

    // 定休日の曜日番号マップ (0=日, 1=月, ..., 6=土)
    $closedDays = [];
    $dayMap = [
      'closed_day_sunday'    => 0,
      'closed_day_monday'    => 1,
      'closed_day_tuesday'   => 2,
      'closed_day_wednesday' => 3,
      'closed_day_thursday'  => 4,
      'closed_day_friday'    => 5,
      'closed_day_saturday'  => 6,
    ];
    foreach ($dayMap as $col => $dayNum) {
      if ($clinicInfo->$col == 1) {
        $closedDays[] = $dayNum;
      }
    }

    // 営業時間をminutes単位に変換
    [$bStartH, $bStartM] = array_map('intval', explode(':', $clinicInfo->business_hours_start));
    [$bEndH,   $bEndM]   = array_map('intval', explode(':', $clinicInfo->business_hours_end));
    $businessStartMin = $bStartH * 60 + $bStartM;
    $businessEndMin   = $bEndH   * 60 + $bEndM;

    // 定休日かどうか判定
    $isClosedDay = function (string $date) use ($closedDays): bool {
      $dow = (int) date('w', strtotime($date)); // 0=日〜6=土
      return in_array($dow, $closedDays, true);
    };

    // 2つの時間帯が重複するか判定 (分単位)
    $isOverlapping = function (int $s1, int $e1, int $s2, int $e2): bool {
      return $s1 < $e2 && $s2 < $e1;
    };

    // 各日付のスロット管理
    // $slots[$date][] = ['start' => int, 'end' => int, 'clinic_user_id' => int, 'therapist_id' => int]
    $slots = [];

    $data = [];

    foreach ($clinicUserIds as $userId) {
      $target   = rand(10, 30); // この利用者に登録したいレコード件数
      $placed   = 0;
      $attempts = 0;
      $maxAttempts = $target * 10;

      while ($placed < $target && $attempts < $maxAttempts) {
        $attempts++;

        // 日付を生成（定休日は除外）
        $dateAttempts = 0;
        do {
          $date = date('Y-m-d', strtotime('-' . rand(0, 730) . ' days'));
          $dateAttempts++;
        } while ($isClosedDay($date) && $dateAttempts < 20);

        if ($isClosedDay($date)) {
          continue;
        }

        // 施術時間を生成
        $durations = [30, 45, 60];
        $duration  = $durations[array_rand($durations)];

        // 営業時間内に収まる開始時刻候補（30分刻み）をランダム順で試す
        $latestStart = $businessEndMin - $duration;
        if ($latestStart < $businessStartMin) {
          continue;
        }

        $candidates = [];
        for ($m = $businessStartMin; $m <= $latestStart; $m += 30) {
          $candidates[] = $m;
        }
        shuffle($candidates);

        $slotPlaced = false;
        foreach ($candidates as $startMin) {
          $endMin = $startMin + $duration;

          $daySlots = $slots[$date] ?? [];

          // 新スロットと重複する既存スロット
          $overlappingSlots = array_values(array_filter(
            $daySlots,
            fn($s) => $isOverlapping($s['start'], $s['end'], $startMin, $endMin)
          ));

          // 新スロット自身が2件以上の既存スロットと重複するなら不可
          if (count($overlappingSlots) >= 2) {
            continue;
          }

          // 新スロットと重複する既存スロットXそれぞれについて、
          // X自身が他に何件の既存スロットと重複するかを確認する。
          // 新スロット追加後にXの重複数が2件以上になる場合は不可。
          $wouldViolate = false;
          foreach ($overlappingSlots as $existingSlot) {
            // existingSlot と重複する他の既存スロット数（新スロット除く）
            $existingOverlapCount = count(array_filter(
              $daySlots,
              fn($s) => $s !== $existingSlot
                && $isOverlapping($s['start'], $s['end'], $existingSlot['start'], $existingSlot['end'])
            ));
            // +1（新スロット）で2件以上になるなら不可
            if ($existingOverlapCount + 1 >= 2) {
              $wouldViolate = true;
              break;
            }
          }
          if ($wouldViolate) {
            continue;
          }

          // 重複スロットで使用済みのIDを収集
          $usedUserIds      = array_column($overlappingSlots, 'clinic_user_id');
          $usedTherapistIds = array_column($overlappingSlots, 'therapist_id');

          // 利用者: 現在のuserIdが使えるなら優先、使えなければスキップ
          if (in_array($userId, $usedUserIds, true)) {
            continue;
          }
          $selectedUserId = $userId;

          // 施術者: 重複スロットで使われていないIDを選ぶ
          $availableTherapistIds = array_values(array_diff($therapistIds, $usedTherapistIds));
          if (empty($availableTherapistIds)) {
            continue;
          }
          $selectedTherapistId = $availableTherapistIds[array_rand($availableTherapistIds)];

          $startTime = sprintf('%02d:%02d', intdiv($startMin, 60), $startMin % 60);
          $endTime   = sprintf('%02d:%02d', intdiv($endMin, 60), $endMin % 60);

          $therapyType = rand(1, 2);
          $contentIds  = $therapyType === 2 ? $massageContentIds : $acuContentIds;
          $contentId   = !empty($contentIds) ? $contentIds[array_rand($contentIds)] : null;

          $data[] = [
            'clinic_user_id'     => $selectedUserId,
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
            'therapist_id'       => $selectedTherapistId,
            'abstract'           => null,
            'created_at'         => now(),
            'updated_at'         => now(),
          ];

          // スロット登録
          $slots[$date][] = [
            'start'          => $startMin,
            'end'            => $endMin,
            'clinic_user_id' => $selectedUserId,
            'therapist_id'   => $selectedTherapistId,
          ];

          $slotPlaced = true;
          break;
        }

        if ($slotPlaced) {
          $placed++;
        }
      }
    }

    \App\Models\Record::withoutEvents(function () use ($data) {
      foreach (array_chunk($data, 500) as $chunk) {
        DB::connection('sinkyu_massage_system_db')->table('records')->insert($chunk);
      }
    });
  }
}
