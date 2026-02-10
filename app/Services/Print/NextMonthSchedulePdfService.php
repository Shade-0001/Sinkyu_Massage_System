<?php

namespace App\Services\Print;

/**
 * 翌月予定表PDF生成サービス
 */
class NextMonthSchedulePdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/next_month_schedule_coordinates.json');
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
