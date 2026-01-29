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

      // 初検かどうかを判定（最初のレコードのみ）
      if ($index === 0) {
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
  protected function fillTitleYearMonth(Fpdi $pdf, array $japaneseYear, int $month): void {}
  protected function fillInstitutionAndPublicFunds(Fpdi $pdf, $clinicInfo, $insurance): void {}
  protected function fillInsuranceType(Fpdi $pdf, $insurance): void {}
  protected function fillPartialPaymentEllipse(Fpdi $pdf, $insurance): void {}
  protected function fillInsuranceInfoSection(Fpdi $pdf, $insurance): void {}
  protected function fillPatientBasicInfo(Fpdi $pdf, $clinicUser, $insurance, string $fullName): void {}
  protected function fillPatientBirthday(Fpdi $pdf, $clinicUser): void {}
  protected function fillPatientAddressInfo(Fpdi $pdf, $clinicUser): void {}
  protected function fillTreatmentPeriodSection(Fpdi $pdf, \Illuminate\Support\Collection $records): void {}
  protected function fillDiseaseAndSymptoms(Fpdi $pdf, $consent): void {}
  protected function fillOnsetInfo(Fpdi $pdf, $consent): void {}
  protected function fillFirstTreatmentDate(Fpdi $pdf, \Illuminate\Support\Collection $records): void {}
  protected function fillTreatmentDayCount(Fpdi $pdf, \Illuminate\Support\Collection $records): void {}
  protected function fillBillCategorySection(Fpdi $pdf, $consent): void {}
  protected function fillOutcomeSection(Fpdi $pdf, $consent): void {}
  protected function fillWorkRelatedSection(Fpdi $pdf, $consent): void {}
  protected function fillCauseAndProgressSection(Fpdi $pdf, $consent): void {}
  protected function fillTreatmentMonth(Fpdi $pdf, string $serviceYearMonth): void {}
  protected function fillDiseaseAndSymptomsMassage(Fpdi $pdf, $consent): void {}
  protected function fillDiseaseCheckboxes(Fpdi $pdf, $consent): void {}
  protected function fillTreatmentDayCalendar(Fpdi $pdf, \Illuminate\Support\Collection $records): void {}
  protected function fillAbstractSection(Fpdi $pdf, \Illuminate\Support\Collection $records): void {}
  protected function fillTherapistSection(Fpdi $pdf, $consent): void {}
  protected function fillHealthOfficeRegistration(Fpdi $pdf, $consent): void {}
  protected function fillConsentRecordSection(Fpdi $pdf, $consent): void {}
  protected function fillApplicationSection(Fpdi $pdf, string $submissionDate): void {}
  protected function fillPaymentInstitutionSection(Fpdi $pdf, $clinicInfo): void {}
  protected function fillDelegationSection(Fpdi $pdf, $insurance, $doctor): void {}
  protected function fillTreatmentFeeSection(Fpdi $pdf, \Illuminate\Support\Collection $records, $insurance): void {}

}