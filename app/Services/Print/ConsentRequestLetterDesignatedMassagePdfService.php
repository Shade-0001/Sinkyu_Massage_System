<?php

namespace App\Services\Print;

/**
 * 同意書依頼状（医師指定）（あんま･マッサージ）PDF生成サービス
 */
class ConsentRequestLetterDesignatedMassagePdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/consent_request_letter_designated_massage_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate, string $remarks = '', array $doctorIds = []): string
  {
    // 未実装：テンプレートのみ表示
    return $this->generateTemplatePdf();
  }
}
