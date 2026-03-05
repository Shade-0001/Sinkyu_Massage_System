<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 予定表PDF生成サービス
 */
class ScheduleListPdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/schedule_list_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  /**
   * PDF生成
   *
   * @param array  $clinicUserIds     利用者IDの配列
   * @param string $serviceYearMonth  サービス提供年月（Y-m形式）
   * @return string PDFバイナリ
   */
  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = ''): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    $outputDate = date('Y-m-d H:i:s');

    // 定休日情報を取得（曜日番号 0=日〜6=土 → bool のマップ）
    // 月初日時点で有効なclinic_infoを基準とする
    $closedDays = $this->fetchClosedDays($serviceYearMonth);

    foreach ($clinicUserIds as $clinicUserId) {
      $user = DB::table('clinic_users')->where('id', $clinicUserId)->first();
      if (!$user) {
        continue;
      }

      $records     = $this->fetchRecords($clinicUserId, $serviceYearMonth);
      $daysInMonth = (int)(new \DateTime($serviceYearMonth . '-01'))->format('t');

      // 1ページ目を追加して描画
      $pdf->AddPage();
      $this->renderUserSchedule($pdf, $user, $records, $serviceYearMonth, $daysInMonth, $closedDays, $outputDate);
    }

    return $pdf->Output('', 'S');
  }

  /**
   * 指定利用者・年月のrecordsを取得
   *
   * @return array  キー: 'Y-m-d' => [ ['start_time'=>'', 'end_time'=>'', 'therapy_type'=>1, 'therapy_content_id'=>...], ... ]
   */
  protected function fetchRecords(int $clinicUserId, string $serviceYearMonth): array
  {
    // therapy_contents テーブルとJOINして取得
    $rows = DB::table('records')
      ->leftJoin('therapy_contents', 'therapy_contents.id', '=', 'records.therapy_content_id')
      ->whereRaw("DATE_FORMAT(records.date, '%Y-%m') = ?", [$serviceYearMonth])
      ->where('records.clinic_user_id', $clinicUserId)
      ->select(
        'records.date',
        'records.start_time',
        'records.end_time',
        'records.therapy_type',
        'therapy_contents.therapy_content'
      )
      ->orderBy('records.date')
      ->orderBy('records.start_time')
      ->get();

    $map = [];
    foreach ($rows as $row) {
      $dateKey = (new \DateTime($row->date))->format('Y-m-d');
      $map[$dateKey][] = [
        'start_time'      => $row->start_time,
        'end_time'        => $row->end_time,
        'therapy_type'    => $row->therapy_type,
        'therapy_content' => $row->therapy_content,
      ];
    }
    return $map;
  }

  /**
   * 1利用者分のページを描画
   */
  protected function renderUserSchedule(
    Fpdi   $pdf,
    object $user,
    array  $records,
    string $serviceYearMonth,
    int    $daysInMonth,
    array  $closedDays = [],
    string $outputDate = ''
  ): void {
    // A4縦：210mm × 297mm、左右マージン8mm、有効幅194mm
    $startX         = 8;
    $startY         = 30;
    $availableWidth = 194;
    $rowHeight      = 7;

    $pdf->SetTextColor(0, 0, 0);

    // ---- タイトル（左上） ----
    $userName  = ($user->last_name ?? '') . '  ' . ($user->first_name ?? '');
    $titleText = '予定表（' . $userName . '  様）';
    $pdf->SetFont('kozgopromedium', '', 17);
    $pdf->Text($startX, 15, $titleText);

    // ---- サービス提供年月（右上） ----
    $yearMonthText      = $this->formatJapaneseYearMonth($serviceYearMonth);
    $pdf->SetFont('kozgopromedium', '', 13);
    $yearMonthWidth     = $pdf->GetStringWidth($yearMonthText);
    $oneCharWidth       = $pdf->GetStringWidth('年');
    $pdf->Text($startX + $availableWidth - $yearMonthWidth - $oneCharWidth, 15, $yearMonthText);

    // ---- PDF出力日時（右上） ----
    if ($outputDate) {
      $ts      = strtotime($outputDate);
      $dateStr = '〈 PDF出力日時 │ ' . date('Y/m/d', $ts) . "\u{2002}" . date('H:i', $ts) . ' 〉';
      $pdf->SetFont('kozgopromedium', '', 8);
      $pdf->SetXY($startX, 6);
      $pdf->Cell($availableWidth, 0, $dateStr, 0, 0, 'R');
    }

    // ---- カラム幅（合計186mm） ----
    // 日付:30, 開始･終了時刻:52, 施術種類:40, 施術内容:64
    $colWidths = [
      'date'           => 30,
      'time'           => 52,
      'therapy_type'   => 40,
      'therapy_content'=> 64,
    ];
    // 30+52+40+64 = 186 ✓

    $headers = [
      ['key' => 'date',            'text' => '日付'],
      ['key' => 'time',            'text' => '開始･終了時刻'],
      ['key' => 'therapy_type',    'text' => '施術種類'],
      ['key' => 'therapy_content', 'text' => '施術内容'],
    ];

    $currentY = $startY;

    // ---- ヘッダー行 ----
    $pdf->SetFont('kozgopromedium', '', 10);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);

    // TCPDFのcell_height_ratio=1.25を考慮した垂直中央配置
    $headerFontMm  = 10 * 0.352 * 1.25;
    $headerOffsetY = ($rowHeight - $headerFontMm) / 2;

    $x = $startX;
    foreach ($headers as $header) {
      $w = $colWidths[$header['key']];
      $pdf->Rect($x, $currentY, $w, $rowHeight, 'F');
      $pdf->setCellPaddings(0, 0, 0, 0);
      $pdf->SetXY($x, $currentY + $headerOffsetY);
      $pdf->Cell($w, 0, $header['text'], 0, 0, 'C', false);
      $this->drawCellBorder($pdf, $x, $currentY, $w, $rowHeight);
      $x += $w;
    }
    $currentY += $rowHeight;

    // ---- データ行：月の全日付を生成 ----
    $pdf->SetFont('kozgopromedium', '', 9);
    // TCPDFのcell_height_ratio=1.25を考慮した垂直中央配置
    $dataFontMm  = 9 * 0.352 * 1.25;
    $dataOffsetY = ($rowHeight - $dataFontMm) / 2;

    $yearInt  = (int)substr($serviceYearMonth, 0, 4);
    $monthInt = (int)substr($serviceYearMonth, 5, 2);

    // 施術種類マップ
    $therapyTypeMap = [
      1 => 'はり･きゅう',
      2 => 'あんま･マッサージ',
    ];

    $dayNames = ['日', '月', '火', '水', '木', '金', '土'];

    for ($day = 1; $day <= $daysInMonth; $day++) {
      $dateObj  = new \DateTime(sprintf('%04d-%02d-%02d', $yearInt, $monthInt, $day));
      $dateKey  = $dateObj->format('Y-m-d');
      $dow      = (int)$dateObj->format('w'); // 0=日, 6=土

      // 行の背景色（定休日 > 土日の優先順）
      if ($closedDays[$dow] ?? false) {
        // 定休日：濃いグレー
        $pdf->SetFillColor(180, 180, 180);
        $fillStyle = 'F';
      } elseif ($dow === 6) {
        // 土曜：薄青
        $pdf->SetFillColor(210, 230, 255);
        $fillStyle = 'F';
      } elseif ($dow === 0) {
        // 日曜：薄赤
        $pdf->SetFillColor(255, 210, 210);
        $fillStyle = 'F';
      } else {
        $fillStyle = null;
      }

      // 1桁の日は先頭にNBSP2つ（\xc2\xa0 = UTF-8のNBSP）
      $nbsp    = "\xc2\xa0";
      $dayStr  = ($day < 10) ? $nbsp . $nbsp . $day : (string)$day;
      $dateText = $dayStr . '日（' . $dayNames[$dow] . '）';

      // その日の施術データ
      $dayRecords = $records[$dateKey] ?? [];

      // 施術が複数あれば複数行、なければ1行（空）
      $rowCount = max(1, count($dayRecords));

      // ページオーバーチェック（有効高：A4縦 297mm、下マージン10mm）
      if ($currentY + $rowHeight * $rowCount > 287) {
        $pdf->AddPage();
        $currentY = 20;
      }

      $blockH = $rowHeight * $rowCount;

      // 定休日は行全体を塗りつぶし、それ以外（土日）は日付列のみ
      $isClosedDay = $closedDays[$dow] ?? false;
      if ($isClosedDay && $fillStyle) {
        $totalW = $colWidths['date'] + $colWidths['time'] + $colWidths['therapy_type'] + $colWidths['therapy_content'];
        $pdf->Rect($startX, $currentY, $totalW, $blockH, $fillStyle);
      }

      // 日付列（縦結合）
      $x = $startX;
      if (!$isClosedDay && $fillStyle) {
        $pdf->Rect($x, $currentY, $colWidths['date'], $blockH, $fillStyle);
      }
      $blockFontMm  = 9 * 0.352 * 1.25;
      $blockOffsetY = ($blockH - $blockFontMm) / 2;
      $pdf->setCellPaddings(0, 0, 0, 0);
      $pdf->SetXY($x, $currentY + $blockOffsetY);
      $pdf->Cell($colWidths['date'], 0, $dateText, 0, 0, 'C', false);
      $this->drawCellBorder($pdf, $x, $currentY, $colWidths['date'], $blockH);

      // 施術種類・施術内容の縦結合グループを事前に計算
      // 同じ値が連続する行をまとめる
      $therapyTypeGroups    = $this->buildMergeGroups($dayRecords, 'therapy_type');
      $therapyContentGroups = $this->buildMergeGroups($dayRecords, 'therapy_content');

      // 施術データ各行
      for ($ri = 0; $ri < $rowCount; $ri++) {
        $rec       = $dayRecords[$ri] ?? null;
        $rowY      = $currentY + $ri * $rowHeight;

        // 時刻列
        $x = $startX + $colWidths['date'];
        $timeText = '';
        if ($rec && $rec['start_time'] && $rec['end_time']) {
          $timeText = $this->formatTime($rec['start_time']) . ' ～ ' . $this->formatTime($rec['end_time']);
        }
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->SetXY($x, $rowY + $dataOffsetY);
        $pdf->Cell($colWidths['time'], 0, $timeText, 0, 0, 'C', false);
        $this->drawCellBorder($pdf, $x, $rowY, $colWidths['time'], $rowHeight);

        // 施術種類列：グループの先頭行のみ結合セルを描画、データ無しは空セル
        $x += $colWidths['time'];
        $groupForThisRow = null;
        foreach ($therapyTypeGroups as $grp) {
          if ($grp['start'] === $ri) {
            $groupForThisRow = $grp;
            break;
          }
        }
        if ($groupForThisRow !== null) {
          $mergedH         = $rowHeight * $groupForThisRow['count'];
          $therapyTypeText = $therapyTypeMap[$groupForThisRow['value']] ?? '';
          $mergedFontMm    = 9 * 0.352 * 1.25;
          $mergedOffsetY   = ($mergedH - $mergedFontMm) / 2;
          $pdf->setCellPaddings(0, 0, 0, 0);
          $pdf->SetXY($x, $rowY + $mergedOffsetY);
          $pdf->Cell($colWidths['therapy_type'], 0, $therapyTypeText, 0, 0, 'C', false);
          $this->drawCellBorder($pdf, $x, $rowY, $colWidths['therapy_type'], $mergedH);
        } else {
          // 途中行ではなくデータ無し行（$dayRecords 空）の空セル
          if (empty($dayRecords)) {
            $this->drawCellBorder($pdf, $x, $rowY, $colWidths['therapy_type'], $rowHeight);
          }
        }

        // 施術内容列：グループの先頭行のみ結合セルを描画、データ無しは空セル
        $x += $colWidths['therapy_type'];
        $contentGroupForThisRow = null;
        foreach ($therapyContentGroups as $grp) {
          if ($grp['start'] === $ri) {
            $contentGroupForThisRow = $grp;
            break;
          }
        }
        if ($contentGroupForThisRow !== null) {
          $mergedH            = $rowHeight * $contentGroupForThisRow['count'];
          $therapyContentText = $contentGroupForThisRow['value'] ?? '';
          $contentFontMm      = 9 * 0.352 * 1.25;
          $contentOffsetY     = ($mergedH - $contentFontMm) / 2;
          $pdf->setCellPaddings(0, 0, 0, 0);
          $pdf->SetXY($x + 1, $rowY + $contentOffsetY);
          $pdf->Cell($colWidths['therapy_content'] - 2, 0, $therapyContentText, 0, 0, 'L', false);
          $this->drawCellBorder($pdf, $x, $rowY, $colWidths['therapy_content'], $mergedH);
        } else {
          // 途中行ではなくデータ無し行（$dayRecords 空）の空セル
          if (empty($dayRecords)) {
            $this->drawCellBorder($pdf, $x, $rowY, $colWidths['therapy_content'], $rowHeight);
          }
        }
      }

      $currentY += $blockH;
    }
  }

  /**
   * 定休日情報を取得
   *
   * 月初日（$serviceYearMonth-01）以前で最も新しいclinic_infoを参照する。
   * 該当レコードがなければ最古のレコードにフォールバック。
   *
   * @param string $serviceYearMonth  Y-m形式
   * @return array  曜日番号(0=日〜6=土) => bool(true=定休)
   */
  protected function fetchClosedDays(string $serviceYearMonth): array
  {
    $firstDayOfMonth = $serviceYearMonth . '-01';

    $info = DB::table('clinic_info')
      ->where('created_at', '<=', $firstDayOfMonth . ' 23:59:59')
      ->orderByDesc('created_at')
      ->first();

    // 月初日以前にレコードがない場合は最古のレコードにフォールバック
    if (!$info) {
      $info = DB::table('clinic_info')->orderBy('created_at')->first();
    }

    // closed_day_* カラムを曜日番号にマッピング
    $map = [
      0 => 'closed_day_sunday',
      1 => 'closed_day_monday',
      2 => 'closed_day_tuesday',
      3 => 'closed_day_wednesday',
      4 => 'closed_day_thursday',
      5 => 'closed_day_friday',
      6 => 'closed_day_saturday',
    ];

    $result = [];
    foreach ($map as $dow => $col) {
      $result[$dow] = $info ? (bool)($info->$col ?? false) : false;
    }
    return $result;
  }

  /**
   * 連続する同一値の行をグループ化
   *
   * @param array  $rows   施術データの配列
   * @param string $key    グループ化対象のキー名
   * @return array  [{value, start, count}, ...]
   */
  protected function buildMergeGroups(array $rows, string $key): array
  {
    if (empty($rows)) {
      return [];
    }
    $groups = [['value' => $rows[0][$key], 'start' => 0, 'count' => 1]];
    $gi     = 0;
    for ($i = 1; $i < count($rows); $i++) {
      if ($rows[$i][$key] === $groups[$gi]['value']) {
        $groups[$gi]['count']++;
      } else {
        $gi++;
        $groups[] = ['value' => $rows[$i][$key], 'start' => $i, 'count' => 1];
      }
    }
    return $groups;
  }

  /**
   * セルの枠線を描画
   */
  protected function drawCellBorder(Fpdi $pdf, float $x, float $y, float $w, float $h): void
  {
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
    $pdf->Line($x,     $y,     $x + $w, $y);
    $pdf->Line($x,     $y + $h, $x + $w, $y + $h);
    $pdf->Line($x,     $y,     $x,      $y + $h);
    $pdf->Line($x + $w, $y,   $x + $w, $y + $h);
  }

  /**
   * 時刻フォーマット（HH:MM、1桁の時は先頭にNBSP2つ）
   *
   * @param string $time  'HH:MM:SS' または 'HH:MM' または 'H:MM'
   * @return string
   */
  protected function formatTime(string $time): string
  {
    // 'HH:MM:SS' → 'HH:MM'
    $parts = explode(':', $time);
    $h     = (int)($parts[0] ?? 0);
    $m     = (int)($parts[1] ?? 0);

    $nbsp = "\xc2\xa0";
    $hStr = ($h < 10) ? $nbsp . $nbsp . $h : (string)$h;
    $mStr = str_pad((string)$m, 2, '0', STR_PAD_LEFT);

    return $hStr . ':' . $mStr;
  }

  /**
   * 和暦年月フォーマット（例：令和6年 2月分）
   */
  protected function formatJapaneseYearMonth(string $yearMonth): string
  {
    $timestamp = strtotime($yearMonth . '-01');
    $year      = (int)date('Y', $timestamp);
    $month     = (int)date('n', $timestamp);
    $era       = $this->getJapaneseEra($year, $month, 1);

    return $era['era'] . $era['year'] . '年 ' . $month . '月分';
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
    } else {
      return ['era' => '明治', 'year' => $year - 1867];
    }
  }
}
