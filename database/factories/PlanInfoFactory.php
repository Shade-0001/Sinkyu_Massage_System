<?php
// database/factories/PlanInfoFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PlanInfoFactory extends Factory
{
  private const ADL_LEVELS = ['全介助', '一部介助', '監視', '自立'];

  public function definition(): array
  {
    return [
      'clinic_user_id'          => null,
      'evaluation_date'         => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
      'evaluator'               => $this->faker->name(),
      'respiration'             => $this->faker->randomElement(['自立', '要観察', null]),
      'meal_assistance_level'   => $this->faker->randomElement(self::ADL_LEVELS),
      'meal_assistance_note'    => null,
      'mobility_level'          => $this->faker->randomElement(self::ADL_LEVELS),
      'mobility_note'           => null,
      'grooming_level'          => $this->faker->randomElement(self::ADL_LEVELS),
      'grooming_note'           => null,
      'toilet_level'            => $this->faker->randomElement(self::ADL_LEVELS),
      'toilet_note'             => null,
      'bathing_level'           => $this->faker->randomElement(self::ADL_LEVELS),
      'bathing_note'            => null,
      'flat_walking_level'      => $this->faker->randomElement(self::ADL_LEVELS),
      'flat_walking_note'       => null,
      'stairs_level'            => $this->faker->randomElement(self::ADL_LEVELS),
      'stairs_note'             => null,
      'dressing_level'          => $this->faker->randomElement(self::ADL_LEVELS),
      'dressing_note'           => null,
      'defecation_level'        => $this->faker->randomElement(self::ADL_LEVELS),
      'defecation_note'         => null,
      'urination_level'         => $this->faker->randomElement(self::ADL_LEVELS),
      'urination_note'          => null,
      'communication'           => null,
      'patient_family_request'  => null,
      'treatment_purpose'       => null,
      'rehabilitation_program'  => null,
      'home_rehabilitation'     => null,
      'improvement_changes'     => null,
      'disability_notes'        => null,
      'consent_date'            => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
    ];
  }
}
