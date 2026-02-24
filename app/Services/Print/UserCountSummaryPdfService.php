<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 利用者数集計表（保険者名称毎）PDF生成サービス
 */
class UserCountSummaryPdfService
{
  /**
   * PDF生成
   *
   * @param string $serviceYearMonth サービス提供年月（Y-m形式）
   * @return string PDFバイナリ
   */
  public function generate(string $serviceYearMonth): string
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
   * データベースからデータを取得
   * 保険者×施術種類ごとのDISTINCT利用者数を集計
   */
  protected function fetchData(string $serviceYearMonth): array
  {
    // 全保険者を取得
    $insurers = DB::table('insurers')
      ->orderBy('insurer_name')
      ->get();

    // 指定年月の施術記録から、保険者×施術種類ごとのDISTINCT利用者IDを集計
    $counts = DB::table('records')
      ->whereNull('records.self_fee_id')
      ->whereRaw("DATE_FORMAT(records.date, '%Y-%m') = ?", [$serviceYearMonth])
      ->join('insurances', function ($join) {
        $join->on('insurances.clinic_user_id', '=', 'records.clinic_user_id')
          ->whereRaw('insurances.id = (SELECT MAX(id) FROM insurances WHERE clinic_user_id = records.clinic_user_id)');
      })
      ->whereNotNull('insurances.insurers_id')
      ->select(
        'records.therapy_type',
        'insurances.insurers_id',
        DB::raw('COUNT(DISTINCT records.clinic_user_id) as user_count')
      )
      ->groupBy('records.therapy_type', 'insurances.insurers_id')
      ->get();

    // [insurers_id][therapy_type] => count のマップを作成
    $countMap = [];
    foreach ($counts as $row) {
      $countMap[$row->insurers_id][$row->therapy_type] = (int)$row->user_count;
    }

    return [
      'insurers' => $insurers,
      'countMap' => $countMap,
    ];
  }

  /**
   * PDFを描画
   */
  protected function renderPdf(Fpdi $pdf, array $data, string $serviceYearMonth): void
  {
    $pdf->SetTextColor(0, 0, 0);

    $startX         = 10;
    $startY         = 30;
    $currentY       = $startY;
    $availableWidth = 190;

    // タイトル（左上）
    $pdf->SetFont('kozgopromedium', '', 17);
    $pdf->Text($startX, 15, '利用者数集計表（保険者名称毎）');

    // サービス提供年月（右上）
    $titleYearMonth      = $this->formatJapaneseYearMonth($serviceYearMonth);
    $pdf->SetFont('kozgopromedium', '', 15);
    $titleYearMonthWidth = $pdf->GetStringWidth($titleYearMonth);
    $oneCharWidth        = $pdf->GetStringWidth('年');
    $pdf->Text($startX + $availableWidth - $titleYearMonthWidth - $oneCharWidth, 15, $titleYearMonth);

    // カラム幅（合計190mm）
    $colWidths = [
      'therapy_type' => 45,
      'insurer_name' => 115,
      'user_count'   => 30,
    ];

    $rowHeight = 8;

    // ヘッダー描画
    $pdf->SetFont('kozgopromedium', '', 12);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->SetLineWidth(0.2);
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);

    $headers = [
      ['text' => '施術種類',   'width' => $colWidths['therapy_type']],
      ['text' => '保険者名称', 'width' => $colWidths['insurer_name']],
      ['text' => '該当人数',   'width' => $colWidths['user_count']],
    ];

    $x = $startX;
    foreach ($headers as $header) {
      $pdf->Rect($x, $currentY, $header['width'], $rowHeight, 'F');
      $pdf->SetXY($x, $currentY);
      $pdf->Cell($header['width'], $rowHeight, $header['text'], 0, 0, 'C', false);
      $pdf->Line($x, $currentY,              $x + $header['width'], $currentY);
      $pdf->Line($x, $currentY + $rowHeight, $x + $header['width'], $currentY + $rowHeight);
      $pdf->Line($x, $currentY,              $x, $currentY + $rowHeight);
      $pdf->Line($x + $header['width'], $currentY, $x + $header['width'], $currentY + $rowHeight);
      $x += $header['width'];
    }

    $currentY += $rowHeight;

    // データ行
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetFont('kozgopromedium', '', 11);

    $insurers = $data['insurers'];
    $countMap = $data['countMap'];

    // 施術種類の定義
    $therapyTypes = [
      1 => 'はり・きゅう',
      2 => 'あんま・マッサージ',
    ];

    $insurerList = $insurers->all();

    foreach ($therapyTypes as $therapyTypeValue => $therapyTypeLabel) {
      // 該当人数が1以上の保険者のみ描画対象とする
      $visibleInsurers = array_filter($insurerList, function ($insurer) use ($countMap, $therapyTypeValue) {
        return ($countMap[$insurer->id][$therapyTypeValue] ?? 0) > 0;
      });

      // 描画対象が0件の施術種類はブロックごとスキップ
      if (empty($visibleInsurers)) {
        continue;
      }

      $blockStartY  = $currentY;
      $blockHeight  = $rowHeight * count($visibleInsurers);
      $firstInsurer = true;

      foreach ($visibleInsurers as $insurer) {
        $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);

        $userCount = $countMap[$insurer->id][$therapyTypeValue];

        $x = $startX;

        // 施術種類セルは最初の行のみ結合描画
        if ($firstInsurer) {
          $pdf->SetXY($x, $blockStartY);
          $pdf->Cell($colWidths['therapy_type'], $blockHeight, $therapyTypeLabel, 0, 0, 'C', false);
          $pdf->Line($x, $blockStartY,                $x + $colWidths['therapy_type'], $blockStartY);
          $pdf->Line($x, $blockStartY + $blockHeight, $x + $colWidths['therapy_type'], $blockStartY + $blockHeight);
          $pdf->Line($x, $blockStartY,                $x, $blockStartY + $blockHeight);
          $pdf->Line($x + $colWidths['therapy_type'], $blockStartY, $x + $colWidths['therapy_type'], $blockStartY + $blockHeight);
        }
        $x += $colWidths['therapy_type'];

        // 保険者名称
        $pdf->SetXY($x, $currentY);
        $pdf->Cell($colWidths['insurer_name'], $rowHeight, $insurer->insurer_name, 0, 0, 'L', false);
        $pdf->Line($x, $currentY,              $x + $colWidths['insurer_name'], $currentY);
        $pdf->Line($x, $currentY + $rowHeight, $x + $colWidths['insurer_name'], $currentY + $rowHeight);
        $pdf->Line($x, $currentY,              $x, $currentY + $rowHeight);
        $pdf->Line($x + $colWidths['insurer_name'], $currentY, $x + $colWidths['insurer_name'], $currentY + $rowHeight);
        $x += $colWidths['insurer_name'];

        // 該当人数
        $pdf->SetXY($x, $currentY);
        $pdf->Cell($colWidths['user_count'], $rowHeight, (string)$userCount, 0, 0, 'C', false);
        $pdf->Line($x, $currentY,              $x + $colWidths['user_count'], $currentY);
        $pdf->Line($x, $currentY + $rowHeight, $x + $colWidths['user_count'], $currentY + $rowHeight);
        $pdf->Line($x, $currentY,              $x, $currentY + $rowHeight);
        $pdf->Line($x + $colWidths['user_count'], $currentY, $x + $colWidths['user_count'], $currentY + $rowHeight);

        $currentY    += $rowHeight;
        $firstInsurer = false;

        // ページ末尾チェック
        if ($currentY > 270) {
          $pdf->AddPage();
          $currentY    = 20;
          $blockStartY = $currentY;
        }
      }
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
