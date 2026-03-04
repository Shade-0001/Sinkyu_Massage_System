<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * ケアマネ情報一覧PDF生成サービス
 *
 * レイアウト概要：
 * - A4横 (297mm × 210mm)、左右マージン 8mm → 利用可能幅 281mm
 * - カラム幅：ID=16, ケアマネ氏名=45, 事業所名=55, 郵便番号=22, 住所=80, 電話番号=32, 携帯番号=31 合計=281mm
 * - 1ページあたりのデータ行数：可変高のため行ごとに判定
 */
class CareManagerInfoListPdfService extends BasePdfService
{
  // レイアウト定数
  const MARGIN_X         = 8;    // 左右マージン mm
  const AVAILABLE_W      = 281;  // 利用可能幅 mm（A4横: 297-8×2）
  const CELL_PADDING_X   = 2.4;  // セル左右パディング合計 mm
  const BASE_ROW_H       = 6;    // 行の基本高さ mm
  const LINE_PITCH       = 3.2;  // 折り返し行のピッチ mm
  const FONT_SIZE        = 7;    // データフォント pt
  const HEADER_FONT      = 7;    // ヘッダーフォント pt
  const HEADER_H         = 8;    // ヘッダー行高 mm

  // カラム幅（合計281mm）
  const COL_WIDTHS = [16, 45, 55, 22, 80, 32, 31];
  // カラムラベル
  const COL_LABELS = ['ID', 'ケアマネ氏名', '事業所名', '郵便番号', '住所', '電話番号', '携帯番号'];
  // データキー
  const DATA_KEYS  = ['id', 'name', 'service_provider', 'postal_code', 'address', 'phone', 'cell_phone'];

  // ページ座標
  const START_Y_PAGE1  = 30;  // 1ページ目の開始Y（タイトル分）
  const START_Y_OTHER  = 12;  // 2ページ目以降の開始Y
  const BOTTOM_MARGIN  = 198; // 下マージン mm（これを超えたら改ページ）

  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/care_manager_info_list_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  /**
   * PDF生成
   *
   * @param array  $clinicUserIds 未使用（BasePdfServiceシグネチャ互換）
   * @param string $serviceYearMonth 未使用
   * @param string $submissionDate  PDF出力日（Y-m-d形式）
   * @return string PDFバイナリ
   */
  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = ''): string
  {
    $pdf = new Fpdi('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetTextColor(0, 0, 0);

    $outputDate   = date('Y-m-d H:i:s');
    $careManagers = $this->fetchCareManagers();

    $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);

    // 各行の高さを事前計算
    $rowHeights = $this->calcRowHeights($pdf, $careManagers);

    // ページ割り付け（可変高なので行ごとに判定）
    $pages       = $this->splitIntoPages($rowHeights);
    $isFirstPage = true;

    foreach ($pages as $pageIndex => $rowIndices) {
      $pdf->AddPage();
      $startY = $isFirstPage ? self::START_Y_PAGE1 : self::START_Y_OTHER;

      if ($isFirstPage) {
        $this->drawTitleAndDate($pdf, $outputDate);
      }

      // このページに描画するケアマネデータを抽出
      $pageData = array_map(fn($i) => $careManagers[$i], $rowIndices);
      $this->drawTable($pdf, $rowHeights, $rowIndices, $pageData, $startY);

      $isFirstPage = false;
    }

    // 2ページ以上の場合、全ページにページ番号を描画（後処理）
    $totalPages = $pdf->getNumPages();
    if ($totalPages >= 2) {
      for ($p = 1; $p <= $totalPages; $p++) {
        $pdf->setPage($p);
        $pageText = '-' . "\u{2002}" . "\u{2002}" . $p . ' / ' . $totalPages . "\u{2002}" . "\u{2002}" . '-';
        $pdf->SetFont('kozgopromedium', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        // A4横向き297mm幅、高さ210mm、下端7mm上
        $pdf->SetXY(0, 203);
        $pdf->Cell(297, 0, $pageText, 0, 0, 'C');
      }
    }

    return $pdf->Output('', 'S');
  }

  /**
   * 全ケアマネデータを取得
   */
  protected function fetchCareManagers(): array
  {
    $rows = DB::table('caremanagers')
      ->leftJoin('service_providers', 'caremanagers.service_providers_id', '=', 'service_providers.id')
      ->select(
        'caremanagers.id',
        'caremanagers.last_name',
        'caremanagers.first_name',
        'caremanagers.postal_code',
        'caremanagers.address_1',
        'caremanagers.address_2',
        'caremanagers.address_3',
        'caremanagers.phone',
        'caremanagers.cell_phone',
        'service_providers.service_provider_name'
      )
      ->orderBy('caremanagers.id')
      ->get();

    $result = [];
    foreach ($rows as $cm) {
      $address = trim(($cm->address_1 ?? '') . ($cm->address_2 ?? '') . ($cm->address_3 ?? ''));
      $result[] = [
        'id'               => (string)$cm->id,
        'name'             => $this->formatName($cm->last_name, $cm->first_name),
        'service_provider' => $cm->service_provider_name ?? '',
        'postal_code'      => $this->formatPostalCode($cm->postal_code ?? ''),
        'address'          => $address,
        'phone'            => $this->formatPhoneNumber($cm->phone ?? ''),
        'cell_phone'       => $this->formatPhoneNumber($cm->cell_phone ?? ''),
      ];
    }

    return $result;
  }

  /**
   * 氏名フォーマット（姓と名をen spaceで結合）
   */
  protected function formatName(?string $lastName, ?string $firstName): string
  {
    $last  = $lastName  ?? '';
    $first = $firstName ?? '';
    if ($last === '' && $first === '') {
      return '';
    }
    if ($last === '') {
      return $first;
    }
    if ($first === '') {
      return $last;
    }
    return $last . "\u{2002}" . $first;
  }

  /**
   * 郵便番号フォーマット（〒XXX-XXXX）
   */
  protected function formatPostalCode(string $postalCode): string
  {
    $nums = preg_replace('/[^0-9]/', '', $postalCode);
    if (strlen($nums) === 7) {
      return '〒 ' . substr($nums, 0, 3) . '-' . substr($nums, 3, 4);
    } elseif ($postalCode !== '') {
      return '〒 ' . $postalCode;
    }
    return '';
  }

  /**
   * 電話番号フォーマット
   */
  protected function formatPhoneNumber(string $phone): string
  {
    $nums = preg_replace('/[^0-9]/', '', $phone);
    if (empty($nums)) {
      return '';
    }
    if (strlen($nums) === 10) {
      if (substr($nums, 0, 2) === '03') {
        return substr($nums, 0, 2) . '-' . substr($nums, 2, 4) . '-' . substr($nums, 6);
      }
      return substr($nums, 0, 3) . '-' . substr($nums, 3, 3) . '-' . substr($nums, 6);
    }
    if (strlen($nums) === 11) {
      return substr($nums, 0, 3) . '-' . substr($nums, 3, 4) . '-' . substr($nums, 7);
    }
    return $phone;
  }

  /**
   * 各行の描画高さを計算
   *
   * @return float[] 行高の配列（careManagers のインデックスに対応）
   */
  protected function calcRowHeights(Fpdi $pdf, array $careManagers): array
  {
    $heights = [];
    foreach ($careManagers as $i => $cm) {
      $maxLines = 1;
      foreach (self::DATA_KEYS as $col => $key) {
        $text  = (string)($cm[$key] ?? '');
        $w     = self::COL_WIDTHS[$col];
        $lines = count($this->wrapText($pdf, $text, $w));
        if ($lines > $maxLines) {
          $maxLines = $lines;
        }
      }
      $fontMm      = self::FONT_SIZE * 0.352;
      $textH       = $maxLines > 1
        ? $fontMm + ($maxLines - 1) * self::LINE_PITCH
        : $fontMm;
      $heights[$i] = max(self::BASE_ROW_H, $textH + (self::BASE_ROW_H - $fontMm));
    }
    return $heights;
  }

  /**
   * 行をページに割り付ける
   *
   * @param float[] $rowHeights
   * @return int[][] ページごとの行インデックスの配列
   */
  protected function splitIntoPages(array $rowHeights): array
  {
    $pages       = [];
    $currentPage = [];
    $y           = self::START_Y_PAGE1 + self::HEADER_H;

    foreach ($rowHeights as $i => $h) {
      if ($y + $h > self::BOTTOM_MARGIN && !empty($currentPage)) {
        $pages[]     = $currentPage;
        $currentPage = [];
        $y           = self::START_Y_OTHER + self::HEADER_H;
      }
      $currentPage[] = $i;
      $y            += $h;
    }

    if (!empty($currentPage)) {
      $pages[] = $currentPage;
    }

    return $pages;
  }

  /**
   * タイトルと出力日を描画（1ページ目のみ）
   */
  protected function drawTitleAndDate(Fpdi $pdf, string $outputDate): void
  {
    $x = self::MARGIN_X;

    $pdf->SetFont('kozgopromedium', '', 15);
    $pdf->Text($x, 13, 'ケアマネ情報一覧表');

    $ts      = strtotime($outputDate);
    $dateStr = '［ PDF出力日時 │ ' . date('Y/m/d', $ts) . "\u{2002}" . date('H:i', $ts) . ' ］';
    $pdf->SetFont('kozgopromedium', '', 8);
    $pdf->SetXY($x, 6);
    $pdf->Cell(self::AVAILABLE_W, 0, $dateStr, 0, 0, 'R');
  }

  /**
   * テーブルを描画（1ページ分）
   */
  protected function drawTable(Fpdi $pdf, array $rowHeights, array $rowIndices, array $pageData, float $startY): void
  {
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetTextColor(0, 0, 0);

    $startX = self::MARGIN_X;

    $this->drawHeaderRow($pdf, $startX, $startY);

    $y = $startY + self::HEADER_H;
    foreach ($rowIndices as $j => $i) {
      $rowH = $rowHeights[$i];
      $row  = $pageData[$j];
      $this->drawDataRow($pdf, $startX, $y, $rowH, $row);
      $y += $rowH;
    }

    $rightX = $startX + self::AVAILABLE_W;
    $pdf->Line($startX, $y, $rightX, $y);
    $pdf->Line($startX, $startY, $startX, $y);
  }

  /**
   * ヘッダー行を描画
   */
  protected function drawHeaderRow(Fpdi $pdf, float $startX, float $startY): void
  {
    $x = $startX;
    foreach (self::COL_WIDTHS as $ci => $w) {
      $label = self::COL_LABELS[$ci];
      $this->drawCell($pdf, $x, $startY, $w, self::HEADER_H, $label, true, 'C');
      $x += $w;
    }
    $pdf->Line($x, $startY, $x, $startY + self::HEADER_H);
  }

  /**
   * データ行を描画
   */
  protected function drawDataRow(Fpdi $pdf, float $startX, float $y, float $rowH, array $row): void
  {
    $x = $startX;
    foreach (self::DATA_KEYS as $ci => $key) {
      $w    = self::COL_WIDTHS[$ci];
      $text = (string)($row[$key] ?? '');
      $align = ($key === 'id') ? 'C' : 'L';
      $this->drawCell($pdf, $x, $y, $w, $rowH, $text, false, $align);
      $x += $w;
    }
    $pdf->Line($x, $y, $x, $y + $rowH);
  }

  /**
   * セルを描画（枠線＋テキスト）
   */
  protected function drawCell(Fpdi $pdf, float $x, float $y, float $w, float $h, string $text, bool $isHeader, string $align): void
  {
    if ($isHeader) {
      $pdf->SetFillColor(230, 230, 230);
      $pdf->Rect($x, $y, $w, $h, 'F');
    }

    $pdf->Line($x,       $y,     $x + $w, $y);
    $pdf->Line($x,       $y,     $x,      $y + $h);
    $pdf->Line($x + $w,  $y,     $x + $w, $y + $h);

    $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);
    $lines     = $this->wrapText($pdf, $text, $w);
    $lineCount = count($lines);
    $fontMm    = self::FONT_SIZE * 0.352;

    $pdf->setCellPaddings(0, 0, 0, 0);
    if ($isHeader) {
      $totalTextH = $lineCount > 1
        ? $fontMm + ($lineCount - 1) * self::LINE_PITCH
        : $fontMm;
      $offsetY    = ($h - $totalTextH) / 2;
      foreach ($lines as $li => $line) {
        $lineY = $y + $offsetY + $li * self::LINE_PITCH;
        $pdf->SetXY($x, $lineY);
        $pdf->Cell($w, $fontMm, $line, 0, 0, 'C', false);
      }
    } else {
      $paddingTop = (self::BASE_ROW_H - $fontMm) / 2;
      foreach ($lines as $li => $line) {
        $lineY = $y + $paddingTop + $li * self::LINE_PITCH;
        $lineX = $x + ($align === 'C' ? 0 : 1.6);
        if ($align === 'C') {
          $pdf->SetXY($x, $lineY);
          $pdf->Cell($w, $fontMm, $line, 0, 0, 'C', false);
        } else {
          $pdf->Text($lineX, $lineY, $line);
        }
      }
    }
  }

  /**
   * セル幅に応じてテキストを折り返した行配列を返す
   */
  protected function wrapText(Fpdi $pdf, string $text, float $cellWidth): array
  {
    if ($text === '') {
      return [''];
    }
    $maxW  = $cellWidth - self::CELL_PADDING_X;
    $lines = [];
    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $line  = '';
    foreach ($chars as $ch) {
      if ($pdf->GetStringWidth($line . $ch) > $maxW) {
        $lines[] = $line;
        $line    = $ch;
      } else {
        $line .= $ch;
      }
    }
    if ($line !== '') {
      $lines[] = $line;
    }
    return $lines;
  }

  /**
   * 和暦情報を取得
   */
  protected function getJapaneseEra(int $year, int $month, int $day): array
  {
    $date = sprintf('%04d%02d%02d', $year, $month, $day);
    if ($date >= '20190501') {
      return ['era' => '令和', 'year' => $year - 2018];
    } elseif ($date >= '19890108') {
      return ['era' => '平成', 'year' => $year - 1988];
    } elseif ($date >= '19261225') {
      return ['era' => '昭和', 'year' => $year - 1925];
    } elseif ($date >= '19120730') {
      return ['era' => '大正', 'year' => $year - 1911];
    }
    return ['era' => '明治', 'year' => $year - 1867];
  }
}
