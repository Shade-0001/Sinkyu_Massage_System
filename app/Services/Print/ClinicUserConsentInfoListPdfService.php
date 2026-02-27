<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 利用者情報一覧（同意医師情報）PDF生成サービス
 *
 * レイアウト概要：
 * - A4縦 (210mm × 297mm)、左右マージン 8mm → 利用可能幅 194mm
 * - 第1カラム（縦書きラベル）：8mm
 * - 第2カラム（行ラベル）：22mm
 * - ヘッダー合計：30mm
 * - データカラム幅：32mm（最大16文字）
 * - 1ページあたりのデータカラム数：floor((194 - 30) / 32) = 5
 * - 1ページあたりのリスト数：2（上段・下段）
 * - 行構成：
 *   - ROW1〜2  ：利用者ID・利用者氏名（COL1+COL2 結合）
 *   - ROW3〜9  ：はり・きゅう（COL1縦書き、COL2行ラベル）
 *   - ROW10〜16：あんま・マッサージ（COL1縦書き、COL2行ラベル）
 */
class ClinicUserConsentInfoListPdfService extends BasePdfService
{
  // レイアウト定数
  const MARGIN_X          = 8;    // 左右マージン mm
  const AVAILABLE_W       = 194;  // 利用可能幅 mm
  const COL1_W            = 8;    // 第1カラム（縦書きラベル）幅
  const COL2_W            = 22;   // 第2カラム（行ラベル）幅
  const HEADER_W          = 30;   // COL1 + COL2
  const DATA_COL_W        = 32;   // データカラム幅
  const MAX_COLS_PER_PAGE = 5;    // 1ページのデータカラム数
  const CELL_PADDING_X    = 2.4;  // セル左右パディング合計 mm
  const BASE_ROW_H        = 6;    // 行の基本高さ mm
  const LINE_PITCH        = 3.2;  // 折り返し行のピッチ mm
  const FONT_SIZE         = 7;    // データフォント
  const HEADER_FONT       = 7;    // ヘッダーフォント

  const LISTS_PER_PAGE    = 2;    // 1ページあたりのリスト数

  // ページ座標
  const START_Y_PAGE1  = 30;   // 1ページ目の開始Y（タイトル分）
  const START_Y_OTHER  = 12;   // 2ページ目以降の開始Y
  const LIST_GAP       = 24;   // 上段・下段リスト間の縦間隔 mm（上側10mm + 下側14mm）

  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/clinic_user_consent_info_list_coordinates.json');
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
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetTextColor(0, 0, 0);

    $outputDate = $submissionDate ?: date('Y-m-d');
    $users      = $this->fetchUsers();
    $rowDefs    = $this->getRowDefinitions();

    $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);

    $rowHeights  = $this->calcRowHeights($pdf, $rowDefs, $users);
    $tableH      = array_sum($rowHeights);

    $chunks      = array_chunk($users, self::MAX_COLS_PER_PAGE);
    $totalLists  = count($chunks);
    $pageGroups  = array_chunk($chunks, self::LISTS_PER_PAGE);
    $isFirstPage = true;

    foreach ($pageGroups as $pageIndex => $group) {
      $pdf->AddPage();
      $topStartY = $isFirstPage ? self::START_Y_PAGE1 : self::START_Y_OTHER;

      if ($isFirstPage) {
        $this->drawTitleAndDate($pdf, $outputDate);
      }

      foreach ($group as $slotIndex => $chunk) {
        $listIndex = $pageIndex * self::LISTS_PER_PAGE + $slotIndex;
        $startY    = $topStartY + $slotIndex * ($tableH + self::LIST_GAP);

        $this->drawTable($pdf, $rowDefs, $rowHeights, $chunk, $startY);
        $this->drawListNumber($pdf, $listIndex + 1, $totalLists, $startY, self::MARGIN_X, self::FONT_SIZE);

        // 上段と下段の間に破線を描画（上段の直後のみ）
        if ($slotIndex === 0 && count($group) > 1) {
          $sepY = $startY + $tableH + 10; // 上側10mm、下側14mm
          $pdf->SetLineStyle(['width' => 0.3, 'dash' => '4,4', 'color' => [100, 100, 100]]);
          $pdf->Line(self::MARGIN_X, $sepY, self::MARGIN_X + self::AVAILABLE_W, $sepY);
          $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
        }
      }

      $isFirstPage = false;
    }

    return $pdf->Output('', 'S');
  }

  /**
   * 全利用者データを取得
   */
  protected function fetchUsers(): array
  {
    $users = DB::table('clinic_users')
      ->select('clinic_users.id', 'clinic_users.last_name', 'clinic_users.first_name')
      ->orderBy('clinic_users.id')
      ->get();

    // はり・きゅう同意（最新）
    $acu = DB::table('consents_acupuncture as ca')
      ->leftJoin('doctors as d', 'd.id', '=', 'ca.consenting_doctor_id')
      ->whereRaw('ca.id = (SELECT MAX(id) FROM consents_acupuncture WHERE clinic_user_id = ca.clinic_user_id)')
      ->select(
        'ca.clinic_user_id',
        'ca.consenting_date',
        'ca.first_care_date',
        'ca.consenting_start_date',
        'ca.consenting_end_date',
        'ca.benefit_period_start_date',
        'ca.benefit_period_end_date',
        'ca.reconsenting_expiry',
        'd.last_name as doc_last',
        'd.first_name as doc_first'
      )
      ->get()
      ->keyBy('clinic_user_id');

    // あんま・マッサージ同意（最新）
    $mas = DB::table('consents_massage as cm')
      ->leftJoin('doctors as d', 'd.id', '=', 'cm.consenting_doctor_id')
      ->whereRaw('cm.id = (SELECT MAX(id) FROM consents_massage WHERE clinic_user_id = cm.clinic_user_id)')
      ->select(
        'cm.clinic_user_id',
        'cm.consenting_date',
        'cm.first_care_date',
        'cm.consenting_start_date',
        'cm.consenting_end_date',
        'cm.benefit_period_start_date',
        'cm.benefit_period_end_date',
        'cm.reconsenting_expiry',
        'd.last_name as doc_last',
        'd.first_name as doc_first'
      )
      ->get()
      ->keyBy('clinic_user_id');

    $result = [];
    foreach ($users as $u) {
      $uid = $u->id;
      $a   = $acu[$uid] ?? null;
      $m   = $mas[$uid] ?? null;

      $result[] = [
        'id'   => $uid,
        'name' => $u->last_name . ' ' . $u->first_name,
        // はり・きゅう
        'acu_consenting_date'        => $this->formatJapaneseDate($a->consenting_date ?? null),
        'acu_first_care_date'        => $this->formatJapaneseDate($a->first_care_date ?? null),
        'acu_consenting_start_date'  => $this->formatJapaneseDate($a->consenting_start_date ?? null),
        'acu_consenting_end_date'    => $this->formatJapaneseDate($a->consenting_end_date ?? null),
        'acu_benefit_start'          => $this->formatJapaneseDate($a->benefit_period_start_date ?? null),
        'acu_benefit_end'            => $this->formatJapaneseDate($a->benefit_period_end_date ?? null),
        'acu_reconsenting_expiry'    => $this->formatJapaneseDate($a->reconsenting_expiry ?? null),
        // あんま・マッサージ
        'mas_consenting_date'        => $this->formatJapaneseDate($m->consenting_date ?? null),
        'mas_first_care_date'        => $this->formatJapaneseDate($m->first_care_date ?? null),
        'mas_consenting_start_date'  => $this->formatJapaneseDate($m->consenting_start_date ?? null),
        'mas_consenting_end_date'    => $this->formatJapaneseDate($m->consenting_end_date ?? null),
        'mas_benefit_start'          => $this->formatJapaneseDate($m->benefit_period_start_date ?? null),
        'mas_benefit_end'            => $this->formatJapaneseDate($m->benefit_period_end_date ?? null),
        'mas_reconsenting_expiry'    => $this->formatJapaneseDate($m->reconsenting_expiry ?? null),
      ];
    }

    return $result;
  }

  /**
   * 行定義を返す
   * [col1Label, col2Label, dataKey, section]
   * section: 'basic'|'acu'|'mas'
   */
  protected function getRowDefinitions(): array
  {
    return [
      // ROW1〜2：基本情報（COL1+COL2 結合）
      ['', '利用者ID',   'id',   'basic'],
      ['', '利用者氏名', 'name', 'basic'],
      // ROW3〜9：はり・きゅう（COL1縦書き）
      ['はり・きゅう', '同意年月日',       'acu_consenting_date',       'acu'],
      ['はり・きゅう', '初療年月日',       'acu_first_care_date',       'acu'],
      ['はり・きゅう', '同意開始年月日',   'acu_consenting_start_date', 'acu'],
      ['はり・きゅう', '同意終了年月日',   'acu_consenting_end_date',   'acu'],
      ['はり・きゅう', '支給期間開始日', 'acu_benefit_start',         'acu'],
      ['はり・きゅう', '支給期間終了日', 'acu_benefit_end',           'acu'],
      ['はり・きゅう', '再同意有効期限',   'acu_reconsenting_expiry',   'acu'],
      // ROW10〜16：あんま・マッサージ（COL1縦書き）
      ['あんま・マッサージ', '同意年月日',       'mas_consenting_date',       'mas'],
      ['あんま・マッサージ', '初療年月日',       'mas_first_care_date',       'mas'],
      ['あんま・マッサージ', '同意開始年月日',   'mas_consenting_start_date', 'mas'],
      ['あんま・マッサージ', '同意終了年月日',   'mas_consenting_end_date',   'mas'],
      ['あんま・マッサージ', '支給期間開始日', 'mas_benefit_start',         'mas'],
      ['あんま・マッサージ', '支給期間終了日', 'mas_benefit_end',           'mas'],
      ['あんま・マッサージ', '再同意有効期限',   'mas_reconsenting_expiry',   'mas'],
    ];
  }

  /**
   * 各行の描画高さを計算
   */
  protected function calcRowHeights(Fpdi $pdf, array $rowDefs, array $users): array
  {
    $heights = [];
    foreach ($rowDefs as $i => $row) {
      $dataKey  = $row[2];
      $maxLines = 1;
      foreach ($users as $u) {
        $text  = (string)($u[$dataKey] ?? '');
        $lines = count($this->wrapText($pdf, $text, self::DATA_COL_W));
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
   * タイトルと出力日を描画（1ページ目のみ）
   */
  protected function drawTitleAndDate(Fpdi $pdf, string $outputDate): void
  {
    $x = self::MARGIN_X;

    $pdf->SetFont('kozgopromedium', '', 15);
    $pdf->Text($x, 13, '利用者情報一覧（同意医師情報）');

    $dateStr = 'PDF出力日：' . $this->formatJapaneseDate($outputDate);
    $pdf->SetFont('kozgopromedium', '', 10);
    $dateW   = $pdf->GetStringWidth($dateStr);
    $pdf->Text($x + self::AVAILABLE_W - $dateW, 13, $dateStr);
  }

  /**
   * テーブルを描画（1チャンク分）
   */
  protected function drawTable(Fpdi $pdf, array $rowDefs, array $rowHeights, array $users, float $startY): void
  {
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetTextColor(0, 0, 0);

    $startX    = self::MARGIN_X;
    $sectionRanges = $this->getSectionRanges($rowDefs);

    // 各行のY座標を事前計算
    $rowYs = [];
    $y     = $startY;
    foreach ($rowDefs as $i => $row) {
      $rowYs[$i] = $y;
      $y        += $rowHeights[$i];
    }
    $tableBottom = $y;

    // セクション別の縦結合高さを計算
    $sectionHeights = [];
    foreach ($sectionRanges as $sec => [$secStart, $secEnd]) {
      $h = 0;
      for ($i = $secStart; $i <= $secEnd; $i++) {
        $h += $rowHeights[$i];
      }
      $sectionHeights[$sec] = $h;
    }

    $col1X      = $startX;
    $col2X      = $startX + self::COL1_W;
    $dataStartX = $startX + self::HEADER_W;

    // 各行のセル（第2カラム + データカラム）
    foreach ($rowDefs as $i => $rowDef) {
      [, $col2Label, $dataKey, $section] = $rowDef;
      $rowY = $rowYs[$i];
      $rowH = $rowHeights[$i];

      if ($section === 'basic') {
        $this->drawCell($pdf, $col1X, $rowY, self::HEADER_W, $rowH, $col2Label, true, 'C');
      } else {
        $this->drawCell($pdf, $col2X, $rowY, self::COL2_W, $rowH, $col2Label, true, 'C');
      }

      foreach ($users as $j => $user) {
        $cellX = $dataStartX + $j * self::DATA_COL_W;
        $text  = (string)($user[$dataKey] ?? '');
        $this->drawCell($pdf, $cellX, $rowY, self::DATA_COL_W, $rowH, $text, false, 'L');
      }
    }

    // 第1カラムを描画（acu/masセクションのみ縦結合）
    foreach ($sectionRanges as $sec => [$secStart, $secEnd]) {
      if ($sec === 'basic') {
        continue;
      }
      $secY  = $rowYs[$secStart];
      $secH  = $sectionHeights[$sec];
      $label = ($sec === 'acu') ? 'はり・きゅう' : 'あんま・マッサージ';

      $pdf->SetFillColor(230, 230, 230);
      $pdf->Rect($col1X, $secY, self::COL1_W, $secH, 'FD');
      $this->drawVerticalText($pdf, $col1X, $secY, self::COL1_W, $secH, $label);
    }

    // 右端の縦線
    $rightX = $dataStartX + count($users) * self::DATA_COL_W;
    $pdf->Line($rightX, $startY, $rightX, $tableBottom);

    // テーブル全体の左端縦線
    $pdf->Line($col1X, $startY, $col1X, $tableBottom);

    // テーブル下端横線
    $pdf->Line($col1X, $tableBottom, $rightX, $tableBottom);
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

    $pdf->Line($x, $y, $x + $w, $y);
    $pdf->Line($x, $y, $x, $y + $h);
    $pdf->Line($x + $w, $y, $x + $w, $y + $h);

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
        $lineX = $x + 1.6;
        $pdf->Text($lineX, $lineY, $line);
      }
    }
  }

  /**
   * 縦書きテキストを描画（第1カラム用）
   */
  protected function drawVerticalText(Fpdi $pdf, float $x, float $y, float $w, float $h, string $text): void
  {
    $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);
    $pdf->setCellPaddings(0, 0, 0, 0);

    $text   = str_replace('ー', '｜', $text);
    $chars  = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $count  = count($chars);
    $charH  = self::FONT_SIZE * 0.352;
    $gap    = 1.0;
    $totalH = $count * ($charH + $gap) - $gap;

    $startCharY = $y + ($h - $totalH) / 2;

    foreach ($chars as $ci => $ch) {
      $charY = $startCharY + $ci * ($charH + $gap);
      $pdf->SetXY($x, $charY);
      $pdf->Cell($w, $charH, $ch, 0, 0, 'C', false);
    }
  }

  /**
   * セクション区間を取得
   */
  protected function getSectionRanges(array $rowDefs): array
  {
    $ranges = [];
    foreach ($rowDefs as $i => $row) {
      $sec = $row[3];
      if (!isset($ranges[$sec])) {
        $ranges[$sec] = [$i, $i];
      } else {
        $ranges[$sec][1] = $i;
      }
    }
    return $ranges;
  }

  /**
   * 日付を和暦フォーマットに変換
   */
  protected function formatJapaneseDate(?string $date): string
  {
    if (!$date) {
      return '';
    }
    $ts    = strtotime($date);
    $year  = (int)date('Y', $ts);
    $month = (int)date('n', $ts);
    $day   = (int)date('j', $ts);
    $era   = $this->getJapaneseEra($year, $month, $day);
    return $era['era'] . $era['year'] . '年 ' . $month . '月 ' . $day . '日';
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
