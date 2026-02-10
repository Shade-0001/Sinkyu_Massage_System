<?php

namespace App\Services\Print;

/**
 * 同意書依頼状（医師指定）PDF生成サービス
 */
class ConsentRequestLetterDesignatedPdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/consent_request_letter_designated_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate, string $remarks = ''): string
  {
    // TODO: 実装予定
    throw new \Exception('このPDFタイプはまだ実装されていません');
  }
}
