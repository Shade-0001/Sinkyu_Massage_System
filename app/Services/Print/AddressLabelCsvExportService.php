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
 *   12面 : 2列 × 6行（各 約95mm × 44mm）
 *   10面 : 2列 × 5行（各 約95mm × 52mm）
 */
class AddressLabelCsvExportService extends BasePdfService
{
  // ---------- 12面シールレイアウト（A4: 210mm × 297mm）----------
  // 2列 × 6行
  // 左マージン 9mm, 上マージン 11mm, 列間 2mm, 行間 3mm
  // ラベルサイズ: 幅 95mm × 高さ 44mm
  const LABEL_12_COLS       = 2;
  const LABEL_12_ROWS       = 6;
  const LABEL_12_W          = 95;
  const LABEL_12_H          = 44;
  const LABEL_12_MARGIN_L   = 9;
  const LABEL_12_MARGIN_T   = 11;
  const LABEL_12_GAP_X      = 2;
  const LABEL_12_GAP_Y      = 3;

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
  const FONT_NAME           = 18;  // 氏名（大）
  const FONT_POSTAL         = 12;
  const FONT_ADDRESS        = 12;

  // ---------- セル内パディング ----------
  const PAD_X               = 3;
  const PAD_Y               = 2;

  // ---------- 境界線色（利用者ラベル用）----------
  const BORDER_R            = 240;
  const BORDER_G            = 240;
  const BORDER_B            = 240;

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
          ->orderBy('id')
          ->get(['id', 'last_name', 'first_name', 'postal_code', 'address_1', 'address_2', 'address_3', 'phone']);
        return array_map(function ($r) {
          return [
            'name'        => trim($r->last_name . '　' . $r->first_name),
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
          ->orderBy('doctors.id')
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
            'name'         => trim($r->last_name . '　' . $r->first_name),
            'postal_code'  => $r->postal_code,
            'address_1'    => $r->address_1 ?? '',
            'address_2'    => $r->address_2 ?? '',
            'address_3'    => $r->address_3 ?? '',
            'address'      => trim($r->address_1 . $r->address_2 . $r->address_3),
            'phone'        => $r->phone,
            'id'           => $r->id,
            'organization' => $r->medical_institution_name ?? '',
          ];
        }, $rows->toArray());

      case 'insurer':
        $rows = DB::table('insurers')
          ->orderBy('id')
          ->get(['id', 'insurer_name', 'insurer_number', 'postal_code', 'address', 'recipient_name']);
        return array_map(function ($r) {
          return [
            'name'           => ($r->recipient_name ? $r->recipient_name . '　御中' : $r->insurer_name . '　御中'),
            'organization'   => $r->insurer_name,
            'postal_code'    => $r->postal_code,
            'address'        => $r->address,
            'recipient_name' => $r->recipient_name ?? '',
            'phone'          => '',
          ];
        }, $rows->toArray());

      case 'caremanager':
        $rows = DB::table('caremanagers')
          ->leftJoin('service_providers', 'caremanagers.service_providers_id', '=', 'service_providers.id')
          ->orderBy('caremanagers.id')
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
            'name'         => trim($r->last_name . '　' . $r->first_name),
            'postal_code'  => $r->postal_code,
            'address_1'    => $r->address_1 ?? '',
            'address_2'    => $r->address_2 ?? '',
            'address_3'    => $r->address_3 ?? '',
            'address'      => trim($r->address_1 . $r->address_2 . $r->address_3),
            'phone'        => $r->phone,
            'id'           => $r->id,
            'organization' => $r->service_provider_name ?? '',
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

    if ($dataType === 'clinic_user') {
      $output .= '"氏名","郵便番号","住所１","住所２","住所３","電話番号"' . "\n";
    } elseif ($dataType === 'doctor') {
      $output .= '"氏名","郵便番号","住所１","住所２","住所３","電話番号","ID","医療機関名"' . "\n";
    } elseif ($dataType === 'insurer') {
      $output .= '"保険者名称","郵便番号","住所","提出先名称"' . "\n";
    } elseif ($dataType === 'caremanager') {
      $output .= '"氏名","郵便番号","住所１","住所２","住所３","電話番号","ID","サービス事業者名"' . "\n";
    } else {
      $output .= '"氏名","郵便番号","住所","電話番号"' . "\n";
    }

    foreach ($records as $r) {
      $name    = $r['name'] ?? '';
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
      } elseif ($dataType === 'doctor') {
        $addr1 = $r['address_1'] ?? '';
        $addr2 = $r['address_2'] ?? '';
        $addr3 = $r['address_3'] ?? '';
        $id    = $r['id'] ?? '';
        $org   = $r['organization'] ?? '';
        $output .= '"' . str_replace('"', '""', $name) . '",'
          . '"' . str_replace('"', '""', $postal) . '",'
          . '"' . str_replace('"', '""', $addr1) . '",'
          . '"' . str_replace('"', '""', $addr2) . '",'
          . '"' . str_replace('"', '""', $addr3) . '",'
          . '"' . str_replace('"', '""', $phone) . '",'
          . '"' . str_replace('"', '""', $id) . '",'
          . '"' . str_replace('"', '""', $org) . '"' . "\n";
      } elseif ($dataType === 'insurer') {
        $insurer_name   = $r['organization'] ?? '';
        $recipient_name = $r['recipient_name'] ?? '';
        $output .= '"' . str_replace('"', '""', $insurer_name) . '",'
          . '"' . str_replace('"', '""', $postal) . '",'
          . '"' . str_replace('"', '""', $address) . '",'
          . '"' . str_replace('"', '""', $recipient_name) . '"' . "\n";
      } elseif ($dataType === 'caremanager') {
        $addr1 = $r['address_1'] ?? '';
        $addr2 = $r['address_2'] ?? '';
        $addr3 = $r['address_3'] ?? '';
        $id    = $r['id'] ?? '';
        $org   = $r['organization'] ?? '';
        $output .= '"' . str_replace('"', '""', $name) . '",'
          . '"' . str_replace('"', '""', $postal) . '",'
          . '"' . str_replace('"', '""', $addr1) . '",'
          . '"' . str_replace('"', '""', $addr2) . '",'
          . '"' . str_replace('"', '""', $addr3) . '",'
          . '"' . str_replace('"', '""', $phone) . '",'
          . '"' . str_replace('"', '""', $id) . '",'
          . '"' . str_replace('"', '""', $org) . '"' . "\n";
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
        $this->drawLabel($pdf, $records[$i], $x, $y, $labelW, $labelH, $fontName, $dataType);
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
    string $fontName,
    string $dataType = ''
  ): void {
    // 利用者ラベルのみ境界線を描画
    if ($dataType === 'clinic_user') {
      $pdf->SetDrawColor(self::BORDER_R, self::BORDER_G, self::BORDER_B);
      $pdf->SetLineWidth(0.2);
      $pdf->Rect($x, $y, $w, $h, 'D');
    }

    $px     = $x + self::PAD_X;
    $py     = $y + self::PAD_Y;
    $innerW = $w - self::PAD_X * 2;

    if ($dataType === 'clinic_user') {
      // 利用者フォーマット: 郵便番号 → 住所 → 氏名（大）
      // フォントサイズ(pt) → 行高(mm): pt * 0.3528 + 余白
      $bottomY = $y + $h - self::PAD_Y;

      $postal = $record['postal_code'] ?? '';
      if ($postal !== '' && $postal !== null) {
        $postalDisplay = '〒 ' . $this->formatPostalCode($postal);
        $pdf->SetFont($fontName, '', self::FONT_POSTAL);
        $lineH = round(self::FONT_POSTAL * 0.3528 + 1.5, 2);
        if ($py + $lineH <= $bottomY) {
          $pdf->SetXY($px, $py);
          $pdf->Cell($innerW, $lineH, $postalDisplay, 0, 0, 'L');
          $py += $lineH;
        }
      }

      $address = $record['address'] ?? '';
      if ($address !== '' && $address !== null) {
        $pdf->SetFont($fontName, '', self::FONT_ADDRESS);
        $lineH = round(self::FONT_ADDRESS * 0.3528 + 1.5, 2);
        $strW  = $pdf->GetStringWidth($address);
        $lines = max(1, (int)ceil($strW / $innerW));
        $blockH = $lineH * $lines;
        if ($py + $blockH <= $bottomY) {
          $pdf->SetXY($px, $py);
          $pdf->MultiCell($innerW, $lineH, $address, 0, 'L');
          $py += $blockH;
        } elseif ($py < $bottomY) {
          // 入る分だけ描画
          $pdf->SetXY($px, $py);
          $pdf->MultiCell($innerW, $lineH, $address, 0, 'L');
          $py = $bottomY;
        }
      }

      // 氏名（大フォント）＋敬称
      $name = $record['name'] ?? '';
      if ($name !== '' && $name !== null) {
        $pdf->SetFont($fontName, '', self::FONT_NAME);
        $lineH = round(self::FONT_NAME * 0.3528 + 1.5, 2);
        $py += 2;
        if ($py < $bottomY) {
          $pdf->SetXY($px, $py);
          $pdf->MultiCell($innerW, $lineH, $name . '　様', 0, 'L');
        }
      }

      return;
    }

    // 利用者以外の共通フォーマット
    $bottomY = $y + $h - self::PAD_Y;

    $postal = $record['postal_code'] ?? '';
    if ($postal !== '' && $postal !== null) {
      $postalDisplay = '〒 ' . $this->formatPostalCode($postal);
      $pdf->SetFont($fontName, '', self::FONT_POSTAL);
      $lineH = round(self::FONT_POSTAL * 0.3528 + 1.5, 2);
      if ($py + $lineH <= $bottomY) {
        $pdf->SetXY($px, $py);
        $pdf->Cell($innerW, $lineH, $postalDisplay, 0, 0, 'L');
        $py += $lineH;
      }
    }

    $address = $record['address'] ?? '';
    if ($address !== '' && $address !== null) {
      $pdf->SetFont($fontName, '', self::FONT_ADDRESS);
      $lineH  = round(self::FONT_ADDRESS * 0.3528 + 1.5, 2);
      $strW   = $pdf->GetStringWidth($address);
      $lines  = max(1, (int)ceil($strW / $innerW));
      $blockH = $lineH * $lines;
      if ($py + $blockH <= $bottomY) {
        $pdf->SetXY($px, $py);
        $pdf->MultiCell($innerW, $lineH, $address, 0, 'L');
        $py += $blockH;
      } elseif ($py < $bottomY) {
        $pdf->SetXY($px, $py);
        $pdf->MultiCell($innerW, $lineH, $address, 0, 'L');
        $py = $bottomY;
      }
    }

    $org = $record['organization'] ?? '';
    if ($org !== '' && $org !== null) {
      $py += 2;
      $pdf->SetFont($fontName, '', self::FONT_ADDRESS);
      $lineH  = round(self::FONT_ADDRESS * 0.3528 + 1.5, 2);
      $strW   = $pdf->GetStringWidth($org);
      $lines  = max(1, (int)ceil($strW / $innerW));
      $blockH = $lineH * $lines;
      if ($py + $blockH <= $bottomY) {
        $pdf->SetXY($px, $py);
        $pdf->MultiCell($innerW, $lineH, $org, 0, 'L');
        $py += $blockH;
      } elseif ($py < $bottomY) {
        $pdf->SetXY($px, $py);
        $pdf->MultiCell($innerW, $lineH, $org, 0, 'L');
        $py = $bottomY;
      }
    }

    $name = $record['name'] ?? '';
    if ($name !== '' && $name !== null) {
      if ($dataType === 'doctor') {
        $nameDisplay = $name . '　先生御侍史';
      } elseif ($dataType === 'caremanager') {
        $nameDisplay = $name . '　様';
      } else {
        $nameDisplay = $name;  // 保険者は fetchRecords で既に「御中」付き
      }
      $pdf->SetFont($fontName, '', self::FONT_NAME);
      $lineH  = round(self::FONT_NAME * 0.3528 + 1.5, 2);
      $strW   = $pdf->GetStringWidth($nameDisplay);
      $lines  = max(1, (int)ceil($strW / $innerW));
      $blockH = $lineH * $lines;
      $py += 2;
      if ($py < $bottomY) {
        $pdf->SetXY($px, $py);
        $pdf->MultiCell($innerW, $lineH, $nameDisplay, 0, 'L');
      }
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
