<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 施術料金一覧表（保険扱い）PDF生成サービス
 */
class TreatmentFeeListPdfService
{
  /**
   * 施術タイプ（acupuncture or massage）
   */
  protected $receiptType = 'acupuncture';

  /**
   * コンストラクタ
   */
  public function __construct()
  {
  }

  /**
   * 施術タイプを設定
   */
  public function setReceiptType(string $type): void
  {
    $this->receiptType = $type;
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

    return $pdf->Output('', 'S');
  }

  /**
   * データベースからデータを取得
   */
  protected function fetchData(string $serviceYearMonth): array
  {
    // 施術タイプに応じたtherapy_type（1=はり・きゅう、2=あんま・マッサージ）
    $therapyType = $this->receiptType === 'acupuncture' ? 1 : 2;

    // 指定年月の施術記録を取得（はり・きゅう且つ自費施術以外）
    $records = DB::table('records')
      ->where('therapy_type', $therapyType)
      ->whereNull('self_fee_id') // 自費施術以外
      ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$serviceYearMonth])
      ->orderBy('clinic_user_id')
      ->orderBy('therapy_content_id')
      ->get();

    // 利用者ごとにグループ化
    $userGroups = $records->groupBy('clinic_user_id');

    // 利用者情報を取得
    $clinicUserIds = $userGroups->keys()->toArray();
    $clinicUsers = DB::table('clinic_users')
      ->leftJoin('insurances', function($join) {
        $join->on('clinic_users.id', '=', 'insurances.clinic_user_id')
          ->whereRaw('insurances.id = (SELECT MAX(id) FROM insurances WHERE clinic_user_id = clinic_users.id)');
      })
      ->leftJoin('expenses_borne_ratios', 'insurances.expenses_borne_ratio_id', '=', 'expenses_borne_ratios.id')
      ->whereIn('clinic_users.id', $clinicUserIds)
      ->select(
        'clinic_users.id',
        'clinic_users.last_name',
        'clinic_users.first_name',
        'expenses_borne_ratios.id as ratio_id',
        'expenses_borne_ratios.expenses_borne_ratio'
      )
      ->get()
      ->keyBy('id');

    // 施術内容マスタを取得
    $therapyContents = DB::table('therapy_contents')
      ->where('therapy_type', $therapyType)
      ->get()
      ->keyBy('id');

    // 施術料金マスタ
    $treatmentFees = DB::table('treatment_fees')
      ->orderBy('id', 'desc')
      ->first();

    return [
      'userGroups' => $userGroups,
      'clinicUsers' => $clinicUsers,
      'therapyContents' => $therapyContents,
      'treatmentFees' => $treatmentFees,
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
    $startX = 5;
    $startY = 30;  // タイトル（15mm）との間隔を15mmに設定
    $currentY = $startY;

    // A4用紙の幅210mm、左右マージン5mmずつで、利用可能幅は200mm
    $availableWidth = 200;

    // タイトル（テーブルの左辺に揃える）
    $titleText = '施術料金一覧表（保険扱い）';
    $pdf->SetFont('kozgopromedium', '', 17);
    $pdf->Text($startX, 15, $titleText);

    // 元号年月（テーブルの右辺に揃える、全角1文字分左にズラす）
    $titleYearMonth = $this->formatJapaneseYearMonth($serviceYearMonth);
    $pdf->SetFont('kozgopromedium', '', 15);
    $titleYearMonthWidth = $pdf->GetStringWidth($titleYearMonth);
    $oneCharWidth = $pdf->GetStringWidth('年'); // 全角1文字分の幅を取得
    $pdf->Text($startX + $availableWidth - $titleYearMonthWidth - $oneCharWidth, 15, $titleYearMonth);

    // テーブル描画開始
    $availableWidth = 200;

    // カラム幅（合計190mm）
    $colWidths = [
      'name' => 30,        // 利用者氏名
      'treatment' => 56,   // 施術名
      'count' => 17,       // 回数
      'fee' => 29,         // 料金
      'copayment' => 29,   // 一部負担額
      'insurance' => 29,   // 保険請求額
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
      ['text' => '一部負担額', 'width' => $colWidths['copayment']],
      ['text' => '保険請求額', 'width' => $colWidths['insurance']],
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
    $therapyContents = $data['therapyContents'];
    $treatmentFees = $data['treatmentFees'];

    $grandTotalFee = 0;
    $grandTotalCopayment = 0;
    $grandTotalInsurance = 0;

    foreach ($userGroups as $clinicUserId => $records) {
      $clinicUser = $clinicUsers[$clinicUserId] ?? null;
      if (!$clinicUser) continue;

      $fullName = ($clinicUser->last_name ?? '') . "\u{2002}" . ($clinicUser->first_name ?? '');
      $ratioId = $clinicUser->ratio_id ?? 1;
      $ratioMap = [1 => 0.1, 2 => 0.2, 3 => 0.3];
      $ratio = $ratioMap[$ratioId] ?? 0.1;
      $ratioText = ($ratioId == 1 ? '1割' : ($ratioId == 2 ? '2割' : '3割'));

      // 施術内容ごとにグループ化
      $therapyGroups = $records->groupBy('therapy_content_id');

      $userTotalFee = 0;
      $userTotalCount = 0;

      // 利用者氏名のセル結合用：行数をカウント
      $userRowCount = $therapyGroups->count();
      $userStartY = $currentY;

      $firstRow = true;
      $therapyGroupsArray = $therapyGroups->toArray();
      $therapyGroupKeys = array_keys($therapyGroupsArray);
      $lastTherapyKey = end($therapyGroupKeys);
      
      foreach ($therapyGroups as $therapyContentId => $therapyRecords) {
        $therapyContent = $therapyContents[$therapyContentId] ?? null;
        if (!$therapyContent) continue;

        $count = $therapyRecords->count();
        $unitPrice = $this->getUnitPrice($treatmentFees, $therapyContentId);
        $totalFee = $count * $unitPrice;
        
        // 施術ごとの一部負担額と保険請求額を計算
        $copayment = round($totalFee * $ratio, -1);
        $insurance = $totalFee - $copayment;

        $userTotalFee += $totalFee;
        $userTotalCount += $count;
        
        $isLastRow = ($therapyContentId === $lastTherapyKey);

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
        $pdf->Cell($colWidths['treatment'], $rowHeight, $therapyContent->therapy_content, 0, 0, 'L', false);
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
        $x += $colWidths['fee'];

        // 一部負担額（中央揃え）
        $pdf->SetXY($x, $currentY);
        $pdf->Cell($colWidths['copayment'], $rowHeight, number_format($copayment), 0, 0, 'C', false);
        if (!$firstRow) {
          $pdf->SetLineStyle(array('width' => 0.2, 'dash' => '2,2', 'color' => array(0, 0, 0)));
        }
        $pdf->Line($x, $currentY, $x + $colWidths['copayment'], $currentY); // 上
        $pdf->SetLineStyle(array('width' => 0.2, 'dash' => '2,2', 'color' => array(0, 0, 0)));
        $pdf->Line($x, $currentY + $rowHeight, $x + $colWidths['copayment'], $currentY + $rowHeight); // 下
        $pdf->SetLineStyle(array('width' => 0.2, 'dash' => 0, 'color' => array(0, 0, 0)));
        $pdf->Line($x, $currentY, $x, $currentY + $rowHeight); // 左
        $pdf->Line($x + $colWidths['copayment'], $currentY, $x + $colWidths['copayment'], $currentY + $rowHeight); // 右
        $x += $colWidths['copayment'];

        // 保険請求額（中央揃え）
        $pdf->SetXY($x, $currentY);
        $pdf->Cell($colWidths['insurance'], $rowHeight, number_format($insurance), 0, 0, 'C', false);
        if (!$firstRow) {
          $pdf->SetLineStyle(array('width' => 0.2, 'dash' => '2,2', 'color' => array(0, 0, 0)));
        }
        $pdf->Line($x, $currentY, $x + $colWidths['insurance'], $currentY); // 上
        $pdf->SetLineStyle(array('width' => 0.2, 'dash' => '2,2', 'color' => array(0, 0, 0)));
        $pdf->Line($x, $currentY + $rowHeight, $x + $colWidths['insurance'], $currentY + $rowHeight); // 下
        $pdf->SetLineStyle(array('width' => 0.2, 'dash' => 0, 'color' => array(0, 0, 0)));
        $pdf->Line($x, $currentY, $x, $currentY + $rowHeight); // 左
        $pdf->Line($x + $colWidths['insurance'], $currentY, $x + $colWidths['insurance'], $currentY + $rowHeight); // 右

        $currentY += $rowHeight;
        $firstRow = false;

        // ページ末尾チェック
        if ($currentY > 270) {
          $pdf->AddPage();
          $currentY = 20;
        }
      }

      // 合計行
      $userCopayment = round($userTotalFee * $ratio, -1);
      $userInsurance = $userTotalFee - $userCopayment;

      $grandTotalFee += $userTotalFee;
      $grandTotalCopayment += $userCopayment;
      $grandTotalInsurance += $userInsurance;

      $x = $startX;

      // 利用者氏名と施術名を結合して「合計（*割負担）」を中央揃え
      $pdf->SetXY($x, $currentY);
      $mergedWidth = $colWidths['name'] + $colWidths['treatment'];
      $pdf->Cell($mergedWidth, $rowHeight, '合計（' . $ratioText . '負担）', 0, 0, 'C', false);
      
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
      $x += $colWidths['fee'];

      // 一部負担額（中央揃え）
      $pdf->SetXY($x, $currentY);
      $pdf->Cell($colWidths['copayment'], $rowHeight, number_format($userCopayment), 0, 0, 'C', false);
      $pdf->Line($x, $currentY + $rowHeight, $x + $colWidths['copayment'], $currentY + $rowHeight); // 下
      $pdf->Line($x, $currentY, $x, $currentY + $rowHeight); // 左
      $pdf->Line($x + $colWidths['copayment'], $currentY, $x + $colWidths['copayment'], $currentY + $rowHeight); // 右
      $x += $colWidths['copayment'];

      // 保険請求額（中央揃え）
      $pdf->SetXY($x, $currentY);
      $pdf->Cell($colWidths['insurance'], $rowHeight, number_format($userInsurance), 0, 0, 'C', false);
      $pdf->Line($x, $currentY + $rowHeight, $x + $colWidths['insurance'], $currentY + $rowHeight); // 下
      $pdf->Line($x, $currentY, $x, $currentY + $rowHeight); // 左
      $pdf->Line($x + $colWidths['insurance'], $currentY, $x + $colWidths['insurance'], $currentY + $rowHeight); // 右

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
    $x += $colWidths['fee'];

    // 一部負担額（中央揃え）
    $pdf->SetXY($x, $currentY);
    $pdf->Cell($colWidths['copayment'], $rowHeight, number_format($grandTotalCopayment), 0, 0, 'C', false);
    $pdf->Line($x, $currentY, $x + $colWidths['copayment'], $currentY); // 上
    $pdf->Line($x, $currentY + $rowHeight, $x + $colWidths['copayment'], $currentY + $rowHeight); // 下
    $pdf->Line($x, $currentY, $x, $currentY + $rowHeight); // 左
    $pdf->Line($x + $colWidths['copayment'], $currentY, $x + $colWidths['copayment'], $currentY + $rowHeight); // 右
    $x += $colWidths['copayment'];

    // 保険請求額（中央揃え）
    $pdf->SetXY($x, $currentY);
    $pdf->Cell($colWidths['insurance'], $rowHeight, number_format($grandTotalInsurance), 0, 0, 'C', false);
    $pdf->Line($x, $currentY, $x + $colWidths['insurance'], $currentY); // 上
    $pdf->Line($x, $currentY + $rowHeight, $x + $colWidths['insurance'], $currentY + $rowHeight); // 下
    $pdf->Line($x, $currentY, $x, $currentY + $rowHeight); // 左
    $pdf->Line($x + $colWidths['insurance'], $currentY, $x + $colWidths['insurance'], $currentY + $rowHeight); // 右
  }

  /**
   * 施術内容IDから単価を取得
   */
  protected function getUnitPrice($treatmentFees, int $therapyContentId): int
  {
    if (!$treatmentFees) {
      return 0;
    }

    // treatment_feesテーブルのカラム名とtherapy_content_idのマッピング
    $columnMap = [
      11 => 'hari_normal',
      12 => 'kyu_normal',
      13 => 'hari_and_kyu_normal',
      14 => 'hari_and_elec_needle_normal',
      15 => 'kyu_and_elec_moxa_heater_normal',
      16 => 'hari_and_kyu_elec_ray_normal',
      18 => 'massage_trunk_normal',
      19 => 'manual_correction_normal',
      20 => 'fomentation_normal',
      21 => 'fomentation_and_elec_ray_normal',
    ];

    $column = $columnMap[$therapyContentId] ?? null;
    if ($column && isset($treatmentFees->$column)) {
      return (int)$treatmentFees->$column;
    }

    return 0;
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
