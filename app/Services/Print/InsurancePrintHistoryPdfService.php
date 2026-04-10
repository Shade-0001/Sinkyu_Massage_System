<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 医療保険情報履歴一覧表 PDF生成サービス
 *
 * レイアウト概要：
 * - A4横 (297mm × 210mm)、左右マージン 8mm → 利用可能幅 281mm
 * - ヘッダー：タイトル・利用者名・PDF出力日時
 * - テーブル：状態 / 保険区分 / 被保険者番号 / 資格取得日 / 認定日 / 発行日 / 一部負担 / 有効期限 / 世帯主 / 被保険者 / 助成 / 公費負担番号 / 公費受給番号 / 保険者番号 / 保険者名
 */
class InsurancePrintHistoryPdfService extends BasePdfService
{
  const MARGIN_X    = 8;
  const AVAILABLE_W = 281;  // A4横 297mm - 左右各8mm
  const ROW_H       = 6;
  const HEADER_H    = 7;
  const FONT_SIZE   = 8;
  const TITLE_SIZE  = 14;

  // カラム幅（合計 281mm）
  const COL_WIDTHS = [
    'status'            => 10,
    'insurance_type'    => 21,
    'insured_number'    => 21,
    'license_date'      => 18,
    'certification_date'=> 13,
    'issue_date'        => 13,
    'copayment'         => 16,
    'expiry_date'       => 16,
    'household_name'    => 18,
    'insured_name'      => 21,
    'subsidized'        => 16,
    'public_payer'      => 21,
    'public_recipient'  => 21,
    'insurer_number'    => 18,
    'insurer_name'      => 38,
  ];

  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/insurance_print_history_coordinates.json');
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
    $user       = DB::table('clinic_users')->where('id', $clinicUserId)->first();
    $insurances = $this->fetchInsurances($clinicUserId);

    $pdf = new Fpdi('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetTextColor(0, 0, 0);

    $outputDate = date('Y-m-d H:i:s');

    $pdf->AddPage();
    $currentY = $this->renderHeader($pdf, $user, $outputDate);
    $this->renderTable($pdf, $insurances, $currentY);

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
   * 保険情報を取得
   */
  protected function fetchInsurances(int $clinicUserId): array
  {
    return DB::table('insurances as i')
      ->leftJoin('insurers as ins', 'ins.id', '=', 'i.insurers_id')
      ->leftJoin('expenses_borne_ratios as ebr', 'ebr.id', '=', 'i.expenses_borne_ratio_id')
      ->where('i.clinic_user_id', $clinicUserId)
      ->orderBy('i.id', 'desc')
      ->select(
        'i.*',
        'ins.insurer_number',
        'ins.insurer_name',
        'ebr.expenses_borne_ratio'
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
    $titleY = 13;
    $pdf->SetFont('kozgopromedium', '', self::TITLE_SIZE);
    $pdf->Text($x, $titleY, '医療保険情報履歴一覧表');

    // 利用者名（タイトル右端・同一基線Y）
    $userName  = ($user->last_name ?? '') . "\u{2002}" . ($user->first_name ?? '');
    $userLabel = '利用者：' . $userName;
    $pdf->SetFont('kozgopromedium', '', 10);
    $userLabelW = $pdf->GetStringWidth($userLabel);
    $pdf->Text(self::MARGIN_X + self::AVAILABLE_W - $userLabelW, $titleY, $userLabel);

    return 30;
  }

  /**
   * テーブル描画
   */
  protected function renderTable(Fpdi $pdf, array $insurances, float $startY): void
  {
    $x       = self::MARGIN_X;
    $colW    = self::COL_WIDTHS;
    $headers = [
      'status'             => '状態',
      'insurance_type'     => '保険区分',
      'insured_number'     => '被保険者番号',
      'license_date'       => '資格取得日',
      'certification_date' => '認定日',
      'issue_date'         => '発行日',
      'copayment'          => '一部負担',
      'expiry_date'        => '有効期限',
      'household_name'     => '世帯主氏名',
      'insured_name'       => '被保険者氏名',
      'subsidized'         => '助成対象',
      'public_payer'       => '公費負担番号',
      'public_recipient'   => '公費受給番号',
      'insurer_number'     => '保険者番号',
      'insurer_name'       => '保険者名',
    ];

    // ヘッダー行
    $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetDrawColor(80, 80, 80);
    $pdf->SetLineWidth(0.2);
    $curX = $x;
    foreach ($headers as $key => $label) {
      $pdf->SetXY($curX, $startY);
      $pdf->Cell($colW[$key], self::HEADER_H, $label, 1, 0, 'C', true);
      $curX += $colW[$key];
    }

    // データ行
    $pdf->SetFillColor(255, 255, 255);
    $bottomLimit = 202;  // A4横210mm - 下マージン8mm

    $currentY = $startY + self::HEADER_H;

    foreach ($insurances as $idx => $ins) {
      $isLatest = ($idx === 0);

      // 保険区分（insurer_number の先頭2桁で判定）
      $insurerNumber = $ins->insurer_number ?? '';
      $prefix        = (int) substr($insurerNumber, 0, 2);
      $typeLabel     = '保険';
      if ($prefix === 6)                            $typeLabel = '協会けんぽ';
      elseif ($prefix >= 13 && $prefix <= 19)       $typeLabel = '組合健保';
      elseif ($prefix >= 31 && $prefix <= 34)       $typeLabel = '国保';
      elseif ($prefix === 39)                        $typeLabel = '後期高齢者';
      elseif ($prefix === 67)                        $typeLabel = '国保組合';
      elseif ($prefix >= 72 && $prefix <= 75)       $typeLabel = '共済組合';
      elseif ($prefix === 2)                         $typeLabel = '船員保険';

      // 一部負担割合
      $copayMap = [1 => '1割', 2 => '2割', 3 => '3割'];
      $copay    = $copayMap[$ins->expenses_borne_ratio_id] ?? '';

      // 続柄
      $relMap        = [1 => '本人', 2 => '家族'];
      $rel           = $relMap[$ins->relationship_with_clinic_user_id] ?? '';
      $householdName = ($rel !== '本人') ? ($ins->insured_name ?? '') : '';
      $insuredName   = ($rel === '本人')  ? ($ins->insured_name ?? '') : '';

      $insurerName = $ins->insurer_name ?? '';

      // insurer_name が1行に収まるか判定して行高を決定
      $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);
      $nameW    = $pdf->GetStringWidth($insurerName);
      $innerW   = $colW['insurer_name'] - 1.6 * 2;
      $nameRows = ($nameW > $innerW) ? 2 : 1;
      $rowH     = self::ROW_H * $nameRows;

      // ページ溢れチェック
      if ($currentY + $rowH > $bottomLimit) {
        $pdf->AddPage();
        $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);
        $pdf->SetFillColor(230, 230, 230);
        $curX = $x;
        foreach ($headers as $key => $label) {
          $pdf->SetXY($curX, self::MARGIN_X);
          $pdf->Cell($colW[$key], self::HEADER_H, $label, 1, 0, 'C', true);
          $curX += $colW[$key];
        }
        $pdf->SetFillColor(255, 255, 255);
        $currentY = self::MARGIN_X + self::HEADER_H;
      }

      // 状態列の色
      if ($isLatest) {
        $pdf->SetTextColor(180, 0, 0);
      } else {
        $pdf->SetTextColor(100, 100, 100);
      }

      $cells = [
        'status'             => $isLatest ? '最新' : '履歴',
        'insurance_type'     => $typeLabel,
        'insured_number'     => $ins->insured_number ?? '',
        'license_date'       => $this->formatDate($ins->license_acquisition_date),
        'certification_date' => $this->formatDate($ins->certification_date),
        'issue_date'         => $this->formatDate($ins->issue_date),
        'copayment'          => $copay,
        'expiry_date'        => $this->formatDate($ins->expiry_date),
        'household_name'     => $householdName,
        'insured_name'       => $insuredName,
        'subsidized'         => $ins->is_healthcare_subsidized ? '対象' : '非対象',
        'public_payer'       => $ins->locality_code ?? ($ins->public_funds_payer_code ?? ''),
        'public_recipient'   => $ins->recipient_code ?? ($ins->public_funds_recipient_code ?? ''),
        'insurer_number'     => $insurerNumber,
      ];

      $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);
      $curX = $x;
      foreach ($cells as $key => $val) {
        $pdf->SetXY($curX, $currentY);
        $pdf->Cell($colW[$key], $rowH, $val, 1, 0, 'C', false, '', 0, false, 'T', 'M');
        $curX += $colW[$key];
      }

      // insurer_name: 枠を rowH で描いてからテキストを手動で折り返し配置
      $nameW  = $colW['insurer_name'];
      $innerW = $nameW - 1.6 * 2;
      $pdf->SetXY($curX, $currentY);
      $pdf->Cell($nameW, $rowH, '', 1, 0, 'C');  // 枠のみ
      if ($nameRows === 1) {
        // 1行：垂直中央
        $textY = $currentY + ($rowH - self::FONT_SIZE * 0.352777) / 2;
        $pdf->SetXY($curX + 1.6, $textY);
        $pdf->Cell($innerW, self::FONT_SIZE * 0.352777, $insurerName, 0, 0, 'C');
      } else {
        // 2行：上半分・下半分それぞれ垂直中央
        $lineH = $rowH / 2;
        $fh    = self::FONT_SIZE * 0.352777;
        // 1行目
        $line1 = $this->truncateToFit($pdf, $insurerName, $innerW);
        $rest  = mb_substr($insurerName, mb_strlen($line1));
        $pdf->SetXY($curX + 1.6, $currentY + ($lineH - $fh) / 2);
        $pdf->Cell($innerW, $fh, $line1, 0, 0, 'C');
        // 2行目
        $pdf->SetXY($curX + 1.6, $currentY + $lineH + ($lineH - $fh) / 2);
        $pdf->Cell($innerW, $fh, $rest, 0, 0, 'C');
      }

      $pdf->SetTextColor(0, 0, 0);
      $currentY += $rowH;
    }

    // データなし
    if (empty($insurances)) {
      $totalW = array_sum($colW);
      $pdf->SetXY($x, $startY + self::HEADER_H);
      $pdf->SetTextColor(100, 100, 100);
      $pdf->Cell($totalW, self::ROW_H, 'データがありません', 1, 0, 'C');
      $pdf->SetTextColor(0, 0, 0);
    }
  }

  /**
   * 指定幅に収まる最大の文字列を返す（折り返し1行目用）
   */
  protected function truncateToFit(Fpdi $pdf, string $text, float $maxW): string
  {
    $len = mb_strlen($text);
    for ($i = $len; $i > 0; $i--) {
      $sub = mb_substr($text, 0, $i);
      if ($pdf->GetStringWidth($sub) <= $maxW) {
        return $sub;
      }
    }
    return '';
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
