<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 施術者情報一覧PDF生成サービス
 *
 * レイアウト概要：
 * - A4横 (297mm × 210mm)、左右マージン 8mm → 利用可能幅 281mm
 * - 2段ヘッダー構造：COL1~6はROW1+ROW2結合、COL7~12はROW1にグループ名・ROW2に個別ラベル
 * - カラム幅：ID=12, 氏名=36, 郵便番号=20, 住所=63, 電話=26, 携帯=26, はり(14+18), きゅう(14+18), あんまマ(18+16) 合計=281mm
 */
class TherapistInfoListPdfService extends BasePdfService
{
  // レイアウト定数
  const MARGIN_X         = 8;    // 左右マージン mm
  const AVAILABLE_W      = 281;  // 利用可能幅 mm（A4横: 297-8×2）
  const CELL_PADDING_X   = 2.4;  // セル左右パディング合計 mm
  const BASE_ROW_H       = 6;    // 行の基本高さ mm
  const LINE_PITCH       = 3.2;  // 折り返し行のピッチ mm
  const FONT_SIZE        = 7;    // データフォント pt
  const HEADER_FONT      = 7;    // ヘッダーフォント pt
  const HEADER_H1        = 5;    // 上段ヘッダー行高 mm (ROW1)
  const HEADER_H2        = 8;    // 下段ヘッダー行高 mm (ROW2)
  const HEADER_H         = 13;   // ヘッダー合計高さ mm (HEADER_H1 + HEADER_H2)

  // カラム幅（COL1~12）合計281mm
  // COL1~6合計:183mm, COL7~12合計:98mm
  const COL_WIDTHS = [12, 36, 20, 63, 26, 26, 14, 18, 14, 18, 18, 16];

  // データキー（COL1~12に対応）
  const DATA_KEYS = [
    'id',
    'name',
    'postal_code',
    'address',
    'phone',
    'cell_phone',
    'license_hari_code',
    'license_hari_issued',
    'license_kyu_code',
    'license_kyu_issued',
    'license_massage_code',
    'license_massage_issued',
  ];

  // ROW1：COL1~6（ROW1+ROW2結合）ラベル
  const ROW1_SINGLE_LABELS = [
    0 => 'ID',
    1 => '施術者氏名',
    2 => '郵便番号',
    3 => '住所',
    4 => '電話番号',
    5 => '携帯番号',
  ];

  // ROW1：COL7~12グループ定義
  const ROW1_GROUPS = [
    ['label' => 'はり',               'start' => 6, 'span' => 2],
    ['label' => 'きゅう',             'start' => 8, 'span' => 2],
    ['label' => 'あんま・マッサージ', 'start' => 10, 'span' => 2],
  ];

  // ROW2：COL7~12の個別ラベル
  const ROW2_LABELS = [
    6  => '記号番号',
    7  => '交付年月日',
    8  => '記号番号',
    9  => '交付年月日',
    10 => '記号番号',
    11 => '交付年月日',
  ];

  // ページ座標
  const START_Y_PAGE1  = 30;  // 1ページ目の開始Y（タイトル分）
  const START_Y_OTHER  = 12;  // 2ページ目以降の開始Y
  const BOTTOM_MARGIN  = 198; // 下マージン mm

  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/therapist_info_list_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  /**
   * PDF生成
   */
  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = ''): string
  {
    $pdf = new Fpdi('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetTextColor(0, 0, 0);

    $outputDate = $submissionDate ?: date('Y-m-d');
    $therapists = $this->fetchTherapists();

    $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);

    $rowHeights  = $this->calcRowHeights($pdf, $therapists);
    $pages       = $this->splitIntoPages($rowHeights);
    $isFirstPage = true;

    foreach ($pages as $rowIndices) {
      $pdf->AddPage();
      $startY = $isFirstPage ? self::START_Y_PAGE1 : self::START_Y_OTHER;

      if ($isFirstPage) {
        $this->drawTitleAndDate($pdf, $outputDate);
      }

      $pageData = array_map(fn($i) => $therapists[$i], $rowIndices);
      $this->drawTable($pdf, $rowHeights, $rowIndices, $pageData, $startY);

      $isFirstPage = false;
    }

    return $pdf->Output('', 'S');
  }

  /**
   * 全施術者データを取得
   */
  protected function fetchTherapists(): array
  {
    $rows = DB::table('therapists')
      ->select(
        'id',
        'last_name',
        'first_name',
        'postal_code',
        'address_1',
        'address_2',
        'address_3',
        'phone',
        'cell_phone',
        'license_hari_code_number',
        'license_hari_issued_date',
        'license_kyu_code_number',
        'license_kyu_issued_date',
        'license_massage_code_number',
        'license_massage_issued_date'
      )
      ->orderBy('id')
      ->get();

    $result = [];
    foreach ($rows as $t) {
      $address = trim(($t->address_1 ?? '') . ($t->address_2 ?? '') . ($t->address_3 ?? ''));
      $result[] = [
        'id'                     => (string)$t->id,
        'name'                   => $this->formatName($t->last_name, $t->first_name),
        'postal_code'            => $this->formatPostalCode($t->postal_code ?? ''),
        'address'                => $address,
        'phone'                  => $this->formatPhoneNumber($t->phone ?? ''),
        'cell_phone'             => $this->formatPhoneNumber($t->cell_phone ?? ''),
        'license_hari_code'      => $t->license_hari_code_number !== null ? (string)$t->license_hari_code_number : '',
        'license_hari_issued'    => $this->formatDate($t->license_hari_issued_date ?? ''),
        'license_kyu_code'       => $t->license_kyu_code_number !== null ? (string)$t->license_kyu_code_number : '',
        'license_kyu_issued'     => $this->formatDate($t->license_kyu_issued_date ?? ''),
        'license_massage_code'   => $t->license_massage_code_number !== null ? (string)$t->license_massage_code_number : '',
        'license_massage_issued' => $this->formatDate($t->license_massage_issued_date ?? ''),
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
   * 日付フォーマット（YYYY/MM/DD 形式）
   */
  protected function formatDate(string $date): string
  {
    if ($date === '' || $date === null) {
      return '';
    }
    $ts = strtotime($date);
    if ($ts === false) {
      return $date;
    }
    return date('Y/m/d', $ts);
  }

  /**
   * 各行の描画高さを計算
   */
  protected function calcRowHeights(Fpdi $pdf, array $therapists): array
  {
    $heights = [];
    foreach ($therapists as $i => $t) {
      $maxLines = 1;
      foreach (self::DATA_KEYS as $col => $key) {
        $text  = (string)($t[$key] ?? '');
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
    $pdf->Text($x, 13, '施術者情報一覧表');

    $dateStr = 'PDF出力日：' . date('Y年n月j日', strtotime($outputDate));
    $pdf->SetFont('kozgopromedium', '', 10);
    $dateW   = $pdf->GetStringWidth($dateStr);
    $pdf->Text($x + self::AVAILABLE_W - $dateW, 13, $dateStr);
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
   * 2段ヘッダー行を描画
   *
   * ROW1: COL1~6は高さHEADER_H全体（ROW1+ROW2結合）
   *       COL7~8: "はり", COL9~10: "きゅう", COL11~12: "あんま・マッサージ"（高さHEADER_H1）
   * ROW2: COL7~12に個別ラベル（高さHEADER_H2）
   */
  protected function drawHeaderRow(Fpdi $pdf, float $startX, float $startY): void
  {
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetFont('kozgopromedium', '', self::HEADER_FONT);

    // --- ROW1 ---
    $x = $startX;

    // COL1~6：高さHEADER_H全体（ROW1+ROW2結合）
    foreach (self::ROW1_SINGLE_LABELS as $ci => $label) {
      $w = self::COL_WIDTHS[$ci];
      $this->drawHeaderCell($pdf, $x, $startY, $w, self::HEADER_H, $label);
      $x += $w;
    }

    // COL7~12：グループラベル（高さHEADER_H1）＋ROW1/ROW2境界線
    foreach (self::ROW1_GROUPS as $group) {
      $groupW = 0;
      for ($ci = $group['start']; $ci < $group['start'] + $group['span']; $ci++) {
        $groupW += self::COL_WIDTHS[$ci];
      }
      $this->drawHeaderCell($pdf, $x, $startY, $groupW, self::HEADER_H1, $group['label']);
      // ROW1/ROW2の境界線
      $pdf->Line($x, $startY + self::HEADER_H1, $x + $groupW, $startY + self::HEADER_H1);
      $x += $groupW;
    }

    // 右端縦線（ヘッダー全体）
    $rightX = $startX + self::AVAILABLE_W;
    $pdf->Line($rightX, $startY, $rightX, $startY + self::HEADER_H);

    // --- ROW2：COL7~12の個別ラベル ---
    $row2Y = $startY + self::HEADER_H1;
    $x     = $startX;
    for ($ci = 0; $ci < 6; $ci++) {
      $x += self::COL_WIDTHS[$ci];
    }

    foreach (self::ROW2_LABELS as $ci => $label) {
      $w = self::COL_WIDTHS[$ci];
      $this->drawHeaderCell($pdf, $x, $row2Y, $w, self::HEADER_H2, $label);
      $x += $w;
    }
  }

  /**
   * ヘッダーセルを描画（塗り＋枠線＋テキスト中央揃え）
   */
  protected function drawHeaderCell(Fpdi $pdf, float $x, float $y, float $w, float $h, string $label): void
  {
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Rect($x, $y, $w, $h, 'F');

    $pdf->Line($x,      $y,     $x + $w, $y);
    $pdf->Line($x,      $y,     $x,      $y + $h);
    $pdf->Line($x + $w, $y,     $x + $w, $y + $h);

    $pdf->SetFont('kozgopromedium', '', self::HEADER_FONT);
    $lines     = $this->wrapText($pdf, $label, $w);
    $lineCount = count($lines);
    $fontMm    = self::HEADER_FONT * 0.352;

    $totalTextH = $lineCount > 1
      ? $fontMm + ($lineCount - 1) * self::LINE_PITCH
      : $fontMm;
    $offsetY = ($h - $totalTextH) / 2;

    $pdf->setCellPaddings(0, 0, 0, 0);
    foreach ($lines as $li => $line) {
      $lineY = $y + $offsetY + $li * self::LINE_PITCH;
      $pdf->SetXY($x, $lineY);
      $pdf->Cell($w, $fontMm, $line, 0, 0, 'C', false);
    }
  }

  /**
   * データ行を描画
   */
  protected function drawDataRow(Fpdi $pdf, float $startX, float $y, float $rowH, array $row): void
  {
    $x = $startX;
    foreach (self::DATA_KEYS as $ci => $key) {
      $w     = self::COL_WIDTHS[$ci];
      $text  = (string)($row[$key] ?? '');
      $align = ($key === 'id') ? 'C' : 'L';
      $this->drawCell($pdf, $x, $y, $w, $rowH, $text, false, $align);
      $x += $w;
    }
    $pdf->Line($x, $y, $x, $y + $rowH);
  }

  /**
   * データセルを描画（枠線＋テキスト）
   */
  protected function drawCell(Fpdi $pdf, float $x, float $y, float $w, float $h, string $text, bool $isHeader, string $align): void
  {
    $pdf->Line($x,      $y,     $x + $w, $y);
    $pdf->Line($x,      $y,     $x,      $y + $h);
    $pdf->Line($x + $w, $y,     $x + $w, $y + $h);

    $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);
    $lines  = $this->wrapText($pdf, $text, $w);
    $fontMm = self::FONT_SIZE * 0.352;

    $pdf->setCellPaddings(0, 0, 0, 0);
    $paddingTop = (self::BASE_ROW_H - $fontMm) / 2;
    foreach ($lines as $li => $line) {
      $lineY = $y + $paddingTop + $li * self::LINE_PITCH;
      if ($align === 'C') {
        $pdf->SetXY($x, $lineY);
        $pdf->Cell($w, $fontMm, $line, 0, 0, 'C', false);
      } else {
        $pdf->Text($x + 1.6, $lineY, $line);
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
}
