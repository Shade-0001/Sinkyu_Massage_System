<?php
// database/factories/PlanInfoFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PlanInfoFactory extends Factory
{
  private const ADL_LEVELS = ['全介助', '一部介助', '監視', '自立'];

  private const STAFF_NAMES = [
    '田中 太郎', '鈴木 花子', '佐藤 健一', '山田 美咲', '伊藤 誠',
    '渡辺 直子', '中村 義雄', '小林 由美', '加藤 浩', '吉田 恵子',
  ];

  private const ADL_NOTES = [
    '軽介助で可能', '声掛けにて対応可', '福祉用具使用', '一部自立', '要見守り',
  ];

  private const COMM_OPTIONS = [
    '良好', '発語あり・聴力低下あり', '筆談使用', '意思疎通やや困難',
  ];

  private const REQUEST_OPTIONS = [
    '痛みを和らげてほしい', '自宅での生活を続けたい', '少しでも歩けるようになりたい', '家族に迷惑をかけたくない',
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

  private const DISABILITY_OPTIONS = [
    '高血圧あり', '糖尿病あり', '骨粗鬆症あり', '認知機能低下あり', '視力低下あり',
  ];

  public function definition(): array
  {
    return [
      'clinic_user_id'          => null,
      'evaluation_date'         => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
      'evaluator'               => $this->faker->randomElement(self::STAFF_NAMES),
      'respiration'             => $this->faker->randomElement(['自立', '要観察', null]),
      'meal_assistance_level'   => $this->faker->randomElement(self::ADL_LEVELS),
      'meal_assistance_note'    => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'mobility_level'          => $this->faker->randomElement(self::ADL_LEVELS),
      'mobility_note'           => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'grooming_level'          => $this->faker->randomElement(self::ADL_LEVELS),
      'grooming_note'           => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'toilet_level'            => $this->faker->randomElement(self::ADL_LEVELS),
      'toilet_note'             => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'bathing_level'           => $this->faker->randomElement(self::ADL_LEVELS),
      'bathing_note'            => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'flat_walking_level'      => $this->faker->randomElement(self::ADL_LEVELS),
      'flat_walking_note'       => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'stairs_level'            => $this->faker->randomElement(self::ADL_LEVELS),
      'stairs_note'             => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'dressing_level'          => $this->faker->randomElement(self::ADL_LEVELS),
      'dressing_note'           => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'defecation_level'        => $this->faker->randomElement(self::ADL_LEVELS),
      'defecation_note'         => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'urination_level'         => $this->faker->randomElement(self::ADL_LEVELS),
      'urination_note'          => $this->faker->boolean(20) ? $this->faker->randomElement(self::ADL_NOTES) : null,
      'communication'           => $this->faker->boolean(30) ? $this->faker->randomElement(self::COMM_OPTIONS) : null,
      'patient_family_request'  => $this->faker->boolean(40) ? $this->faker->randomElement(self::REQUEST_OPTIONS) : null,
      'treatment_purpose'       => $this->faker->boolean(40) ? $this->faker->randomElement(self::PURPOSE_OPTIONS) : null,
      'rehabilitation_program'  => $this->faker->boolean(30) ? $this->faker->randomElement(self::REHAB_OPTIONS) : null,
      'home_rehabilitation'     => $this->faker->boolean(30) ? $this->faker->randomElement(self::REHAB_OPTIONS) : null,
      'improvement_changes'     => $this->faker->boolean(30) ? $this->faker->randomElement(self::CHANGE_OPTIONS) : null,
      'disability_notes'        => $this->faker->boolean(30) ? $this->faker->randomElement(self::DISABILITY_OPTIONS) : null,
      'consent_date'            => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
    ];
  }
}
