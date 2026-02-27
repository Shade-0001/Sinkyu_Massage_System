<?php
// database/factories/RecordFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RecordFactory extends Factory
{
  // therapy_type => therapy_content_id の対応（マイグレーションの最終形に準拠）
  // therapy_type=1(鍼灸): id=1〜6, therapy_type=2(マッサージ): id=7〜11
  private const THERAPY_CONTENT_IDS = [
    1 => [1, 2, 3, 4, 5, 6],
    2 => [7, 8, 9, 10, 11],
  ];

  public function definition(): array
  {
    $therapyType    = $this->faker->randomElement([1, 2]);
    $contentIds     = self::THERAPY_CONTENT_IDS[$therapyType];
    $contentId      = $this->faker->randomElement($contentIds);
    $date           = $this->faker->dateTimeBetween('-2 years', 'now');
    $startHour      = $this->faker->numberBetween(9, 17);
    $startMinute    = $this->faker->randomElement([0, 30]);
    $durationMins   = $this->faker->randomElement([30, 45, 60]);
    $startTime      = sprintf('%02d:%02d', $startHour, $startMinute);
    $endMinutes     = $startHour * 60 + $startMinute + $durationMins;
    $endTime        = sprintf('%02d:%02d', intdiv($endMinutes, 60), $endMinutes % 60);

    $abstractOptions = [
      '状態安定。前回より可動域改善。',
      '疼痛の訴えあり。施術後軽減。',
      '浮腫あり。下肢を中心に施術。',
      '体調良好。通常通り施術。',
      '血圧高め。安静後施術開始。',
      '家族より状態悪化の報告あり。',
      '前回より筋緊張軽減を確認。',
      '施術後、本人より楽になったとのこと。',
    ];

    return [
      'clinic_user_id'      => null,
      'date'                => $date->format('Y-m-d'),
      'start_time'          => $startTime,
      'end_time'            => $endTime,
      'therapy_type'        => $therapyType,
      'therapy_category'    => $this->faker->randomElement([1, 2]),
      'insurance_category'  => $this->faker->randomElement([1, 2, 3]),
      'housecall_distance'  => $this->faker->randomFloat(1, 1.0, 15.0),
      'therapy_days'        => $this->faker->numberBetween(1, 30),
      'consent_expiry'      => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
      'therapy_content_id'  => $contentId,
      'self_fee_id'         => null,
      'bill_category_id'    => null,
      'therapist_id'        => null,
      'abstract'            => $this->faker->boolean(30)
        ? $this->faker->randomElement($abstractOptions)
        : null,
    ];
  }
}
