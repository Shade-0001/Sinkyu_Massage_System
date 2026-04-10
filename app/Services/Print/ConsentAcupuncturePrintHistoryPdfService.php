<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 同意医師履歴一覧表（はり・きゅう）PDF生成サービス
 *
 * レイアウト概要：
 * - A4横 (297mm × 210mm)、左右マージン 8mm → 利用可能幅 281mm
 * - ヘッダー：タイトル（左）・利用者名（右、タイトル同一基線）・PDF出力日時（右上）
 * - テーブル：状態 / 同意医師 / 同意日 / 同意開始日 / 同意終了日 / 給付期間開始 / 給付期間終了 / 初療日 / 再同意期限 / 登録日
 */
class ConsentAcupuncturePrintHistoryPdfService extends BasePdfService
{
  const MARGIN_X    = 8;
  const AVAILABLE_W = 281;  // A4横 297mm - 左右各8mm
  const ROW_H       = 6;
  const HEADER_H    = 7;
  const FONT_SIZE   = 8;
  const TITLE_SIZE  = 14;

  // カラム幅（合計 281mm）
  const COL_WIDTHS = [
    'status'              => 12,
    'doctor'              => 54,
    'consenting_date'     => 28,
    'start_date'          => 28,
    'end_date'            => 28,
    'benefit_start'       => 28,
    'benefit_end'         => 28,
    'first_care'          => 28,
    'reconsenting_expiry' => 28,
    'registered_at'       => 19,
  ];

  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/consent_acupuncture_print_history_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  /**
   * PDF生成
   */
  public function generateHistory(int $clinicUserId): string
  {
    $user      = DB::table('clinic_users')->where('id', $clinicUserId)->first();
    $histories = $this->fetchHistories($clinicUserId);

    $pdf = new Fpdi('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetTextColor(0, 0, 0);

    $outputDate = date('Y-m-d H:i:s');

    $pdf->AddPage();
    $currentY = $this->renderHeader($pdf, $user, $outputDate);
    $this->renderTable($pdf, $histories, $currentY);

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
   * 履歴データを取得
   */
  protected function fetchHistories(int $clinicUserId): array
  {
    return DB::table('consents_acupuncture as ca')
      ->leftJoin('doctors as d', 'd.id', '=', 'ca.consenting_doctor_id')
      ->where('ca.clinic_user_id', $clinicUserId)
      ->orderBy('ca.id', 'desc')
      ->select(
        'ca.id',
        'ca.consenting_date',
        'ca.consenting_start_date',
        'ca.consenting_end_date',
        'ca.benefit_period_start_date',
        'ca.benefit_period_end_date',
        'ca.first_care_date',
        'ca.reconsenting_expiry',
        'ca.created_at',
        'd.last_name as doc_last',
        'd.first_name as doc_first'
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

    // タイトル（左）
    $titleY = 13;
    $pdf->SetFont('kozgopromedium', '', self::TITLE_SIZE);
    $pdf->Text($x, $titleY, '同意医師履歴一覧表（はり・きゅう）');

    // 利用者名（右端・タイトル同一基線Y）
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
  protected function renderTable(Fpdi $pdf, array $histories, float $startY): void
  {
    $x       = self::MARGIN_X;
    $colW    = self::COL_WIDTHS;
    $headers = [
      'status'              => '状態',
      'doctor'              => '同意医師名',
      'consenting_date'     => '同意日',
      'start_date'          => '同意開始日',
      'end_date'            => '同意終了日',
      'benefit_start'       => '給付期間開始',
      'benefit_end'         => '給付期間終了',
      'first_care'          => '初療日',
      'reconsenting_expiry' => '再同意期限',
      'registered_at'       => '登録日',
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
    $bottomLimit = 202;  // A4横 210mm - 下マージン8mm
    $currentY    = $startY + self::HEADER_H;

    foreach ($histories as $idx => $h) {
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
        $currentY = self::MARGIN_X + self::HEADER_H;
      }

      $isLatest = ($idx === 0);

      if ($isLatest) {
        $pdf->SetTextColor(180, 0, 0);
      } else {
        $pdf->SetTextColor(100, 100, 100);
      }

      $docName = trim(($h->doc_last ?? '') . "\u{2002}" . ($h->doc_first ?? ''));
      if ($docName === "\u{2002}") $docName = '未設定';

      $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);
      $curX = $x;
      $cells = [
        'status'              => $isLatest ? '最新' : '履歴',
        'doctor'              => $docName ?: '未設定',
        'consenting_date'     => $this->formatDate($h->consenting_date),
        'start_date'          => $this->formatDate($h->consenting_start_date),
        'end_date'            => $this->formatDate($h->consenting_end_date),
        'benefit_start'       => $this->formatDate($h->benefit_period_start_date),
        'benefit_end'         => $this->formatDate($h->benefit_period_end_date),
        'first_care'          => $this->formatDate($h->first_care_date),
        'reconsenting_expiry' => $this->formatDate($h->reconsenting_expiry),
        'registered_at'       => $this->formatDate($h->created_at),
      ];

      foreach ($cells as $key => $val) {
        $align = ($key === 'status') ? 'C' : 'L';
        $pdf->SetXY($curX, $currentY);
        $pdf->Cell($colW[$key], self::ROW_H, $val, 1, 0, $align);
        $curX += $colW[$key];
      }

      $pdf->SetTextColor(0, 0, 0);
      $currentY += self::ROW_H;
    }

    // データなし
    if (empty($histories)) {
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
