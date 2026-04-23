<?php

namespace App\Services\Print;

/**
 * 初回体験用資料PDF生成サービス
 */
class FirstExperienceMaterialPdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/first_experience_material_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = ''): string
  {
    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/others_1/初回体験用資料.pdf');
    return $this->generateTemplatePdf($templatePath);
  }
}
