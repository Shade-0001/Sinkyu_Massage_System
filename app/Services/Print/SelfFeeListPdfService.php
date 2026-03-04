<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 施術料金一覧表（自費）PDF生成サービス
 */
class SelfFeeListPdfService
{
  /**
   * コンストラクタ
   */
  public function __construct()
  {
  }

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

    // データを取得
    $data = $this->fetchData($serviceYearMonth);

    // PDFを描画
    $this->renderPdf($pdf, $data, $serviceYearMonth);

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
   * データベースからデータを取得
   */
  protected function fetchData(string $serviceYearMonth): array
  {
    // 指定年月の自費施術記録を取得
    $records = DB::table('records')
      ->whereNotNull('self_fee_id') // 自費施術のみ
      ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$serviceYearMonth])
      ->orderBy('clinic_user_id')
      ->orderBy('self_fee_id')
      ->get();

    // 利用者ごとにグループ化
    $userGroups = $records->groupBy('clinic_user_id');

    // 利用者情報を取得
    $clinicUserIds = $userGroups->keys()->toArray();
    $clinicUsers = DB::table('clinic_users')
      ->whereIn('id', $clinicUserIds)
      ->select(
        'id',
        'last_name',
        'first_name'
      )
      ->get()
      ->keyBy('id');

    // 自費施術料金マスタを取得
    $selfFees = DB::table('self_fees')
      ->get()
      ->keyBy('id');

    return [
      'userGroups' => $userGroups,
      'clinicUsers' => $clinicUsers,
      'selfFees' => $selfFees,
    ];
  }

  /**
   * PDFを描画
   */
  protected function renderPdf(Fpdi $pdf, array $data, string $serviceYearMonth): void
  {
    $pdf->SetFont('kozgopromedium', '', 13);
    $pdf->SetTextColor(0, 0, 0);

    // テーブル開始位置（タイトルより先に定義）
    $startX = 8;
    $startY = 30;  // タイトル（15mm）との間隔を15mmに設定
    $currentY = $startY;

    // A4用紙の幅210mm、左右マージン8mmずつで、利用可能幅は194mm
    $availableWidth = 194;

    // タイトル（テーブルの左辺に揃える）
    $titleText = '施術料金一覧表（自費）';
    $pdf->SetFont('kozgopromedium', '', 17);
    $pdf->Text($startX, 15, $titleText);

    // 元号年月（テーブルの右辺に揃える、全角1文字分左にズラす）
    $titleYearMonth = $this->formatJapaneseYearMonth($serviceYearMonth);
    $pdf->SetFont('kozgopromedium', '', 15);
    $titleYearMonthWidth = $pdf->GetStringWidth($titleYearMonth);
    $oneCharWidth = $pdf->GetStringWidth('年'); // 全角1文字分の幅を取得
    $pdf->Text($startX + $availableWidth - $titleYearMonthWidth - $oneCharWidth, 15, $titleYearMonth);

    // テーブル描画開始
    $availableWidth = 194;

    // カラム幅（合計190mm）※自費版は3列のみ
    $colWidths = [
      'name' => 50,        // 利用者氏名
      'treatment' => 90,   // 施術名
      'count' => 25,       // 回数
      'fee' => 25,         // 料金
    ];

    $totalWidth = array_sum($colWidths);
    $rowHeight = 8;  // フォントサイズに合わせて6から8に増加

    // ヘッダー描画
    $pdf->SetFont('kozgopromedium', '', 12);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->SetLineWidth(0.2);
    // テーブル全体を実線に設定
    $pdf->SetLineStyle(array('width' => 0.2, 'dash' => 0, 'color' => array(0, 0, 0)));

    $headers = [
      ['text' => '利用者氏名', 'width' => $colWidths['name']],
      ['text' => '施術名', 'width' => $colWidths['treatment']],
      ['text' => '回数', 'width' => $colWidths['count']],
      ['text' => '料金', 'width' => $colWidths['fee']],
    ];

    $x = $startX;
    foreach ($headers as $header) {
      // 背景色を塗りつぶし（枠線なし）
      $pdf->Rect($x, $currentY, $header['width'], $rowHeight, 'F');
      $pdf->SetXY($x, $currentY);
      $pdf->Cell($header['width'], $rowHeight, $header['text'], 0, 0, 'C', false);

      // 破線で枠線を手動描画
      $pdf->Line($x, $currentY, $x + $header['width'], $currentY); // 上
      $pdf->Line($x, $currentY + $rowHeight, $x + $header['width'], $currentY + $rowHeight); // 下
      $pdf->Line($x, $currentY, $x, $currentY + $rowHeight); // 左
      $pdf->Line($x + $header['width'], $currentY, $x + $header['width'], $currentY + $rowHeight); // 右

      $x += $header['width'];
    }

    $currentY += $rowHeight;

    // データ部分
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetFont('kozgopromedium', '', 11);

    $userGroups = $data['userGroups'];
    $clinicUsers = $data['clinicUsers'];
    $selfFees = $data['selfFees'];

    $grandTotalFee = 0;

    foreach ($userGroups as $clinicUserId => $records) {
      $clinicUser = $clinicUsers[$clinicUserId] ?? null;
      if (!$clinicUser) continue;

      $fullName = ($clinicUser->last_name ?? '') . '  ' . ($clinicUser->first_name ?? '');

      // 自費施術ごとにグループ化
      $selfFeeGroups = $records->groupBy('self_fee_id');

      $userTotalFee = 0;
      $userTotalCount = 0;

      // 利用者氏名のセル結合用：行数をカウント
      $userRowCount = $selfFeeGroups->count();
      $userStartY = $currentY;

      $firstRow = true;
      $selfFeeGroupsArray = $selfFeeGroups->toArray();
      $selfFeeGroupKeys = array_keys($selfFeeGroupsArray);
      $lastSelfFeeKey = end($selfFeeGroupKeys);

      foreach ($selfFeeGroups as $selfFeeId => $selfFeeRecords) {
        $selfFee = $selfFees[$selfFeeId] ?? null;
        if (!$selfFee) continue;

        $count = $selfFeeRecords->count();
        $unitPrice = (int)($selfFee->amount ?? 0);
        $totalFee = $count * $unitPrice;

        $userTotalFee += $totalFee;
        $userTotalCount += $count;

        $isLastRow = ($selfFeeId === $lastSelfFeeKey);

        // 行描画
        $x = $startX;

        // 利用者氏名（最初の行のみ結合セルとして描画）
        if ($firstRow) {
          $pdf->SetXY($x, $currentY);
          $pdf->Cell($colWidths['name'], $rowHeight * $userRowCount, $fullName, 0, 0, 'C', false);

          // 利用者氏名セルの枠線
          $nameHeight = $rowHeight * $userRowCount;
          $pdf->Line($startX, $userStartY, $startX + $colWidths['name'], $userStartY); // 上（実線）
          // 左右は実線
          $pdf->Line($startX, $userStartY, $startX, $userStartY + $nameHeight); // 左
          $pdf->Line($startX + $colWidths['name'], $userStartY, $startX + $colWidths['name'], $userStartY + $nameHeight); // 右
        }
        // 最終行で利用者氏名セルの下辺を破線で描画
        if ($isLastRow) {
          $pdf->SetLineStyle(array('width' => 0.2, 'dash' => '2,2', 'color' => array(0, 0, 0)));
          $pdf->Line($startX, $currentY + $rowHeight, $startX + $colWidths['name'], $currentY + $rowHeight);
          $pdf->SetLineStyle(array('width' => 0.2, 'dash' => 0, 'color' => array(0, 0, 0)));
        }
        $x += $colWidths['name'];

        // 施術名
        $pdf->SetXY($x, $currentY);
        $pdf->Cell($colWidths['treatment'], $rowHeight, $selfFee->self_fee_name, 0, 0, 'L', false);
        // 上辺は最初の行のみ実線、それ以外は破線
        if (!$firstRow) {
          $pdf->SetLineStyle(array('width' => 0.2, 'dash' => '2,2', 'color' => array(0, 0, 0)));
        }
        $pdf->Line($x, $currentY, $x + $colWidths['treatment'], $currentY); // 上
        // 下辺は常に破線
        $pdf->SetLineStyle(array('width' => 0.2, 'dash' => '2,2', 'color' => array(0, 0, 0)));
        $pdf->Line($x, $currentY + $rowHeight, $x + $colWidths['treatment'], $currentY + $rowHeight); // 下
        $pdf->SetLineStyle(array('width' => 0.2, 'dash' => 0, 'color' => array(0, 0, 0)));
        // 左辺は最初の行のみ描画（利用者氏名セルの右辺と重複を避ける）
        if ($firstRow) {
          $pdf->Line($x, $currentY, $x, $currentY + $rowHeight); // 左
        }
        $pdf->Line($x + $colWidths['treatment'], $currentY, $x + $colWidths['treatment'], $currentY + $rowHeight); // 右
        $x += $colWidths['treatment'];

        // 回数（中央揃え）
        $pdf->SetXY($x, $currentY);
        $pdf->Cell($colWidths['count'], $rowHeight, (string)$count, 0, 0, 'C', false);
        if (!$firstRow) {
          $pdf->SetLineStyle(array('width' => 0.2, 'dash' => '2,2', 'color' => array(0, 0, 0)));
        }
        $pdf->Line($x, $currentY, $x + $colWidths['count'], $currentY); // 上
        $pdf->SetLineStyle(array('width' => 0.2, 'dash' => '2,2', 'color' => array(0, 0, 0)));
        $pdf->Line($x, $currentY + $rowHeight, $x + $colWidths['count'], $currentY + $rowHeight); // 下
        $pdf->SetLineStyle(array('width' => 0.2, 'dash' => 0, 'color' => array(0, 0, 0)));
        $pdf->Line($x, $currentY, $x, $currentY + $rowHeight); // 左
        $pdf->Line($x + $colWidths['count'], $currentY, $x + $colWidths['count'], $currentY + $rowHeight); // 右
        $x += $colWidths['count'];

        // 料金（中央揃え）
        $pdf->SetXY($x, $currentY);
        $pdf->Cell($colWidths['fee'], $rowHeight, number_format($totalFee), 0, 0, 'C', false);
        if (!$firstRow) {
          $pdf->SetLineStyle(array('width' => 0.2, 'dash' => '2,2', 'color' => array(0, 0, 0)));
        }
        $pdf->Line($x, $currentY, $x + $colWidths['fee'], $currentY); // 上
        $pdf->SetLineStyle(array('width' => 0.2, 'dash' => '2,2', 'color' => array(0, 0, 0)));
        $pdf->Line($x, $currentY + $rowHeight, $x + $colWidths['fee'], $currentY + $rowHeight); // 下
        $pdf->SetLineStyle(array('width' => 0.2, 'dash' => 0, 'color' => array(0, 0, 0)));
        $pdf->Line($x, $currentY, $x, $currentY + $rowHeight); // 左
        $pdf->Line($x + $colWidths['fee'], $currentY, $x + $colWidths['fee'], $currentY + $rowHeight); // 右

        $currentY += $rowHeight;
        $firstRow = false;

        // ページ末尾チェック
        if ($currentY > 270) {
          $pdf->AddPage();
          $currentY = 20;
        }
      }

      // 合計行
      $grandTotalFee += $userTotalFee;

      $x = $startX;

      // 利用者氏名と施術名を結合して「合計」を中央揃え
      $pdf->SetXY($x, $currentY);
      $mergedWidth = $colWidths['name'] + $colWidths['treatment'];
      $pdf->Cell($mergedWidth, $rowHeight, '合計', 0, 0, 'C', false);

      // 上辺は描画しない（データ行の最終行の下辺で既に破線が描画されているため）

      // 下辺、左辺、右辺は実線で描画
      $pdf->Line($x, $currentY + $rowHeight, $x + $mergedWidth, $currentY + $rowHeight); // 下
      $pdf->Line($x, $currentY, $x, $currentY + $rowHeight); // 左
      $pdf->Line($x + $mergedWidth, $currentY, $x + $mergedWidth, $currentY + $rowHeight); // 右
      $x += $mergedWidth;

      // 回数（中央揃え）
      $pdf->SetXY($x, $currentY);
      $pdf->Cell($colWidths['count'], $rowHeight, (string)$userTotalCount, 0, 0, 'C', false);
      $pdf->Line($x, $currentY + $rowHeight, $x + $colWidths['count'], $currentY + $rowHeight); // 下
      $pdf->Line($x, $currentY, $x, $currentY + $rowHeight); // 左
      $pdf->Line($x + $colWidths['count'], $currentY, $x + $colWidths['count'], $currentY + $rowHeight); // 右
      $x += $colWidths['count'];

      // 料金（中央揃え）
      $pdf->SetXY($x, $currentY);
      $pdf->Cell($colWidths['fee'], $rowHeight, number_format($userTotalFee), 0, 0, 'C', false);
      $pdf->Line($x, $currentY + $rowHeight, $x + $colWidths['fee'], $currentY + $rowHeight); // 下
      $pdf->Line($x, $currentY, $x, $currentY + $rowHeight); // 左
      $pdf->Line($x + $colWidths['fee'], $currentY, $x + $colWidths['fee'], $currentY + $rowHeight); // 右

      $currentY += $rowHeight;

      // ページ末尾チェック
      if ($currentY > 270) {
        $pdf->AddPage();
        $currentY = 20;
      }
    }

    // 総合計行
    $x = $startX;

    // 利用者氏名と施術名を結合して「総合計」を中央揃え
    $pdf->SetXY($x, $currentY);
    $mergedWidth = $colWidths['name'] + $colWidths['treatment'];
    $pdf->Cell($mergedWidth, $rowHeight, '総合計', 0, 0, 'C', false);
    $pdf->Line($x, $currentY, $x + $mergedWidth, $currentY); // 上
    $pdf->Line($x, $currentY + $rowHeight, $x + $mergedWidth, $currentY + $rowHeight); // 下
    $pdf->Line($x, $currentY, $x, $currentY + $rowHeight); // 左
    $pdf->Line($x + $mergedWidth, $currentY, $x + $mergedWidth, $currentY + $rowHeight); // 右
    $x += $mergedWidth;

    // 回数（空欄）
    $pdf->SetXY($x, $currentY);
    $pdf->Cell($colWidths['count'], $rowHeight, '', 0, 0, 'C', false);
    $pdf->Line($x, $currentY, $x + $colWidths['count'], $currentY); // 上
    $pdf->Line($x, $currentY + $rowHeight, $x + $colWidths['count'], $currentY + $rowHeight); // 下
    $pdf->Line($x, $currentY, $x, $currentY + $rowHeight); // 左
    $pdf->Line($x + $colWidths['count'], $currentY, $x + $colWidths['count'], $currentY + $rowHeight); // 右
    $x += $colWidths['count'];

    // 料金（中央揃え）
    $pdf->SetXY($x, $currentY);
    $pdf->Cell($colWidths['fee'], $rowHeight, number_format($grandTotalFee), 0, 0, 'C', false);
    $pdf->Line($x, $currentY, $x + $colWidths['fee'], $currentY); // 上
    $pdf->Line($x, $currentY + $rowHeight, $x + $colWidths['fee'], $currentY + $rowHeight); // 下
    $pdf->Line($x, $currentY, $x, $currentY + $rowHeight); // 左
    $pdf->Line($x + $colWidths['fee'], $currentY, $x + $colWidths['fee'], $currentY + $rowHeight); // 右
  }

  /**
   * 和暦年月フォーマット（例：令和6年12月分）
   */
  protected function formatJapaneseYearMonth(string $yearMonth): string
  {
    $date = $yearMonth . '-01';
    $timestamp = strtotime($date);
    $year = (int)date('Y', $timestamp);
    $month = (int)date('n', $timestamp);

    $era = $this->getJapaneseEra($year, $month, 1);

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
