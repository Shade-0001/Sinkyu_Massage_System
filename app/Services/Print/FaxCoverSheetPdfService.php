<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * FAX送信票PDF生成サービス
 */
class FaxCoverSheetPdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/fax_cover_sheet_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = ''): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);

    $data = $this->fetchData();
    $this->addPage($pdf, $data);

    return $pdf->Output('', 'S');
  }

  /**
   * データ取得
   */
  protected function fetchData(): array
  {
    if ($this->sampleDataMode) {
      return $this->getSampleData('');
    }

    $clinicInfo = $this->getClinicInfoForDate($serviceYearMonth . '-01');

    return [
      'clinic_info' => $clinicInfo,
    ];
  }

  /**
   * サンプルデータ取得
   */
  protected function getSampleData(string $submissionDate): array
  {
    $custom = $this->customSampleData;

    // clinic_owner_nameは「姓 名」形式で渡されるため分割せずそのまま使用
    $ownerName = $custom['clinic_owner_name'] ?? '田中 一郎';
    $ownerParts = explode(' ', $ownerName, 2);

    $clinicInfo = (object)[
      'postal_code'      => $custom['clinic_postal_code'] ?? '150-0001',
      'address_1'        => $custom['clinic_address'] ?? '東京都渋谷区〇〇1-2-3',
      'address_2'        => '',
      'address_3'        => '',
      'phone'            => $custom['clinic_phone'] ?? '03-9876-5432',
      'clinic_name'      => $custom['clinic_name'] ?? '〇〇鍼灸マッサージ院',
      'owner_last_name'  => $ownerParts[0] ?? '',
      'owner_first_name' => $ownerParts[1] ?? '',
    ];

    return [
      'clinic_info' => $clinicInfo,
    ];
  }

  /**
   * PDFページ追加
   */
  protected function addPage(Fpdi $pdf, array $data): void
  {
    $pdf->AddPage();

    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/others_2/FAX送信票.pdf');

    if (file_exists($templatePath)) {
      $pageCount = $pdf->setSourceFile($templatePath);
      $tplId = $pdf->importPage(1);
      $pdf->useTemplate($tplId, 0, 0, null, null, true);
    }

    $pdf->SetFont('kozminproregular', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    $this->fillFormFields($pdf, $data);
  }

  /**
   * フォームフィールド埋め込み
   */
  protected function fillFormFields(Fpdi $pdf, array $data): void
  {
    $clinicInfo = $data['clinic_info'];

    // 1. 事業所名
    if ($clinicInfo && $this->hasCoord('clinic_name')) {
      $pdf->SetFontSize($this->coord('clinic_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_name', $clinicInfo->clinic_name ?? '');
    }

    // 2. 事業所郵便番号（〒***-****形式）
    if ($clinicInfo && $clinicInfo->postal_code && $this->hasCoord('clinic_postal_code')) {
      $postalCodeNumbers = preg_replace('/[^0-9]/', '', $clinicInfo->postal_code);
      if (strlen($postalCodeNumbers) === 7) {
        $formattedPostalCode = substr($postalCodeNumbers, 0, 3) . '-' . substr($postalCodeNumbers, 3, 4);
      } else {
        $formattedPostalCode = $postalCodeNumbers;
      }
      $pdf->SetFontSize($this->coord('clinic_postal_code', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_postal_code', '〒 ' . $formattedPostalCode);
    }

    // 3. 事業所住所
    if ($clinicInfo && $this->hasCoord('clinic_address')) {
      $address = ($clinicInfo->address_1 ?? '') . ($clinicInfo->address_2 ?? '') . ($clinicInfo->address_3 ?? '');
      $pdf->SetFontSize($this->coord('clinic_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_address', $address);
    }

    // 4. 事業所電話番号（TEL∶ *形式）
    if ($clinicInfo && ($clinicInfo->phone ?? '') && $this->hasCoord('clinic_phone')) {
      $formattedPhone = $this->formatPhoneNumber($clinicInfo->phone);
      $pdf->SetFontSize($this->coord('clinic_phone', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_phone', 'TEL∶ ' . $formattedPhone);
    }

    // 5. 事業所代表者氏名（姓 名形式）
    if ($clinicInfo && $this->hasCoord('clinic_owner_name')) {
      $ownerName = trim(($clinicInfo->owner_last_name ?? '') . '  ' . ($clinicInfo->owner_first_name ?? ''));
      $pdf->SetFontSize($this->coord('clinic_owner_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_owner_name', $ownerName);
    }
  }

  /**
   * 電話番号フォーマット
   */
  protected function formatPhoneNumber(string $phone): string
  {
    $digitsOnly = preg_replace('/[^0-9]/', '', $phone);

    if (empty($digitsOnly)) {
      return '';
    }

    if (strlen($digitsOnly) === 10) {
      if (substr($digitsOnly, 0, 2) === '03') {
        return substr($digitsOnly, 0, 2) . ' - ' . substr($digitsOnly, 2, 4) . ' - ' . substr($digitsOnly, 6);
      } else {
        return substr($digitsOnly, 0, 3) . ' - ' . substr($digitsOnly, 3, 3) . ' - ' . substr($digitsOnly, 6);
      }
    }

    if (strlen($digitsOnly) === 11) {
      return substr($digitsOnly, 0, 3) . ' - ' . substr($digitsOnly, 3, 4) . ' - ' . substr($digitsOnly, 7);
    }

    return $phone;
  }
}
