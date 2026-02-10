<?php

namespace App\Services\Print;

/**
 * 入金管理票（保険）PDF生成サービス
 */
class DepositManagementInsurancePdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/deposit_management_insurance_coordinates.json');
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
