<?php

namespace App\Services\Print;

/**
 * 施術料金一覧表PDF生成サービス
 */
class TreatmentFeeListSelfPdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/treatment_fee_list_self_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate, string $remarks = ''): string
  {
    // 未実装：テンプレートのみ表示
    return $this->generateTemplatePdf();
  }
}
