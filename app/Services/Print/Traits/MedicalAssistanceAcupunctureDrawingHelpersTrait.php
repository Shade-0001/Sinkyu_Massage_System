<?php

namespace App\Services\Print\Traits;

use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * はり・きゅう医療助成費支給申請書PDF - 描画ヘルパーメソッド
 */
trait MedicalAssistanceAcupunctureDrawingHelpersTrait
{
  protected function fillServiceDates(Fpdi $pdf, $records): void
  {
    // デフォルト値（個別フィールドが存在しない場合のフォールバック）
    $defaultCellWidth = $this->coord('treatment_days', 'circleSpacing') ?? 6.45;
    $defaultCircleRadius = $this->coord('treatment_days', 'circleRadius') ?? 1.8;
    $defaultInnerRadius = $this->coord('treatment_days', 'doubleCircleInnerRadius') ?? 2.5;
    $defaultX = $this->coord('treatment_days', 'x');
    $defaultY = $this->coord('treatment_days', 'y');

    // サンプルデータモード時の処理
    \Log::info('[fillServiceDates] デバッグ', [
      'sampleDataMode' => $this->sampleDataMode,
      'treatment_days_array_exists' => isset($this->customSampleData['treatment_days_array']),
      'treatment_days_array' => $this->customSampleData['treatment_days_array'] ?? null,
      'customSampleData_keys' => array_keys($this->customSampleData ?? [])
    ]);

    if ($this->sampleDataMode && isset($this->customSampleData['treatment_days_array'])) {
      $treatmentDaysArray = $this->customSampleData['treatment_days_array'];

      \Log::info('[fillServiceDates] サンプルモード楕円描画開始', [
        'treatmentDaysArray' => $treatmentDaysArray
      ]);

      foreach ($treatmentDaysArray as $day) {
        $fieldKey = "treatment_days_{$day}";

        // 個別フィールドが存在する場合はそれを使用、なければデフォルト座標から計算
        if ($this->hasCoord($fieldKey)) {
          $x = $this->coord($fieldKey, 'x');
          $y = $this->coord($fieldKey, 'y');
          $circleRadius = $this->coord($fieldKey, 'circleRadius') ?? $defaultCircleRadius;
          $innerRadius = $this->coord($fieldKey, 'doubleCircleInnerRadius') ?? $defaultInnerRadius;
        } else {
          // フォールバック: デフォルト座標から計算
          $x = $defaultX + ($day - 1) * $defaultCellWidth;
          $y = $defaultY;
          $circleRadius = $defaultCircleRadius;
          $innerRadius = $defaultInnerRadius;
        }

        // サンプルモードでは通院（単純な円）を描画
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->Ellipse($x, $y, $circleRadius, $circleRadius, 0, 0, 360, 'D');
      }

      return;
    }

    // はり･きゅう版：therapy_content_id 11-16のみ描画
    $acupunctureContentIds = [11, 12, 13, 14, 15, 16];

    foreach ($records as $record) {
      // 施術内容がはり･きゅう関連でない場合はスキップ
      if (!in_array($record->therapy_content_id, $acupunctureContentIds)) {
        continue;
      }

      $day = (int)date('d', strtotime($record->date));
      $fieldKey = "treatment_days_{$day}";

      // 個別フィールドが存在する場合はそれを使用、なければデフォルト座標から計算
      if ($this->hasCoord($fieldKey)) {
        $x = $this->coord($fieldKey, 'x');
        $y = $this->coord($fieldKey, 'y');
        $circleRadius = $this->coord($fieldKey, 'circleRadius') ?? $defaultCircleRadius;
        $innerRadius = $this->coord($fieldKey, 'doubleCircleInnerRadius') ?? $defaultInnerRadius;
      } else {
        // フォールバック: 古い計算方式
        $x = $defaultX + ($day - 1) * $defaultCellWidth;
        $y = $defaultY;
        $circleRadius = $defaultCircleRadius;
        $innerRadius = $defaultInnerRadius;
      }

      // therapy_category: 1=通院（○）、2=往療（◎）
      if ($record->therapy_category == 2) {
        // 往療: 外側と内側の2つの円を描画
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        // 外側の円
        $pdf->Ellipse($x, $y, $circleRadius, $circleRadius, 0, 0, 360, 'D');
        // 内側の円
        $pdf->Ellipse($x, $y, $innerRadius, $innerRadius, 0, 0, 360, 'D');
      } else {
        // 通院: 単純な円
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->Ellipse($x, $y, $circleRadius, $circleRadius, 0, 0, 360, 'D');
      }
    }
  }

  /**
   * デバッグ用グリッド表示
   *
   * @param Fpdi $pdf
   * @return void
   */
  protected function drawDebugGrid(Fpdi $pdf): void
  {
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.1);

    // 縦線（10mm間隔）
    for ($x = 0; $x <= 210; $x += 10) {
      $pdf->Line($x, 0, $x, 297);
      $pdf->SetFontSize(6);
      $pdf->SetTextColor(150, 150, 150);
      $pdf->Text($x + 0.5, 5, (string)$x);
    }

    // 横線（10mm間隔）
    for ($y = 0; $y <= 297; $y += 10) {
      $pdf->Line(0, $y, 210, $y);
      $pdf->SetFontSize(6);
      $pdf->SetTextColor(150, 150, 150);
      $pdf->Text(2, $y + 3, (string)$y);
    }

    // テキスト色を戻す
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFontSize(10);
  }

  /**
   * 文字間隔を考慮したテキスト描画（内部ユーティリティ）
   *
   * @param Fpdi $pdf
   * @param float $startX
   * @param float $y
   * @param string $text
   * @param float $letterSpacing 追加の文字間隔（mm）
   * @return void
   */
  /**
   * 座標キーに基づいて文字間隔設定を反映してテキストを描画する（主に既存コードの置換用）
   *
   * @param Fpdi $pdf
   * @param string $key
   * @param string $text
   * @return void
   */
  protected function drawTextByKey(Fpdi $pdf, string $key, string $text): void
  {
    // キーが存在しない場合は何もしない
    if (!$this->hasCoord($key)) {
      \Log::warning("描画スキップ: キーが存在しない", ['key' => $key, 'text' => $text]);
      return;
    }

    $x = $this->coord($key, 'x');
    $y = $this->coord($key, 'y');

    // デバッグ：座標0,0付近の描画を検出
    if ($x < 5 && $y < 5) {
      \Log::warning("座標0,0付近の描画検出", ['key' => $key, 'x' => $x, 'y' => $y, 'text' => $text]);
    }

    $letterSpacing = $this->coordinates[$key]['letterSpacing'] ?? 0;
    $textAlign = $this->coordinates[$key]['textAlign'] ?? 'left';
    $alignmentWidth = $this->coordinates[$key]['alignmentWidth'] ?? 0;
    $maxCharsPerLine = $this->coordinates[$key]['maxCharsPerLine'] ?? null;
    $verticalAlign = $this->coordinates[$key]['verticalAlign'] ?? 'top';

    // alignmentWidth が指定されていない場合はPDFのページ幅を使用
    if ($alignmentWidth <= 0) {
      $alignmentWidth = $pdf->GetPageWidth();
    }

    $lineHeight = $this->coordinates[$key]['lineHeight'] ?? 5;

    // 折り返し処理（maxCharsPerLine超過時）、それ以外は1要素の配列として統一
    if ($maxCharsPerLine && mb_strlen($text) > $maxCharsPerLine) {
      $lines = [];
      $currentLine = '';
      $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

      foreach ($chars as $char) {
        if (mb_strlen($currentLine) >= $maxCharsPerLine) {
          $lines[] = $currentLine;
          $currentLine = $char;
        } else {
          $currentLine .= $char;
        }
      }
      if ($currentLine !== '') {
        $lines[] = $currentLine;
      }
    } else {
      $lines = [$text];
    }

    // verticalAlign: 'middle' の場合、yを中心点として上方向にオフセット（1行・複数行共通）
    $startY = $y;
    if ($verticalAlign === 'middle') {
      $totalHeight = count($lines) * $lineHeight;
      $startY = $y - ($totalHeight / 2);
    }

    foreach ($lines as $i => $line) {
      $currentY = $startY + ($i * $lineHeight);

      if ($letterSpacing > 0) {
        $this->drawTextWithSpacing($pdf, $x, $currentY, $line, (float)$letterSpacing, $textAlign, (float)$alignmentWidth);
      } else {
        $currentX = $x;
        if ($textAlign === 'center') {
          $textWidth = $pdf->GetStringWidth($line);
          $currentX = $x - ($textWidth / 2);
        } elseif ($textAlign === 'right') {
          $textWidth = $pdf->GetStringWidth($line);
          $currentX = $x - $textWidth;
        }
        $pdf->Text($currentX, $currentY, $line);
      }
    }
  }

}