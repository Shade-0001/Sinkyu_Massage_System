<?php
// database/factories/ConsentAcupunctureFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ConsentAcupunctureFactory extends Factory
{
  public function definition(): array
  {
    $consentingDate     = $this->faker->dateTimeBetween('-2 years', '-6 months');
    $startDate          = $consentingDate;
    $endDate            = (clone $consentingDate)->modify('+6 months');
    $benefitStart       = $consentingDate;
    $benefitEnd         = (clone $consentingDate)->modify('+6 months');
    $therapyPeriodStart = $consentingDate;
    $therapyPeriodEnd   = (clone $consentingDate)->modify('+3 months');

    return [
      'clinic_user_id'                       => null,
      'consenting_doctor_id'                 => null,
      'consenting_date'                      => $consentingDate->format('Y-m-d'),
      'consenting_start_date'                => $startDate->format('Y-m-d'),
      'consenting_end_date'                  => $endDate->format('Y-m-d'),
      'benefit_period_start_date'            => $benefitStart->format('Y-m-d'),
      'benefit_period_end_date'              => $benefitEnd->format('Y-m-d'),
      'first_care_date'                      => $consentingDate->format('Y-m-d'),
      'reconsenting_expiry'                  => $endDate->format('Y-m-d'),
      'bill_category_id'                     => null,
      'outcome_id'                           => null,
      'illness_name_acupuncture_id'          => $this->faker->randomElement([1, 2, 3, 4, 5]),
      'illness_name_acupuncture_addendum'    => null,
      'is_housecall_required'                => true,
      'housecall_reason_id'                  => null,
      'housecall_reason_addendum'            => null,
      'therapy_period'                       => '3ヶ月',
      'therapy_period_start_date'            => $therapyPeriodStart->format('Y-m-d'),
      'therapy_period_end_date'              => $therapyPeriodEnd->format('Y-m-d'),
      'first_therapy_content_id'             => null,
      'condition'                            => null,
      'work_scope_type_id'                   => null,
      'onset_and_injury_date'                => $this->faker->dateTimeBetween('-5 years', '-2 years')->format('Y-m-d'),
    ];
  }
}
