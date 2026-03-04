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

    // clinic_user_id を昇順で取得（末尾10人の特定に使用）
    $clinicUserIds   = DB::connection('sinkyu_massage_system_db')->table('clinic_users')->orderBy('id')->pluck('id')->toArray();
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
    $data  = [];

    // clinic_user_id(昇順)の末尾10人
    $lastTenUserIds = array_slice($clinicUserIds, -10);

    // 日付範囲定義（Unixタイムスタンプ）
    $phase1Start = mktime(0, 0, 0, 1,  1, 2025);
    $phase1End   = mktime(0, 0, 0, 12, 31, 2025);
    $phase2Start = mktime(0, 0, 0, 1,  1, 2026);
    $phase2End   = mktime(0, 0, 0, 5,  31, 2026);

    // 範囲内の定休日でないランダム日付を生成（失敗時null）
    $generateDate = function (int $rangeStart, int $rangeEnd) use ($isClosedDay): ?string {
      $days = (int) floor(($rangeEnd - $rangeStart) / 86400);
      for ($i = 0; $i < 30; $i++) {
        $date = date('Y-m-d', $rangeStart + rand(0, $days) * 86400);
        if (!$isClosedDay($date)) {
          return $date;
        }
      }
      return null;
    };

    // 1スロット配置共通ロジック（成功時true、失敗時false）
    $placeSlot = function (
      string $date,
      int    $userId
    ) use (
      &$slots, &$data,
      $businessStartMin, $businessEndMin,
      $isOverlapping,
      $therapistIds, $billCategoryIds,
      $massageContentIds, $acuContentIds
    ): bool {
      $durations = [30, 45, 60];
      $duration  = $durations[array_rand($durations)];

      $latestStart = $businessEndMin - $duration;
      if ($latestStart < $businessStartMin) {
        return false;
      }

      // 開始時刻候補：10分刻み（**:*0 のみ）
      $firstCandidate = (int) ceil($businessStartMin / 10) * 10;
      $candidates = [];
      for ($m = $firstCandidate; $m <= $latestStart; $m += 10) {
        $candidates[] = $m;
      }
      shuffle($candidates);

      foreach ($candidates as $startMin) {
        $endMin   = $startMin + $duration;
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
        // Xが他に既に1件重複を持つ場合、+1（新スロット）で2件超になるため不可
        $wouldViolate = false;
        foreach ($overlappingSlots as $existingSlot) {
          $existingOverlapCount = count(array_filter(
            $daySlots,
            fn($s) => $s !== $existingSlot
              && $isOverlapping($s['start'], $s['end'], $existingSlot['start'], $existingSlot['end'])
          ));
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

        // 利用者: 重複スロットに既に同じユーザーが存在するなら不可
        if (in_array($userId, $usedUserIds, true)) {
          continue;
        }

        // 施術者: 重複スロットで使われていないIDを選ぶ
        $availableTherapistIds = array_values(array_diff($therapistIds, $usedTherapistIds));
        if (empty($availableTherapistIds)) {
          continue;
        }
        $selectedTherapistId = $availableTherapistIds[array_rand($availableTherapistIds)];

        $startTime   = sprintf('%02d:%02d', intdiv($startMin, 60), $startMin % 60);
        $endTime     = sprintf('%02d:%02d', intdiv($endMin, 60), $endMin % 60);
        $therapyType = rand(1, 2);
        $contentIds  = $therapyType === 2 ? $massageContentIds : $acuContentIds;
        $contentId   = !empty($contentIds) ? $contentIds[array_rand($contentIds)] : null;

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
          'therapist_id'       => $selectedTherapistId,
          'abstract'           => null,
          'created_at'         => now(),
          'updated_at'         => now(),
        ];

        $slots[$date][] = [
          'start'          => $startMin,
          'end'            => $endMin,
          'clinic_user_id' => $userId,
          'therapist_id'   => $selectedTherapistId,
        ];

        return true;
      }

      return false;
    };

    foreach ($clinicUserIds as $userId) {
      $isLastTen = in_array($userId, $lastTenUserIds, true);

      // Phase 1: 2025-01-01 ~ 2025-12-31 → 0~360件
      $target1      = rand(0, 360);
      $placed1      = 0;
      $attempts1    = 0;
      $maxAttempts1 = max($target1 * 15, 50);

      while ($placed1 < $target1 && $attempts1 < $maxAttempts1) {
        $attempts1++;
        $date = $generateDate($phase1Start, $phase1End);
        if ($date === null) continue;
        if ($placeSlot($date, $userId)) {
          $placed1++;
        }
      }

      // Phase 2: 2026-01-01 ~ 2026-05-31
      // 末尾10人: 90~150件, その他: 0~150件
      $target2      = $isLastTen ? rand(90, 150) : rand(0, 150);
      $placed2      = 0;
      $attempts2    = 0;
      $maxAttempts2 = max($target2 * 15, 50);

      while ($placed2 < $target2 && $attempts2 < $maxAttempts2) {
        $attempts2++;
        $date = $generateDate($phase2Start, $phase2End);
        if ($date === null) continue;
        if ($placeSlot($date, $userId)) {
          $placed2++;
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
