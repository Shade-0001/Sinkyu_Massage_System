<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 利用者情報一覧（医療保険情報）PDF生成サービス
 *
 * レイアウト概要：
 * - A4縦 (210mm × 297mm)、左右マージン 5mm → 利用可能幅 200mm
 * - 行ラベルカラム：22mm（COL1のみ、縦書きセクションラベル列なし）
 * - データカラム幅：27mm
 * - 1ページあたりのデータカラム数：floor((194 - 22) / 27) = 6
 * - 行構成：14行（ROW1〜ROW14）
 * - 1ページあたりのリスト数：2（上段・下段）
 */
class ClinicUserInsuranceInfoListPdfService extends BasePdfService
{
  // レイアウト定数
  const MARGIN_X          = 5;
  const AVAILABLE_W       = 200;
  const HEADER_W          = 22;  // 行ラベルカラム幅（COL1のみ）
  const DATA_COL_W        = 27;
  const MAX_COLS_PER_PAGE = 6;
  const CELL_PADDING_X    = 2.4;
  const BASE_ROW_H        = 6;
  const LINE_PITCH        = 3.2;
  const FONT_SIZE         = 7;

  const LISTS_PER_PAGE    = 2;   // 1ページあたりのリスト数

  // ページ座標
  const START_Y_PAGE1 = 30;
  const START_Y_OTHER = 12;
  const LIST_GAP      = 14;  // 上段・下段リスト間の縦間隔 mm（上側6mm + 下側8mm）

  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/clinic_user_insurance_info_list_coordinates.json');
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

    $outputDate = date('Y-m-d H:i:s');
    $users      = $this->fetchUsers();
    $rowDefs    = $this->getRowDefinitions();

    $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);

    $rowHeights  = $this->calcRowHeights($pdf, $rowDefs, $users);
    $tableH      = array_sum($rowHeights);

    $chunks      = array_chunk($users, self::MAX_COLS_PER_PAGE);
    $totalLists  = count($chunks);
    // ページグループ：LISTS_PER_PAGE チャンクずつ1ページに収める
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
          $sepY = $startY + $tableH + 6; // 上側6mm、下側8mm
          $pdf->SetLineStyle(['width' => 0.3, 'dash' => '4,4', 'color' => [100, 100, 100]]);
          $pdf->Line(self::MARGIN_X, $sepY, self::MARGIN_X + self::AVAILABLE_W, $sepY);
          $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
        }
      }

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
        // A4縦向き210mm幅、高さ297mm、下端7mm上
        $pdf->SetXY(0, 290);
        $pdf->Cell(210, 0, $pageText, 0, 0, 'C');
      }
    }

    return $pdf->Output('', 'S');
  }

  /**
   * 全利用者データを取得（医療保険情報）
   */
  protected function fetchUsers(): array
  {
    $users = DB::table('clinic_users')
      ->orderBy('clinic_users.id')
      ->get();

    // insurances（最新1件）
    $insurances = DB::table('insurances as ins')
      ->leftJoin('insurance_types_1 as it1', 'it1.id', '=', 'ins.insurance_type_1_id')
      ->leftJoin('insurance_types_2 as it2', 'it2.id', '=', 'ins.insurance_type_2_id')
      ->leftJoin('insurance_types_3 as it3', 'it3.id', '=', 'ins.insurance_type_3_id')
      ->leftJoin('expenses_borne_ratios as ebr', 'ebr.id', '=', 'ins.expenses_borne_ratio_id')
      ->leftJoin('insurers', 'insurers.id', '=', 'ins.insurers_id')
      ->whereRaw('ins.id = (SELECT MAX(id) FROM insurances WHERE clinic_user_id = ins.clinic_user_id)')
      ->select(
        'ins.clinic_user_id',
        'it1.insurance_type_1',
        'it2.insurance_type_2',
        'it3.insurance_type_3',
        'ins.insured_number',
        'ins.license_acquisition_date',
        'ins.certification_date',
        'ins.issue_date',
        'ebr.expenses_borne_ratio',
        'ins.is_healthcare_subsidized',
        'ins.public_funds_payer_code',
        'ins.public_funds_recipient_code',
        'insurers.insurer_number',
        'insurers.insurer_name',
        'ins.insured_name'
      )
      ->get()
      ->keyBy('clinic_user_id');

    $result = [];
    foreach ($users as $u) {
      $uid = $u->id;
      $ins = $insurances[$uid] ?? null;

      // 保険区分：insurance_type_1 + insurance_type_2 + insurance_type_3 を結合
      $insType = '';
      if ($ins) {
        $parts = array_filter([
          $ins->insurance_type_1 ?? '',
          $ins->insurance_type_2 ?? '',
          $ins->insurance_type_3 ?? '',
        ]);
        $insType = implode(' ', $parts);
      }

      $result[] = [
        'id'                       => $uid,
        'name'                     => $u->last_name . "\u{2002}" . $u->first_name,
        'insurance_type'           => $insType,
        'insured_number'           => $ins ? ($ins->insured_number ?? '') : '',
        'license_acquisition_date' => $this->formatJapaneseDate($ins->license_acquisition_date ?? null),
        'certification_date'       => $this->formatJapaneseDate($ins->certification_date ?? null),
        'issue_date'               => $this->formatJapaneseDate($ins->issue_date ?? null),
        'expenses_borne_ratio'     => $ins ? ($ins->expenses_borne_ratio ?? '') : '',
        'is_healthcare_subsidized' => $ins ? ($ins->is_healthcare_subsidized ? '○' : '✕') : '',
        'public_funds_payer_code'  => $ins ? ($ins->public_funds_payer_code ?? '') : '',
        'public_funds_recipient_code' => $ins ? ($ins->public_funds_recipient_code ?? '') : '',
        'insurer_number'           => $ins ? ($ins->insurer_number ?? '') : '',
        'insurer_name'             => $ins ? ($ins->insurer_name ?? '') : '',
        'insured_name'             => $ins ? ($ins->insured_name ?? '') : '',
      ];
    }

    return $result;
  }

  /**
   * 行定義を返す
   * [rowLabel, dataKey]
   */
  protected function getRowDefinitions(): array
  {
    return [
      ['利用者ID',         'id'],
      ['利用者氏名',        'name'],
      ['保険区分',          'insurance_type'],
      ['被保険者番号',      'insured_number'],
      ['資格取得年月日',    'license_acquisition_date'],
      ['認定年月日',        'certification_date'],
      ['発行年月日',        'issue_date'],
      ['一部負担金割合',    'expenses_borne_ratio'],
      ['医療助成対象',      'is_healthcare_subsidized'],
      ['公費負担者番号',    'public_funds_payer_code'],
      ['公費受給者番号',    'public_funds_recipient_code'],
      ['保険者番号',        'insurer_number'],
      ['保険者名称',        'insurer_name'],
      ['被保険者氏名',      'insured_name'],
    ];
  }

  /**
   * 各行の描画高さを計算
   */
  protected function calcRowHeights(Fpdi $pdf, array $rowDefs, array $users): array
  {
    $heights = [];
    foreach ($rowDefs as $i => $row) {
      $dataKey  = $row[1];
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
    $pdf->Text($x, 13, '利用者情報一覧（医療保険情報）');

    $ts      = strtotime($outputDate);
    $dateStr = '〈 PDF出力日時 │ ' . date('Y/m/d', $ts) . "\u{2002}" . date('H:i', $ts) . ' 〉';
    $pdf->SetFont('kozgopromedium', '', 8);
    $pdf->SetXY($x, 6);
    $pdf->Cell(self::AVAILABLE_W, 0, $dateStr, 0, 0, 'R');
  }

  /**
   * テーブルを描画（1チャンク分）
   */
  protected function drawTable(Fpdi $pdf, array $rowDefs, array $rowHeights, array $users, float $startY): void
  {
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetTextColor(0, 0, 0);

    $startX     = self::MARGIN_X;
    $headerX    = $startX;
    $dataStartX = $startX + self::HEADER_W;

    // 各行のY座標を事前計算
    $rowYs = [];
    $y     = $startY;
    foreach ($rowDefs as $i => $row) {
      $rowYs[$i] = $y;
      $y        += $rowHeights[$i];
    }
    $tableBottom = $y;

    // ---- 行を描画（行ラベル + データカラム） ----
    foreach ($rowDefs as $i => $rowDef) {
      [$rowLabel, $dataKey] = $rowDef;
      $rowY = $rowYs[$i];
      $rowH = $rowHeights[$i];

      // 行ラベルカラム
      $this->drawCell($pdf, $headerX, $rowY, self::HEADER_W, $rowH, $rowLabel, true, 'C');

      // データカラム
      foreach ($users as $j => $user) {
        $cellX = $dataStartX + $j * self::DATA_COL_W;
        $text  = (string)($user[$dataKey] ?? '');
        $this->drawCell($pdf, $cellX, $rowY, self::DATA_COL_W, $rowH, $text, false, 'L');
      }
    }

    // ---- 右端の縦線 ----
    $rightX = $dataStartX + count($users) * self::DATA_COL_W;
    $pdf->Line($rightX, $startY, $rightX, $tableBottom);

    // ---- テーブル全体の左端縦線 ----
    $pdf->Line($headerX, $startY, $headerX, $tableBottom);

    // ---- テーブル下端横線 ----
    $pdf->Line($headerX, $tableBottom, $rightX, $tableBottom);
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
      $offsetY = ($h - $totalTextH) / 2;
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
