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
 * - カラム幅：ID=12, 氏名=36, 郵便番号=20, 住所=59, 電話=26, 携帯=26, はり(12+22), きゅう(12+22), あんまマ(12+22) 合計=281mm
 */
class TherapistInfoListPdfService extends BasePdfService
{
  // レイアウト定数
  const MARGIN_X         = 8;    // 左右マージン mm
  const AVAILABLE_W      = 281;  // 利用可能幅 mm（A4横: 297-8×2）
  const CELL_PADDING_X   = 3.0;  // セル左右パディング合計 mm（= CELL_PAD_L × 2、wrapText参照用）
  const CELL_PAD_L       = 1.5;  // セル左右パディング mm
  const CELL_PAD_V       = 1.5;  // セル上下パディング mm
  const BASE_ROW_H       = 6;    // 行の基本高さ mm（参考値）
  const LINE_PITCH       = 3.2;  // 折り返し行のピッチ mm
  const FONT_SIZE        = 9;    // データフォント pt
  const HEADER_FONT      = 9;    // ヘッダーフォント pt
  const HEADER_H1        = 7;    // 上段ヘッダー行高 mm (ROW1)  ※フォント≒3.96mm + 上下1.5mm×2
  const HEADER_H2        = 8;    // 下段ヘッダー行高 mm (ROW2)
  const HEADER_H         = 15;   // ヘッダー合計高さ mm (HEADER_H1 + HEADER_H2)

  // カラム幅（COL1~12）合計281mm ※初期値（自動計算のフォールバック用）
  // COL1~6合計:179mm, COL7~12合計:102mm
  const COL_WIDTHS = [12, 36, 20, 59, 26, 26, 12, 22, 12, 22, 12, 22];

  // 動的に計算されたカラム幅（generate()内で確定）
  protected array $colWidths = [];

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

    $outputDate = date('Y-m-d H:i:s');
    $therapists = $this->fetchTherapists();

    $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);

    $this->colWidths = $this->calcColWidths($pdf, $therapists);
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
   * 日付フォーマット（和暦形式：例 令和6年 3月 1日）
   */
  protected function formatDate(?string $date): string
  {
    if (!$date) {
      return '';
    }
    $ts = strtotime($date);
    if ($ts === false) {
      return $date;
    }
    $year  = (int)date('Y', $ts);
    $month = (int)date('n', $ts);
    $day   = (int)date('j', $ts);
    $era   = $this->getJapaneseEra($year, $month, $day);
    return $era['era'] . $era['year'] . '年' . $month . '月' . $day . '日';
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

  /**
   * 各カラム幅を動的計算して返す
   * - 住所（COL3）以外：ヘッダー・データテキストで改行しない最小幅に固定
   * - 住所（COL3）：AVAILABLE_W から他カラム合計を引いた残余幅を割り当て
   */
  protected function calcColWidths(Fpdi $pdf, array $therapists): array
  {
    $minWidths = array_fill(0, 12, 0.0);
    $pad = self::CELL_PAD_L * 2; // 左右均等パディング（CELL_PAD_L × 2）

    // ROW1_SINGLE_LABELS（COL0~5）のヘッダーテキスト幅
    $pdf->SetFont('kozgopromedium', '', self::HEADER_FONT);
    foreach (self::ROW1_SINGLE_LABELS as $ci => $label) {
      $minWidths[$ci] = max($minWidths[$ci], $pdf->GetStringWidth($label) + $pad);
    }

    // ROW2個別ラベル（COL6~11）のヘッダーテキスト幅
    foreach (self::ROW2_LABELS as $ci => $label) {
      $minWidths[$ci] = max($minWidths[$ci], $pdf->GetStringWidth($label) + $pad);
    }

    // データテキスト幅
    $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);
    foreach ($therapists as $t) {
      foreach (self::DATA_KEYS as $ci => $key) {
        $text = (string)($t[$key] ?? '');
        $minWidths[$ci] = max($minWidths[$ci], $pdf->GetStringWidth($text) + $pad);
      }
    }

    // ROW1グループラベルの制約：グループ内合計 >= グループラベル幅
    $pdf->SetFont('kozgopromedium', '', self::HEADER_FONT);
    foreach (self::ROW1_GROUPS as $group) {
      $groupLabelW = $pdf->GetStringWidth($group['label']) + $pad;
      $cols = range($group['start'], $group['start'] + $group['span'] - 1);
      $currentGroupW = array_sum(array_map(fn($c) => $minWidths[$c], $cols));
      if ($currentGroupW < $groupLabelW) {
        $ratio = $groupLabelW / $currentGroupW;
        foreach ($cols as $c) {
          $minWidths[$c] *= $ratio;
        }
      }
    }

    // 住所（COL3）以外の合計を固定し、住所に余導全てを割り当てる
    $addressIdx = 3;
    $otherTotal = array_sum($minWidths) - $minWidths[$addressIdx];
    $addressW   = self::AVAILABLE_W - $otherTotal;
    $minWidthAddr = 8.0; // 住所最低保証幅 mm

    if ($addressW >= $minWidthAddr) {
      // 通常ケース：住所以外は最小幅固定、住所に余導
      $minWidths[$addressIdx] = round($addressW, 4);
    } else {
      // フォールバック：住所以外の合計がAVAILABLE_Wを超えた場合は全カラムを比例スケール
      $totalW = array_sum($minWidths);
      if ($totalW > 0) {
        $scale = self::AVAILABLE_W / $totalW;
        for ($i = 0; $i < 12; $i++) {
          $minWidths[$i] = round($minWidths[$i] * $scale, 4);
        }
        $diff = self::AVAILABLE_W - array_sum($minWidths);
        $minWidths[$addressIdx] = round($minWidths[$addressIdx] + $diff, 4);
      }
    }

    return $minWidths;
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
        $w     = $this->colWidths[$col] ?? self::COL_WIDTHS[$col];
        $lines = count($this->wrapText($pdf, $text, $w));
        if ($lines > $maxLines) {
          $maxLines = $lines;
        }
      }
      $fontMm      = self::FONT_SIZE * 0.352;
      $textH       = $maxLines > 1
        ? $fontMm + ($maxLines - 1) * self::LINE_PITCH
        : $fontMm;
      $heights[$i] = $textH + self::CELL_PAD_V * 2;
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

    $ts      = strtotime($outputDate);
    $dateStr = '〈 PDF出力日時 │ ' . date('Y/m/d', $ts) . "\u{2002}" . date('H:i', $ts) . ' 〉';
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
      $w = $this->colWidths[$ci] ?? self::COL_WIDTHS[$ci];
      $this->drawHeaderCell($pdf, $x, $startY, $w, self::HEADER_H, $label);
      $x += $w;
    }

    // COL7~12：グループラベル（高さHEADER_H1）＋ROW1/ROW2境界線
    foreach (self::ROW1_GROUPS as $group) {
      $groupW = 0;
      for ($ci = $group['start']; $ci < $group['start'] + $group['span']; $ci++) {
        $groupW += $this->colWidths[$ci] ?? self::COL_WIDTHS[$ci];
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
      $w = $this->colWidths[$ci] ?? self::COL_WIDTHS[$ci];
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
    $fontMm    = self::HEADER_FONT * 0.352 * 1.25;

    $totalTextH = $lineCount > 1
      ? $fontMm + ($lineCount - 1) * self::LINE_PITCH
      : $fontMm;
    $offsetY = ($h - $totalTextH) / 2;

    $pdf->setCellPaddings(0, 0, 0, 0);
    foreach ($lines as $li => $line) {
      $lineY = $y + $offsetY + $li * self::LINE_PITCH;
      $pdf->SetXY($x, $lineY);
      $pdf->Cell($w, 0, $line, 0, 0, 'C', false);
    }
  }

  /**
   * データ行を描画
   */
  protected function drawDataRow(Fpdi $pdf, float $startX, float $y, float $rowH, array $row): void
  {
    $x = $startX;
    foreach (self::DATA_KEYS as $ci => $key) {
      $w     = $this->colWidths[$ci] ?? self::COL_WIDTHS[$ci];
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
    $lines     = $this->wrapText($pdf, $text, $w);
    $lineCount = count($lines);
    $fontMm    = self::FONT_SIZE * 0.352 * 1.25;

    $pdf->setCellPaddings(0, 0, 0, 0);
    $totalTextH = $lineCount > 1
      ? $fontMm + ($lineCount - 1) * self::LINE_PITCH
      : $fontMm;
    $offsetY = ($h - $totalTextH) / 2;
    foreach ($lines as $li => $line) {
      $lineY = $y + $offsetY + $li * self::LINE_PITCH;
      if ($align === 'C') {
        $pdf->SetXY($x, $lineY);
        $pdf->Cell($w, 0, $line, 0, 0, 'C', false);
      } else {
        $pdf->SetXY($x + self::CELL_PAD_L, $lineY);
        $pdf->Cell($w - self::CELL_PAD_L, 0, $line, 0, 0, 'L', false);
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
