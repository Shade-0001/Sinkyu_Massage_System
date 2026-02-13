<?php

namespace App\Services\Print;

/**
 * 同意書依頼状（サンプル版）（あんま･マッサージ）PDF生成サービス
 */
class ConsentRequestLetterSampleMassagePdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/consent_request_letter_sample_massage_coordinates.json');
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
