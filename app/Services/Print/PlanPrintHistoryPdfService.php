<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 計画情報履歴一覧表 PDF生成サービス
 *
 * レイアウト概要：
 * - A4横 (297mm × 210mm)、左右マージン 8mm → 利用可能幅 281mm
 * - ヘッダー：タイトル・利用者名・PDF出力日時
 * - テーブル：評価日 / 評価者 / 聴衆者 / ADL合計 / 摂食 / 移動 / 更衣 / 排尿 / データ登録日
 */
class PlanPrintHistoryPdfService extends BasePdfService
{
  const MARGIN_X    = 8;
  const AVAILABLE_W = 281;  // A4横 297mm - 左右各8mm
  const ROW_H       = 6;
  const HEADER_H    = 7;
  const FONT_SIZE   = 8;
  const TITLE_SIZE  = 14;

  // 代表データ（列幅計算用）
  const COL_SAMPLE_DATA = [
    'assessment_date'     => ['2025/12/31'],
    'assessor'            => ['山田　太郎'],
    'audience'            => ['山田　太郎'],
    'adl_total'           => ['99'],
    'eating'              => ['自立'],
    'moving'              => ['要介護'],
    'changing_clothes'    => ['自立'],
    'urination'           => ['自立'],
    'created_at'          => ['2025/12/31'],
  ];

  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/plan_print_history_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  /**
   * PDF生成
   *
   * @param int    $clinicUserId  利用者ID
   * @return string PDFバイナリ
   */
  public function generateHistory(int $clinicUserId): string
  {
    $user  = DB::table('clinic_users')->where('id', $clinicUserId)->first();
    $plans = $this->fetchPlans($clinicUserId);

    $pdf = new Fpdi('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetTextColor(0, 0, 0);

    $outputDate = date('Y-m-d H:i:s');

    $pdf->AddPage();
    $currentY = $this->renderHeader($pdf, $user, $outputDate);
    $this->renderTable($pdf, $plans, $currentY);

    return $pdf->Output('', 'S');
  }

  /**
   * BasePdfService 抽象メソッド互換
   */
  public function generate(array $clinicUserIds = [], string $serviceYearMonth = '', string $submissionDate = '', string $remarks = ''): string
  {
    return '';
  }

  /**
   * 計画情報を取得
   */
  protected function fetchPlans(int $clinicUserId): array
  {
    return DB::table('plans as p')
      ->leftJoin('assistance_levels as al_eating', 'al_eating.id', '=', 'p.eating_assistance_level_id')
      ->leftJoin('assistance_levels as al_moving', 'al_moving.id', '=', 'p.moving_assistance_level_id')
      ->leftJoin('assistance_levels as al_clothes', 'al_clothes.id', '=', 'p.changing_clothes_assistance_level_id')
      ->leftJoin('assistance_levels as al_urination', 'al_urination.id', '=', 'p.urination_assistance_level_id')
      ->where('p.clinic_user_id', $clinicUserId)
      ->orderBy('p.assessment_date', 'desc')
      ->select(
        'p.*',
        'al_eating.assistance_level as eating_level',
        'al_moving.assistance_level as moving_level',
        'al_clothes.assistance_level as clothes_level',
        'al_urination.assistance_level as urination_level'
      )
      ->get()
      ->toArray();
  }

  /**
   * ヘッダー描画
   * @return float テーブル開始Y座標
   */
  protected function renderHeader(Fpdi $pdf, $user, string $outputDate): float
  {
    $x = self::MARGIN_X;

    // PDF出力日時（右上）
    $ts      = strtotime($outputDate);
    $dateStr = '〈 PDF出力日時 │ ' . date('Y/m/d', $ts) . "\u{2002}" . date('H:i', $ts) . ' 〉';
    $pdf->SetFont('kozgopromedium', '', 7);
    $pdf->SetXY($x, 5);
    $pdf->Cell(self::AVAILABLE_W + 4, 0, $dateStr, 0, 0, 'R');

    // タイトル
    $titleY    = 13;
    $userSize  = 10;
    $pdf->SetFont('kozgopromedium', '', self::TITLE_SIZE);
    $pdf->Text($x, $titleY, '計画情報履歴一覧表');

    // 利用者名（右端・タイトル下端に揃える）
    $userLabelY = $titleY + (self::TITLE_SIZE - $userSize) * 0.352777;
    $userName   = ($user->last_name ?? '') . "\u{2002}" . ($user->first_name ?? '');
    $userLabel  = '利用者：' . $userName;
    $pdf->SetFont('kozgopromedium', '', $userSize);
    $userLabelW = $pdf->GetStringWidth($userLabel);
    $pdf->Text(self::MARGIN_X + self::AVAILABLE_W - $userLabelW, $userLabelY, $userLabel);

    return 30;
  }

  /**
   * テーブル描画
   */
  protected function renderTable(Fpdi $pdf, array $plans, float $startY): void
  {
    $x       = self::MARGIN_X;
    $headers = [
      'assessment_date'     => '評価日',
      'assessor'            => '評価者',
      'audience'            => '聴衆者',
      'adl_total'           => 'ADL合計',
      'eating'              => '摂食',
      'moving'              => '移動',
      'changing_clothes'    => '更衣',
      'urination'           => '排尿',
      'created_at'          => 'データ登録日',
    ];

    // フォントを指定してから列幅計算
    $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);

    // 列幅を動的に計算
    $colW = $this->calculateColumnWidths($pdf, $headers);

    // デバッグ: 列幅の合計確認
    $totalW = array_sum($colW);
    error_log("DEBUG: col widths sum = {$totalW}mm (urination = {$colW['urination']}mm)");

    // ヘッダー行
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetDrawColor(80, 80, 80);
    $pdf->SetLineWidth(0.2);
    $pdf->setCellPaddings(1, 0, 1, 0);
    $curX = $x;
    foreach ($headers as $key => $label) {
      $pdf->SetXY($curX, $startY);
      $pdf->Cell($colW[$key], self::HEADER_H, $label, 1, 0, 'C', true);
      $curX += $colW[$key];
    }

    // データ行
    $pdf->SetFillColor(255, 255, 255);
    $bottomLimit = 202;

    $currentY = $startY + self::HEADER_H;

    foreach ($plans as $plan) {
      $cells = [
        'assessment_date'     => $this->formatDate($plan->assessment_date),
        'assessor'            => $plan->assessor ?? '',
        'audience'            => $plan->audience ?? '',
        'adl_total'           => $plan->adl_total ?? '',
        'eating'              => $plan->eating_level ?? '',
        'moving'              => $plan->moving_level ?? '',
        'changing_clothes'    => $plan->clothes_level ?? '',
        'urination'           => $plan->urination_level ?? '',
        'created_at'          => $this->formatDate($plan->created_at),
      ];

      // ページ溢れチェック
      if ($currentY + self::ROW_H > $bottomLimit) {
        $pdf->AddPage();
        $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetDrawColor(80, 80, 80);
        $pdf->SetLineWidth(0.2);
        $pdf->setCellPaddings(1, 0, 1, 0);
        $newPageHeaderY = 8;
        $curX = $x;
        foreach ($headers as $key => $label) {
          $pdf->SetXY($curX, $newPageHeaderY);
          $pdf->Cell($colW[$key], self::HEADER_H, $label, 1, 0, 'C', true);
          $curX += $colW[$key];
        }
        $pdf->SetFillColor(255, 255, 255);
        $currentY = $newPageHeaderY + self::HEADER_H;
      }

      $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);
      $pdf->setCellPaddings(1, 0, 1, 0);
      $curX = $x;
      foreach ($cells as $key => $val) {
        $pdf->SetXY($curX, $currentY);
        $pdf->Cell($colW[$key], self::ROW_H, $val, 1, 0, 'C', false, '', 0, false, 'T', 'M');
        $curX += $colW[$key];
      }

      $currentY += self::ROW_H;
    }

    // データなし
    if (empty($plans)) {
      $totalW = array_sum($colW);
      $pdf->SetXY($x, $startY + self::HEADER_H);
      $pdf->SetTextColor(100, 100, 100);
      $pdf->Cell($totalW, self::ROW_H, 'データがありません', 1, 0, 'C');
      $pdf->SetTextColor(0, 0, 0);
    }
  }

  /**
   * 列幅を動的に計算
   */
  protected function calculateColumnWidths(Fpdi $pdf, array $headers): array
  {
    $pad         = 2.0;
    $minWidths   = [];
    $totalMinW   = 0;

    foreach ($headers as $key => $label) {
      $labelW = $pdf->GetStringWidth($label);

      $sampleData = self::COL_SAMPLE_DATA[$key] ?? [];
      if (is_array($sampleData) && !empty($sampleData)) {
        $dataW = max(array_map(fn($s) => $pdf->GetStringWidth($s), $sampleData));
        $minW  = ceil(max($labelW, $dataW) + $pad);
      } else {
        $minW = ceil($labelW + $pad);
      }

      $minWidths[$key] = $minW;
      $totalMinW       += $minW;
    }

    $diff = self::AVAILABLE_W - $totalMinW;

    if ($diff < 0) {
      $reduction           = min(-$diff, $minWidths['urination'] - 10);
      $minWidths['urination'] -= $reduction;
    } elseif ($diff > 0) {
      $minWidths['urination'] += $diff;
    }

    $finalSum = array_sum($minWidths);
    if (abs($finalSum - self::AVAILABLE_W) > 0.1) {
      error_log("WARNING: Column width sum ({$finalSum}mm) != AVAILABLE_W (" . self::AVAILABLE_W . "mm)");
    }

    return $minWidths;
  }

  /**
   * 日付フォーマット（Y/n/j）
   */
  protected function formatDate(?string $date): string
  {
    if (!$date) return '';
    try {
      return date('Y/n/j', strtotime($date));
    } catch (\Throwable $e) {
      return '';
    }
  }
}
