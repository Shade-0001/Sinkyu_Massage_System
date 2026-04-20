<?php
//-- app/Services/Print/GenericDocumentPdfService.php --//

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 汎用文書PDF生成サービス
 * document_association未設定の新規文書プレビュー用
 * タイトル・日付・本文・自社情報のみ描画
 */
class GenericDocumentPdfService extends BasePdfService
{
  protected bool $showPatientInfo = false;
  protected string $patientName = '';
  protected string $patientIllness = '';

  public function setShowPatientInfo(bool $value): void
  {
    $this->showPatientInfo = $value;
  }

  public function setPatientName(string $value): void
  {
    $this->patientName = $value;
  }

  public function setPatientIllness(string $value): void
  {
    $this->patientIllness = $value;
  }

  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/report_greeting_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = '', array $doctorIds = [], array $caremanagerIds = []): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);

    $data = $this->fetchData($submissionDate);
    $this->addPage($pdf, $data, $submissionDate);

    return $pdf->Output('', 'S');
  }

  protected function fetchData(string $submissionDate): array
  {
    if ($this->sampleDataMode) {
      return $this->getSampleData($submissionDate);
    }

    $yearMonth  = $submissionDate ? substr($submissionDate, 0, 7) : date('Y-m');
    $clinicInfo = $this->getClinicInfoForDate($yearMonth . '-01');

    return [
      'clinic_info'      => $clinicInfo,
      'document_content' => $this->overrideDocumentContent ?? '',
      'submission_date'  => $submissionDate,
    ];
  }

  protected function getSampleData(string $submissionDate): array
  {
    $custom = $this->customSampleData;

    $clinicInfo = (object)[
      'postal_code'      => $custom['clinic_postal_code'] ?? '100-0001',
      'address_1'        => $custom['clinic_address'] ?? '東京都千代田区千代田1-1-1',
      'address_2'        => '',
      'address_3'        => '',
      'phone'            => $custom['clinic_phone'] ?? '03-1234-5678',
      'clinic_name'      => $custom['clinic_name'] ?? 'サンプル鍼灸院',
      'owner_last_name'  => $custom['clinic_owner_last_name'] ?? '鈴木',
      'owner_first_name' => $custom['clinic_owner_first_name'] ?? '一郎',
    ];

    return [
      'clinic_info'      => $clinicInfo,
      'document_content' => $custom['document_content'] ?? '',
      'submission_date'  => $submissionDate,
    ];
  }

  protected function addPage(Fpdi $pdf, array $data, string $submissionDate): void
  {
    $pdf->AddPage();

    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/汎用文書.pdf');
    if (file_exists($templatePath)) {
      $pageCount = $pdf->setSourceFile($templatePath);
      $tplId     = $pdf->importPage(1);
      $pdf->useTemplate($tplId, 0, 0, null, null, true);
    }

    $pdf->SetFont('kozminproregular', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    $this->fillFormFields($pdf, $data, $submissionDate);
  }

  protected function fillFormFields(Fpdi $pdf, array $data, string $submissionDate): void
  {
    $clinicInfo      = $data['clinic_info'];
    $documentContent = $data['document_content'];

    // タイトル
    if ($this->hasCoord('custom_title_text')) {
      $pdf->SetFontSize($this->coord('custom_title_text', 'fontSize'));
      $this->drawTextByKey($pdf, 'custom_title_text', (string)($this->customTitleText ?? ''));
    }

    // 提出年月日
    if ($submissionDate) {
      [$year, $month, $day] = explode('-', $submissionDate);
      $japaneseDate = $this->convertToJapaneseYear((int)$year, (int)$month);
      $dateText     = $japaneseDate['era'] . $japaneseDate['year'] . '年 ' . (int)$month . '月 ' . (int)$day . '日';
      $pdf->SetFontSize($this->coord('submission_date', 'fontSize'));
      $this->drawTextByKey($pdf, 'submission_date', $dateText);
    }

    // 本文
    if ($documentContent) {
      $pdf->SetFontSize($this->coord('document_content', 'fontSize'));
      $this->drawMultilineTextByKey($pdf, 'document_content', $documentContent);
    }

    // 施設郵便番号
    if ($clinicInfo && $clinicInfo->postal_code) {
      $postalCodeNumbers = preg_replace('/[^0-9]/', '', $clinicInfo->postal_code);
      $formattedPostalCode = strlen($postalCodeNumbers) === 7
        ? substr($postalCodeNumbers, 0, 3) . '-' . substr($postalCodeNumbers, 3, 4)
        : $postalCodeNumbers;
      $pdf->SetFontSize($this->coord('clinic_postal_code', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_postal_code', '〒 ' . $formattedPostalCode);
    }

    // 施設住所
    if ($clinicInfo) {
      $address = ($clinicInfo->address_1 ?? '') . ($clinicInfo->address_2 ?? '') . ($clinicInfo->address_3 ?? '');
      $pdf->SetFontSize($this->coord('clinic_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_address', $address);
    }

    // 施設電話番号
    if ($clinicInfo && $clinicInfo->phone) {
      $formattedPhone = $this->formatPhoneNumber($clinicInfo->phone);
      $pdf->SetFontSize($this->coord('clinic_phone', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_phone', 'TEL∶ ' . $formattedPhone);
    }

    // 施設名
    if ($clinicInfo && $clinicInfo->clinic_name) {
      $pdf->SetFontSize($this->coord('clinic_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_name', $clinicInfo->clinic_name);
    }

    // 施設代表者氏名
    if ($clinicInfo) {
      $ownerName = ($clinicInfo->owner_last_name ?? '') . '  ' . ($clinicInfo->owner_first_name ?? '');
      $pdf->SetFontSize($this->coord('clinic_owner_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_owner_name', $ownerName);
    }

    // 患者情報エリア（テンプレートに印字された「記 氏名： 発病：」を制御）
    if (!$this->showPatientInfo) {
      // 白矩形で上書きして隠す
      $pdf->SetFillColor(255, 255, 255);
      $pdf->Rect(10, 238, 70, 22, 'F');
    } else {
      // 患者氏名・傷病名を描画
      if ($this->hasCoord('user_name')) {
        $pdf->SetFontSize($this->coord('user_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'user_name', $this->patientName);
      }
      if ($this->hasCoord('illness_name')) {
        $pdf->SetFontSize($this->coord('illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'illness_name', $this->patientIllness);
      }
    }
  }

  protected function drawMultilineTextByKey(Fpdi $pdf, string $key, string $text): void
  {
    if (!$this->hasCoord($key)) {
      return;
    }

    $x               = $this->coord($key, 'x');
    $y               = $this->coord($key, 'y');
    $fontSize        = $this->coord($key, 'fontSize') ?: 10;
    $lineHeight      = $this->coord($key, 'lineHeight') ?: 5;
    $maxCharsPerLine = $this->coord($key, 'maxCharsPerLine') ?: 40;

    $pdf->SetFontSize($fontSize);

    $originalLines = preg_split('/\r\n|\r|\n/', $text);
    $allLines = [];
    foreach ($originalLines as $originalLine) {
      if (mb_strlen($originalLine) > $maxCharsPerLine) {
        foreach (mb_str_split($originalLine, $maxCharsPerLine) as $chunk) {
          $allLines[] = $chunk;
        }
      } else {
        $allLines[] = $originalLine;
      }
    }

    $currentY = $y;
    foreach ($allLines as $line) {
      $pdf->SetXY($x, $currentY);
      $pdf->Cell(0, 0, $line, 0, 0, 'L', false);
      $currentY += $lineHeight;
    }
  }

  protected function formatPhoneNumber(string $phone): string
  {
    $d = preg_replace('/[^0-9]/', '', $phone);
    if (empty($d)) return '';
    if (strlen($d) === 10) {
      return substr($d, 0, 2) === '03'
        ? substr($d, 0, 2) . ' - ' . substr($d, 2, 4) . ' - ' . substr($d, 6)
        : substr($d, 0, 3) . ' - ' . substr($d, 3, 3) . ' - ' . substr($d, 6);
    }
    if (strlen($d) === 11) {
      return substr($d, 0, 3) . ' - ' . substr($d, 3, 4) . ' - ' . substr($d, 7);
    }
    return $phone;
  }
}
