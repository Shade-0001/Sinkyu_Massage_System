<?php

namespace App\Services\Print;

/**
 * 後期高齢者医療療養費支給申請書（はり･きゅう）PDF生成サービス
 */
class ElderlyTherapyBenefitAcupuncturePdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/elderly_therapy_benefit_acupuncture_coordinates.json');
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
