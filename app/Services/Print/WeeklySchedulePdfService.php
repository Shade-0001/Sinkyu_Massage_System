<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 週間スケジュールPDF生成サービス
 *
 * レイアウト概要：
 * - A4縦 (210mm × 297mm)、左右マージン 8mm → 利用可能幅 194mm
 * - ヘッダー：PDF出力日・タイトル・期間・凡例・施術担当者
 * - テーブル：COL1=時刻, COL2=日(日), COL3-7=月-金, COL8=土
 * - 時刻スロット：30分刻み
 * - イベントスクエア：施術時間に応じて複数スロットにまたがる
 */
class WeeklySchedulePdfService extends BasePdfService
{
  const MARGIN_X    = 8;    // 左右マージン mm
  const AVAILABLE_W = 194;  // 利用可能幅 mm
  const ROW_H       = 12;   // 各スロット行の高さ mm（9ptフォント2行対応）
  const HEADER_H    = 7;    // テーブルヘッダー行高 mm
  const SLOT_MIN    = 30;   // スロット単位（分）
  const FONT_MIN    = 9;    // 最低フォントサイズ pt（PDF出力日時を除く）
  // イベント矩形の色（はり・きゅう）
  const EVENT_COLOR_ACUPUNCTURE = [31, 145, 206];   // #1f91ce
  // イベント矩形の色（あんま・マッサージ）
  const EVENT_COLOR_MASSAGE     = [230, 126, 34];   // #e67e22

  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/weekly_schedule_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  /**
   * BasePdfService の抽象メソッド実装（このサービスでは generateWeekly を使用）
   */
  public function generate(
    array $clinicUserIds = [],
    string $serviceYearMonth = '',
    string $submissionDate = '',
    string $remarks = ''
  ): string {
    return '';
  }

  /**
   * 週間スケジュールPDF生成（メイン）
   */
  public function generateWeekly(string $weekStartDate, ?string $therapistId = null): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetTextColor(0, 0, 0);

    $outputDate = date('Y-m-d H:i:s');

    $weekDates = $this->buildWeekDates($weekStartDate);
    $records   = $this->fetchWeekRecords($weekDates, $therapistId);
    $timeSlots = $this->fetchTimeSlots();

    $pdf->AddPage();
    $this->renderPage($pdf, $weekDates, $records, $timeSlots, $outputDate, $therapistId);

    return $pdf->Output('', 'S');
  }

  /**
   * 週の7日分の日付配列を生成（日〜土）
   */
  protected function buildWeekDates(string $weekStartDate): array
  {
    $dates = [];
    $base  = new \DateTime($weekStartDate);
    for ($i = 0; $i < 7; $i++) {
      $d = clone $base;
      $d->modify("+{$i} days");
      $dates[] = $d->format('Y-m-d');
    }
    return $dates;
  }

  /**
   * 週の施術データを取得
   *
   * @return array  [dateKey => [['start_time', 'end_time', 'therapy_type', 'last_name', 'first_name'], ...], ...]
   */
  protected function fetchWeekRecords(array $weekDates, ?string $therapistId): array
  {
    $startDate = $weekDates[0];
    $endDate   = $weekDates[6];

    $query = DB::table('records')
      ->join('clinic_users', 'records.clinic_user_id', '=', 'clinic_users.id')
      ->whereBetween('records.date', [$startDate, $endDate])
      ->select(
        'records.date',
        'records.start_time',
        'records.end_time',
        'records.therapy_type',
        'clinic_users.last_name',
        'clinic_users.first_name'
      )
      ->orderBy('records.date')
      ->orderBy('records.start_time');

    if ($therapistId && $therapistId !== 'all') {
      $query->where('records.therapist_id', (int)$therapistId);
    }

    $rows = $query->get();

    $map = [];
    foreach ($rows as $row) {
      $dateKey = (new \DateTime($row->date))->format('Y-m-d');
      $map[$dateKey][] = [
        'start_time'   => $row->start_time,
        'end_time'     => $row->end_time,
        'therapy_type' => $row->therapy_type,
        'last_name'    => $row->last_name,
        'first_name'   => $row->first_name,
      ];
    }
    return $map;
  }

  /**
   * 営業時間からの時刻スロット取得（30分刻み）
   *
   * @return array  ['HH:MM', ...]
   */
  protected function fetchTimeSlots(): array
  {
    $clinicInfo = DB::table('clinic_info')->orderByDesc('id')->first();
    $startTime  = $clinicInfo->business_hours_start ?? '09:00:00';
    $endTime    = $clinicInfo->business_hours_end   ?? '18:00:00';

    $slots   = [];
    $current = new \DateTime($startTime);
    $end     = new \DateTime($endTime);

    while ($current <= $end) {
      $slots[] = $current->format('H:i');
      $current->modify('+' . self::SLOT_MIN . ' minutes');
    }
    return $slots;
  }

  /**
   * 施術者情報を取得
   *
   * @return array  ['last_name' => '', 'first_name' => ''] または null（全員）
   */
  protected function fetchTherapist(?string $therapistId): ?array
  {
    if (!$therapistId || $therapistId === 'all') {
      return null;
    }
    $row = DB::table('therapists')
      ->where('id', (int)$therapistId)
      ->select('last_name', 'first_name')
      ->first();
    return $row ? ['last_name' => $row->last_name, 'first_name' => $row->first_name] : null;
  }

  /**
   * ページ全体を描画
   */
  protected function renderPage(
    Fpdi    $pdf,
    array   $weekDates,
    array   $records,
    array   $timeSlots,
    string  $outputDate,
    ?string $therapistId
  ): void {
    $startX    = self::MARGIN_X;
    $availW    = self::AVAILABLE_W;
    $titleY    = 15;
    $legendY   = 21;
    $subLineY  = 21;  // 施術者テキストのY（期間テキスト下）

    // ---- PDF出力日時（右上、例外的に8pt） ----
    $ts      = strtotime($outputDate);
    $dateStr = '〈 PDF出力日時 │ ' . date('Y/m/d', $ts) . "\u{2002}" . date('H:i', $ts) . ' 〉';
    $pdf->SetFont('kozgopromedium', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY($startX, 6);
    $pdf->Cell($availW + self::MARGIN_X / 2, 0, $dateStr, 0, 0, 'R');

    // ---- タイトル（左） ----
    $pdf->SetFont('kozgopromedium', '', 17);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Text($startX, 8, '週間スケジュール');

    // ---- 期間テキスト（右端・タイトルと同Y） ----
    $startDateObj = new \DateTime($weekDates[0]);
    $endDateObj   = new \DateTime($weekDates[6]);
    $periodText   = $this->formatJpDate($startDateObj) . ' ～ ' . $this->formatJpDate($endDateObj);
    $pdf->SetFont('kozgopromedium', '', 11);
    $pdf->SetTextColor(0, 0, 0);
    $periodW = $pdf->GetStringWidth($periodText);
    $pdf->Text($startX + $availW - $periodW, $titleY, $periodText);

    // ---- 凡例（タイトル下・左揃え・1行） ----
    // 全角サイズの正方形 + テキストで1行描画
    $pdf->SetFont('kozgopromedium', '', self::FONT_MIN);
    $legendFontMm = self::FONT_MIN * 0.352;           // フォント実寸 mm（line-height係数なし）
    $squareSize   = $legendFontMm;                   // フォントサイズと同じ辺長
    $squareY      = $legendY + $squareSize * 0.13;     // 視覚的中央合わせ（Text上端より少し下）

    // ブルー正方形（はり・きゅう）
    $pdf->SetFillColor(...self::EVENT_COLOR_ACUPUNCTURE);
    $pdf->RoundedRect($startX, $squareY, $squareSize, $squareSize, 0.4, '1111', 'F');
    // テキスト
    $pdf->SetTextColor(0, 0, 0);
    $textAcuX = $startX + $squareSize * 0.5;
    $pdf->SetFont('kozgopromedium', '', self::FONT_MIN * 1.5);
    $colonW = $pdf->GetStringWidth('：');
    $pdf->Text($textAcuX, $legendY - 1.6, '：');
    $pdf->SetFont('kozgopromedium', '', self::FONT_MIN);
    $pdf->Text($textAcuX + $colonW * 0.8, $legendY, 'はり・きゅう');
    $labelAcuW = $colonW + $pdf->GetStringWidth('はり・きゅう');

    // 区切りスペース
    $gapX = $startX + $squareSize + $labelAcuW + 5;

    // オレンジ正方形（あんま・マッサージ）
    $pdf->SetFillColor(...self::EVENT_COLOR_MASSAGE);
    $pdf->RoundedRect($gapX, $squareY, $squareSize, $squareSize, 0.4, '1111', 'F');
    // テキスト
    $pdf->SetTextColor(0, 0, 0);
    $textMasX = $gapX + $squareSize * 0.5;
    $pdf->SetFont('kozgopromedium', '', self::FONT_MIN * 1.5);
    $pdf->Text($textMasX, $legendY - 1.6, '：');
    $pdf->SetFont('kozgopromedium', '', self::FONT_MIN);
    $pdf->Text($textMasX + $colonW * 0.8, $legendY, 'あんま・マッサージ');

    // ---- 施術担当者（期間テキスト下・右揃え） ----
    $therapist = $this->fetchTherapist($therapistId);
    if ($therapist) {
      $therapistLabel = '担当施術者：' . $therapist['last_name'] . "\u{2002}" . $therapist['first_name'];
    } else {
      $therapistLabel = '担当施術者：全員';
    }
    $pdf->SetFont('kozgopromedium', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    $therapistW = $pdf->GetStringWidth($therapistLabel);
    $pdf->Text($startX + $availW - $therapistW, $subLineY, $therapistLabel);

    // ---- テーブル ----
    $tableStartY = 27;
    $this->drawTable($pdf, $weekDates, $records, $timeSlots, $startX, $tableStartY, $availW);
  }

  /**
   * メインテーブルを描画
   *
   * 描画順序：
   * 1. 全スロット行の枠線・時刻テキストを描画
   * 2. 各列のイベントスクエアをまとめて描画（枠線の上に重ねる）
   */
  protected function drawTable(
    Fpdi   $pdf,
    array  $weekDates,
    array  $records,
    array  $timeSlots,
    float  $startX,
    float  $startY,
    float  $availW
  ): void {
    $dayNames = ['日', '月', '火', '水', '木', '金', '土'];

    // カラム幅を計算
    $timeColW = 13;
    $restW    = $availW - $timeColW;
    $dayColW  = round($restW / 7, 4);
    $satColW  = round($restW - $dayColW * 6, 4);

    $colWidths = [$timeColW];
    for ($i = 0; $i < 6; $i++) {
      $colWidths[] = $dayColW;
    }
    $colWidths[] = $satColW;

    $headerH     = self::HEADER_H;
    $pageBottomY = 287;  // A4縦297mm、下マージン10mm
    $bodyAvailH  = $pageBottomY - $startY - $headerH;
    $slotCount   = \count($timeSlots);
    // スロット数に応じて行高を動的計算（最大ROW_H、最小6mm）
    $rowH = $slotCount > 0
      ? max(6.0, min(self::ROW_H, floor($bodyAvailH / $slotCount * 10) / 10))
      : self::ROW_H;

    // ---- ヘッダー行 ----
    $this->drawTableHeader($pdf, $weekDates, $dayNames, $colWidths, $startX, $startY, $headerH);

    $bodyStartY = $startY + $headerH;

    // ---- スロット行の枠線と時刻テキストを全行描画 ----
    $this->drawSlotGridAndTimeLabels($pdf, $timeSlots, $colWidths, $startX, $bodyStartY, $rowH);

    // ---- 各日列のイベントスクエアを描画（枠線の上に重ねる） ----
    $x = $startX + $timeColW;
    for ($dayIdx = 0; $dayIdx < 7; $dayIdx++) {
      $dateKey = $weekDates[$dayIdx];
      $colW    = $colWidths[$dayIdx + 1];
      $dayRecords = $records[$dateKey] ?? [];

      if (!empty($dayRecords)) {
        $this->drawDayEvents($pdf, $dayRecords, $timeSlots, $x, $bodyStartY, $colW, $rowH);
      }

      $x += $colW;
    }
  }

  /**
   * テーブルヘッダー行を描画
   */
  protected function drawTableHeader(
    Fpdi  $pdf,
    array $weekDates,
    array $dayNames,
    array $colWidths,
    float $startX,
    float $startY,
    float $headerH
  ): void {
    $fontMm  = 9 * 0.352 * 1.25;
    $offsetY = ($headerH - $fontMm) / 2;

    // 時刻列ヘッダー（斜線）
    $x = $startX;
    $pdf->SetFillColor(220, 220, 220);
    $pdf->Rect($x, $startY, $colWidths[0], $headerH, 'F');
    $this->drawCellBorder($pdf, $x, $startY, $colWidths[0], $headerH);
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
    $pdf->Line($x, $startY, $x + $colWidths[0], $startY + $headerH);
    $x += $colWidths[0];

    for ($i = 0; $i < 7; $i++) {
      $dow     = $i;
      $dateObj = new \DateTime($weekDates[$i]);
      $month   = (int)$dateObj->format('m');
      $day     = (int)$dateObj->format('j');
      $label   = $month . '/' . $day . '（' . $dayNames[$dow] . '）';
      $colW    = $colWidths[$i + 1];

      if ($dow === 0) {
        $pdf->SetFillColor(255, 210, 210);
      } elseif ($dow === 6) {
        $pdf->SetFillColor(210, 230, 255);
      } else {
        $pdf->SetFillColor(220, 220, 220);
      }

      $pdf->Rect($x, $startY, $colW, $headerH, 'F');

      $pdf->SetFont('kozgopromedium', '', 9);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->setCellPaddings(0, 0, 0, 0);
      $pdf->SetXY($x, $startY + $offsetY);
      $pdf->Cell($colW, 0, $label, 0, 0, 'C', false);

      $this->drawCellBorder($pdf, $x, $startY, $colW, $headerH);
      $x += $colW;
    }
  }

  /**
   * 全スロット行のグリッド枠線と時刻テキストを描画
   */
  protected function drawSlotGridAndTimeLabels(
    Fpdi  $pdf,
    array $timeSlots,
    array $colWidths,
    float $startX,
    float $bodyStartY,
    float $rowH
  ): void {
    $fontMm    = self::FONT_MIN * 0.352 * 1.25;

    foreach ($timeSlots as $slotIdx => $timeSlot) {
      $rowY    = $bodyStartY + $slotIdx * $rowH;
      $offsetY = ($rowH - $fontMm) / 2;

      // 時刻列（グレー背景）
      $pdf->SetFillColor(240, 240, 240);
      $pdf->Rect($startX, $rowY, $colWidths[0], $rowH, 'F');
      $pdf->SetFont('kozgopromedium', '', self::FONT_MIN);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->setCellPaddings(0, 0, 0, 0);
      $pdf->SetXY($startX, $rowY + $offsetY);
      $pdf->Cell($colWidths[0], 0, $timeSlot, 0, 0, 'C', false);
      $this->drawCellBorder($pdf, $startX, $rowY, $colWidths[0], $rowH);

      // 日付列セルの枠線（背景なし）
      $x = $startX + $colWidths[0];
      for ($i = 0; $i < 7; $i++) {
        $colW = $colWidths[$i + 1];
        $this->drawCellBorder($pdf, $x, $rowY, $colW, $rowH);
        $x += $colW;
      }
    }
  }

  /**
   * 1日分のイベントスクエアを描画（施術時間に応じてスロットをまたぐ）
   *
   * 同一スロット開始のイベントが複数ある場合は列内で横分割して表示。
   */
  protected function drawDayEvents(
    Fpdi  $pdf,
    array $dayRecords,
    array $timeSlots,
    float $colX,
    float $bodyStartY,
    float $colW,
    float $rowH
  ): void {
    // スロット開始分のインデックスマップを構築（分 => スロットインデックス）
    $slotIndexMap = [];
    foreach ($timeSlots as $idx => $slot) {
      [$h, $m]               = explode(':', $slot);
      $slotIndexMap[(int)$h * 60 + (int)$m] = $idx;
    }

    $slotCount  = count($timeSlots);
    $tableEndY  = $bodyStartY + $slotCount * $rowH;

    // 各レコードのスロット開始インデックスを取得
    $events = [];
    foreach ($dayRecords as $rec) {
      $parts        = explode(':', $rec['start_time']);
      $startMinutes = (int)$parts[0] * 60 + (int)($parts[1] ?? 0);

      // スロット表に存在しない開始時刻は最近接スロットにスナップ
      if (isset($slotIndexMap[$startMinutes])) {
        $slotIdx = $slotIndexMap[$startMinutes];
      } else {
        // 最も近い（以下の）スロットを探す
        $slotIdx = 0;
        foreach ($slotIndexMap as $slotMin => $idx) {
          if ($slotMin <= $startMinutes) {
            $slotIdx = $idx;
          }
        }
      }

      $endParts   = explode(':', $rec['end_time']);
      $endMinutes = (int)$endParts[0] * 60 + (int)($endParts[1] ?? 0);

      // 施術時間（分）からスロット数を計算（切り上げ、最低1スロット）
      $durationMin   = max(self::SLOT_MIN, $endMinutes - $startMinutes);
      $spanSlots     = (int)ceil($durationMin / self::SLOT_MIN);

      $events[] = [
        'rec'      => $rec,
        'slotIdx'  => $slotIdx,
        'spanSlots' => $spanSlots,
      ];
    }

    // スロットごとにそのスロットを開始するイベントをグループ化
    $slotGroups = [];
    foreach ($events as $ev) {
      $slotGroups[$ev['slotIdx']][] = $ev;
    }

    foreach ($slotGroups as $slotIdx => $group) {
      $count   = count($group);
      $eventW  = $colW / $count;

      foreach ($group as $subIdx => $ev) {
        $rec       = $ev['rec'];
        $spanSlots = $ev['spanSlots'];
        $eventX    = $colX + $subIdx * $eventW;
        $eventY    = $bodyStartY + $slotIdx * $rowH;
        $eventH    = $spanSlots * $rowH;

        // テーブル下端を超えないようにクリップ
        if ($eventY + $eventH > $tableEndY) {
          $eventH = $tableEndY - $eventY;
        }
        if ($eventH <= 0) {
          continue;
        }

        $this->drawEventRect($pdf, $rec, $eventX, $eventY, $eventW, $eventH);
      }
    }
  }

  /**
   * イベントスクエア（矩形＋テキスト）を描画
   */
  protected function drawEventRect(
    Fpdi   $pdf,
    array  $rec,
    float  $eventX,
    float  $eventY,
    float  $eventW,
    float  $eventH
  ): void {
    $padding      = 0.7;
    $textPaddingL = 2;
    $textPaddingT = 2;
    $fontSize = self::FONT_MIN;
    $lineH    = $fontSize * 0.352 + 0.3;
    $innerW   = $eventW - $padding - $textPaddingL;

    // 施術種別による背景色
    if ((int)$rec['therapy_type'] === 1) {
      $pdf->SetFillColor(...self::EVENT_COLOR_ACUPUNCTURE);
    } else {
      $pdf->SetFillColor(...self::EVENT_COLOR_MASSAGE);
    }

    $pdf->RoundedRect($eventX + $padding, $eventY + $padding, $eventW - $padding * 2, $eventH - $padding * 2, 0.4, '1111', 'F');

    $pdf->SetFont('kozgopromedium', 'B', $fontSize);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->setCellPaddings(0, 0, 0, 0);

    // 時刻テキスト（HH:MM - HH:MM）
    $timeText = $this->formatHHMM($rec['start_time']) . ' - ' . $this->formatHHMM($rec['end_time']);
    // 氏名テキスト（姓\u{2002}名）
    $nameText = ($rec['last_name'] ?? '') . "\u{2002}" . ($rec['first_name'] ?? '');

    $timeText = $this->trimTextToWidth($pdf, $timeText, $innerW);
    $nameText = $this->trimTextToWidth($pdf, $nameText, $innerW);

    $totalTextH = $lineH * 2;
    $textStartY = ($textPaddingT + $totalTextH <= $eventH - $padding)
      ? $eventY + $textPaddingT
      : $eventY + $padding;

    $pdf->SetXY($eventX + $textPaddingL, $textStartY);
    $pdf->Cell($innerW, 0, $timeText, 0, 0, 'L', false);

    $pdf->SetXY($eventX + $textPaddingL, $textStartY + $lineH);
    $pdf->Cell($innerW, 0, $nameText, 0, 0, 'L', false);

    $pdf->SetTextColor(0, 0, 0);
  }

  /**
   * 時刻文字列を HH:MM 形式にフォーマット
   */
  protected function formatHHMM(string $time): string
  {
    $parts = explode(':', $time);
    $h     = str_pad((string)(int)($parts[0] ?? 0), 2, '0', STR_PAD_LEFT);
    $m     = str_pad((string)(int)($parts[1] ?? 0), 2, '0', STR_PAD_LEFT);
    return $h . ':' . $m;
  }

  /**
   * テキストを指定幅に収まるようにトリム（末尾に…を付加）
   */
  protected function trimTextToWidth(Fpdi $pdf, string $text, float $maxW): string
  {
    if ($pdf->GetStringWidth($text) <= $maxW) {
      return $text;
    }
    while (mb_strlen($text) > 0 && $pdf->GetStringWidth($text . '…') > $maxW) {
      $text = mb_substr($text, 0, -1);
    }
    return $text . '…';
  }

  /**
   * セル枠線を描画
   */
  protected function drawCellBorder(Fpdi $pdf, float $x, float $y, float $w, float $h): void
  {
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
    $pdf->Line($x,      $y,      $x + $w, $y);
    $pdf->Line($x,      $y + $h, $x + $w, $y + $h);
    $pdf->Line($x,      $y,      $x,      $y + $h);
    $pdf->Line($x + $w, $y,      $x + $w, $y + $h);
  }

  /**
   * 和暦日付フォーマット（例：令和7年 4月 6日）
   */
  protected function formatJpDate(\DateTime $date): string
  {
    $year  = (int)$date->format('Y');
    $month = (int)$date->format('m');
    $day   = (int)$date->format('j');

    $dateStr = sprintf('%04d%02d%02d', $year, $month, $day);
    if ($dateStr >= '20190501') {
      $era = '令和'; $eraYear = $year - 2018;
    } elseif ($dateStr >= '19890108') {
      $era = '平成'; $eraYear = $year - 1988;
    } elseif ($dateStr >= '19261225') {
      $era = '昭和'; $eraYear = $year - 1925;
    } else {
      $era = '平成'; $eraYear = $year - 1988;
    }

    return $era . $eraYear . '年 ' . $month . '月 ' . $day . '日';
  }
}
