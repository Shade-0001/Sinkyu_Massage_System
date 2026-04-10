<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 計画情報詳細一覧表 PDF生成サービス
 *
 * レイアウト概要：
 * - A4縦 (210mm × 297mm)、左右マージン 10mm → 利用可能幅 190mm
 * - 各計画情報を詳細ブロックで表示（ADL各項目+各種コメント項目）
 * - ページ区切りで複数計画に対応
 */
class PlanPrintHistoryPdfService extends BasePdfService
{
  const MARGIN_X    = 10;
  const MARGIN_Y    = 10;
  const AVAILABLE_W = 190;  // A4縦 210mm - 左右各10mm
  const FONT_SIZE   = 11;
  const TITLE_SIZE  = 16;
  const SECTION_H   = 5;   // セクション高さ
  const ITEM_H      = 5;   // 項目行高さ
  const BLOCK_MARGIN = 3;  // ブロック間マージン

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
   */
  public function generateHistory(int $clinicUserId): string
  {
    $user  = DB::table('clinic_users')->where('id', $clinicUserId)->first();
    $plans = $this->fetchPlans($clinicUserId);

    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetTextColor(0, 0, 0);

    $outputDate = date('Y-m-d H:i:s');
    $pdf->AddPage();

    $currentY = $this->renderHeader($pdf, $user, $outputDate);
    $this->renderDetailBlocks($pdf, $plans, $currentY);

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
   * 計画情報を全カラム取得
   */
  protected function fetchPlans(int $clinicUserId): array
  {
    return DB::table('plans as p')
      ->leftJoin('assistance_levels as al_eating', 'al_eating.id', '=', 'p.eating_assistance_level_id')
      ->leftJoin('assistance_levels as al_personal', 'al_personal.id', '=', 'p.personal_grooming_assistance_level_id')
      ->leftJoin('assistance_levels as al_toilet', 'al_toilet.id', '=', 'p.using_toilet_assistance_level_id')
      ->leftJoin('assistance_levels as al_bathing', 'al_bathing.id', '=', 'p.bathing_assistance_level_id')
      ->leftJoin('assistance_levels as al_walking', 'al_walking.id', '=', 'p.walking_assistance_level_id')
      ->leftJoin('assistance_levels as al_stairs', 'al_stairs.id', '=', 'p.using_stairs_assistance_level_id')
      ->leftJoin('assistance_levels as al_clothes', 'al_clothes.id', '=', 'p.changing_clothes_assistance_level_id')
      ->leftJoin('assistance_levels as al_defecation', 'al_defecation.id', '=', 'p.defecation_assistance_level_id')
      ->leftJoin('assistance_levels as al_urination', 'al_urination.id', '=', 'p.urination_assistance_level_id')
      ->leftJoin('assistance_levels as al_moving', 'al_moving.id', '=', 'p.moving_assistance_level_id')
      ->where('p.clinic_user_id', $clinicUserId)
      ->orderBy('p.assessment_date', 'desc')
      ->select(
        'p.*',
        'al_eating.assistance_level as eating_level',
        'al_personal.assistance_level as personal_grooming_level',
        'al_toilet.assistance_level as toilet_level',
        'al_bathing.assistance_level as bathing_level',
        'al_walking.assistance_level as walking_level',
        'al_stairs.assistance_level as stairs_level',
        'al_clothes.assistance_level as clothes_level',
        'al_defecation.assistance_level as defecation_level',
        'al_urination.assistance_level as urination_level',
        'al_moving.assistance_level as moving_level'
      )
      ->get()
      ->toArray();
  }

  /**
   * ヘッダー描画
   */
  protected function renderHeader(Fpdi $pdf, $user, string $outputDate): float
  {
    $x = self::MARGIN_X;
    $y = self::MARGIN_Y;

    // PDF出力日時
    $ts      = strtotime($outputDate);
    $dateStr = '〈 PDF出力日時 │ ' . date('Y/m/d', $ts) . "\u{2002}" . date('H:i', $ts) . ' 〉';
    $pdf->SetFont('kozgopromedium', '', 11);
    $pdf->SetXY($x, $y);
    $pdf->Cell(self::AVAILABLE_W, 0, $dateStr, 0, 0, 'R');

    // タイトル
    $titleY = $y + 8;
    $userSize = 10;
    $pdf->SetFont('kozgopromedium', '', self::TITLE_SIZE);
    $pdf->Text($x, $titleY, '計画情報詳細一覧表');

    // 利用者名（右端・タイトル下端に揃える）
    $userLabelY = $titleY + (self::TITLE_SIZE - $userSize) * 0.352777;
    $userName   = ($user->last_name ?? '') . "\u{2002}" . ($user->first_name ?? '');
    $userLabel  = '利用者：' . $userName;
    $pdf->SetFont('kozgopromedium', '', $userSize);
    $userLabelW = $pdf->GetStringWidth($userLabel);
    $pdf->Text(self::MARGIN_X + self::AVAILABLE_W - $userLabelW, $userLabelY, $userLabel);

    return $userLabelY + 8;
  }

  /**
   * 詳細ブロック群を描画
   */
  protected function renderDetailBlocks(Fpdi $pdf, array $plans, float $startY): void
  {
    if (empty($plans)) {
      $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);
      $pdf->SetXY(self::MARGIN_X, $startY + 5);
      $pdf->SetTextColor(100, 100, 100);
      $pdf->Cell(self::AVAILABLE_W, self::ITEM_H, 'データがありません');
      $pdf->SetTextColor(0, 0, 0);
      return;
    }

    $currentY = $startY;
    $bottomLimit = 280;  // A4縦297mm - 下マージン17mm

    foreach ($plans as $idx => $plan) {
      $blockH = $this->calculateBlockHeight($pdf, $plan);

      // ページ溢れチェック
      if ($currentY + $blockH > $bottomLimit) {
        $pdf->AddPage();
        $currentY = self::MARGIN_Y;
      }

      $currentY = $this->renderDetailBlock($pdf, $plan, $currentY);
      $currentY += self::BLOCK_MARGIN;  // ブロック間のマージン
    }
  }

  /**
   * 1つの計画情報ブロックのおおよその高さを計算
   */
  protected function calculateBlockHeight(Fpdi $pdf, $plan): float
  {
    // ざっくりした計算：セクションヘッダー + ADL10項目 + その他項目
    $h = 3;  // セクションヘッダー前の空白
    $h += 4;  // セクションヘッダー「評価基本情報」
    $h += self::ITEM_H * 3;  // 評価日、評価者、聴衆者
    $h += 4;  // セクションヘッダー「ADL評価」
    $h += self::ITEM_H * 10;  // ADL 10項目
    $h += 4;  // セクションヘッダー「その他の情報」
    $h += self::ITEM_H * 7;  // コミュニケーション、希望、治療目的、リハビリ、自宅リハビリ、改善、障害・注意、同意日

    return $h;
  }

  /**
   * 1つの計画情報ブロックを描画
   */
  protected function renderDetailBlock(Fpdi $pdf, $plan, float $startY): float
  {
    $x = self::MARGIN_X;
    $y = $startY + self::BLOCK_MARGIN;
    $colW = self::AVAILABLE_W;
    $labelW = 60;  // ラベル幅
    $valueW = $colW - $labelW;

    $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);
    $pdf->SetDrawColor(180, 180, 180);
    $pdf->SetLineWidth(0.1);

    // ▼ 評価基本情報セクション
    $y = $this->renderSection($pdf, $y, '評価基本情報');

    $items = [
      '評価日' => $this->formatDate($plan->assessment_date),
      '評価者' => $plan->assessor ?? '',
      '聴衆者' => $plan->audience ?? '',
    ];

    foreach ($items as $label => $value) {
      $y = $this->renderItem($pdf, $x, $y, $label, $value, $labelW, $valueW);
    }

    // ▼ ADL評価セクション
    $y = $this->renderSection($pdf, $y, 'ADL評価');

    $adlItems = [
      '摂食' => $plan->eating_level ?? '',
      '摂食備考' => $plan->eating_assistance_note ?? '',
      '起居移動' => $plan->moving_level ?? '',
      '起居移動備考' => $plan->moving_assistance_note ?? '',
      '整容' => $plan->personal_grooming_level ?? '',
      '整容備考' => $plan->personal_grooming_assistance_note ?? '',
      'トイレ' => $plan->toilet_level ?? '',
      'トイレ備考' => $plan->using_toilet_assistance_note ?? '',
      '入浴' => $plan->bathing_level ?? '',
      '入浴備考' => $plan->bathing_assistance_note ?? '',
      '平地歩行' => $plan->walking_level ?? '',
      '平地歩行備考' => $plan->walking_assistance_note ?? '',
      '階段昇降' => $plan->stairs_level ?? '',
      '階段昇降備考' => $plan->using_stairs_assistance_note ?? '',
      '更衣' => $plan->clothes_level ?? '',
      '更衣備考' => $plan->changing_clothes_assistance_note ?? '',
      '排便' => $plan->defecation_level ?? '',
      '排便備考' => $plan->defecation_assistance_note ?? '',
      '排尿' => $plan->urination_level ?? '',
      '排尿備考' => $plan->urination_assistance_note ?? '',
    ];

    foreach ($adlItems as $label => $value) {
      $y = $this->renderItem($pdf, $x, $y, $label, $value, $labelW, $valueW);
    }

    // ▼ その他の情報セクション
    $y = $this->renderSection($pdf, $y, 'その他の情報');

    $otherItems = [
      'コミュニケーション' => $plan->communication_note ?? '',
      'ご本人・ご家族の希望' => $plan->wish_of_user_and_familiy ?? '',
      '治療目的' => $plan->care_purpose ?? '',
      'リハビリテーションプログラム' => $plan->rehabilitation_program ?? '',
      '自宅でのリハビリテーション' => $plan->home_rehabilitation ?? '',
      '前回計画書作成時からの改善・変化' => $plan->change_since_previous_planning ?? '',
      '障害・注意事項' => $plan->note ?? '',
      '本人・家族同意日' => $this->formatDate($plan->user_and_family_consent_date),
    ];

    foreach ($otherItems as $label => $value) {
      $y = $this->renderItem($pdf, $x, $y, $label, $value, $labelW, $valueW);
    }

    // ブロック下部に区切り線
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.2);
    $pdf->Line($x, $y + 2, $x + $colW, $y + 2);

    return $y;
  }

  /**
   * セクションヘッダーを描画
   */
  protected function renderSection(Fpdi $pdf, float $y, string $title): float
  {
    $x = self::MARGIN_X;
    $colW = self::AVAILABLE_W;

    $pdf->SetFont('kozgopromedium', 'B', 11);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetDrawColor(150, 150, 150);
    $pdf->SetLineWidth(0.1);

    $pdf->SetXY($x, $y);
    $pdf->Cell($colW, 4, $title, 1, 0, 'L', true);

    return $y + 5;
  }

  /**
   * ラベル値ペアを描画
   */
  protected function renderItem(Fpdi $pdf, float $x, float $y, string $label, string $value, float $labelW, float $valueW): float
  {
    $pdf->SetFont('kozgopromedium', '', 10);
    $pdf->SetDrawColor(220, 220, 220);
    $pdf->SetLineWidth(0.05);

    // ラベルセル
    $pdf->SetXY($x, $y);
    $pdf->Cell($labelW, self::ITEM_H, $label, 1, 0, 'L');

    // 値セル
    $pdf->SetXY($x + $labelW, $y);
    $pdf->Cell($valueW, self::ITEM_H, $value, 1, 0, 'L');

    return $y + self::ITEM_H;
  }

  /**
   * 日付フォーマット
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
