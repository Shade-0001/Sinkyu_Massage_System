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
    $addendumOptions = ['詳細は別紙参照', '再評価後に変更あり', '前回同様'];
    $reasonAddendums = ['2階以上のため', 'エレベーターなし', '歩行困難なため', '車椅子使用のため'];

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
      'illness_name_acupuncture_addendum'    => $this->faker->boolean(30)
        ? $this->faker->randomElement($addendumOptions)
        : null,
      'is_housecall_required'                => true,
      'housecall_reason_id'                  => null,
      'housecall_reason_addendum'            => $this->faker->boolean(40)
        ? $this->faker->randomElement($reasonAddendums)
        : null,
      'therapy_period'                       => $this->faker->boolean(90)
        ? $this->faker->randomElement(['1ヶ月', '2ヶ月', '3ヶ月', '3ヶ月', '3ヶ月', '4ヶ月', '4ヶ月', '5ヶ月', '6ヶ月'])
        : $this->faker->randomElement(['1週間', '2週間', '3週間']),
      'first_therapy_content_id'             => null,
      'condition'                            => $this->faker->boolean(30)
        ? $this->faker->randomElement([1, 2, 3, 4, 5, 6, 7])
        : null,
      'work_scope_type_id'                   => null,
      'onset_and_injury_date'                => $this->faker->dateTimeBetween('-5 years', '-2 years')->format('Y-m-d'),
    ];
  }
}
