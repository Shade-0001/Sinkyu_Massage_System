<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 月間スケジュールPDF生成サービス
 *
 * レイアウト概要：
 * - A4横 (297mm × 210mm)、左右マージン 6mm → 利用可能幅 285mm
 * - ヘッダー：PDF出力日・タイトル・期間・凡例・施術担当者
 * - テーブル：COL1=時刻, COL2以降=各日付（月の全日）
 * - 時刻スロット：30分刻み
 * - 日付ヘッダーフォーマット："日付 (曜日)" 例：5 (木)
 * - イベントスクエア：施術時間に応じて複数スロットにまたがる
 */
class MonthlySchedulePdfService extends BasePdfService
{
  const MARGIN_X    = 6;    // 左右マージン mm
  const AVAILABLE_W = 285;  // 利用可能幅 mm (297 - 6*2)
  const AVAILABLE_H = 198;  // 利用可能高さ mm (210 - 6*2)
  const ROW_H       = 10;   // 各スロット行の高さ mm（基準値）
  const HEADER_H    = 6;    // テーブルヘッダー行高 mm
  const SLOT_MIN    = 30;   // スロット単位（分）
  const FONT_MIN    = 5;    // 最低フォントサイズ pt
  // イベント矩形の色（はり・きゅう）
  const EVENT_COLOR_ACUPUNCTURE = [31, 145, 206];   // #1f91ce
  // イベント矩形の色（あんま・マッサージ）
  const EVENT_COLOR_MASSAGE     = [230, 126, 34];   // #e67e22

  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/monthly_schedule_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  /**
   * BasePdfService の抽象メソッド実装（このサービスでは generateMonthly を使用）
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
   * 月間スケジュールPDF生成（メイン）
   */
  public function generateMonthly(string $yearMonth, ?string $therapistId = null): string
  {
    $pdf = new Fpdi('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetTextColor(0, 0, 0);

    $outputDate = date('Y-m-d H:i:s');

    $monthDates = $this->buildMonthDates($yearMonth);
    $records    = $this->fetchMonthRecords($yearMonth, $therapistId);
    $timeSlots  = $this->fetchTimeSlots();

    $pdf->AddPage();
    $this->renderPage($pdf, $yearMonth, $monthDates, $records, $timeSlots, $outputDate, $therapistId);

    return $pdf->Output('', 'S');
  }

  /**
   * 月の全日付配列を生成
   */
  protected function buildMonthDates(string $yearMonth): array
  {
    $dates     = [];
    $firstDay  = new \DateTime($yearMonth . '-01');
    $daysInMonth = (int)$firstDay->format('t');

    for ($i = 0; $i < $daysInMonth; $i++) {
      $d = clone $firstDay;
      $d->modify("+{$i} days");
      $dates[] = $d->format('Y-m-d');
    }
    return $dates;
  }

  /**
   * 月の施術データを取得
   *
   * @return array  [dateKey => [['start_time', 'end_time', 'therapy_type', 'last_name', 'first_name'], ...], ...]
   */
  protected function fetchMonthRecords(string $yearMonth, ?string $therapistId): array
  {
    $startDate = $yearMonth . '-01';
    $firstDay  = new \DateTime($startDate);
    $endDate   = $firstDay->format('Y-m-') . $firstDay->format('t');

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
    string  $yearMonth,
    array   $monthDates,
    array   $records,
    array   $timeSlots,
    string  $outputDate,
    ?string $therapistId
  ): void {
    $startX   = self::MARGIN_X;
    $availW   = self::AVAILABLE_W;
    $titleY   = 12;
    $legendY  = 18;
    $subLineY = 18;

    // ---- PDF出力日時（右上、8pt） ----
    $ts      = strtotime($outputDate);
    $dateStr = '〈 PDF出力日時 │ ' . date('Y/m/d', $ts) . "\u{2002}" . date('H:i', $ts) . ' 〉';
    $pdf->SetFont('kozgopromedium', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY($startX, 4);
    $pdf->Cell($availW + self::MARGIN_X / 2, 0, $dateStr, 0, 0, 'R');

    // ---- タイトル（左） ----
    $pdf->SetFont('kozgopromedium', '', 15);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Text($startX, 8, '月間スケジュール');

    // ---- 期間テキスト（右端・タイトルと同Y） ----
    [$year, $month] = explode('-', $yearMonth);
    $eraInfo    = $this->convertToJapaneseYear((int)$year, (int)$month);
    $periodText = $eraInfo['era'] . $eraInfo['year'] . '年 ' . (int)$month . '月';
    $pdf->SetFont('kozgopromedium', '', 11);
    $pdf->SetTextColor(0, 0, 0);
    $periodW = $pdf->GetStringWidth($periodText);
    $pdf->Text($startX + $availW - $periodW, $titleY, $periodText);

    // ---- 凡例（担当施術者と同Y・テキスト9pt・コロン13.5pt） ----
    $pdf->SetFont('kozgopromedium', '', 9);
    $legendFontMm = 9 * 0.352;
    $squareSize   = $legendFontMm;
    $squareY      = $legendY + $squareSize * 0.13;

    // ブルー正方形（はり・きゅう）
    $pdf->SetFillColor(...self::EVENT_COLOR_ACUPUNCTURE);
    $pdf->RoundedRect($startX, $squareY, $squareSize, $squareSize, 0.4, '1111', 'F');
    $pdf->SetTextColor(0, 0, 0);
    $textAcuX = $startX + $squareSize * 0.5;
    $pdf->SetFont('kozgopromedium', '', 9 * 1.5);
    $colonW = $pdf->GetStringWidth('：');
    $pdf->Text($textAcuX, $legendY - 1.6, '：');
    $pdf->SetFont('kozgopromedium', '', 9);
    $pdf->Text($textAcuX + $colonW * 0.8, $legendY, 'はり・きゅう');
    $labelAcuW = $colonW + $pdf->GetStringWidth('はり・きゅう');

    $gapX = $startX + $squareSize + $labelAcuW + 5;

    // オレンジ正方形（あんま・マッサージ）
    $pdf->SetFillColor(...self::EVENT_COLOR_MASSAGE);
    $pdf->RoundedRect($gapX, $squareY, $squareSize, $squareSize, 0.4, '1111', 'F');
    $pdf->SetTextColor(0, 0, 0);
    $textMasX = $gapX + $squareSize * 0.5;
    $pdf->SetFont('kozgopromedium', '', 9 * 1.5);
    $pdf->Text($textMasX, $legendY - 1.6, '：');
    $pdf->SetFont('kozgopromedium', '', 9);
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
    $tableStartY = 24;
    $this->drawTable($pdf, $monthDates, $records, $timeSlots, $startX, $tableStartY, $availW);
  }

  /**
   * メインテーブルを描画
   */
  protected function drawTable(
    Fpdi   $pdf,
    array  $monthDates,
    array  $records,
    array  $timeSlots,
    float  $startX,
    float  $startY,
    float  $availW
  ): void {
    $dayCount = count($monthDates);

    // カラム幅を計算（時刻列 + 日付列×dayCount）
    $timeColW = 7;
    $restW    = $availW - $timeColW;
    $dayColW  = round($restW / $dayCount, 4);
    // 端数調整：最後の列で吸収
    $lastDayColW = round($restW - $dayColW * ($dayCount - 1), 4);

    $colWidths = [$timeColW];
    for ($i = 0; $i < $dayCount - 1; $i++) {
      $colWidths[] = $dayColW;
    }
    $colWidths[] = $lastDayColW;

    $headerH    = self::HEADER_H;
    $pageBottomY = self::AVAILABLE_H + self::MARGIN_X; // A4横 210mm、下マージン
    $bodyAvailH  = $pageBottomY - $startY - $headerH;
    $slotCount   = count($timeSlots);
    // スロット数に応じて行高を動的計算（最大ROW_H、最小4mm）
    $rowH = $slotCount > 0
      ? max(4.0, min(self::ROW_H, floor($bodyAvailH / $slotCount * 10) / 10))
      : self::ROW_H;

    // ---- ヘッダー行 ----
    $this->drawTableHeader($pdf, $monthDates, $colWidths, $startX, $startY, $headerH);

    $bodyStartY = $startY + $headerH;

    // ---- スロット行の枠線と時刻テキストを全行描画 ----
    $this->drawSlotGridAndTimeLabels($pdf, $timeSlots, $colWidths, $startX, $bodyStartY, $rowH);

    // ---- 各日列のイベントスクエアを描画（枠線の上に重ねる） ----
    $x = $startX + $timeColW;
    for ($dayIdx = 0; $dayIdx < $dayCount; $dayIdx++) {
      $dateKey    = $monthDates[$dayIdx];
      $colW       = $colWidths[$dayIdx + 1];
      $dayRecords = $records[$dateKey] ?? [];

      if (!empty($dayRecords)) {
        $this->drawDayEvents($pdf, $dayRecords, $timeSlots, $x, $bodyStartY, $colW, $rowH);
      }

      $x += $colW;
    }
  }

  /**
   * テーブルヘッダー行を描画
   * 日付フォーマット："日 (曜)" 例：5 (木)
   */
  protected function drawTableHeader(
    Fpdi  $pdf,
    array $monthDates,
    array $colWidths,
    float $startX,
    float $startY,
    float $headerH
  ): void {
    $dayNames = ['日', '月', '火', '水', '木', '金', '土'];
    $fontPt   = self::FONT_MIN + 1; // 6pt
    $fontMm   = $fontPt * 0.352 * 1.25;
    $offsetY  = ($headerH - $fontMm) / 2;

    // 時刻列ヘッダー（斜線）
    $x = $startX;
    $pdf->SetFillColor(220, 220, 220);
    $pdf->Rect($x, $startY, $colWidths[0], $headerH, 'F');
    $this->drawCellBorder($pdf, $x, $startY, $colWidths[0], $headerH);
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
    $pdf->Line($x, $startY, $x + $colWidths[0], $startY + $headerH);
    $x += $colWidths[0];

    foreach ($monthDates as $idx => $dateStr) {
      $dateObj = new \DateTime($dateStr);
      $dow     = (int)$dateObj->format('w'); // 0=日, 6=土
      $day     = (int)$dateObj->format('j');
      $label   = $day . ' (' . $dayNames[$dow] . ')';
      $colW    = $colWidths[$idx + 1];

      if ($dow === 0) {
        $pdf->SetFillColor(255, 210, 210);
      } elseif ($dow === 6) {
        $pdf->SetFillColor(210, 230, 255);
      } else {
        $pdf->SetFillColor(220, 220, 220);
      }

      $pdf->Rect($x, $startY, $colW, $headerH, 'F');

      $pdf->SetFont('kozgopromedium', '', $fontPt);
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
    $fontMm   = self::FONT_MIN * 0.352 * 1.25;
    $totalW   = array_sum($colWidths);

    $slotCount = count($timeSlots);

    // ---- 垂直線（列境界）を先に全行まとめて描画 ----
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
    $tableH = $slotCount * $rowH;
    $x = $startX;
    foreach ($colWidths as $colW) {
      $pdf->Line($x, $bodyStartY, $x, $bodyStartY + $tableH);
      $x += $colW;
    }
    // 右端
    $pdf->Line($startX + $totalW, $bodyStartY, $startX + $totalW, $bodyStartY + $tableH);

    // ---- 水平線（行境界）を1本ずつスタイル指定して描画 ----
    // 先頭行の上辺（実線）
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
    $pdf->Line($startX, $bodyStartY, $startX + $totalW, $bodyStartY);

    foreach ($timeSlots as $slotIdx => $timeSlot) {
      $rowY     = $bodyStartY + $slotIdx * $rowH;
      $minutes  = (int)explode(':', $timeSlot)[1];
      $isOnHour = ($minutes === 0);
      $offsetY  = ($rowH - $fontMm) / 2;

      // 時刻列テキスト（背景塗り）
      $pdf->SetFillColor(240, 240, 240);
      $pdf->Rect($startX, $rowY, $colWidths[0], $rowH, 'F');
      $pdf->SetFont('kozgopromedium', '', self::FONT_MIN);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->setCellPaddings(0, 0, 0, 0);
      $pdf->SetXY($startX, $rowY + $offsetY);
      $pdf->Cell($colWidths[0], 0, $timeSlot, 0, 0, 'C', false);

      // 下辺（:00=破線、:30=実線）
      $pdf->SetLineStyle($isOnHour
        ? ['width' => 0.2, 'dash' => '1,1', 'color' => [0, 0, 0]]
        : ['width' => 0.2, 'dash' => 0,     'color' => [0, 0, 0]]
      );
      $pdf->Line($startX, $rowY + $rowH, $startX + $totalW, $rowY + $rowH);
    }
  }

  /**
   * 1日分のイベントスクエアを描画
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
    $slotIndexMap = [];
    foreach ($timeSlots as $idx => $slot) {
      [$h, $m]                                 = explode(':', $slot);
      $slotIndexMap[(int)$h * 60 + (int)$m] = $idx;
    }

    $slotCount = count($timeSlots);
    $tableEndY = $bodyStartY + $slotCount * $rowH;

    $events = [];
    foreach ($dayRecords as $rec) {
      $parts        = explode(':', $rec['start_time']);
      $startMinutes = (int)$parts[0] * 60 + (int)($parts[1] ?? 0);

      if (isset($slotIndexMap[$startMinutes])) {
        $slotIdx = $slotIndexMap[$startMinutes];
      } else {
        $slotIdx = 0;
        foreach ($slotIndexMap as $slotMin => $idx) {
          if ($slotMin <= $startMinutes) {
            $slotIdx = $idx;
          }
        }
      }

      $endParts   = explode(':', $rec['end_time']);
      $endMinutes = (int)$endParts[0] * 60 + (int)($endParts[1] ?? 0);

      $durationMin = max(self::SLOT_MIN, $endMinutes - $startMinutes);
      $spanSlots   = (int)ceil($durationMin / self::SLOT_MIN);

      $events[] = [
        'rec'       => $rec,
        'slotIdx'   => $slotIdx,
        'spanSlots' => $spanSlots,
      ];
    }

    $slotGroups = [];
    foreach ($events as $ev) {
      $slotGroups[$ev['slotIdx']][] = $ev;
    }

    foreach ($slotGroups as $slotIdx => $group) {
      $count  = count($group);
      $eventW = $colW / $count;

      foreach ($group as $subIdx => $ev) {
        $rec       = $ev['rec'];
        $spanSlots = $ev['spanSlots'];
        $eventX    = $colX + $subIdx * $eventW;
        $eventY    = $bodyStartY + $slotIdx * $rowH;
        $eventH    = $spanSlots * $rowH;

        if ($eventY + $eventH > $tableEndY) {
          $eventH = $tableEndY - $eventY;
        }
        if ($eventH <= 0) {
          continue;
        }

        $this->drawEventRect($pdf, $rec, $eventX, $eventY, $eventW, $eventH, $spanSlots);
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
    float  $eventH,
    int    $spanSlots = 1
  ): void {
    $padding      = 0.5;
    $textPaddingX = 1;
    $innerH       = $eventH - $padding * 2;
    $innerW       = $eventW - $textPaddingX * 2;
    $fontSize     = self::FONT_MIN;
    $minFontSize  = 2.0;
    $use3Lines    = $spanSlots >= 2;

    $startText = $this->formatHHMM($rec['start_time']);
    $endText   = $this->formatHHMM($rec['end_time']);
    $nameText  = ($rec['last_name'] ?? '') . " " . ($rec['first_name'] ?? '');

    // 氏名フォントサイズ（時刻と独立して決定）
    $nameFontSize = self::FONT_MIN;
    while ($nameFontSize > $minFontSize) {
      $pdf->SetFont('kozgopromedium', 'B', $nameFontSize);
      if ($pdf->GetStringWidth($nameText) <= $innerW) {
        break;
      }
      $nameFontSize -= 0.5;
    }

    if ($use3Lines) {
      // 4行モード：開始時刻 / ～ / 終了時刻 / 氏名　時刻3行が縦・横に収まるフォントサイズを探す
      while ($fontSize > $minFontSize) {
        $pdf->SetFont('kozgopromedium', 'B', $fontSize);
        $lineH        = $fontSize * 0.352 + 0.3;
        $nameLineH    = $nameFontSize * 0.352 + 0.3;
        $textPaddingT = min(1.0, $innerH * 0.1);
        $neededH      = $textPaddingT + $lineH * 3 + $nameLineH;
        $neededW      = max(
          $pdf->GetStringWidth($startText),
          $pdf->GetStringWidth('～'),
          $pdf->GetStringWidth($endText)
        );
        if ($neededH <= $innerH && $neededW <= $innerW) {
          break;
        }
        $fontSize -= 0.5;
      }
      $lineH        = $fontSize * 0.352 + 0.3;
      $nameLineH    = $nameFontSize * 0.352 + 0.3;
      $textPaddingT = min(1.0, max(0, ($innerH - $lineH * 3 - $nameLineH) / 2));
    } else {
      // 2行モード：時刻テキストが $innerW に収まる最大フォントサイズを上から探す
      $timeText  = $startText . ' - ' . $endText;
      $timeFontSize = 20.0;
      while ($timeFontSize > $minFontSize) {
        $pdf->SetFont('kozgopromedium', 'B', $timeFontSize);
        if ($pdf->GetStringWidth($timeText) <= $innerW) {
          break;
        }
        $timeFontSize -= 0.5;
      }
      $fontSize     = $timeFontSize;
      $lineH        = $fontSize * 0.352 + 0.3;
      $nameLineH    = $nameFontSize * 0.352 + 0.3;
      $textPaddingT = min(1.0, max(0, ($innerH - $lineH - $nameLineH) / 2));
    }

    if ((int)$rec['therapy_type'] === 1) {
      $pdf->SetFillColor(...self::EVENT_COLOR_ACUPUNCTURE);
    } else {
      $pdf->SetFillColor(...self::EVENT_COLOR_MASSAGE);
    }

    $pdf->RoundedRect($eventX + $padding, $eventY + $padding, $eventW - $padding * 2, $eventH - $padding * 2, 0.4, '1111', 'F');

    $pdf->SetFont('kozgopromedium', 'B', $fontSize);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->setCellPaddings(0, 0, 0, 0);

    $textStartY = $eventY + $padding + $textPaddingT;

    $baseX = $eventX + $textPaddingX;

    if ($use3Lines) {
      $pdf->SetXY($baseX, $textStartY);
      $pdf->Cell($innerW, 0, $startText, 0, 0, 'C', false);

      // '～' を90度回転してスクエア幅中央に配置
      $tildeW   = $pdf->GetStringWidth('～');
      $tildeH   = $fontSize * 0.352;
      // 他テキストの中央X（Cell 'C' と同じ基準）
      $rotateCX = $baseX + $innerW / 2 - 0.3;
      $rotateCY = $textStartY + $lineH + $lineH / 2 - 0.3;
      $pdf->StartTransform();
      $pdf->Rotate(90, $rotateCX, $rotateCY);
      $pdf->SetXY($rotateCX - $tildeW / 2, $rotateCY - $tildeH / 2);
      $pdf->Cell($tildeW, 0, '～', 0, 0, 'L', false);
      $pdf->StopTransform();

      $pdf->SetXY($baseX, $textStartY + $lineH * 2 - 0.7);
      $pdf->Cell($innerW, 0, $endText, 0, 0, 'C', false);

      $pdf->SetFont('kozgopromedium', 'B', $nameFontSize);
      $pdf->SetXY($baseX, $textStartY + $lineH * 3);
      $pdf->Cell($innerW, 0, $nameText, 0, 0, 'C', false);
      $pdf->SetFont('kozgopromedium', 'B', $fontSize);
    } else {
      $pdf->SetXY($baseX, $textStartY);
      $pdf->Cell($innerW, 0, $timeText, 0, 0, 'C', false);

      $pdf->SetFont('kozgopromedium', 'B', $nameFontSize);
      $pdf->SetXY($baseX, $textStartY + $lineH);
      $pdf->Cell($innerW, 0, $nameText, 0, 0, 'C', false);
      $pdf->SetFont('kozgopromedium', 'B', $fontSize);
    }

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
   * セル枠線を描画（下辺なし）
   */
  protected function drawCellBorderNoBottom(Fpdi $pdf, float $x, float $y, float $w, float $h): void
  {
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
    $pdf->Line($x,      $y, $x + $w, $y);       // 上辺
    $pdf->Line($x,      $y, $x,      $y + $h);  // 左辺
    $pdf->Line($x + $w, $y, $x + $w, $y + $h);  // 右辺
  }

  /**
   * セル枠線を描画（下辺スタイル切替対応）
   * $dashedBottom = true の場合、下辺を破線で描画
   */
  protected function drawCellBorderWithBottomStyle(Fpdi $pdf, float $x, float $y, float $w, float $h, bool $dashedBottom = false): void
  {
    $solid = ['width' => 0.2, 'dash' => 0,     'color' => [0, 0, 0]];
    $dash  = ['width' => 0.2, 'dash' => '1,1', 'color' => [0, 0, 0]];

    $pdf->SetLineStyle($solid);
    $pdf->Line($x,      $y, $x + $w, $y);      // 上辺
    $pdf->Line($x,      $y, $x,      $y + $h); // 左辺
    $pdf->Line($x + $w, $y, $x + $w, $y + $h); // 右辺

    $pdf->SetLineStyle($dashedBottom ? $dash : $solid);
    $pdf->Line($x, $y + $h, $x + $w, $y + $h); // 下辺
  }
}
