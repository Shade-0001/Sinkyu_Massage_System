<?php
// database/factories/InsuranceFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InsuranceFactory extends Factory
{
  public function definition(): array
  {
    $lastNames  = ['田中', '鈴木', '佐藤', '山田', '伊藤', '渡辺', '中村', '小林', '加藤', '吉田'];
    $firstNames = ['太郎', '花子', '健一', '美咲', '誠', '直子', '義雄', '由美', '浩', '恵子'];

    return [
      'clinic_user_id'                   => null,
      'insurers_id'                      => null,
      'insurance_type_1_id'              => $this->faker->randomElement([1, 2, 3]),
      'insurance_type_2_id'              => 1,
      'insurance_type_3_id'              => 1,
      'self_or_family_id'                => $this->faker->randomElement([1, 2]),
      'insured_number'                   => $this->faker->numerify('##########'),
      'code_number'                      => $this->faker->numerify('######'),
      'account_number'                   => $this->faker->numerify('########'),
      'locality_code'                    => $this->faker->boolean(50) ? $this->faker->numerify('######') : null,
      'recipient_code'                   => $this->faker->boolean(50) ? $this->faker->numerify('########') : null,
      'license_acquisition_date'         => $this->faker->boolean(50) ? $this->faker->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d') : null,
      'certification_date'               => $this->faker->boolean(50) ? $this->faker->dateTimeBetween('-3 years', '-6 months')->format('Y-m-d') : null,
      'issue_date'                       => $this->faker->dateTimeBetween('-3 years', '-6 months')->format('Y-m-d'),
      'expenses_borne_ratio_id'          => $this->faker->randomElement([1, 2, 3]),
      'expiry_date'                      => $this->faker->dateTimeBetween('+1 month', '+2 years')->format('Y-m-d'),
      'is_redeemed'                      => false,
      'insured_name'                     => $this->faker->boolean(50)
        ? $this->faker->randomElement($lastNames) . ' ' . $this->faker->randomElement($firstNames)
        : null,
      'relationship_with_clinic_user_id' => 1,
      'is_healthcare_subsidized'         => $this->faker->boolean(30),
      'public_funds_payer_code'          => $this->faker->boolean(30) ? $this->faker->numerify('######') : null,
      'public_funds_recipient_code'      => $this->faker->boolean(30) ? $this->faker->numerify('########') : null,
      'locality_code_family'             => $this->faker->boolean(30) ? $this->faker->numerify('######') : null,
      'recipient_code_family'            => $this->faker->boolean(30) ? $this->faker->numerify('########') : null,
    ];
  }
}
