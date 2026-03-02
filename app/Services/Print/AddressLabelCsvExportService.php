<?php

namespace App\Services\Print;

use Illuminate\Support\Facades\DB;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * 宛名シール・住所データCSV出力サービス
 *
 * 対応出力データ:
 *   clinic_user  : 利用者関連
 *   doctor       : 同意医師関連
 *   insurer      : 保険者関連
 *   caremanager  : ケアマネ関連
 *
 * 宛名シールレイアウト:
 *   12面 : 4列 × 3行（各 約47mm × 85mm）
 *   10面 : 2列 × 5行（各 約95mm × 52mm）
 */
class AddressLabelCsvExportService extends BasePdfService
{
  // ---------- 12面シールレイアウト（A4: 210mm × 297mm）----------
  // 市販シート "エーワン 28870" 等を想定
  // 左マージン 8mm, 上マージン 11mm, 列間 2mm, 行間 3mm
  // ラベルサイズ: 幅 48mm × 高さ 83mm
  const LABEL_12_COLS       = 4;
  const LABEL_12_ROWS       = 3;
  const LABEL_12_W          = 48;
  const LABEL_12_H          = 83;
  const LABEL_12_MARGIN_L   = 9;
  const LABEL_12_MARGIN_T   = 11;
  const LABEL_12_GAP_X      = 2;
  const LABEL_12_GAP_Y      = 4;

  // ---------- 10面シールレイアウト ----------
  // 市販シート "エーワン 28384" 等を想定
  // 左マージン 10mm, 上マージン 21mm, 列間 2mm, 行間 3mm
  // ラベルサイズ: 幅 93mm × 高さ 48mm
  const LABEL_10_COLS       = 2;
  const LABEL_10_ROWS       = 5;
  const LABEL_10_W          = 93;
  const LABEL_10_H          = 48;
  const LABEL_10_MARGIN_L   = 9;
  const LABEL_10_MARGIN_T   = 21;
  const LABEL_10_GAP_X      = 2;
  const LABEL_10_GAP_Y      = 4;

  // ---------- フォントサイズ ----------
  const FONT_NAME           = 10;  // 氏名/組織名
  const FONT_POSTAL         = 8;
  const FONT_ADDRESS        = 8;

  // ---------- セル内パディング ----------
  const PAD_X               = 3;
  const PAD_Y               = 3;

  // -------------------------------------------------------

  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/address_label_csv_export_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = ''): string
  {
    return '';
  }

  // -------------------------------------------------------
  // データ取得
  // -------------------------------------------------------

  private function fetchRecords(string $dataType): array
  {
    switch ($dataType) {
      case 'clinic_user':
        $rows = DB::table('clinic_users')
          ->orderBy('last_kana')
          ->orderBy('first_kana')
          ->get(['id', 'last_name', 'first_name', 'postal_code', 'address_1', 'address_2', 'address_3', 'phone']);
        return array_map(function ($r) {
          return [
            'name'        => trim($r->last_name . '　' . $r->first_name) . ' 様',
            'postal_code' => $r->postal_code,
            'address_1'   => $r->address_1 ?? '',
            'address_2'   => $r->address_2 ?? '',
            'address_3'   => $r->address_3 ?? '',
            'address'     => trim($r->address_1 . $r->address_2 . $r->address_3),
            'phone'       => $r->phone,
          ];
        }, $rows->toArray());

      case 'doctor':
        $rows = DB::table('doctors')
          ->leftJoin('medical_institutions', 'doctors.medical_institutions_id', '=', 'medical_institutions.id')
          ->orderBy('doctors.last_name_kana')
          ->orderBy('doctors.first_name_kana')
          ->get([
            'doctors.id',
            'doctors.last_name',
            'doctors.first_name',
            'doctors.postal_code',
            'doctors.address_1',
            'doctors.address_2',
            'doctors.address_3',
            'doctors.phone',
            'medical_institutions.medical_institution_name',
          ]);
        return array_map(function ($r) {
          return [
            'name'        => trim($r->last_name . '　' . $r->first_name) . ' 先生',
            'organization' => $r->medical_institution_name ?? '',
            'postal_code' => $r->postal_code,
            'address'     => trim($r->address_1 . $r->address_2 . $r->address_3),
            'phone'       => $r->phone,
          ];
        }, $rows->toArray());

      case 'insurer':
        $rows = DB::table('insurers')
          ->orderBy('insurer_name')
          ->get(['id', 'insurer_name', 'insurer_number', 'postal_code', 'address', 'recipient_name']);
        return array_map(function ($r) {
          return [
            'name'        => ($r->recipient_name ? $r->recipient_name . ' 御中' : $r->insurer_name . ' 御中'),
            'organization' => $r->insurer_name,
            'postal_code' => $r->postal_code,
            'address'     => $r->address,
            'phone'       => '',
          ];
        }, $rows->toArray());

      case 'caremanager':
        $rows = DB::table('caremanagers')
          ->leftJoin('service_providers', 'caremanagers.service_providers_id', '=', 'service_providers.id')
          ->orderBy('caremanagers.last_name_kana')
          ->orderBy('caremanagers.first_name_kana')
          ->get([
            'caremanagers.id',
            'caremanagers.last_name',
            'caremanagers.first_name',
            'caremanagers.postal_code',
            'caremanagers.address_1',
            'caremanagers.address_2',
            'caremanagers.address_3',
            'caremanagers.phone',
            'service_providers.service_provider_name',
          ]);
        return array_map(function ($r) {
          return [
            'name'        => trim($r->last_name . '　' . $r->first_name) . ' 様',
            'organization' => $r->service_provider_name ?? '',
            'postal_code' => $r->postal_code,
            'address'     => trim($r->address_1 . $r->address_2 . $r->address_3),
            'phone'       => $r->phone,
          ];
        }, $rows->toArray());

      default:
        return [];
    }
  }

  // -------------------------------------------------------
  // CSV生成
  // -------------------------------------------------------

  public function generateCsv(string $dataType): array
  {
    $records = $this->fetchRecords($dataType);

    // BOM付きUTF-8（Excel対応）
    $output = "\xEF\xBB\xBF";

    $hasOrg = in_array($dataType, ['doctor', 'insurer', 'caremanager']);

    if ($dataType === 'clinic_user') {
      $output .= '"氏名","郵便番号","住所１","住所２","住所３","電話番号"' . "\n";
    } elseif ($hasOrg) {
      $output .= '"氏名/宛名","組織名","郵便番号","住所","電話番号"' . "\n";
    } else {
      $output .= '"氏名/宛名","郵便番号","住所","電話番号"' . "\n";
    }

    foreach ($records as $r) {
      $name    = $r['name'] ?? '';
      $org     = $r['organization'] ?? '';
      $postal  = $r['postal_code'] ?? '';
      $address = $r['address'] ?? '';
      $phone   = $r['phone'] ?? '';

      if ($dataType === 'clinic_user') {
        $addr1 = $r['address_1'] ?? '';
        $addr2 = $r['address_2'] ?? '';
        $addr3 = $r['address_3'] ?? '';
        $output .= '"' . str_replace('"', '""', $name) . '",'
          . '"' . str_replace('"', '""', $postal) . '",'
          . '"' . str_replace('"', '""', $addr1) . '",'
          . '"' . str_replace('"', '""', $addr2) . '",'
          . '"' . str_replace('"', '""', $addr3) . '",'
          . '"' . str_replace('"', '""', $phone) . '"' . "\n";
      } elseif ($hasOrg) {
        $output .= '"' . str_replace('"', '""', $name) . '",'
          . '"' . str_replace('"', '""', $org) . '",'
          . '"' . str_replace('"', '""', $postal) . '",'
          . '"' . str_replace('"', '""', $address) . '",'
          . '"' . str_replace('"', '""', $phone) . '"' . "\n";
      } else {
        $output .= '"' . str_replace('"', '""', $name) . '",'
          . '"' . str_replace('"', '""', $postal) . '",'
          . '"' . str_replace('"', '""', $address) . '",'
          . '"' . str_replace('"', '""', $phone) . '"' . "\n";
      }
    }

    return ['csv' => $output, 'count' => count($records)];
  }

  // -------------------------------------------------------
  // 宛名シールPDF生成
  // -------------------------------------------------------

  public function generateLabelPdf(string $dataType, int $faces): string
  {
    if ($faces === 10) {
      return $this->buildLabelPdf(
        $dataType,
        self::LABEL_10_COLS,
        self::LABEL_10_ROWS,
        self::LABEL_10_W,
        self::LABEL_10_H,
        self::LABEL_10_MARGIN_L,
        self::LABEL_10_MARGIN_T,
        self::LABEL_10_GAP_X,
        self::LABEL_10_GAP_Y
      );
    }

    return $this->buildLabelPdf(
      $dataType,
      self::LABEL_12_COLS,
      self::LABEL_12_ROWS,
      self::LABEL_12_W,
      self::LABEL_12_H,
      self::LABEL_12_MARGIN_L,
      self::LABEL_12_MARGIN_T,
      self::LABEL_12_GAP_X,
      self::LABEL_12_GAP_Y
    );
  }

  private function buildLabelPdf(
    string $dataType,
    int $cols,
    int $rows,
    float $labelW,
    float $labelH,
    float $marginL,
    float $marginT,
    float $gapX,
    float $gapY
  ): string {
    $records  = $this->fetchRecords($dataType);
    $perPage  = $cols * $rows;
    $total    = count($records);

    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    $fontName = 'kozgopromedium';

    $pageIdx = 0;

    for ($i = 0; $i < max($total, 1); $i++) {
      $posOnPage = $i % $perPage;

      if ($posOnPage === 0) {
        $pdf->AddPage();
        $pageIdx++;
      }

      $col = $posOnPage % $cols;
      $row = intdiv($posOnPage, $cols);

      $x = $marginL + $col * ($labelW + $gapX);
      $y = $marginT + $row * ($labelH + $gapY);

      if ($i < $total) {
        $this->drawLabel($pdf, $records[$i], $x, $y, $labelW, $labelH, $fontName);
      }
    }

    return $pdf->Output('', 'S');
  }

  private function drawLabel(
    Fpdi $pdf,
    array $record,
    float $x,
    float $y,
    float $w,
    float $h,
    string $fontName
  ): void {
    $px      = $x + self::PAD_X;
    $py      = $y + self::PAD_Y;
    $innerW  = $w - self::PAD_X * 2;

    // 郵便番号
    $postal = $record['postal_code'] ?? '';
    if ($postal !== '' && $postal !== null) {
      $postalDisplay = '〒 ' . $this->formatPostalCode($postal);
      $pdf->SetFont($fontName, '', self::FONT_POSTAL);
      $pdf->SetXY($px, $py);
      $pdf->Cell($innerW, 5, $postalDisplay, 0, 0, 'L');
      $py += 5;
    }

    // 住所
    $address = $record['address'] ?? '';
    if ($address !== '' && $address !== null) {
      $pdf->SetFont($fontName, '', self::FONT_ADDRESS);
      $pdf->SetXY($px, $py);
      $pdf->MultiCell($innerW, 4.5, $address, 0, 'L');
      $lineCount = ceil(mb_strlen($address) / max(1, intdiv((int)$innerW, 3)));
      $py += max(4.5, $lineCount * 4.5);
    }

    // 組織名
    $org = $record['organization'] ?? '';
    if ($org !== '' && $org !== null) {
      $pdf->SetFont($fontName, '', self::FONT_ADDRESS);
      $pdf->SetXY($px, $py);
      $pdf->MultiCell($innerW, 4.5, $org, 0, 'L');
      $lineCount = ceil(mb_strlen($org) / max(1, intdiv((int)$innerW, 3)));
      $py += max(4.5, $lineCount * 4.5);
    }

    // 氏名
    $name = $record['name'] ?? '';
    if ($name !== '' && $name !== null) {
      $pdf->SetFont($fontName, '', self::FONT_NAME);
      $nameY = $y + $h - self::PAD_Y - 8;
      $pdf->SetXY($px, $nameY);
      $pdf->Cell($innerW, 8, $name, 0, 0, 'L');
    }
  }

  private function formatPostalCode(string $postal): string
  {
    $digits = preg_replace('/\D/', '', $postal);
    if (strlen($digits) === 7) {
      return substr($digits, 0, 3) . '-' . substr($digits, 3);
    }
    return $postal;
  }
}
