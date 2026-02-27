<?php
// database/factories/InsuranceFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InsuranceFactory extends Factory
{
  public function definition(): array
  {
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
      'locality_code'                    => null,
      'recipient_code'                   => null,
      'license_acquisition_date'         => null,
      'certification_date'               => null,
      'issue_date'                       => $this->faker->dateTimeBetween('-3 years', '-6 months')->format('Y-m-d'),
      'expenses_borne_ratio_id'          => $this->faker->randomElement([1, 2, 3]),
      'expiry_date'                      => $this->faker->dateTimeBetween('+1 month', '+2 years')->format('Y-m-d'),
      'is_redeemed'                      => false,
      'insured_name'                     => null,
      'relationship_with_clinic_user_id' => 1,
      'is_healthcare_subsidized'         => false,
      'public_funds_payer_code'          => null,
      'public_funds_recipient_code'      => null,
      'locality_code_family'             => null,
      'recipient_code_family'            => null,
    ];
  }
}
