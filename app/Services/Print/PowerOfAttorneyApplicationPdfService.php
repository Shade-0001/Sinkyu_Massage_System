<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 委任状（申請･受領）PDF生成サービス
 */
class PowerOfAttorneyApplicationPdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/power_of_attorney_application_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = ''): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetCellPadding(0);

    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/others_1/委任状（申請･受領）.pdf');

    if (!file_exists($templatePath)) {
      return $this->generateTemplatePdf();
    }

    $pdf->AddPage();
    $pageCount = $pdf->setSourceFile($templatePath);
    $tplId = $pdf->importPage(1);
    $pdf->useTemplate($tplId, 0, 0, 210, 297);

    $pdf->SetFont('kozgopromedium', '', 10);

    // 事業所情報取得
    $clinicInfo = $this->getClinicInfoForDate($serviceYearMonth . '-01');

    // 1. 事業所住所
    if ($this->hasCoord('clinic_address')) {
      if ($this->sampleDataMode && isset($this->customSampleData['clinic_address'])) {
        $address = (string)$this->customSampleData['clinic_address'];
      } elseif ($clinicInfo) {
        $address = ($clinicInfo->address_1 ?? '') . ($clinicInfo->address_2 ?? '') . ($clinicInfo->address_3 ?? '');
      } else {
        $address = '';
      }
      if ($address !== '') {
        $pdf->SetFontSize($this->coord('clinic_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_address', $address);
      }
    }

    // 2. 代表者氏名
    if ($this->hasCoord('clinic_owner_name')) {
      if ($this->sampleDataMode && isset($this->customSampleData['clinic_owner_name'])) {
        $ownerName = (string)$this->customSampleData['clinic_owner_name'];
      } elseif ($clinicInfo) {
        $ownerName = trim(($clinicInfo->owner_last_name ?? '') . '  ' . ($clinicInfo->owner_first_name ?? ''));
      } else {
        $ownerName = '';
      }
      if ($ownerName !== '') {
        $pdf->SetFontSize($this->coord('clinic_owner_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_owner_name', $ownerName);
      }
    }

    // 3. 代表者生年月日（元号*年 *月 *日 形式）
    if ($this->hasCoord('clinic_owner_birthday')) {
      if ($this->sampleDataMode && isset($this->customSampleData['clinic_owner_birthday'])) {
        $birthday = (string)$this->customSampleData['clinic_owner_birthday'];
      } elseif ($clinicInfo && !empty($clinicInfo->owner_birthday)) {
        $birthday = $this->convertToJapaneseDate((string)$clinicInfo->owner_birthday);
      } else {
        $birthday = '';
      }
      if ($birthday !== '') {
        $pdf->SetFontSize($this->coord('clinic_owner_birthday', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_owner_birthday', $birthday);
      }
    }

    return $pdf->Output('', 'S');
  }
}
