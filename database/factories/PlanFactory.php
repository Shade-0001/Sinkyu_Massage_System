<?php
// database/factories/PlanFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
  // assistance_levels の有効な ID 組み合わせ（scoreMap準拠）
  private const ADL_OPTIONS = [
    'eating'            => [2, 3, 8],
    'moving'            => [2, 4, 6, 8],
    'personal_grooming' => [1, 8],
    'using_toilet'      => [2, 3, 8],
    'bathing'           => [1, 8],
    'walking'           => [5, 7, 6, 8],
    'using_stairs'      => [2, 3, 8],
    'changing_clothes'  => [5, 3, 8],
    'defecation'        => [2, 1, 8],
    'urination'         => [2, 1, 9],
  ];

  private const STAFF_NAMES = [
    '田中 太郎', '鈴木 花子', '佐藤 健一', '山田 美咲', '伊藤 誠',
    '渡辺 直子', '中村 義雄', '小林 由美', '加藤 浩', '吉田 恵子',
  ];

  private const ADL_NOTES = [
    '軽介助で可能', '声掛けにて対応可', '福祉用具使用', '一部自立', '要見守り',
  ];

  private const WISH_OPTIONS = [
    '自宅での生活を続けたい', '少しでも動けるようになりたい', '痛みを和らげたい', '家族に迷惑をかけたくない',
  ];

  private const PURPOSE_OPTIONS = [
    '筋力維持・向上', '関節可動域の維持', '疼痛緩和', 'ADL改善', '廃用予防',
  ];

  private const REHAB_OPTIONS = [
    '自主トレーニング実施中', '訪問リハビリ併用', 'デイサービス利用', '家族による介助訓練実施',
  ];

  private const CHANGE_OPTIONS = [
    '前回より改善傾向', '状態変化なし', '一部悪化、対応検討中', '新たな症状なし',
  ];

  private const COMM_OPTIONS = [
    '良好', '発語あり・聴力低下あり', '筆談使用', '意思疎通やや困難',
  ];

  public function definition(): array
  {
    return [
      'clinic_user_id'                          => null,
      'assessment_date'                         => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
      'assessor'                                => $this->faker->randomElement(self::STAFF_NAMES),
      'audience'                                => $this->faker->randomElement(['本人', '本人・家族', '家族']),
      'eating_assistance_level_id'              => $this->faker->randomElement(self::ADL_OPTIONS['eating']),
      'eating_assistance_note'                  => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'moving_assistance_level_id'              => $this->faker->randomElement(self::ADL_OPTIONS['moving']),
      'moving_assistance_note'                  => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'personal_grooming_assistance_level_id'   => $this->faker->randomElement(self::ADL_OPTIONS['personal_grooming']),
      'personal_grooming_assistance_note'       => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'using_toilet_assistance_level_id'        => $this->faker->randomElement(self::ADL_OPTIONS['using_toilet']),
      'using_toilet_assistance_note'            => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'bathing_assistance_level_id'             => $this->faker->randomElement(self::ADL_OPTIONS['bathing']),
      'bathing_assistance_note'                 => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'walking_assistance_level_id'             => $this->faker->randomElement(self::ADL_OPTIONS['walking']),
      'walking_assistance_note'                 => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'using_stairs_assistance_level_id'        => $this->faker->randomElement(self::ADL_OPTIONS['using_stairs']),
      'using_stairs_assistance_note'            => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'changing_clothes_assistance_level_id'    => $this->faker->randomElement(self::ADL_OPTIONS['changing_clothes']),
      'changing_clothes_assistance_note'        => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'defecation_assistance_level_id'          => $this->faker->randomElement(self::ADL_OPTIONS['defecation']),
      'defecation_assistance_note'              => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'urination_assistance_level_id'           => $this->faker->randomElement(self::ADL_OPTIONS['urination']),
      'urination_assistance_note'               => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'communication_note'                      => $this->faker->boolean(30) ? $this->faker->randomElement(self::COMM_OPTIONS) : null,
      'wish_of_user_and_familiy'                => $this->faker->boolean(40) ? $this->faker->randomElement(self::WISH_OPTIONS) : null,
      'care_purpose'                            => $this->faker->boolean(40) ? $this->faker->randomElement(self::PURPOSE_OPTIONS) : null,
      'rehabilitation_program'                  => $this->faker->boolean(30) ? $this->faker->randomElement(self::REHAB_OPTIONS) : null,
      'home_rehabilitation'                     => $this->faker->boolean(30) ? $this->faker->randomElement(self::REHAB_OPTIONS) : null,
      'change_since_previous_planning'          => $this->faker->boolean(30) ? $this->faker->randomElement(self::CHANGE_OPTIONS) : null,
      'note'                                    => $this->faker->boolean(20) ? $this->faker->randomElement(self::CHANGE_OPTIONS) : null,
      'user_and_family_consent_date'            => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
    ];
  }
}
