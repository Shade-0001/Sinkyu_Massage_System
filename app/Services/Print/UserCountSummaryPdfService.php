<?php

namespace App\Services\Print;

/**
 * 利用者数集計表PDF生成サービス
 */
class UserCountSummaryPdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/user_count_summary_coordinates.json');
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
