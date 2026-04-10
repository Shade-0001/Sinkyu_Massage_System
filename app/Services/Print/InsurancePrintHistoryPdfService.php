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
    'insurance_type'    => 20,
    'insured_number'    => 24,
    'license_date'      => 18,
    'certification_date'=> 18,
    'issue_date'        => 18,
    'copayment'         => 13,
    'expiry_date'       => 18,
    'household_name'    => 16,  // -4
    'insured_name'      => 16,  // -4
    'subsidized'        => 10,  // -2
    'public_payer'      => 22,
    'public_recipient'  => 22,
    'insurer_number'    => 24,
    'insurer_name'      => 32,  // +10（はみ出し対策）
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
    $currentY    = $startY + self::HEADER_H;
    $pageAdded   = false;

    foreach ($insurances as $idx => $ins) {
      // ページ溢れチェック
      if ($currentY + self::ROW_H > $bottomLimit) {
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
        $currentY  = self::MARGIN_X + self::HEADER_H;
        $pageAdded = true;
      }

      $isLatest = ($idx === 0);

      // 状態列の色
      if ($isLatest) {
        $pdf->SetTextColor(180, 0, 0);
      } else {
        $pdf->SetTextColor(100, 100, 100);
      }

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
      $relMap   = [1 => '本人', 2 => '家族'];
      $rel      = $relMap[$ins->relationship_with_clinic_user_id] ?? '';

      $householdName = ($rel !== '本人') ? ($ins->insured_name ?? '') : '';
      $insuredName   = ($rel === '本人')  ? ($ins->insured_name ?? '') : '';

      $curX = $x;
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
        'insurer_name'       => $ins->insurer_name ?? '',
      ];

      $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);
      foreach ($cells as $key => $val) {
        $align = in_array($key, ['status', 'copayment', 'subsidized']) ? 'C' : 'L';
        $pdf->SetXY($curX, $currentY);
        $pdf->Cell($colW[$key], self::ROW_H, $val, 1, 0, $align);
        $curX += $colW[$key];
      }

      $pdf->SetTextColor(0, 0, 0);
      $currentY += self::ROW_H;
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
