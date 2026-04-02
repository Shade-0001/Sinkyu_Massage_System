<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 週間スケジュールPDF生成サービス
 *
 * レイアウト概要：
 * - A4縦 (210mm × 297mm)、左右マージン 8mm → 利用可能幅 194mm
 * - ヘッダー：PDF出力日・タイトル・期間・施術担当者凡例
 * - テーブル：COL1=時刻, COL2=日(日), COL3-7=月-金, COL8=土
 * - 時刻スロット：30分刻み
 */
class WeeklySchedulePdfService extends BasePdfService
{
  const MARGIN_X    = 8;    // 左右マージン mm
  const AVAILABLE_W = 194;  // 利用可能幅 mm
  const ROW_H       = 10;   // 各スロット行の高さ mm（2行テキスト対応）
  const HEADER_H    = 7;    // テーブルヘッダー行高 mm

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

    // 週の7日分の日付を生成（日〜土）
    $weekDates = $this->buildWeekDates($weekStartDate);

    // スケジュールデータを取得
    $records = $this->fetchWeekRecords($weekDates, $therapistId);

    // 時刻スロットを生成（30分刻み）
    $timeSlots = $this->fetchTimeSlots();

    $pdf->AddPage();
    $this->renderPage($pdf, $weekDates, $records, $timeSlots, $outputDate, $therapistId);

    return $pdf->Output('', 'S');
  }

  /**
   * 週の7日分の日付配列を生成（日〜土）
   *
   * @param string $weekStartDate  週の開始日 (YYYY-MM-DD, 日曜日)
   * @return array  ['YYYY-MM-DD', ...]  0=日曜〜6=土曜
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
   * @return array  [dateKey => [['start_time', 'end_time', 'therapy_type', 'user_name'], ...], ...]
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
      $current->modify('+30 minutes');
    }
    return $slots;
  }

  /**
   * 施術者名を取得
   */
  protected function fetchTherapistName(?string $therapistId): string
  {
    if (!$therapistId || $therapistId === 'all') {
      return '全員';
    }
    $therapist = DB::table('therapists')
      ->where('id', (int)$therapistId)
      ->select('last_name', 'first_name')
      ->first();
    return $therapist ? ($therapist->last_name . $therapist->first_name) : '不明';
  }

  /**
   * ページ全体を描画
   */
  protected function renderPage(
    Fpdi   $pdf,
    array  $weekDates,
    array  $records,
    array  $timeSlots,
    string $outputDate,
    ?string $therapistId
  ): void {
    $startX    = self::MARGIN_X;
    $availW    = self::AVAILABLE_W;
    $pageTitle = '週間スケジュール';
    $titleY    = 14;

    // ---- PDF出力日時（右上） ----
    $ts      = strtotime($outputDate);
    $dateStr = '〈 PDF出力日時 │ ' . date('Y/m/d', $ts) . "\u{2002}" . date('H:i', $ts) . ' 〉';
    $pdf->SetFont('kozgopromedium', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY($startX, 6);
    $pdf->Cell($availW, 0, $dateStr, 0, 0, 'R');

    // ---- タイトル（左） ----
    $pdf->SetFont('kozgopromedium', '', 17);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Text($startX, $titleY, $pageTitle);

    // ---- 期間テキスト（右端・タイトルと同Y） ----
    $startDateObj = new \DateTime($weekDates[0]);
    $endDateObj   = new \DateTime($weekDates[6]);
    $periodText   = $this->formatJpDate($startDateObj) . '　～　' . $this->formatJpDate($endDateObj);
    $pdf->SetFont('kozgopromedium', '', 11);
    $pdf->SetTextColor(0, 0, 0);
    $periodW = $pdf->GetStringWidth($periodText);
    $pdf->Text($startX + $availW - $periodW, $titleY, $periodText);

    // ---- 施術担当者（左・タイトル下） ----
    $therapistName  = $this->fetchTherapistName($therapistId);
    $therapistLabel = '施術担当者：' . $therapistName;
    $pdf->SetFont('kozgopromedium', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Text($startX, 22, $therapistLabel);

    // ---- 凡例（右・施術担当者と同Y） ----
    $this->drawLegend($pdf, $startX + $availW - 50, 19.5);

    // ---- テーブル ----
    $tableStartY = 28;
    $this->drawTable($pdf, $weekDates, $records, $timeSlots, $startX, $tableStartY, $availW);
  }

  /**
   * 凡例を描画
   */
  protected function drawLegend(Fpdi $pdf, float $x, float $y): void
  {
    $dotR = 1.5;
    $gap  = 1.2;

    // ブルー（はり・きゅう）
    $pdf->SetFillColor(45, 134, 194);
    $pdf->Circle($x + $dotR, $y + $dotR, $dotR, 0, 360, 'F');
    $pdf->SetFont('kozgopromedium', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Text($x + $dotR * 2 + $gap, $y + 0.2, '：はり・きゅう');

    // オレンジ（あんま・マッサージ）
    $y2 = $y + 5;
    $pdf->SetFillColor(220, 110, 30);
    $pdf->Circle($x + $dotR, $y2 + $dotR, $dotR, 0, 360, 'F');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Text($x + $dotR * 2 + $gap, $y2 + 0.2, '：あんま・マッサージ');
  }

  /**
   * メインテーブルを描画
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
    $timeColW = 13;  // 時刻列
    $restW    = $availW - $timeColW;
    $dayColW  = round($restW / 7, 4);
    // 端数を土曜列に加算
    $satColW  = round($restW - $dayColW * 6, 4);

    $colWidths = [$timeColW];
    for ($i = 0; $i < 6; $i++) {
      $colWidths[] = $dayColW;
    }
    $colWidths[] = $satColW;

    $rowH    = self::ROW_H;
    $headerH = self::HEADER_H;

    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);

    // ---- ヘッダー行描画 ----
    $this->drawTableHeader($pdf, $weekDates, $dayNames, $colWidths, $startX, $startY, $headerH);

    $currentY = $startY + $headerH;

    // ---- データ行描画 ----
    foreach ($timeSlots as $slotIdx => $timeSlot) {
      $isLast = ($slotIdx === count($timeSlots) - 1);
      $this->drawTimeSlotRow($pdf, $timeSlot, $weekDates, $records, $colWidths, $startX, $currentY, $rowH, $isLast);
      $currentY += $rowH;
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

    // 時刻列ヘッダー（空）
    $x = $startX;
    $pdf->SetFillColor(220, 220, 220);
    $pdf->Rect($x, $startY, $colWidths[0], $headerH, 'F');
    $this->drawCellBorder($pdf, $x, $startY, $colWidths[0], $headerH);
    $x += $colWidths[0];

    // 各曜日ヘッダー（COL2=日, COL3-7=月-金, COL8=土）
    for ($i = 0; $i < 7; $i++) {
      $dow     = $i; // 0=日, 6=土
      $dateObj = new \DateTime($weekDates[$i]);
      $month   = (int)$dateObj->format('m');
      $day     = (int)$dateObj->format('j');
      $dayName = $dayNames[$dow];
      $label   = $month . '/' . $day . '（' . $dayName . '）';
      $colW    = $colWidths[$i + 1];

      // 曜日による背景色（ヘッダーのみ）
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
   * 時刻スロット行を描画
   */
  protected function drawTimeSlotRow(
    Fpdi   $pdf,
    string $timeSlot,
    array  $weekDates,
    array  $records,
    array  $colWidths,
    float  $startX,
    float  $rowY,
    float  $rowH,
    bool   $isLast
  ): void {
    $fontMm  = 8 * 0.352 * 1.25;
    $offsetY = ($rowH - $fontMm) / 2;

    // 時刻列（背景グレー・黒テキスト）
    $x = $startX;
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Rect($x, $rowY, $colWidths[0], $rowH, 'F');
    $pdf->SetFont('kozgopromedium', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->setCellPaddings(0, 0, 0, 0);
    $pdf->SetXY($x, $rowY + $offsetY);
    $pdf->Cell($colWidths[0], 0, $timeSlot, 0, 0, 'C', false);
    $this->drawCellBorder($pdf, $x, $rowY, $colWidths[0], $rowH);
    $x += $colWidths[0];

    // 各曜日の施術データセル（背景色なし・土日も白）
    for ($i = 0; $i < 7; $i++) {
      $dateKey = $weekDates[$i];
      $colW    = $colWidths[$i + 1];

      // 時刻スロットに合致する施術データを収集
      $slotRecords = $this->getSlotRecords($records[$dateKey] ?? [], $timeSlot);

      if (!empty($slotRecords)) {
        $this->drawSlotEvents($pdf, $slotRecords, $x, $rowY, $colW, $rowH);
      }

      $this->drawCellBorder($pdf, $x, $rowY, $colW, $rowH);
      $x += $colW;
    }
  }

  /**
   * 指定スロット時刻（HH:MM）に開始する施術データを取得
   */
  protected function getSlotRecords(array $dayRecords, string $timeSlot): array
  {
    // スロット時刻を分に変換
    [$slotH, $slotM] = explode(':', $timeSlot);
    $slotMinutes = (int)$slotH * 60 + (int)$slotM;

    $result = [];
    foreach ($dayRecords as $rec) {
      $parts       = explode(':', $rec['start_time']);
      $startMinutes = (int)$parts[0] * 60 + (int)($parts[1] ?? 0);
      if ($startMinutes === $slotMinutes) {
        $result[] = $rec;
      }
    }
    return $result;
  }

  /**
   * セル内の施術イベントを描画
   * テキスト形式：「HH:MM ~ HH:MM」＋改行＋「姓 名」
   */
  protected function drawSlotEvents(
    Fpdi  $pdf,
    array $slotRecords,
    float $cellX,
    float $cellY,
    float $cellW,
    float $cellH
  ): void {
    $count   = count($slotRecords);
    $eventW  = $count > 0 ? $cellW / $count : $cellW;
    $padding = 0.8;
    $fontSize = 6.5;
    $lineH    = $fontSize * 0.352 * 1.25 + 0.6; // 行ピッチ
    $innerW   = $eventW - $padding * 2 - 0.5;

    foreach ($slotRecords as $idx => $rec) {
      $ex = $cellX + $idx * $eventW;

      // 施術種別による背景色
      if ((int)$rec['therapy_type'] === 1) {
        $pdf->SetFillColor(45, 134, 194);
      } else {
        $pdf->SetFillColor(220, 110, 30);
      }

      $pdf->Rect($ex + $padding, $cellY + $padding, $eventW - $padding * 2, $cellH - $padding * 2, 'F');

      $pdf->SetFont('kozgopromedium', '', $fontSize);
      $pdf->SetTextColor(255, 255, 255);
      $pdf->setCellPaddings(0, 0, 0, 0);

      // 時刻テキスト（HH:MM ~ HH:MM）
      $startStr = $this->formatHHMM($rec['start_time']);
      $endStr   = $this->formatHHMM($rec['end_time']);
      $timeText = $startStr . ' ~ ' . $endStr;

      // 氏名テキスト（姓 名）
      $nameText = ($rec['last_name'] ?? '') . "\u{3000}" . ($rec['first_name'] ?? '');

      // テキストが幅を超える場合はトリム
      $timeText = $this->trimTextToWidth($pdf, $timeText, $innerW);
      $nameText = $this->trimTextToWidth($pdf, $nameText, $innerW);

      // 2行をセル内に縦中央配置
      $totalH   = $lineH * 2;
      $startY   = $cellY + $padding + ($cellH - $padding * 2 - $totalH) / 2;
      if ($startY < $cellY + $padding) {
        $startY = $cellY + $padding + 0.3;
      }

      $pdf->SetXY($ex + $padding + 0.5, $startY);
      $pdf->Cell($innerW, 0, $timeText, 0, 0, 'L', false);

      $pdf->SetXY($ex + $padding + 0.5, $startY + $lineH);
      $pdf->Cell($innerW, 0, $nameText, 0, 0, 'L', false);
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
