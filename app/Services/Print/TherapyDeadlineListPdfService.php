<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 要加療期限切れリストPDF生成サービス
 */
class TherapyDeadlineListPdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/therapy_deadline_list_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  /**
   * PDF生成
   *
   * @param array  $clinicUserIds    未使用（BasePdfServiceシグネチャ互換）
   * @param string $serviceYearMonth 対象年月（Y-m形式）
   * @param string $submissionDate   未使用（BasePdfServiceシグネチャ互換）
   * @return string PDFバイナリ
   */
  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = ''): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    $pdf->AddPage();

    $data = $this->fetchData($serviceYearMonth);
    $this->renderPdf($pdf, $data, $serviceYearMonth);

    return $pdf->Output('', 'S');
  }

  /**
   * 対象年月範囲内に同意終了年月日があるデータを取得
   * はり・きゅう（consents_acupuncture）とあんま・マッサージ（consents_massage）を結合して返す
   */
  protected function fetchData(string $targetYearMonth): array
  {
    // 対象年月の開始・終了日
    $startDate = $targetYearMonth . '-01';
    $endDate   = date('Y-m-t', strtotime($startDate));

    $rows = [];

    // テーブル別定義
    $tables = [
      'consents_acupuncture' => 'HK',
      'consents_massage'     => 'AM',
    ];

    foreach ($tables as $table => $division) {
      $records = DB::table($table)
        ->whereBetween('consenting_end_date', [$startDate, $endDate])
        ->join('clinic_users', 'clinic_users.id', '=', $table . '.clinic_user_id')
        ->join('doctors', 'doctors.id', '=', $table . '.consenting_doctor_id')
        ->join('medical_institutions', 'medical_institutions.id', '=', 'doctors.medical_institutions_id')
        ->select(
          'clinic_users.id as user_id',
          'clinic_users.last_name as user_last_name',
          'clinic_users.first_name as user_first_name',
          $table . '.consenting_date',
          $table . '.consenting_start_date',
          $table . '.consenting_end_date',
          'doctors.last_name as doctor_last_name',
          'doctors.first_name as doctor_first_name',
          'medical_institutions.medical_institution_name'
        )
        ->orderBy('clinic_users.id')
        ->get();

      foreach ($records as $rec) {
        $rows[] = [
          'user_id'                  => $rec->user_id,
          'user_name'                => $rec->user_last_name . "\u{2002}" . $rec->user_first_name,
          'consenting_date'          => $rec->consenting_date,
          'consenting_start_date'    => $rec->consenting_start_date,
          'consenting_end_date'      => $rec->consenting_end_date,
          'doctor_name'              => $rec->doctor_last_name . "\u{2002}" . $rec->doctor_first_name,
          'medical_institution_name' => $rec->medical_institution_name,
          'division'                 => $division,
        ];
      }
    }

    // 利用者IDでソート
    usort($rows, fn($a, $b) => $a['user_id'] <=> $b['user_id']);

    return ['rows' => $rows];
  }

  /**
   * PDFを描画
   */
  protected function renderPdf(Fpdi $pdf, array $data, string $targetYearMonth): void
  {
    // A4縦：210mm × 297mm、左右マージン5mmで利用可能幅200mm
    $startX         = 5;
    $startY         = 30;
    $availableWidth = 200;
    $rowHeight      = 7;

    $pdf->SetTextColor(0, 0, 0);

    // タイトル（左上）
    $pdf->SetFont('kozgopromedium', '', 17);
    $pdf->Text($startX, 15, '要加療期限切れリスト');

    // 対象年月（右上）
    $titleYearMonth      = $this->formatJapaneseYearMonth($targetYearMonth);
    $pdf->SetFont('kozgopromedium', '', 15);
    $titleYearMonthWidth = $pdf->GetStringWidth($titleYearMonth);
    $oneCharWidth        = $pdf->GetStringWidth('年');
    $pdf->Text($startX + $availableWidth - $titleYearMonthWidth - $oneCharWidth, 15, $titleYearMonth);

    // カラム幅（合計194mm）
    // 利用者ID:16, 利用者氏名:28, 同意日:28, 同意開始年月日:28, 同意終了年月日:28, 医師名:22, 医療機関名:36, 区分:8
    // 16+28+28+28+28+22+36+8 = 194 ✓
    $colWidths = [
      'user_id'       => 16,
      'user_name'     => 28,
      'consent_date'  => 28,
      'start_date'    => 28,
      'end_date'      => 28,
      'doctor_name'   => 22,
      'institution'   => 36,
      'division'      => 8,
    ];

    $headers = [
      ['text' => '利用者ID',       'key' => 'user_id'],
      ['text' => '利用者氏名',     'key' => 'user_name'],
      ['text' => '同意日',         'key' => 'consent_date'],
      ['text' => '同意開始年月日', 'key' => 'start_date'],
      ['text' => '同意終了年月日', 'key' => 'end_date'],
      ['text' => '医師名',         'key' => 'doctor_name'],
      ['text' => '医療機関名',     'key' => 'institution'],
      ['text' => '区分',           'key' => 'division'],
    ];

    $currentY = $startY;

    // ヘッダー行
    $pdf->SetFont('kozgopromedium', '', 9);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);

    $x = $startX;
    foreach ($headers as $header) {
      $w = $colWidths[$header['key']];
      $pdf->Rect($x, $currentY, $w, $rowHeight, 'F');
      $pdf->SetXY($x, $currentY);
      $pdf->Cell($w, $rowHeight, $header['text'], 0, 0, 'C', false);
      $pdf->Line($x,     $currentY,             $x + $w, $currentY);
      $pdf->Line($x,     $currentY + $rowHeight, $x + $w, $currentY + $rowHeight);
      $pdf->Line($x,     $currentY,             $x,      $currentY + $rowHeight);
      $pdf->Line($x + $w, $currentY,            $x + $w, $currentY + $rowHeight);
      $x += $w;
    }

    $currentY += $rowHeight;

    // データ行
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetFont('kozgopromedium', '', 8);

    $rows = $data['rows'];

    foreach ($rows as $row) {
      // A4縦の有効高：297mm、下マージン10mm → 287mm まで
      if ($currentY + $rowHeight > 287) {
        $pdf->AddPage();
        $currentY = 20;
      }

      $cells = [
        'user_id'      => (string)$row['user_id'],
        'user_name'    => $row['user_name'],
        'consent_date' => $this->formatJapaneseDate($row['consenting_date']),
        'start_date'   => $this->formatJapaneseDate($row['consenting_start_date']),
        'end_date'     => $this->formatJapaneseDate($row['consenting_end_date']),
        'doctor_name'  => $row['doctor_name'],
        'institution'  => $row['medical_institution_name'],
        'division'     => $row['division'],
      ];

      $x = $startX;
      foreach ($headers as $header) {
        $key  = $header['key'];
        $w    = $colWidths[$key];
        $text = $cells[$key];

        $pdf->SetXY($x, $currentY);
        $pdf->Cell($w, $rowHeight, $text, 0, 0, 'C', false);

        $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
        $pdf->Line($x,     $currentY,             $x + $w, $currentY);
        $pdf->Line($x,     $currentY + $rowHeight, $x + $w, $currentY + $rowHeight);
        $pdf->Line($x,     $currentY,             $x,      $currentY + $rowHeight);
        $pdf->Line($x + $w, $currentY,            $x + $w, $currentY + $rowHeight);

        $x += $w;
      }

      $currentY += $rowHeight;
    }
  }

  /**
   * 和暦年月フォーマット（例：令和6年 12月分）
   */
  protected function formatJapaneseYearMonth(string $yearMonth): string
  {
    $date      = $yearMonth . '-01';
    $timestamp = strtotime($date);
    $year      = (int)date('Y', $timestamp);
    $month     = (int)date('n', $timestamp);
    $era       = $this->getJapaneseEra($year, $month, 1);

    return $era['era'] . $era['year'] . '年 ' . $month . '月分';
  }

  /**
   * 日付を和暦フォーマットに変換（例：令和6年 1月15日）
   */
  protected function formatJapaneseDate(?string $date): string
  {
    if (!$date) {
      return '';
    }
    $timestamp = strtotime($date);
    $year      = (int)date('Y', $timestamp);
    $month     = (int)date('n', $timestamp);
    $day       = (int)date('j', $timestamp);
    $era       = $this->getJapaneseEra($year, $month, $day);

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
    } else {
      return ['era' => '明治', 'year' => $year - 1867];
    }
  }
}
