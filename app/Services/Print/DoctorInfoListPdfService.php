<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 医師情報一覧PDF生成サービス
 *
 * レイアウト概要：
 * - A4横 (297mm × 210mm)、左右マージン 8mm → 利用可能幅 281mm
 * - カラム幅：ID=16, 医師氏名=45, 医療機関名=55, 郵便番号=22, 住所=80, 電話番号=32, 携帯番号=31 合計=281mm
 * - 1ページあたりのデータ行数：可変高のため行ごとに判定
 */
class DoctorInfoListPdfService extends BasePdfService
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
  const COL_LABELS = ['ID', '医師氏名', '医療機関名', '郵便番号', '住所', '電話番号', '携帯番号'];
  // データキー
  const DATA_KEYS  = ['id', 'name', 'medical_institution', 'postal_code', 'address', 'phone', 'cell_phone'];

  // ページ座標
  const START_Y_PAGE1  = 30;  // 1ページ目の開始Y（タイトル分）
  const START_Y_OTHER  = 12;  // 2ページ目以降の開始Y
  const BOTTOM_MARGIN  = 198; // 下マージン mm（これを超えたら改ページ）

  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/doctor_info_list_coordinates.json');
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

    $outputDate = $submissionDate ?: date('Y-m-d');
    $doctors    = $this->fetchDoctors();

    $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);

    // 各行の高さを事前計算
    $rowHeights = $this->calcRowHeights($pdf, $doctors);

    // ページ割り付け（可変高なので行ごとに判定）
    $pages       = $this->splitIntoPages($rowHeights);
    $totalPages  = count($pages);
    $isFirstPage = true;

    foreach ($pages as $pageIndex => $rowIndices) {
      $pdf->AddPage();
      $startY = $isFirstPage ? self::START_Y_PAGE1 : self::START_Y_OTHER;

      if ($isFirstPage) {
        $this->drawTitleAndDate($pdf, $outputDate);
      }

      // このページに描画する医師データを抽出
      $pageDoctors = array_map(fn($i) => $doctors[$i], $rowIndices);
      $this->drawTable($pdf, $rowHeights, $rowIndices, $pageDoctors, $startY);

      $isFirstPage = false;
    }

    return $pdf->Output('', 'S');
  }

  /**
   * 全医師データを取得
   */
  protected function fetchDoctors(): array
  {
    $rows = DB::table('doctors')
      ->leftJoin('medical_institutions', 'doctors.medical_institutions_id', '=', 'medical_institutions.id')
      ->select(
        'doctors.id',
        'doctors.last_name',
        'doctors.first_name',
        'doctors.postal_code',
        'doctors.address_1',
        'doctors.address_2',
        'doctors.address_3',
        'doctors.phone',
        'doctors.cell_phone',
        'medical_institutions.medical_institution_name'
      )
      ->orderBy('doctors.id')
      ->get();

    $result = [];
    foreach ($rows as $d) {
      $address = trim(($d->address_1 ?? '') . ($d->address_2 ?? '') . ($d->address_3 ?? ''));
      $result[] = [
        'id'                  => (string)$d->id,
        'name'                => $this->formatDoctorName($d->last_name, $d->first_name),
        'medical_institution' => $d->medical_institution_name ?? '',
        'postal_code'         => $this->formatPostalCode($d->postal_code ?? ''),
        'address'             => $address,
        'phone'               => $this->formatPhoneNumber($d->phone ?? ''),
        'cell_phone'          => $this->formatPhoneNumber($d->cell_phone ?? ''),
      ];
    }

    return $result;
  }

  /**
   * 医師氏名フォーマット（姓と名をen spaceで結合）
   */
  protected function formatDoctorName(?string $lastName, ?string $firstName): string
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
   * @return float[] 行高の配列（doctors のインデックスに対応）
   */
  protected function calcRowHeights(Fpdi $pdf, array $doctors): array
  {
    $heights = [];
    foreach ($doctors as $i => $doctor) {
      $maxLines = 1;
      foreach (self::DATA_KEYS as $col => $key) {
        $text  = (string)($doctor[$key] ?? '');
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
   * 各ページの開始Yから積み上げて BOTTOM_MARGIN を超えたら改ページ
   *
   * @param float[] $rowHeights
   * @return int[][] ページごとの行インデックスの配列
   */
  protected function splitIntoPages(array $rowHeights): array
  {
    $pages       = [];
    $currentPage = [];
    $y           = self::START_Y_PAGE1 + self::HEADER_H; // 1ページ目はタイトル分オフセット
    $isFirstPage = true;

    foreach ($rowHeights as $i => $h) {
      if ($y + $h > self::BOTTOM_MARGIN && !empty($currentPage)) {
        $pages[]     = $currentPage;
        $currentPage = [];
        $y           = self::START_Y_OTHER + self::HEADER_H;
        $isFirstPage = false;
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
    $pdf->Text($x, 13, '医師情報一覧表');

    $dateStr = 'PDF出力日：' . date('Y年n月j日', strtotime($outputDate));
    $pdf->SetFont('kozgopromedium', '', 10);
    $dateW   = $pdf->GetStringWidth($dateStr);
    $pdf->Text($x + self::AVAILABLE_W - $dateW, 13, $dateStr);
  }

  /**
   * テーブルを描画（1ページ分）
   *
   * @param Fpdi    $pdf
   * @param float[] $rowHeights 全行の高さ配列
   * @param int[]   $rowIndices このページに表示する行インデックス
   * @param array   $doctors    このページに表示する医師データ
   * @param float   $startY     描画開始Y座標
   */
  protected function drawTable(Fpdi $pdf, array $rowHeights, array $rowIndices, array $doctors, float $startY): void
  {
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetTextColor(0, 0, 0);

    $startX = self::MARGIN_X;

    // ヘッダー行を描画
    $this->drawHeaderRow($pdf, $startX, $startY);

    // データ行を描画
    $y = $startY + self::HEADER_H;
    foreach ($rowIndices as $j => $i) {
      $rowH   = $rowHeights[$i];
      $doctor = $doctors[$j];
      $this->drawDataRow($pdf, $startX, $y, $rowH, $doctor);
      $y += $rowH;
    }

    // テーブル下端横線
    $rightX = $startX + self::AVAILABLE_W;
    $pdf->Line($startX, $y, $rightX, $y);

    // テーブル全体の左端縦線
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
    // 右端縦線
    $pdf->Line($x, $startY, $x, $startY + self::HEADER_H);
  }

  /**
   * データ行を描画
   */
  protected function drawDataRow(Fpdi $pdf, float $startX, float $y, float $rowH, array $doctor): void
  {
    $x = $startX;
    foreach (self::DATA_KEYS as $ci => $key) {
      $w    = self::COL_WIDTHS[$ci];
      $text = (string)($doctor[$key] ?? '');
      // IDは中央揃え、その他は左揃え
      $align = ($key === 'id') ? 'C' : 'L';
      $this->drawCell($pdf, $x, $y, $w, $rowH, $text, false, $align);
      $x += $w;
    }
    // 右端縦線
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

    // 枠線（上・左・右）
    $pdf->Line($x,       $y,     $x + $w, $y);      // 上
    $pdf->Line($x,       $y,     $x,      $y + $h);  // 左
    $pdf->Line($x + $w,  $y,     $x + $w, $y + $h);  // 右

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
}
