<?php

namespace App\Services\Print\Traits;

use Illuminate\Support\Facades\DB;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * あんま・マッサージ医療助成費支給申請書PDF - フォームフィールド関連メソッド
 */
trait MedicalAssistanceMassageFormFieldsTrait
{
  protected function fillTreatmentFees(Fpdi $pdf, array $data): void
  {
    $treatmentFees = $data['treatment_fees'] ?? null;
    $records = $data['records'];
    $insurance = $data['insurance'];

    // デバッグ:施術料金描画開始
    \Log::info('=== 施術料金描画開始 ===', [
      'sample_data_mode' => $this->sampleDataMode,
      'has_treatment_fees' => !empty($treatmentFees),
      'has_custom_sample_data' => $this->sampleDataMode && !empty($this->customSampleData),
      'records_count' => $records->count()
    ]);

    // サンプルデータモードでカスタムサンプルデータがある場合は直接使用
    if ($this->sampleDataMode && $this->customSampleData) {
      $custom = $this->customSampleData;

      // マッサージ料金（躯幹）
      if (isset($custom['fee_massage_trunk_unit']) && $this->hasCoord('fee_massage_trunk_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_trunk_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_trunk_unit', (string)$custom['fee_massage_trunk_unit']);
      }
      if (isset($custom['fee_massage_trunk_count']) && $this->hasCoord('fee_massage_trunk_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_trunk_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_trunk_count', (string)$custom['fee_massage_trunk_count']);
      }
      if (isset($custom['fee_massage_trunk_total']) && $this->hasCoord('fee_massage_trunk_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_trunk_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_trunk_total', (string)$custom['fee_massage_trunk_total']);
      }

      // マッサージ料金（右上肢）
      if (isset($custom['fee_massage_upper_limb_r_unit']) && $this->hasCoord('fee_massage_upper_limb_r_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_r_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_unit', (string)$custom['fee_massage_upper_limb_r_unit']);
      }
      if (isset($custom['fee_massage_upper_limb_r_count']) && $this->hasCoord('fee_massage_upper_limb_r_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_r_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_count', (string)$custom['fee_massage_upper_limb_r_count']);
      }
      if (isset($custom['fee_massage_upper_limb_r_total']) && $this->hasCoord('fee_massage_upper_limb_r_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_r_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_total', (string)$custom['fee_massage_upper_limb_r_total']);
      }

      // マッサージ料金（左上肢）
      if (isset($custom['fee_massage_upper_limb_l_unit']) && $this->hasCoord('fee_massage_upper_limb_l_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_l_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_unit', (string)$custom['fee_massage_upper_limb_l_unit']);
      }
      if (isset($custom['fee_massage_upper_limb_l_count']) && $this->hasCoord('fee_massage_upper_limb_l_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_l_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_count', (string)$custom['fee_massage_upper_limb_l_count']);
      }
      if (isset($custom['fee_massage_upper_limb_l_total']) && $this->hasCoord('fee_massage_upper_limb_l_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_l_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_total', (string)$custom['fee_massage_upper_limb_l_total']);
      }

      // マッサージ料金（右下肢）
      if (isset($custom['fee_massage_lower_limb_r_unit']) && $this->hasCoord('fee_massage_lower_limb_r_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_r_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_unit', (string)$custom['fee_massage_lower_limb_r_unit']);
      }
      if (isset($custom['fee_massage_lower_limb_r_count']) && $this->hasCoord('fee_massage_lower_limb_r_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_r_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_count', (string)$custom['fee_massage_lower_limb_r_count']);
      }
      if (isset($custom['fee_massage_lower_limb_r_total']) && $this->hasCoord('fee_massage_lower_limb_r_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_r_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_total', (string)$custom['fee_massage_lower_limb_r_total']);
      }

      // マッサージ料金（左下肢）
      if (isset($custom['fee_massage_lower_limb_l_unit']) && $this->hasCoord('fee_massage_lower_limb_l_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_l_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_unit', (string)$custom['fee_massage_lower_limb_l_unit']);
      }
      if (isset($custom['fee_massage_lower_limb_l_count']) && $this->hasCoord('fee_massage_lower_limb_l_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_l_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_count', (string)$custom['fee_massage_lower_limb_l_count']);
      }
      if (isset($custom['fee_massage_lower_limb_l_total']) && $this->hasCoord('fee_massage_lower_limb_l_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_l_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_total', (string)$custom['fee_massage_lower_limb_l_total']);
      }

      // 変形徒手矯正術
      if (isset($custom['fee_manual_correction_unit']) && $this->hasCoord('fee_manual_correction_unit')) {
        $pdf->SetFontSize($this->coord('fee_manual_correction_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_manual_correction_unit', (string)$custom['fee_manual_correction_unit']);
      }
      if (isset($custom['fee_manual_correction_count']) && $this->hasCoord('fee_manual_correction_count')) {
        $pdf->SetFontSize($this->coord('fee_manual_correction_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_manual_correction_count', (string)$custom['fee_manual_correction_count']);
      }
      if (isset($custom['fee_manual_correction_total']) && $this->hasCoord('fee_manual_correction_total')) {
        $pdf->SetFontSize($this->coord('fee_manual_correction_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_manual_correction_total', (string)$custom['fee_manual_correction_total']);
      }

      // 温罨法
      if (isset($custom['fee_fomentation_unit']) && $this->hasCoord('fee_fomentation_unit')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_unit', (string)$custom['fee_fomentation_unit']);
      }
      if (isset($custom['fee_fomentation_count']) && $this->hasCoord('fee_fomentation_count')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_count', (string)$custom['fee_fomentation_count']);
      }
      if (isset($custom['fee_fomentation_total']) && $this->hasCoord('fee_fomentation_total')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_total', (string)$custom['fee_fomentation_total']);
      }

      // 温罨法・電光線器具
      if (isset($custom['fee_fomentation_electric_light_unit']) && $this->hasCoord('fee_fomentation_electric_light_unit')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_electric_light_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_unit', (string)$custom['fee_fomentation_electric_light_unit']);
      }
      if (isset($custom['fee_fomentation_electric_light_count']) && $this->hasCoord('fee_fomentation_electric_light_count')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_electric_light_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_count', (string)$custom['fee_fomentation_electric_light_count']);
      }
      if (isset($custom['fee_fomentation_electric_light_total']) && $this->hasCoord('fee_fomentation_electric_light_total')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_electric_light_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_total', (string)$custom['fee_fomentation_electric_light_total']);
      }

      // 往療料
      if (isset($custom['fee_housecall_unit']) && $this->hasCoord('fee_housecall_unit')) {
        $pdf->SetFontSize($this->coord('fee_housecall_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_unit', (string)$custom['fee_housecall_unit']);
      }
      if (isset($custom['fee_housecall_count']) && $this->hasCoord('fee_housecall_count')) {
        $pdf->SetFontSize($this->coord('fee_housecall_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_count', (string)$custom['fee_housecall_count']);
      }
      if (isset($custom['fee_housecall_total']) && $this->hasCoord('fee_housecall_total')) {
        $pdf->SetFontSize($this->coord('fee_housecall_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_total', (string)$custom['fee_housecall_total']);
      }

      // 往療料4km超
      if (isset($custom['fee_housecall_additional_unit']) && $this->hasCoord('fee_housecall_additional_unit')) {
        $pdf->SetFontSize($this->coord('fee_housecall_additional_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_additional_unit', (string)$custom['fee_housecall_additional_unit']);
      }
      if (isset($custom['fee_housecall_additional_count']) && $this->hasCoord('fee_housecall_additional_count')) {
        $pdf->SetFontSize($this->coord('fee_housecall_additional_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_additional_count', (string)$custom['fee_housecall_additional_count']);
      }
      if (isset($custom['fee_housecall_additional_total']) && $this->hasCoord('fee_housecall_additional_total')) {
        $pdf->SetFontSize($this->coord('fee_housecall_additional_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_additional_total', (string)$custom['fee_housecall_additional_total']);
      }

      // 施術報告書交付料
      if (isset($custom['fee_previous_payment_unit']) && $this->hasCoord('fee_previous_payment_unit')) {
        $pdf->SetFontSize($this->coord('fee_previous_payment_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_previous_payment_unit', (string)$custom['fee_previous_payment_unit']);
      }
      if (isset($custom['fee_previous_payment_count']) && $this->hasCoord('fee_previous_payment_count')) {
        $pdf->SetFontSize($this->coord('fee_previous_payment_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_previous_payment_count', (string)$custom['fee_previous_payment_count']);
      }
      if (isset($custom['fee_previous_payment_total']) && $this->hasCoord('fee_previous_payment_total')) {
        $pdf->SetFontSize($this->coord('fee_previous_payment_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_previous_payment_total', (string)$custom['fee_previous_payment_total']);
      }

      // 合計
      if (isset($custom['fee_subtotal']) && $this->hasCoord('fee_subtotal')) {
        $pdf->SetFontSize($this->coord('fee_subtotal', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_subtotal', (string)$custom['fee_subtotal']);
      }

      // 一部負担金
      if (isset($custom['fee_partial_payment']) && $this->hasCoord('fee_partial_payment')) {
        $pdf->SetFontSize($this->coord('fee_partial_payment', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_partial_payment', (string)$custom['fee_partial_payment']);
      }

      // 請求額
      if (isset($custom['fee_total_claim']) && $this->hasCoord('fee_total_claim')) {
        $pdf->SetFontSize($this->coord('fee_total_claim', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_total_claim', (string)$custom['fee_total_claim']);
      }

      $pdf->SetFontSize(10);
      return;
    }

    // 通常モード:治療費データが存在しない場合は警告
    if (!$treatmentFees) {
      \Log::warning('施術料金データがありません - 描画スキップ');
      return;
    }

    // デバッグ:通常モードで施術料金データあり
    \Log::info('施術料金データ取得成功 - 通常モード描画処理', [
      'treatment_fees_id' => $treatmentFees->id ?? null
    ]);

    // 通常モード:施術実績から料金を計算
    $therapyTypeCounts = [];
    $bodypartCounts = []; // 部位ごとのカウント
    $isFirstTreatment = false;

    \Log::info('=== ノーマルモード：料金計算開始 ===', [
      'records_count' => $records->count(),
      'has_treatment_fees' => !empty($treatmentFees)
    ]);

    // 施術実績を集計
    foreach ($records as $index => $record) {
      $therapyContentId = $record->therapy_content_id ?? null;

      // 初検かどうかを判定（指定月に請求区分が「新規」のレコードがあるか）
      if (!$isFirstTreatment && isset($record->bill_category_id) && $record->bill_category_id == 1) {
        $isFirstTreatment = true;
      }

      // 施術内容ごとにカウント
      if ($therapyContentId) {
        if (!isset($therapyTypeCounts[$therapyContentId])) {
          $therapyTypeCounts[$therapyContentId] = 0;
        }
        $therapyTypeCounts[$therapyContentId]++;

        // therapy_content_id = 18 (マッサージ)の場合、部位情報を取得
        if ($therapyContentId == 18) {
          $bodyparts = DB::table('bodyparts-records')
            ->where('records_id', $record->id)
            ->pluck('therapy_type_bodyparts_id');

          foreach ($bodyparts as $bodypartId) {
            if (!isset($bodypartCounts[$bodypartId])) {
              $bodypartCounts[$bodypartId] = 0;
            }
            $bodypartCounts[$bodypartId]++;
          }
        }
      }
    }

    \Log::info('施術内容集計結果', [
      'therapy_type_counts' => $therapyTypeCounts,
      'bodypart_counts' => $bodypartCounts,
      'is_first_treatment' => $isFirstTreatment
    ]);

    $totalFee = 0;

    // マッサージ料金（躯幹）bodypart_id: 1
    $count = $bodypartCounts[1] ?? 0;
    $feeKey = $isFirstTreatment ? 'massage_trunk_first' : 'massage_trunk_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    \Log::info('マッサージ料金（躯幹）描画', [
      'bodypart_id' => 1,
      'count' => $count,
      'fee_key' => $feeKey,
      'unit_price' => $unitPrice,
      'total' => $total,
      'has_coord' => $this->hasCoord('fee_massage_trunk_unit')
    ]);

    if ($this->hasCoord('fee_massage_trunk_unit')) {
      $pdf->SetFontSize($this->coord('fee_massage_trunk_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_massage_trunk_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_massage_trunk_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_massage_trunk_total', (string)$total);
      $totalFee += $total;
    }

    // マッサージ料金（右上肢）bodypart_id: 2
    $count = $bodypartCounts[2] ?? 0;
    $feeKey = $isFirstTreatment ? 'massage_upper_limb_r_first' : 'massage_upper_limb_r_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_massage_upper_limb_r_unit')) {
      $pdf->SetFontSize($this->coord('fee_massage_upper_limb_r_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_total', (string)$total);
      $totalFee += $total;
    }

    // マッサージ料金（左上肢）bodypart_id: 3
    $count = $bodypartCounts[3] ?? 0;
    $feeKey = $isFirstTreatment ? 'massage_upper_limb_l_first' : 'massage_upper_limb_l_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_massage_upper_limb_l_unit')) {
      $pdf->SetFontSize($this->coord('fee_massage_upper_limb_l_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_total', (string)$total);
      $totalFee += $total;
    }

    // マッサージ料金（右下肢）bodypart_id: 4
    $count = $bodypartCounts[4] ?? 0;
    $feeKey = $isFirstTreatment ? 'massage_lower_limb_r_first' : 'massage_lower_limb_r_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_massage_lower_limb_r_unit')) {
      $pdf->SetFontSize($this->coord('fee_massage_lower_limb_r_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_total', (string)$total);
      $totalFee += $total;
    }

    // マッサージ料金（左下肢）bodypart_id: 5
    $count = $bodypartCounts[5] ?? 0;
    $feeKey = $isFirstTreatment ? 'massage_lower_limb_l_first' : 'massage_lower_limb_l_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_massage_lower_limb_l_unit')) {
      $pdf->SetFontSize($this->coord('fee_massage_lower_limb_l_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_total', (string)$total);
      $totalFee += $total;
    }

    // 変形徒手矯正術 therapy_content_id: 19
    $count = $therapyTypeCounts[19] ?? 0;
    $feeKey = $isFirstTreatment ? 'manual_correction_first' : 'manual_correction_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_manual_correction_unit')) {
      $pdf->SetFontSize($this->coord('fee_manual_correction_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_manual_correction_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_manual_correction_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_manual_correction_total', (string)$total);
      $totalFee += $total;
    }

    // 温罨法 therapy_content_id: 20
    $count = $therapyTypeCounts[20] ?? 0;
    $feeKey = $isFirstTreatment ? 'fomentation_first' : 'fomentation_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_fomentation_unit')) {
      $pdf->SetFontSize($this->coord('fee_fomentation_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_fomentation_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_fomentation_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_fomentation_total', (string)$total);
      $totalFee += $total;
    }

    // 温罨法・電光線器具 therapy_content_id: 21
    $count = $therapyTypeCounts[21] ?? 0;
    $feeKey = $isFirstTreatment ? 'fomentation_and_elec_ray_first' : 'fomentation_and_elec_ray_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_fomentation_electric_light_unit')) {
      $pdf->SetFontSize($this->coord('fee_fomentation_electric_light_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_total', (string)$total);
      $totalFee += $total;
    }

    // 往療料4km以下（マッサージ関連の施術のみカウント、0 < housecall_distance <= 4）
    $massageContentIds = [18, 19, 20, 21];
    $housecallCount = 0;
    foreach ($records as $record) {
      $distance = $record->housecall_distance ?? 0;
      if ($distance > 0 && $distance <= 4 && in_array($record->therapy_content_id ?? null, $massageContentIds)) {
        $housecallCount++;
      }
    }

    $feeKey = $isFirstTreatment ? 'housecall_max_2km_first' : 'housecall_max_2km_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $housecallCount;

    if ($this->hasCoord('fee_housecall_unit')) {
      $pdf->SetFontSize($this->coord('fee_housecall_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_housecall_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_housecall_count', (string)$housecallCount);
      $this->drawTextByKey($pdf, 'fee_housecall_total', (string)$total);
      $totalFee += $total;
    }

    // 往療料4km超（マッサージ関連の施術のみカウント、housecall_distance > 4で判定）
    $housecallAdditionalCount = 0;
    foreach ($records as $record) {
      if (isset($record->housecall_distance) && $record->housecall_distance > 4 && in_array($record->therapy_content_id ?? null, $massageContentIds)) {
        $housecallAdditionalCount++;
      }
    }

    $feeKey = $isFirstTreatment ? 'housecall_additional_max_4km_first' : 'housecall_additional_max_4km_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $housecallAdditionalCount;

    if ($this->hasCoord('fee_housecall_additional_unit')) {
      $pdf->SetFontSize($this->coord('fee_housecall_additional_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_housecall_additional_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_housecall_additional_count', (string)$housecallAdditionalCount);
      $this->drawTextByKey($pdf, 'fee_housecall_additional_total', (string)$total);
      $totalFee += $total;
    }

    // 合計
    if ($this->hasCoord('fee_subtotal')) {
      $pdf->SetFontSize($this->coord('fee_subtotal', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_subtotal', (string)$totalFee);
    }

    // 一部負担金計算
    $burdenRatio = 0;
    if (isset($insurance->expenses_borne_ratio)) {
      $burdenRatio = (int)$insurance->expenses_borne_ratio;
    }
    $partialPayment = (int)floor($totalFee * $burdenRatio / 100);

    if ($this->hasCoord('fee_partial_payment')) {
      $pdf->SetFontSize($this->coord('fee_partial_payment', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_partial_payment', (string)$partialPayment);
    }

    // 請求額
    $claimAmount = $totalFee - $partialPayment;
    if ($this->hasCoord('fee_total_claim')) {
      $pdf->SetFontSize($this->coord('fee_total_claim', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_total_claim', (string)$claimAmount);
    }

    $pdf->SetFontSize(10);
  }
  protected function fillTitleYearMonth(Fpdi $pdf, array $japaneseYear, int $month): void
  {
    // === 上部：年月 ===
    // タイトル行「療養費支給申請書（　年　月分）」
    if ($this->sampleDataMode) {
      // サンプルデータモード：カスタムサンプルデータを使用
      $titleYearMonth = $this->customSampleData['title_year_month'] ?? '令和 7年 12月分';
      $pdf->SetFontSize($this->coord('title_year_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'title_year_month', (string)$titleYearMonth);
    } else {
      // 通常モード：実データから和暦変換して「令和 7年 12月分」形式で描画
      $titleYearMonth = $japaneseYear['era'] . ' ' . $japaneseYear['year'] . '年 ' . (int)$month . '月分';
      $pdf->SetFontSize($this->coord('title_year_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'title_year_month', (string)$titleYearMonth);
    }
  }

  protected function fillInstitutionAndPublicFunds(Fpdi $pdf, $clinicInfo, $insurance): void
  {
    // === 機関コード（医療機関番号） ===
    $institutionCode = $this->sampleDataMode && isset($this->customSampleData['institution_code'])
      ? $this->customSampleData['institution_code']
      : ($clinicInfo->medical_institution_number ?? '');
    if ($institutionCode) {
      $pdf->SetFontSize($this->coord('institution_code', 'fontSize'));
      // 医療機関番号は通常7桁
      $this->fillBoxesByKey($pdf, 'institution_code', (string)$institutionCode, 7, 5.6);
      $pdf->SetFontSize(10);
    } else {
      \Log::warning('医療機関番号が設定されていません', ['clinic_info' => $clinicInfo]);
    }
    // === 公費負担者番号（8桁） ===
    if ($this->sampleDataMode && isset($this->customSampleData['public_funds_payer_number'])) {
      if ($this->customSampleData['public_funds_payer_number']) {
        $pdf->SetFontSize($this->coord('public_funds_payer_number', 'fontSize'));
        $this->fillBoxesByKey($pdf, 'public_funds_payer_number', (string)$this->customSampleData['public_funds_payer_number'], 8, 5.6);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->public_funds_payer_code) && $insurance->public_funds_payer_code) {
      $pdf->SetFontSize($this->coord('public_funds_payer_number', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'public_funds_payer_number', $insurance->public_funds_payer_code, 8, 5.6);
      $pdf->SetFontSize(10);
    }
    // === 公費受給者番号（7桁） ===
    if ($this->sampleDataMode && isset($this->customSampleData['public_funds_recipient_number'])) {
      if ($this->customSampleData['public_funds_recipient_number']) {
        $pdf->SetFontSize($this->coord('public_funds_recipient_number', 'fontSize'));
        $this->fillBoxesByKey($pdf, 'public_funds_recipient_number', (string)$this->customSampleData['public_funds_recipient_number'], 7, 5.6);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->public_funds_recipient_code) && $insurance->public_funds_recipient_code) {
      $pdf->SetFontSize($this->coord('public_funds_recipient_number', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'public_funds_recipient_number', $insurance->public_funds_recipient_code, 7, 5.6);
      $pdf->SetFontSize(10);
    }
    // === 区市町村番号（6桁） ===
    if ($this->sampleDataMode && isset($this->customSampleData['locality_code'])) {
      if ($this->customSampleData['locality_code']) {
        $pdf->SetFontSize($this->coord('locality_code', 'fontSize'));
        $this->fillBoxesByKey($pdf, 'locality_code', $this->customSampleData['locality_code'], 6, 5.6);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->locality_code) && $insurance->locality_code) {
      $pdf->SetFontSize($this->coord('locality_code', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'locality_code', $insurance->locality_code, 6, 5.6);
      $pdf->SetFontSize(10);
    }
    // === 受給者番号（区市町村番号と種類の下） ===
    if ($this->sampleDataMode && isset($this->customSampleData['recipient_number'])) {
      if ($this->customSampleData['recipient_number']) {
        $pdf->SetFontSize($this->coord('recipient_number', 'fontSize'));
        $this->fillBoxesByKey($pdf, 'recipient_number', $this->customSampleData['recipient_number'], 6, 5.6);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->recipient_code) && $insurance->recipient_code) {
      $pdf->SetFontSize($this->coord('recipient_number', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'recipient_number', $insurance->recipient_code, 6, 5.6);
      $pdf->SetFontSize(10);
    } else {
      \Log::warning('受給者番号が設定されていません', ['insurance' => $insurance]);
    }
  }

  protected function fillInsuranceType(Fpdi $pdf, $insurance): void
  {
    // === 保険種別１ ===
    if ($insurance && isset($insurance->insurance_type_1)) {
      $insuranceType1Map = [
        '社･国･組' => 'insurance_type_1_shakoku',
        '公費' => 'insurance_type_1_kouhi',
        '後期' => 'insurance_type_1_kouki',
        '退職' => 'insurance_type_1_taishoku',
      ];
      $key = $insuranceType1Map[$insurance->insurance_type_1] ?? null;
      if ($key) {
        $this->drawEllipseByKey($pdf, $key);
      }
    }
  }

  protected function fillPartialPaymentEllipse(Fpdi $pdf, $insurance): void
  {
    // === 一部負担金（楕円） ===
    $expensesBorneRatioKey = null;
    // isSelectedフラグをチェック（サンプルデータの場合）
    if (isset($this->coordinates['expenses_borne_ratio_10']['isSelected']) && $this->coordinates['expenses_borne_ratio_10']['isSelected']) {
      $expensesBorneRatioKey = 'expenses_borne_ratio_10';
      \Log::info('一部負担金: isSelected 10');
    } elseif (isset($this->coordinates['expenses_borne_ratio_20']['isSelected']) && $this->coordinates['expenses_borne_ratio_20']['isSelected']) {
      $expensesBorneRatioKey = 'expenses_borne_ratio_20';
      \Log::info('一部負担金: isSelected 20');
    } elseif (isset($this->coordinates['expenses_borne_ratio_30']['isSelected']) && $this->coordinates['expenses_borne_ratio_30']['isSelected']) {
      $expensesBorneRatioKey = 'expenses_borne_ratio_30';
      \Log::info('一部負担金: isSelected 30');
    } elseif ($insurance && isset($insurance->expenses_borne_ratio)) {
      // 通常モード：保険データから取得
      $ratioValue = (string)$insurance->expenses_borne_ratio;
      \Log::info('一部負担金: 保険データから取得', ['original' => $ratioValue]);
      // 半角数字+全角「割」と全角数字+全角「割」の両方に対応
      if ($ratioValue === '１割' || $ratioValue === '1割') $ratioValue = '10';
      if ($ratioValue === '２割' || $ratioValue === '2割') $ratioValue = '20';
      if ($ratioValue === '３割' || $ratioValue === '3割') $ratioValue = '30';
      $expensesBorneRatioMap = [
        '10' => 'expenses_borne_ratio_10',
        '20' => 'expenses_borne_ratio_20',
        '30' => 'expenses_borne_ratio_30',
      ];
      $expensesBorneRatioKey = $expensesBorneRatioMap[$ratioValue] ?? null;
      \Log::info('一部負担金: 変換後', ['converted' => $ratioValue, 'key' => $expensesBorneRatioKey]);
    }
    if ($expensesBorneRatioKey) {
      $this->drawEllipseByKey($pdf, $expensesBorneRatioKey);
    }
  }

  protected function fillInsuranceInfoSection(Fpdi $pdf, $insurance): void
  {
    // === 保険種別３ ===
    $insuranceType3Key = null;
    // isSelectedフラグをチェック（サンプルデータの場合）
    if (isset($this->coordinates['insurance_type_3_hongai']['isSelected']) && $this->coordinates['insurance_type_3_hongai']['isSelected']) {
      $insuranceType3Key = 'insurance_type_3_hongai';
    } elseif (isset($this->coordinates['insurance_type_3_sangai']['isSelected']) && $this->coordinates['insurance_type_3_sangai']['isSelected']) {
      $insuranceType3Key = 'insurance_type_3_sangai';
    } elseif (isset($this->coordinates['insurance_type_3_kagai']['isSelected']) && $this->coordinates['insurance_type_3_kagai']['isSelected']) {
      $insuranceType3Key = 'insurance_type_3_kagai';
    } elseif (isset($this->coordinates['insurance_type_3_kougai9']['isSelected']) && $this->coordinates['insurance_type_3_kougai9']['isSelected']) {
      $insuranceType3Key = 'insurance_type_3_kougai9';
    } elseif (isset($this->coordinates['insurance_type_3_kougai8']['isSelected']) && $this->coordinates['insurance_type_3_kougai8']['isSelected']) {
      $insuranceType3Key = 'insurance_type_3_kougai8';
    } elseif ($insurance && isset($insurance->insurance_type_3)) {
      // 通常モード：保険データから取得
      $insuranceType3Map = [
        '本外' => 'insurance_type_3_hongai',
        '三外' => 'insurance_type_3_sangai',
        '家外' => 'insurance_type_3_kagai',
        '高外９' => 'insurance_type_3_kougai9',
        '高外８' => 'insurance_type_3_kougai8',
      ];
      $insuranceType3Key = $insuranceType3Map[$insurance->insurance_type_3] ?? null;
    }
    if ($insuranceType3Key) {
      $this->drawEllipseByKey($pdf, $insuranceType3Key);
    }

    // === 保険者番号 ===
    if ($insurance && isset($insurance->insurer_number) && $insurance->insurer_number) {
      $pdf->SetFontSize($this->coord('insurer_number', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'insurer_number', $insurance->insurer_number, 8, 5.6);
      $pdf->SetFontSize(10);
    } else {
      \Log::warning('保険者番号が設定されていません', ['insurance' => $insurance]);
    }

    // === 被保険者記号 ===
    if ($this->sampleDataMode && isset($this->customSampleData['insurance_symbol_code'])) {
      if ($this->customSampleData['insurance_symbol_code']) {
        $pdf->SetFontSize($this->coord('insurance_symbol_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'insurance_symbol_code', (string)$this->customSampleData['insurance_symbol_code']);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->code_number) && $insurance->code_number) {
      $pdf->SetFontSize($this->coord('insurance_symbol_code', 'fontSize'));
      $this->drawTextByKey($pdf, 'insurance_symbol_code', (string)$insurance->code_number);
      $pdf->SetFontSize(10);
    }

    // === 被保険者番号 ===
    if ($this->sampleDataMode && isset($this->customSampleData['insurance_symbol_number'])) {
      if ($this->customSampleData['insurance_symbol_number']) {
        $pdf->SetFontSize($this->coord('insurance_symbol_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'insurance_symbol_number', (string)$this->customSampleData['insurance_symbol_number']);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->account_number) && $insurance->account_number) {
      $pdf->SetFontSize($this->coord('insurance_symbol_number', 'fontSize'));
      $this->drawTextByKey($pdf, 'insurance_symbol_number', (string)$insurance->account_number);
      $pdf->SetFontSize(10);
    }
  }

  protected function fillPatientBasicInfo(Fpdi $pdf, $clinicUser, $insurance, string $fullName): void
  {
    // === 療養を受けた者の氏名 ===
    $fullName = ($clinicUser->last_name ?? '') . ' ' . ($clinicUser->first_name ?? '');
    $fullNameKana = ($clinicUser->last_kana ?? '') . ' ' . ($clinicUser->first_kana ?? '');
    if (empty($fullName)) {
      \Log::warning('患者氏名が設定されていません', ['clinic_user' => $clinicUser]);
    }
    if (empty($fullNameKana)) {
      \Log::warning('患者氏名（カナ）が設定されていません', ['clinic_user' => $clinicUser]);
    }
    $pdf->SetFontSize($this->coord('patient_name_kana', 'fontSize'));
    $this->drawTextByKey($pdf, 'patient_name_kana', (string)$fullNameKana);
    $pdf->SetFontSize($this->coord('patient_name', 'fontSize'));
    $this->drawTextByKey($pdf, 'patient_name', (string)$fullName);
    $pdf->SetFontSize(10);
    // === 続柄 ===
    if ($insurance && isset($insurance->relationship) && $insurance->relationship) {
      $pdf->SetFontSize($this->coord('patient_relationship', 'fontSize'));
      $this->drawTextByKey($pdf, 'patient_relationship', (string)$insurance->relationship);
      $pdf->SetFontSize(10);
    }
    // === 性別（男・女に○を表示） ===
    $genderKey = null;
    // isSelectedフラグをチェック（サンプルデータの場合）
    if (isset($this->coordinates['patient_gender_male']['isSelected']) && $this->coordinates['patient_gender_male']['isSelected']) {
      $genderKey = 'patient_gender_male';
    } elseif (isset($this->coordinates['patient_gender_female']['isSelected']) && $this->coordinates['patient_gender_female']['isSelected']) {
      $genderKey = 'patient_gender_female';
    } elseif ($this->sampleDataMode && $this->customSampleData && isset($this->customSampleData['gender'])) {
      // サンプルデータモード：customSampleDataから取得
      $gender = $this->customSampleData['gender'];
      if ($gender === '男') {
        $genderKey = 'patient_gender_male';
      } elseif ($gender === '女') {
        $genderKey = 'patient_gender_female';
      }
    } elseif (isset($clinicUser->gender) && $clinicUser->gender) {
      // 通常モード：実データから判定
      if ($clinicUser->gender === '男') {
        $genderKey = 'patient_gender_male';
      } elseif ($clinicUser->gender === '女') {
        $genderKey = 'patient_gender_female';
      }
    }
    if ($genderKey) {
      $this->drawEllipseByKey($pdf, $genderKey);
    }
  }

  protected function fillPatientBirthday(Fpdi $pdf, $clinicUser): void
  {
    // === 生年月日 ===
    if ($this->sampleDataMode && $this->customSampleData) {
      // サンプルデータモード：customSampleDataから取得
      $birthYear = $this->customSampleData['birthday_year'] ?? null;
      $birthMonth = $this->customSampleData['birthday_month'] ?? null;
      $birthDay = $this->customSampleData['birthday_day'] ?? null;
      // isSelectedフラグまたはcustomSampleDataから元号を取得
      $birthdayEra = null;
      $birthdayEraKey = null;
      if (isset($this->coordinates['birthday_era_heisei']['isSelected']) && $this->coordinates['birthday_era_heisei']['isSelected']) {
        $birthdayEraKey = 'birthday_era_heisei';
        $birthdayEra = '平成';
      } elseif (isset($this->coordinates['birthday_era_showa']['isSelected']) && $this->coordinates['birthday_era_showa']['isSelected']) {
        $birthdayEraKey = 'birthday_era_showa';
        $birthdayEra = '昭和';
      } elseif (isset($this->coordinates['birthday_era_taisho']['isSelected']) && $this->coordinates['birthday_era_taisho']['isSelected']) {
        $birthdayEraKey = 'birthday_era_taisho';
        $birthdayEra = '大正';
      } elseif (isset($this->coordinates['birthday_era_meiji']['isSelected']) && $this->coordinates['birthday_era_meiji']['isSelected']) {
        $birthdayEraKey = 'birthday_era_meiji';
        $birthdayEra = '明治';
      } elseif (isset($this->customSampleData['birthday_era'])) {
        // customSampleDataから元号を取得
        $birthdayEra = $this->customSampleData['birthday_era'];
        if ($birthdayEra === '平成') {
          $birthdayEraKey = 'birthday_era_heisei';
        } elseif ($birthdayEra === '昭和') {
          $birthdayEraKey = 'birthday_era_showa';
        } elseif ($birthdayEra === '大正') {
          $birthdayEraKey = 'birthday_era_taisho';
        } elseif ($birthdayEra === '明治') {
          $birthdayEraKey = 'birthday_era_meiji';
        }
      }
      if ($birthdayEraKey) {
        $this->drawEllipseByKey($pdf, $birthdayEraKey);
      }
      // 生年月日を結合して描画（例: "令和 6年 3月 15日" または "30年 3月 15日"）
      if ($birthYear && $birthMonth && $birthDay) {
        $fullDate = '';
        if ($birthdayEra === '令和') {
          $fullDate = '令和 ' . $birthYear . '年 ' . (int)$birthMonth . '月 ' . (int)$birthDay . '日';
        } else {
          $fullDate = $birthYear . '年 ' . (int)$birthMonth . '月 ' . (int)$birthDay . '日';
        }
        $pdf->SetFontSize($this->coord('birthday_full_date', 'fontSize'));
        $this->drawTextByKey($pdf, 'birthday_full_date', $fullDate);
        $pdf->SetFontSize(10);
      }
    } elseif (isset($clinicUser->birthday)) {
      // 通常モード：実データから取得
      [$birthYear, $birthMonth, $birthDay] = explode('-', $clinicUser->birthday);
      $birthJapaneseYear = $this->convertToJapaneseYear((int)$birthYear, (int)$birthMonth);
      // 実データから判定（令和は除外）
      if ($birthJapaneseYear['era'] === '平成') {
        $this->drawEllipseByKey($pdf, 'birthday_era_heisei');
      } elseif ($birthJapaneseYear['era'] === '昭和') {
        $this->drawEllipseByKey($pdf, 'birthday_era_showa');
      } elseif ($birthJapaneseYear['era'] === '大正') {
        $this->drawEllipseByKey($pdf, 'birthday_era_taisho');
      } elseif ($birthJapaneseYear['era'] === '明治') {
        $this->drawEllipseByKey($pdf, 'birthday_era_meiji');
      }
      // 生年月日を結合して描画（例: "令和 6年 3月 15日" または "30年 3月 15日"）
      if ($birthJapaneseYear['era'] === '令和') {
        $fullDate = '令和 ' . $birthJapaneseYear['year'] . '年 ' . (int)$birthMonth . '月 ' . (int)$birthDay . '日';
      } else {
        $fullDate = $birthJapaneseYear['year'] . '年 ' . (int)$birthMonth . '月 ' . (int)$birthDay . '日';
      }
      $pdf->SetFontSize($this->coord('birthday_full_date', 'fontSize'));
      $this->drawTextByKey($pdf, 'birthday_full_date', $fullDate);
      $pdf->SetFontSize(10);
    } else {
      \Log::warning('生年月日が設定されていません', ['clinic_user' => $clinicUser]);
    }
  }

  protected function fillPatientAddressInfo(Fpdi $pdf, $clinicUser): void {}
  protected function fillTreatmentPeriodSection(Fpdi $pdf, \Illuminate\Support\Collection $records): void {}
  protected function fillDiseaseAndSymptoms(Fpdi $pdf, $consent): void {}

  protected function fillOnsetInfo(Fpdi $pdf, $consent): void
  {
    // === 発病又は負傷年月日 ===
    if ($this->hasCoord('onset_date')) {
      if ($this->sampleDataMode && $this->customSampleData) {
        // サンプルデータモード：統合フィールド形式で描画
        $onsetDate = $this->customSampleData['onset_date'] ?? '';
        if ($onsetDate) {
          $pdf->SetFontSize($this->coord('onset_date', 'fontSize'));
          $this->drawTextByKey($pdf, 'onset_date', $onsetDate);
        }
        $pdf->SetFontSize(10);
      } elseif ($consent && isset($consent->onset_and_injury_date) && $consent->onset_and_injury_date) {
        // 通常モード：実データから元号付き日付形式で描画
        [$onsetYear, $onsetMonth, $onsetDay] = explode('-', $consent->onset_and_injury_date);
        $onsetJapaneseYear = $this->convertToJapaneseYear((int)$onsetYear, (int)$onsetMonth);
        $formattedDate = sprintf(
          '%s%d年 %d月 %d日',
          $onsetJapaneseYear['era'],
          $onsetJapaneseYear['year'],
          (int)$onsetMonth,
          (int)$onsetDay
        );
        $pdf->SetFontSize($this->coord('onset_date', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date', $formattedDate);
        $pdf->SetFontSize(10);
      }
    }
    // === 傷病名（発病又は負傷年月日の隣） ===
    if ($this->sampleDataMode && $this->customSampleData) {
      // サンプルデータモード：customSampleDataから取得
      $onsetIllnessName = $this->customSampleData['onset_illness_name'] ?? '';
      if ($onsetIllnessName) {
        $pdf->SetFontSize($this->coord('onset_illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_illness_name', (string)$onsetIllnessName);
        $pdf->SetFontSize(10);
      }
    } elseif ($consent) {
      // 通常モード：実データから取得（マッサージ用）
      $onsetIllnessName = '';
      // illness_name_massage_idから病名を取得
      if (isset($consent->illness_name_massage_id) && $consent->illness_name_massage_id) {
        $illness = DB::table('illnesses_massage')
          ->where('id', $consent->illness_name_massage_id)
          ->first();
        if ($illness && isset($illness->illness_name_massage)) {
          $onsetIllnessName = $illness->illness_name_massage;
        }
      }
      // 追記がある場合は追加
      if (isset($consent->illness_name_massage_addendum) && $consent->illness_name_massage_addendum) {
        $onsetIllnessName .= ($onsetIllnessName ? '、' : '') . $consent->illness_name_massage_addendum;
      }
      if ($onsetIllnessName) {
        $pdf->SetFontSize($this->coord('onset_illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_illness_name', (string)$onsetIllnessName);
        $pdf->SetFontSize(10);
      }
    }
    // === 発病負傷の原因･経過 ===
    if ($this->sampleDataMode && $this->customSampleData) {
      // サンプルデータモード：customSampleDataから取得
      $conditionText = $this->customSampleData['condition'] ?? '';
      if ($conditionText) {
        // サンプルデータモードではテキストをそのまま使用
        $pdf->SetFontSize($this->coord('condition', 'fontSize'));
        $this->drawTextByKey($pdf, 'condition', (string)$conditionText);
        $pdf->SetFontSize(10);
      }
    } elseif ($consent && isset($consent->condition) && $consent->condition) {
      // 通常モード：実データから取得
      // IDから名称を取得
      $condition = \App\Models\Condition::find($consent->condition);
      $conditionName = $condition ? $condition->condition_name : '';
      if ($conditionName) {
        $pdf->SetFontSize($this->coord('condition', 'fontSize'));
        $this->drawTextByKey($pdf, 'condition', $conditionName);
        $pdf->SetFontSize(10);
      }
    }
  }

  protected function fillFirstTreatmentDate(Fpdi $pdf, \Illuminate\Support\Collection $records): void
  {
    // === 初療年月日 ===
    if ($this->hasCoord('first_treatment_date')) {
      if ($this->sampleDataMode && $this->customSampleData) {
        // サンプルデータモード：統合フィールド形式で描画
        $firstTreatmentDate = $this->customSampleData['first_treatment_date'] ?? '';
        if ($firstTreatmentDate) {
          $pdf->SetFontSize($this->coord('first_treatment_date', 'fontSize'));
          $this->drawTextByKey($pdf, 'first_treatment_date', $firstTreatmentDate);
        }
        $pdf->SetFontSize(10);
      } elseif ($records->isNotEmpty()) {
        // 通常モード：実データから元号付き日付形式で描画
        $firstRecord = $records->first();
        [$firstYear, $firstMonth, $firstDay] = explode('-', $firstRecord->date);
        $firstJapaneseYear = $this->convertToJapaneseYear((int)$firstYear, (int)$firstMonth);
        $formattedDate = sprintf(
          '%s%d年 %d月 %d日',
          $firstJapaneseYear['era'],
          $firstJapaneseYear['year'],
          (int)$firstMonth,
          (int)$firstDay
        );
        $pdf->SetFontSize($this->coord('first_treatment_date', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_date', $formattedDate);
        $pdf->SetFontSize(10);
      }
    }
  }

  protected function fillTreatmentDayCount(Fpdi $pdf, \Illuminate\Support\Collection $records): void
  {
    // === 実日数（施術内容欄） ===
    if ($this->hasCoord('treatment_day_count')) {
      if ($this->sampleDataMode && isset($this->customSampleData['treatment_days'])) {
        $dayCount = $this->customSampleData['treatment_days'];
      } else {
        // マッサージ関連の施術（therapy_content_id: 18-21）のみカウント
        $massageContentIds = [18, 19, 20, 21];
        $dayCount = $records->filter(function ($record) use ($massageContentIds) {
          return in_array($record->therapy_content_id ?? null, $massageContentIds);
        })->count();
      }
      $pdf->SetFontSize($this->coord('treatment_day_count', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_day_count', (string)$dayCount);
      $pdf->SetFontSize(10);
    }
  }

  protected function fillBillCategorySection(Fpdi $pdf, $consent): void
  {
    // === 請求区分 ===
    $billCategoryText = null;
    if ($this->sampleDataMode && $this->customSampleData && isset($this->customSampleData['bill_category'])) {
      // サンプルデータモード
      $billCategoryText = $this->customSampleData['bill_category'];
    } elseif ($consent && isset($consent->bill_category) && $consent->bill_category) {
      // 通常モード
      $billCategoryText = $consent->bill_category;
    }
    // テキストを描画
    if ($billCategoryText && isset($this->coordinates['bill_category'])) {
      \Log::info('【請求区分】テキスト描画', ['key' => 'bill_category', 'text' => $billCategoryText, 'method' => 'drawTextByKey']);
      $pdf->SetFontSize($this->coord('bill_category', 'fontSize'));
      $this->drawTextByKey($pdf, 'bill_category', $billCategoryText);
      $pdf->SetFontSize(10);
    }
  }

  protected function fillOutcomeSection(Fpdi $pdf, $consent): void
  {
    // === 転帰 ===
    $outcomeText = null;
    if ($this->sampleDataMode && $this->customSampleData && isset($this->customSampleData['outcome'])) {
      // サンプルデータモード
      $outcomeText = $this->customSampleData['outcome'];
    } elseif ($consent && isset($consent->outcome) && $consent->outcome) {
      // 通常モード
      $outcomeText = $consent->outcome;
    }
    // テキストを描画
    if ($outcomeText && isset($this->coordinates['outcome'])) {
      \Log::info('【転帰】テキスト描画', ['key' => 'outcome', 'text' => $outcomeText, 'method' => 'drawTextByKey']);
      $pdf->SetFontSize($this->coord('outcome', 'fontSize'));
      $this->drawTextByKey($pdf, 'outcome', $outcomeText);
      $pdf->SetFontSize(10);
    }
  }

  protected function fillWorkRelatedSection(Fpdi $pdf, $consent): void {}
  protected function fillCauseAndProgressSection(Fpdi $pdf, $consent): void {}
  protected function fillTreatmentMonth(Fpdi $pdf, string $serviceYearMonth): void {}
  protected function fillDiseaseAndSymptomsMassage(Fpdi $pdf, $consent): void {}
  protected function fillDiseaseCheckboxes(Fpdi $pdf, $consent): void {}
  protected function fillTreatmentDayCalendar(Fpdi $pdf, \Illuminate\Support\Collection $records): void {}

  protected function fillAbstractSection(Fpdi $pdf, \Illuminate\Support\Collection $records): void
  {
    // === 摘要 ===
    $abstractText = 'なし'; // デフォルト値
    if ($records->isNotEmpty()) {
      // 全レコードの摘要を結合（重複排除）
      // filter()で空文字列を除外し、さらに"。"だけや空白文字だけの要素も除外
      $abstracts = $records->pluck('abstract')
        ->filter(function($abstract) {
          return !empty(trim($abstract)) && trim($abstract) !== '。';
        })
        ->unique()
        ->values() // インデックスを0から振り直す
        ->toArray();
      if (!empty($abstracts)) {
        // "。"で区切る（前後に既に"。"がある場合は重複しないように）
        $abstractText = '　'; // 先頭に全角スペースを挿入
        foreach ($abstracts as $i => $abstract) {
          if ($i > 0) {
            // 前の文字列の末尾と現在の文字列の先頭をチェック
            $lastChar = mb_substr($abstractText, -1);
            $firstChar = mb_substr($abstract, 0, 1);
            if ($lastChar !== '。' && $firstChar !== '。') {
              $abstractText .= '。';
            }
          }
          $abstractText .= $abstract;
        }
        // 最後に"。"を追加（既に"。"で終わっている場合は追加しない）
        if (mb_substr($abstractText, -1) !== '。') {
          $abstractText .= '。';
        }
      }
    }
    // 摘要を描画
    if ($this->hasCoord('abstract')) {
      $fontSize = $this->coord('abstract', 'fontSize');
      $pdf->SetFontSize($fontSize);
      $this->drawTextByKey($pdf, 'abstract', $abstractText);
    }
  }

  protected function fillTherapistSection(Fpdi $pdf, $consent): void
  {
    // === 施術所情報の一部（施術者情報） ===
    // 施術管理者氏名（施術者情報から取得）
    if ($this->sampleDataMode && isset($this->customSampleData['clinic_manager'])) {
      $therapistName = $this->customSampleData['clinic_manager'];
      $pdf->SetFontSize($this->coord('clinic_manager', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_manager', (string)$therapistName);
      $pdf->SetFontSize(10);
    } else {
      $therapist = DB::table('therapists')->first();
      if ($therapist) {
        $therapistName = ($therapist->last_name ?? '') . ' ' . ($therapist->first_name ?? '');
        if (empty(trim($therapistName))) {
          \Log::warning('施術管理者氏名が設定されていません', ['therapist' => $therapist]);
        }
        $pdf->SetFontSize($this->coord('clinic_manager', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_manager', (string)$therapistName);
        $pdf->SetFontSize(10);
      } else {
        \Log::warning('施術者情報が見つかりません');
      }
    }
  }

  protected function fillHealthOfficeRegistration(Fpdi $pdf, $consent): void
  {
    // === 保健所登録区分 ===
    // このメソッドはfillClinicInfoSectionの一部として実装されているため、
    // ここでは空のままとする
  }

  protected function fillConsentRecordSection(Fpdi $pdf, $consent): void
  {
    // === 同意記録欄 ===
    // 同意医師氏名
    $consentDoctorName = $this->sampleDataMode && isset($this->customSampleData['consent_doctor_name'])
      ? $this->customSampleData['consent_doctor_name']
      : ($consent->consenting_doctor_name ?? '');
    if ($consentDoctorName) {
      $pdf->SetFontSize($this->coord('consent_doctor_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_doctor_name', (string)$consentDoctorName);
      $pdf->SetFontSize(10);
    }
    // 同意年月日
    if ($this->sampleDataMode && isset($this->customSampleData['consent_date_year'])) {
      $pdf->SetFontSize($this->coord('consent_date_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_date_year', (string)$this->customSampleData['consent_date_year']);
      $pdf->SetFontSize($this->coord('consent_date_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_date_month', (string)($this->customSampleData['consent_date_month'] ?? ''));
      $pdf->SetFontSize($this->coord('consent_date_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_date_day', (string)($this->customSampleData['consent_date_day'] ?? ''));
      $pdf->SetFontSize(10);
    } elseif ($consent && isset($consent->consenting_date) && $consent->consenting_date) {
      [$consentYear, $consentMonth, $consentDay] = explode('-', $consent->consenting_date);
      $consentJapaneseYear = $this->convertToJapaneseYear((int)$consentYear, (int)$consentMonth);
      $pdf->SetFontSize($this->coord('consent_date_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_date_year', (string)$consentJapaneseYear['year']);
      $pdf->SetFontSize($this->coord('consent_date_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_date_month', (string)(int)$consentMonth);
      $pdf->SetFontSize($this->coord('consent_date_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_date_day', (string)(int)$consentDay);
      $pdf->SetFontSize(10);
    }
    // 同意書の傷病名
    $consentIllnessName = $this->sampleDataMode && isset($this->customSampleData['consent_illness_name'])
      ? $this->customSampleData['consent_illness_name']
      : '';
    if (!$consentIllnessName && $consent && isset($consent->illness_name_massage_id) && $consent->illness_name_massage_id) {
      $illness = DB::table('illnesses_massage')
        ->where('id', $consent->illness_name_massage_id)
        ->first();
      if ($illness && isset($illness->illness_name_massage)) {
        $consentIllnessName = $illness->illness_name_massage;
        if (isset($consent->illness_name_massage_addendum) && $consent->illness_name_massage_addendum) {
          $consentIllnessName .= '、' . $consent->illness_name_massage_addendum;
        }
      }
    }
    if ($consentIllnessName) {
      $pdf->SetFontSize($this->coord('consent_illness_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_illness_name', (string)$consentIllnessName);
      $pdf->SetFontSize(10);
    }
    // 要加療期間
    $therapyPeriod = $this->sampleDataMode && isset($this->customSampleData['therapy_period'])
      ? $this->customSampleData['therapy_period']
      : ($consent->therapy_period ?? '');
    if ($therapyPeriod) {
      $pdf->SetFontSize($this->coord('therapy_period', 'fontSize'));
      $this->drawTextByKey($pdf, 'therapy_period', (string)$therapyPeriod);
      $pdf->SetFontSize(10);
    }
  }

  protected function fillApplicationSection(Fpdi $pdf, string $submissionDate): void
  {
    // === 申請欄：提出年月日 ===
    if ($this->sampleDataMode && isset($this->customSampleData['submission_date_year'])) {
      // サンプルデータモード：customSampleDataを使用
      $pdf->SetFontSize($this->coord('submission_date_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'submission_date_year', (string)$this->customSampleData['submission_date_year']);
      if (isset($this->customSampleData['submission_date_month'])) {
        $pdf->SetFontSize($this->coord('submission_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'submission_date_month', (string)$this->customSampleData['submission_date_month']);
      }
      if (isset($this->customSampleData['submission_date_day'])) {
        $pdf->SetFontSize($this->coord('submission_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'submission_date_day', (string)$this->customSampleData['submission_date_day']);
      }
      $pdf->SetFontSize(10);
    } else {
      // 通常モード：実データから和暦変換
      $submissionParts = explode('-', $submissionDate);
      $submissionJapaneseYear = $this->convertToJapaneseYear((int)$submissionParts[0], (int)$submissionParts[1]);
      $pdf->SetFontSize($this->coord('submission_date_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'submission_date_year', (string)$submissionJapaneseYear['year']);
      $pdf->SetFontSize($this->coord('submission_date_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'submission_date_month', (string)(int)$submissionParts[1]);
      $pdf->SetFontSize($this->coord('submission_date_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'submission_date_day', (string)(int)$submissionParts[2]);
      $pdf->SetFontSize(10);
    }
  }

  protected function fillPaymentInstitutionSection(Fpdi $pdf, $clinicInfo): void
  {
    // clinic_infoテーブルから銀行口座情報を取得（ノーマルモード用）
    $clinicInfoData = null;
    if (!$this->sampleDataMode) {
      $clinicInfoData = DB::table('clinic_info')->first();
    }

    // === 支払機関情報 ===
    // 支払区分
    if ($this->hasCoord('payment_method')) {
      $paymentMethod = $this->customSampleData['payment_method'] ?? '';
      if ($paymentMethod) {
        $pdf->SetFontSize($this->coord('payment_method', 'fontSize'));
        $this->drawTextByKey($pdf, 'payment_method', $paymentMethod);
      }
    }
    // 預金の種類
    if ($this->hasCoord('deposit_type')) {
      $depositType = $this->sampleDataMode && isset($this->customSampleData['deposit_type'])
        ? $this->customSampleData['deposit_type']
        : ($clinicInfoData->bank_account_type ?? '');
      if ($depositType) {
        $pdf->SetFontSize($this->coord('deposit_type', 'fontSize'));
        $this->drawTextByKey($pdf, 'deposit_type', $depositType);
      }
    }
    // 金融機関名（種類）
    if ($this->hasCoord('financial_institution_type')) {
      $financialInstitutionType = $this->customSampleData['financial_institution_type'] ?? '';
      if ($financialInstitutionType) {
        $pdf->SetFontSize($this->coord('financial_institution_type', 'fontSize'));
        $this->drawTextByKey($pdf, 'financial_institution_type', $financialInstitutionType);
      }
    }
    // 金融機関名（詳細）
    if ($this->hasCoord('financial_institution_name')) {
      $financialInstitutionName = $this->sampleDataMode && isset($this->customSampleData['financial_institution_name'])
        ? $this->customSampleData['financial_institution_name']
        : ($clinicInfoData->bank_name ?? '') . ($clinicInfoData->bank_branch_name ?? '');
      if ($financialInstitutionName) {
        $pdf->SetFontSize($this->coord('financial_institution_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'financial_institution_name', $financialInstitutionName);
      }
    }
    // 本店支店出張所（種類）
    if ($this->hasCoord('branch_type')) {
      $branchType = $this->customSampleData['branch_type'] ?? '';
      if ($branchType) {
        $pdf->SetFontSize($this->coord('branch_type', 'fontSize'));
        $this->drawTextByKey($pdf, 'branch_type', $branchType);
      }
    }
    // 支店名
    if ($this->hasCoord('branch_name')) {
      $branchName = $this->sampleDataMode && isset($this->customSampleData['branch_name'])
        ? $this->customSampleData['branch_name']
        : ($clinicInfoData->bank_branch_name ?? '');
      if ($branchName) {
        $pdf->SetFontSize($this->coord('branch_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'branch_name', $branchName);
      }
    }
    // 口座番号
    if ($this->hasCoord('bank_account_number')) {
      $accountNumber = $this->sampleDataMode && isset($this->customSampleData['account_number'])
        ? $this->customSampleData['account_number']
        : ($clinicInfoData->bank_account_number ?? '');
      if ($accountNumber) {
        $pdf->SetFontSize($this->coord('bank_account_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'bank_account_number', (string)$accountNumber);
      }
    }
    // 口座名義（カナ）
    if ($this->hasCoord('bank_account_holder_kana')) {
      $accountHolder = $this->sampleDataMode && isset($this->customSampleData['account_holder'])
        ? $this->customSampleData['account_holder']
        : ($clinicInfoData->bank_account_name_kana ?? '');
      if ($accountHolder) {
        $pdf->SetFontSize($this->coord('bank_account_holder_kana', 'fontSize'));
        $this->drawTextByKey($pdf, 'bank_account_holder_kana', (string)$accountHolder);
      }
    }
    $pdf->SetFontSize(10);
    // === 支払機関欄の楕円描画 ===
    // 支払区分
    $paymentMethodKey = null;
    if (isset($this->coordinates['payment_category_furikomi']['isSelected']) && $this->coordinates['payment_category_furikomi']['isSelected']) {
      $paymentMethodKey = 'payment_category_furikomi';
    } elseif (isset($this->coordinates['payment_category_bank_transfer']['isSelected']) && $this->coordinates['payment_category_bank_transfer']['isSelected']) {
      $paymentMethodKey = 'payment_category_bank_transfer';
    } elseif (isset($this->coordinates['payment_category_post_transfer']['isSelected']) && $this->coordinates['payment_category_post_transfer']['isSelected']) {
      $paymentMethodKey = 'payment_category_post_transfer';
    } elseif (isset($this->coordinates['payment_category_local_payment']['isSelected']) && $this->coordinates['payment_category_local_payment']['isSelected']) {
      $paymentMethodKey = 'payment_category_local_payment';
    }
    if ($paymentMethodKey) {
      $this->drawEllipseByKey($pdf, $paymentMethodKey);
    }
    // 預金の種類
    $depositTypeKey = null;
    if (isset($this->coordinates['deposit_type_ordinary']['isSelected']) && $this->coordinates['deposit_type_ordinary']['isSelected']) {
      $depositTypeKey = 'deposit_type_ordinary';
    } elseif (isset($this->coordinates['deposit_type_current']['isSelected']) && $this->coordinates['deposit_type_current']['isSelected']) {
      $depositTypeKey = 'deposit_type_current';
    } elseif (isset($this->coordinates['deposit_type_notice']['isSelected']) && $this->coordinates['deposit_type_notice']['isSelected']) {
      $depositTypeKey = 'deposit_type_notice';
    } elseif (isset($this->coordinates['deposit_type_betsudan']['isSelected']) && $this->coordinates['deposit_type_betsudan']['isSelected']) {
      $depositTypeKey = 'deposit_type_betsudan';
    }
    if ($depositTypeKey) {
      $this->drawEllipseByKey($pdf, $depositTypeKey);
    }
    // 金融機関種類
    $financialInstitutionTypeKey = null;
    if (isset($this->coordinates['financial_institution_type_bank']['isSelected']) && $this->coordinates['financial_institution_type_bank']['isSelected']) {
      $financialInstitutionTypeKey = 'financial_institution_type_bank';
    } elseif (isset($this->coordinates['financial_institution_type_kinko']['isSelected']) && $this->coordinates['financial_institution_type_kinko']['isSelected']) {
      $financialInstitutionTypeKey = 'financial_institution_type_kinko';
    } elseif (isset($this->coordinates['financial_institution_type_nokyo']['isSelected']) && $this->coordinates['financial_institution_type_nokyo']['isSelected']) {
      $financialInstitutionTypeKey = 'financial_institution_type_nokyo';
    }
    if ($financialInstitutionTypeKey) {
      $this->drawEllipseByKey($pdf, $financialInstitutionTypeKey);
    }
    // 本店支店出張所
    $branchTypeKey = null;
    if (isset($this->coordinates['branch_type_honten']['isSelected']) && $this->coordinates['branch_type_honten']['isSelected']) {
      $branchTypeKey = 'branch_type_honten';
    } elseif (isset($this->coordinates['branch_type_shiten']['isSelected']) && $this->coordinates['branch_type_shiten']['isSelected']) {
      $branchTypeKey = 'branch_type_shiten';
    } elseif (isset($this->coordinates['branch_type_shucchoujo']['isSelected']) && $this->coordinates['branch_type_shucchoujo']['isSelected']) {
      $branchTypeKey = 'branch_type_shucchoujo';
    }
    if ($branchTypeKey) {
      $this->drawEllipseByKey($pdf, $branchTypeKey);
    }
  }

  protected function fillDelegationSection(Fpdi $pdf, $insurance, $doctor): void {}
  protected function fillTreatmentFeeSection(Fpdi $pdf, \Illuminate\Support\Collection $records, $insurance): void {}

  /**
   * 申請者情報を埋める
   */
  protected function fillApplicantInfo($pdf, $clinicUser, $fullName): void
  {
    // === 申請者情報 ===
    // 申請者郵便番号（前半3桁・後半4桁に分割）
    if ($this->hasCoord('applicant_postal_code')) {
      $applicantPostalCode = $this->sampleDataMode && isset($this->customSampleData['applicant_postal_code'])
        ? $this->customSampleData['applicant_postal_code']
        : ($clinicUser->postal_code ?? '');
      // ハイフンを削除して数字のみにする
      $postalCodeNumbers = preg_replace('/[^0-9]/', '', $applicantPostalCode);
      // 3桁-4桁の形式にフォーマット
      if (strlen($postalCodeNumbers) === 7) {
        $formattedPostalCode = substr($postalCodeNumbers, 0, 3) . '-' . substr($postalCodeNumbers, 3, 4);
      } else {
        $formattedPostalCode = $postalCodeNumbers;
      }
      $pdf->SetFontSize($this->coord('applicant_postal_code', 'fontSize'));
      $this->drawTextByKey($pdf, 'applicant_postal_code', $formattedPostalCode);
      $pdf->SetFontSize(10);
    }
    // 申請者住所
    if ($this->sampleDataMode && isset($this->customSampleData['applicant_address'])) {
      $address = $this->customSampleData['applicant_address'];
    } else {
      $address = ($clinicUser->address_1 ?? '') .
                 ($clinicUser->address_2 ?? '') .
                 ($clinicUser->address_3 ?? '');
    }
    $pdf->SetFontSize($this->coord('applicant_address', 'fontSize'));
    $this->drawTextByKey($pdf, 'applicant_address', (string)$address);
    // 申請者氏名
    $applicantName = $this->sampleDataMode && isset($this->customSampleData['applicant_name'])
      ? $this->customSampleData['applicant_name']
      : $fullName;
    $pdf->SetFontSize($this->coord('applicant_name', 'fontSize'));
    $this->drawTextByKey($pdf, 'applicant_name', (string)$applicantName);
    // 患者住所（申請欄）
    if ($this->hasCoord('patient_address')) {
      $patientAddress = $this->sampleDataMode && isset($this->customSampleData['address'])
        ? $this->customSampleData['address']
        : (($clinicUser->address_1 ?? '') . ($clinicUser->address_2 ?? '') . ($clinicUser->address_3 ?? ''));
      if ($patientAddress) {
        $pdf->SetFontSize($this->coord('patient_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'patient_address', (string)$patientAddress);
      }
    }
    // 電話番号
    if ($this->hasCoord('patient_phone')) {
      $phone = $this->sampleDataMode && isset($this->customSampleData['patient_phone'])
        ? $this->customSampleData['patient_phone']
        : ($clinicUser->phone ?? '');
      if ($phone) {
        $pdf->SetFontSize($this->coord('patient_phone', 'fontSize'));
        $this->drawTextByKey($pdf, 'patient_phone', (string)$phone);
      }
    }
    $pdf->SetFontSize(10);
  }

  /**
   * 被保険者情報を埋める
   */
  protected function fillTemporaryInsurerName($pdf, $fullName): void
  {
    // === 被保険者情報 ===
    // 署名オプションで委任者氏名を空白にする場合はスキップ
    if ($this->signatureOption === 'user_signature_blank' || $this->signatureOption === 'user_address_signature_blank') {
      return;
    }

    if ($this->hasCoord('temporary_insurer_name')) {
      // サンプルデータモード時は専用の値を使用、実データモードでは申請者氏名と同じデータを参照
      $tempInsurerName = $this->sampleDataMode
        ? ($this->customSampleData['temporary_insurer_name'] ?? '')
        : $fullName;
      if ($tempInsurerName) {
        $pdf->SetFontSize($this->coord('temporary_insurer_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'temporary_insurer_name', (string)$tempInsurerName);
        $pdf->SetFontSize(10);
      }
    }
  }

  /**
   * 施術所情報セクションを埋める
   */
  protected function fillClinicInfoSection($pdf, $clinicInfo, $submissionDate): void
  {
    // === 施術所情報 ===
    if ($clinicInfo) {
      // 施術証明年月日
      if ($this->sampleDataMode && isset($this->customSampleData['clinic_date_year'])) {
        $pdf->SetFontSize($this->coord('clinic_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_date_year', (string)$this->customSampleData['clinic_date_year']);
        $pdf->SetFontSize($this->coord('clinic_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_date_month', (string)($this->customSampleData['clinic_date_month'] ?? ''));
        $pdf->SetFontSize($this->coord('clinic_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_date_day', (string)($this->customSampleData['clinic_date_day'] ?? ''));
      } else {
        $submissionParts = explode('-', $submissionDate);
        $submissionJapaneseYear = $this->convertToJapaneseYear((int)$submissionParts[0], (int)$submissionParts[1]);
        $pdf->SetFontSize($this->coord('clinic_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_date_year', (string)$submissionJapaneseYear['year']);
        $pdf->SetFontSize($this->coord('clinic_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_date_month', (string)(int)$submissionParts[1]);
        $pdf->SetFontSize($this->coord('clinic_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_date_day', (string)(int)$submissionParts[2]);
      }
      $pdf->SetFontSize(10);
      // 施術所郵便番号
      if ($this->hasCoord('clinic_postal_code')) {
        $pdf->SetFontSize($this->coord('clinic_postal_code', 'fontSize'));
        $clinicPostalCode = $this->sampleDataMode && isset($this->customSampleData['clinic_postal_code'])
          ? $this->customSampleData['clinic_postal_code']
          : ($clinicInfo->postal_code ?? '');
        // ハイフンを除去
        $cleanPostalCode = str_replace('-', '', $clinicPostalCode);
        // 7桁の数字を〒 XXX-XXXX形式にフォーマット
        $formattedPostalCode = $clinicPostalCode;
        if (preg_match('/^\d{7}$/', $cleanPostalCode)) {
          $formattedPostalCode = '〒 ' . substr($cleanPostalCode, 0, 3) . '-' . substr($cleanPostalCode, 3, 4);
        }
        $this->drawTextByKey($pdf, 'clinic_postal_code', (string)$formattedPostalCode);
      }
      // 施術所住所
      if ($this->sampleDataMode && isset($this->customSampleData['clinic_address'])) {
        $clinicAddress = $this->customSampleData['clinic_address'];
      } else {
        $clinicAddress = ($clinicInfo->address_1 ?? '') .
                         ($clinicInfo->address_2 ?? '') .
                         ($clinicInfo->address_3 ?? '');
      }
      $pdf->SetFontSize($this->coord('clinic_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_address', (string)$clinicAddress);
      $pdf->SetFontSize(10);
      // 施術所名称
      $pdf->SetFontSize($this->coord('clinic_name', 'fontSize'));
      $clinicName = $this->sampleDataMode && isset($this->customSampleData['clinic_name'])
        ? $this->customSampleData['clinic_name']
        : ($clinicInfo->clinic_name ?? '');
      $this->drawTextByKey($pdf, 'clinic_name', (string)$clinicName);
      $pdf->SetFontSize(10);
      // 施術管理者氏名（施術者情報から取得）
      if ($this->sampleDataMode && isset($this->customSampleData['clinic_manager'])) {
        $therapistName = $this->customSampleData['clinic_manager'];
        $pdf->SetFontSize($this->coord('clinic_manager', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_manager', (string)$therapistName);
        $pdf->SetFontSize(10);
      } else {
        $therapist = DB::table('therapists')->first();
        if ($therapist) {
          $therapistName = ($therapist->last_name ?? '') . ' ' . ($therapist->first_name ?? '');
          if (empty(trim($therapistName))) {
            \Log::warning('施術管理者氏名が設定されていません', ['therapist' => $therapist]);
          }
          $pdf->SetFontSize($this->coord('clinic_manager', 'fontSize'));
          $this->drawTextByKey($pdf, 'clinic_manager', (string)$therapistName);
          $pdf->SetFontSize(10);
        } else {
          \Log::warning('施術者情報が見つかりません');
        }
      }
      // 電話番号
      $clinicPhone = $this->sampleDataMode && isset($this->customSampleData['clinic_phone'])
        ? $this->customSampleData['clinic_phone']
        : ($clinicInfo->phone ?? '');
      if (empty($clinicPhone)) {
        \Log::warning('施術所電話番号が設定されていません', ['clinic_info' => $clinicInfo]);
      }
      $pdf->SetFontSize($this->coord('clinic_phone', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_phone', (string)$clinicPhone);
      $pdf->SetFontSize(10);
      // === 保健所登録区分 ===
      // isSelectedフラグをチェック（座標調整ツールで選択された場合）
      if (isset($this->coordinates['health_center_registration_1']['isSelected']) && $this->coordinates['health_center_registration_1']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'health_center_registration_1');
      } elseif (isset($this->coordinates['health_center_registration_2']['isSelected']) && $this->coordinates['health_center_registration_2']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'health_center_registration_2');
      } elseif ($this->sampleDataMode && isset($this->customSampleData['health_center_registration'])) {
        // サンプルデータモード：customSampleDataから取得
        $healthCenterRegistration = $this->customSampleData['health_center_registration'];
        if (strpos($healthCenterRegistration, '施術所') !== false) {
          $this->drawEllipseByKey($pdf, 'health_center_registration_1');
        } elseif (strpos($healthCenterRegistration, '出張') !== false) {
          $this->drawEllipseByKey($pdf, 'health_center_registration_2');
        }
      } else {
        // 通常モード：DBから取得
        $healthCenterRegistration = $clinicInfo->health_center_registerd_location ?? '';
        if (strpos($healthCenterRegistration, '施術所') !== false) {
          $this->drawEllipseByKey($pdf, 'health_center_registration_1');
        } elseif (strpos($healthCenterRegistration, '出張') !== false) {
          $this->drawEllipseByKey($pdf, 'health_center_registration_2');
        }
      }

      // 施術者情報を取得（ノーマルモード用）
      $therapist = null;
      if (!$this->sampleDataMode) {
        $therapist = DB::table('therapists')->first();
      }

      // === 免許番号（はり師） ===
      $licenseHariNumber = $this->sampleDataMode && isset($this->customSampleData['license_hari_number'])
        ? $this->customSampleData['license_hari_number']
        : ($therapist->license_hari_code_number ?? '');
      if ($licenseHariNumber && isset($this->coordinates['license_hari_number'])) {
        $pdf->SetFontSize($this->coord('license_hari_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'license_hari_number', (string)$licenseHariNumber);
        $pdf->SetFontSize(10);
      }
      // === 免許番号（きゅう師） ===
      $licenseKyuNumber = $this->sampleDataMode && isset($this->customSampleData['license_kyu_number'])
        ? $this->customSampleData['license_kyu_number']
        : ($therapist->license_kyu_code_number ?? '');
      if ($licenseKyuNumber && isset($this->coordinates['license_kyu_number'])) {
        $pdf->SetFontSize($this->coord('license_kyu_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'license_kyu_number', (string)$licenseKyuNumber);
        $pdf->SetFontSize(10);
      }
      // === 施術者郵便番号 ===
      $therapistPostalCode = $this->sampleDataMode && isset($this->customSampleData['therapist_postal_code'])
        ? $this->customSampleData['therapist_postal_code']
        : ($therapist->postal_code ?? '');
      if ($therapistPostalCode && isset($this->coordinates['therapist_postal_code'])) {
        $pdf->SetFontSize($this->coord('therapist_postal_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'therapist_postal_code', '〒 ' . (string)$therapistPostalCode);
        $pdf->SetFontSize(10);
      }
      // === 施術者住所 ===
      $therapistAddress = $this->sampleDataMode && isset($this->customSampleData['therapist_address'])
        ? $this->customSampleData['therapist_address']
        : (($therapist->address_1 ?? '') . ($therapist->address_2 ?? '') . ($therapist->address_3 ?? ''));
      if ($therapistAddress && isset($this->coordinates['therapist_address'])) {
        $pdf->SetFontSize($this->coord('therapist_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'therapist_address', (string)$therapistAddress);
        $pdf->SetFontSize(10);
      }
      // === 施術者氏名 ===
      $therapistNameField = $this->sampleDataMode && isset($this->customSampleData['therapist_name'])
        ? $this->customSampleData['therapist_name']
        : (($therapist->last_name ?? '') . ' ' . ($therapist->first_name ?? ''));
      if (trim($therapistNameField) && isset($this->coordinates['therapist_name'])) {
        $pdf->SetFontSize($this->coord('therapist_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'therapist_name', (string)$therapistNameField);
        $pdf->SetFontSize(10);
      }
      // === 施術者電話番号 ===
      $therapistPhoneField = $this->sampleDataMode && isset($this->customSampleData['therapist_phone'])
        ? $this->customSampleData['therapist_phone']
        : ($therapist->phone ?? '');
      if ($therapistPhoneField && isset($this->coordinates['therapist_phone'])) {
        $pdf->SetFontSize($this->coord('therapist_phone', 'fontSize'));
        $this->drawTextByKey($pdf, 'therapist_phone', (string)$therapistPhoneField);
        $pdf->SetFontSize(10);
      }
      // === 登録記号番号（施術者番号） ===
      $therapistNumber = $this->sampleDataMode && isset($this->customSampleData['therapist_registration_number'])
        ? $this->customSampleData['therapist_registration_number']
        : ($clinicInfo->therapist_number ?? '');
      if ($therapistNumber) {
        $pdf->SetFontSize($this->coord('therapist_registration_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'therapist_registration_number', (string)$therapistNumber);
        $pdf->SetFontSize(10);
      }
    }
  }

  /**
   * 代理人情報を埋める（サンプルデータモード用）
   */
  protected function fillAgentInfo($pdf): void
  {
    // === 代理人情報 ===
    if ($this->hasCoord('agent_postal_code') || $this->hasCoord('agent_address') || $this->hasCoord('agent_name')) {
      // カスタムサンプルデータから取得、なければ空文字
      $agentPostalCode = $this->customSampleData['agent_postal_code'] ?? '';
      $agentAddress = $this->customSampleData['agent_address'] ?? '';
      $agentName = $this->customSampleData['agent_name'] ?? '';
      if ($this->hasCoord('agent_postal_code') && $agentPostalCode) {
        $pdf->SetFontSize($this->coord('agent_postal_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_postal_code', (string)$agentPostalCode);
      }
      if ($this->hasCoord('agent_address') && $agentAddress) {
        $pdf->SetFontSize($this->coord('agent_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_address', (string)$agentAddress);
      }
      if ($this->hasCoord('agent_name') && $agentName) {
        $pdf->SetFontSize($this->coord('agent_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_name', (string)$agentName);
      }
      $pdf->SetFontSize(10);
    }
  }

  /**
   * ノーマルモードのフィールドを埋める
   */
  protected function fillRealDataModeFields($pdf, $data, $insurance, $consent, $clinicInfo, $clinicUser, $submissionDate): void
  {
    // === 代理人情報（実データモード） ===
    if (!$this->sampleDataMode && $clinicInfo) {
      // 代理人情報はclinic_infoテーブルを参照
      if ($this->hasCoord('agent_postal_code') && isset($clinicInfo->postal_code)) {
        $pdf->SetFontSize($this->coord('agent_postal_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_postal_code', (string)$clinicInfo->postal_code);
      }
      if ($this->hasCoord('agent_address') && isset($clinicInfo->address_1)) {
        $agentAddress = ($clinicInfo->address_1 ?? '') . ($clinicInfo->address_2 ?? '') . ($clinicInfo->address_3 ?? '');
        $pdf->SetFontSize($this->coord('agent_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_address', (string)$agentAddress);
      }
      // 代理人氏名: 開設者氏名（owner_last_name + owner_first_name）を使用
      if ($this->hasCoord('agent_name') && isset($clinicInfo->owner_last_name) && isset($clinicInfo->owner_first_name)) {
        $agentName = $clinicInfo->owner_last_name . ' ' . $clinicInfo->owner_first_name;
        $pdf->SetFontSize($this->coord('agent_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_name', trim($agentName));
      }
    }
    // === 委任欄（実データモード） ===
    if (!$this->sampleDataMode) {
      // 委任申請者郵便番号・住所は当該利用者のデータを参照
      // 署名オプション: user_address_signature_blank の場合は郵便番号・住所をスキップ
      if ($this->signatureOption !== 'user_address_signature_blank') {
        if ($this->hasCoord('signature_applicant_postal_code') && isset($clinicUser->postal_code)) {
          $pdf->SetFontSize($this->coord('signature_applicant_postal_code', 'fontSize'));
          $this->drawTextByKey($pdf, 'signature_applicant_postal_code', (string)$clinicUser->postal_code);
        }
        if ($this->hasCoord('signature_applicant_address')) {
          $signatureAddress = ($clinicUser->address_1 ?? '') . ($clinicUser->address_2 ?? '') . ($clinicUser->address_3 ?? '');
          if ($signatureAddress) {
            $pdf->SetFontSize($this->coord('signature_applicant_address', 'fontSize'));
            $this->drawTextByKey($pdf, 'signature_applicant_address', (string)$signatureAddress);
          }
        }
      }
    }
  }

}