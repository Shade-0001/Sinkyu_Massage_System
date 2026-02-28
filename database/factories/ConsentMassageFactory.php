<?php
// database/factories/ConsentMassageFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ConsentMassageFactory extends Factory
{
  public function definition(): array
  {
    $consentingDate     = $this->faker->dateTimeBetween('-2 years', '-6 months');
    $startDate          = $consentingDate;
    $endDate            = (clone $consentingDate)->modify('+6 months');
    $benefitStart       = $consentingDate;
    $benefitEnd         = (clone $consentingDate)->modify('+6 months');
    $isSymptom2 = $this->faker->boolean(40);
    $isSymptom3 = $this->faker->boolean(20);

    $symptom2Addendums = ['上肢の拘縮', '下肢の拘縮', '体幹の拘縮', '頸部の拘縮'];
    $symptom3Addendums = ['右片麻痺', '左片麻痺', '対麻痺', '四肢麻痺'];
    $reasonAddendums   = ['2階以上のため', 'エレベーターなし', '歩行困難なため', '車椅子使用のため'];
    $notesOptions      = ['前回より状態改善', '訪問時家族立会い', '状態変化なし確認', '次回再評価予定'];

    return [
      'clinic_user_id'             => null,
      'consenting_doctor_id'       => null,
      'consenting_date'            => $consentingDate->format('Y-m-d'),
      'consenting_start_date'      => $startDate->format('Y-m-d'),
      'consenting_end_date'        => $endDate->format('Y-m-d'),
      'benefit_period_start_date'  => $benefitStart->format('Y-m-d'),
      'benefit_period_end_date'    => $benefitEnd->format('Y-m-d'),
      'first_care_date'            => $consentingDate->format('Y-m-d'),
      'injury_and_illness_name_id' => $this->faker->randomElement([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15]),
      'reconsenting_expiry'        => $endDate->format('Y-m-d'),
      'bill_category_id'           => null,
      'outcome_id'                 => null,
      'is_symptom_1'               => $this->faker->boolean(80),
      'is_symptom_2'               => $isSymptom2,
      'symtom_2_addendum'          => ($isSymptom2 && $this->faker->boolean(50))
        ? $this->faker->randomElement($symptom2Addendums)
        : null,
      'is_symptom_3'               => $isSymptom3,
      'symtom_3_addendum'          => ($isSymptom3 && $this->faker->boolean(50))
        ? $this->faker->randomElement($symptom3Addendums)
        : null,
      'is_therapy_type_1'          => $this->faker->boolean(70),
      'is_therapy_type_2'          => $this->faker->boolean(30),
      'is_housecall_required'      => true,
      'housecall_reason_id'        => null,
      'housecall_reason_addendum'  => $this->faker->boolean(40)
        ? $this->faker->randomElement($reasonAddendums)
        : null,
      'care_level'                 => $this->faker->randomElement(['要介護1', '要介護2', '要介護3', '要介護4', '要介護5', '要支援1', '要支援2', null]),
      'notes'                      => $this->faker->boolean(30)
        ? $this->faker->randomElement($notesOptions)
        : null,
      'therapy_period'             => '3ヶ月',
      'first_therapy_content_id'   => null,
      'condition_id'               => null,
      'work_scope_type_id'         => null,
      'onset_and_injury_date'      => $this->faker->dateTimeBetween('-5 years', '-2 years')->format('Y-m-d'),
    ];
  }
}
