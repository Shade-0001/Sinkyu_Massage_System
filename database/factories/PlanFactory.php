<?php
// database/factories/PlanFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
  // assistance_levels の有効な ID 組み合わせ（scoreMap準拠）
  private const ADL_OPTIONS = [
    'eating'          => [2, 3, 8],
    'moving'          => [2, 4, 6, 8],
    'personal_grooming' => [1, 8],
    'using_toilet'    => [2, 3, 8],
    'bathing'         => [1, 8],
    'walking'         => [5, 7, 6, 8],
    'using_stairs'    => [2, 3, 8],
    'changing_clothes' => [5, 3, 8],
    'defecation'      => [2, 1, 8],
    'urination'       => [2, 1, 9],
  ];

  public function definition(): array
  {
    return [
      'clinic_user_id'                          => null,
      'assessment_date'                         => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
      'assessor'                                => $this->faker->name(),
      'audience'                                => $this->faker->randomElement(['本人', '本人・家族', '家族']),
      'eating_assistance_level_id'              => $this->faker->randomElement(self::ADL_OPTIONS['eating']),
      'eating_assistance_note'                  => null,
      'moving_assistance_level_id'              => $this->faker->randomElement(self::ADL_OPTIONS['moving']),
      'moving_assistance_note'                  => null,
      'personal_grooming_assistance_level_id'   => $this->faker->randomElement(self::ADL_OPTIONS['personal_grooming']),
      'personal_grooming_assistance_note'       => null,
      'using_toilet_assistance_level_id'        => $this->faker->randomElement(self::ADL_OPTIONS['using_toilet']),
      'using_toilet_assistance_note'            => null,
      'bathing_assistance_level_id'             => $this->faker->randomElement(self::ADL_OPTIONS['bathing']),
      'bathing_assistance_note'                 => null,
      'walking_assistance_level_id'             => $this->faker->randomElement(self::ADL_OPTIONS['walking']),
      'walking_assistance_note'                 => null,
      'using_stairs_assistance_level_id'        => $this->faker->randomElement(self::ADL_OPTIONS['using_stairs']),
      'using_stairs_assistance_note'            => null,
      'changing_clothes_assistance_level_id'    => $this->faker->randomElement(self::ADL_OPTIONS['changing_clothes']),
      'changing_clothes_assistance_note'        => null,
      'defecation_assistance_level_id'          => $this->faker->randomElement(self::ADL_OPTIONS['defecation']),
      'defecation_assistance_note'              => null,
      'urination_assistance_level_id'           => $this->faker->randomElement(self::ADL_OPTIONS['urination']),
      'urination_assistance_note'               => null,
      'communication_note'                      => null,
      'wish_of_user_and_familiy'                => null,
      'care_purpose'                            => null,
      'rehabilitation_program'                  => null,
      'home_rehabilitation'                     => null,
      'change_since_previous_planning'          => null,
      'note'                                    => null,
      'user_and_family_consent_date'            => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
    ];
  }
}
