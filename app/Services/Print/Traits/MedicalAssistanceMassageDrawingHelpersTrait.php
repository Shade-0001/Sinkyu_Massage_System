<?php

namespace App\Services\Print\Traits;

use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * あんま・マッサージ医療助成費支給申請書PDF - 描画ヘルパーメソッド
 */
trait MedicalAssistanceMassageDrawingHelpersTrait
{
  protected function fillServiceDates(Fpdi $pdf, $records): void
  {
    $letterSpacing = 0; // 追加間隔（現在は使用しない）
    $cellWidth = $this->coord('treatment_days', 'circleSpacing') ?? 6.45; // 円の間隔
    $circleRadius = $this->coord('treatment_days', 'circleRadius') ?? 1.8;
    $innerRadius = $this->coord('treatment_days', 'doubleCircleInnerRadius') ?? 2.5;

    // サンプルデータモード時の処理
    if ($this->sampleDataMode && isset($this->customSampleData['treatment_days_array'])) {
      $treatmentDaysArray = $this->customSampleData['treatment_days_array'];

      foreach ($treatmentDaysArray as $day) {
        $x = $this->coord('treatment_days', 'x') + ($day - 1) * ($cellWidth + $letterSpacing);
        $y = $this->coord('treatment_days', 'y');

        // サンプルモードでは通院（単純な円）を描画
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->Ellipse($x, $y, $circleRadius, $circleRadius, 0, 0, 360, 'D');
      }

      return;
    }

    // あんま･マッサージ版：therapy_content_id 18-21のみ描画
    $massageContentIds = [18, 19, 20, 21];

    foreach ($records as $record) {
      // 施術内容があんま･マッサージ関連でない場合はスキップ
      if (!in_array($record->therapy_content_id, $massageContentIds)) {
        continue;
      }

      $day = (int)date('d', strtotime($record->date));

      $x = $this->coord('treatment_days', 'x') + ($day - 1) * ($cellWidth + $letterSpacing);
      $y = $this->coord('treatment_days', 'y');

      if ($record->therapy_category == 2) {
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->Ellipse($x, $y, $circleRadius, $circleRadius, 0, 0, 360, 'D');
        $pdf->Ellipse($x, $y, $innerRadius, $innerRadius, 0, 0, 360, 'D');
      } else {
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->Ellipse($x, $y, $circleRadius, $circleRadius, 0, 0, 360, 'D');
      }
    }
  }

  protected function drawTextByKey(Fpdi $pdf, string $key, string $text): void
  {
    // キーが存在しない場合は何もしない
    if (!$this->hasCoord($key)) {
      return;
    }

    $x = $this->coord($key, 'x');
    $y = $this->coord($key, 'y');

    // デバッグ：座標0,0付近の描画を検出
    if ($x < 5 && $y < 5) {
      \Log::warning("座標0,0付近の描画検出", ['key' => $key, 'x' => $x, 'y' => $y, 'text' => $text]);
    }

    // デバッグ：描画成功ログ
    \Log::info("テキスト描画", [
      'key' => $key,
      'x' => $x,
      'y' => $y,
      'text' => mb_strlen($text) > 50 ? mb_substr($text, 0, 50) . '...' : $text,
      'length' => mb_strlen($text)
    ]);

    $letterSpacing = $this->coordinates[$key]['letterSpacing'] ?? 0;
    $textAlign = $this->coordinates[$key]['textAlign'] ?? 'left';
    $alignmentWidth = $this->coordinates[$key]['alignmentWidth'] ?? 0;
    $maxCharsPerLine = $this->coordinates[$key]['maxCharsPerLine'] ?? null;

    // alignmentWidth が指定されていない場合はPDFのページ幅を使用
    if ($alignmentWidth <= 0) {
      $alignmentWidth = $pdf->GetPageWidth();
    }

    // maxCharsPerLineが設定されている場合は折り返し処理
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

      // 各行を描画
      $lineHeight = $this->coordinates[$key]['lineHeight'] ?? 5;
      foreach ($lines as $i => $line) {
        $currentY = $y + ($i * $lineHeight);

        if ($letterSpacing > 0) {
          $this->drawTextWithSpacing($pdf, $x, $currentY, $line, (float)$letterSpacing, $textAlign, (float)$alignmentWidth);
        } else {
          // textAlignに応じてX座標を調整
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
      return;
    }

    if (empty($letterSpacing) && $textAlign === 'left') {
      $pdf->Text($x, $y, $text);
      return;
    }

    $this->drawTextWithSpacing($pdf, $x, $y, $text, (float)$letterSpacing, $textAlign, (float)$alignmentWidth);
  }

}