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
    $circleRadius = $this->coord('treatment_days', 'circleRadius') ?? 1.2;
    $innerRadius = $this->coord('treatment_days', 'doubleCircleInnerRadius') ?? 0.4;

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

    if ($alignmentWidth <= 0) {
      $alignmentWidth = $pdf->GetPageWidth();
    }

    if (empty($letterSpacing) && $textAlign === 'left') {
      $pdf->Text($x, $y, $text);
      return;
    }

    $this->drawTextWithSpacing($pdf, $x, $y, $text, (float)$letterSpacing, $textAlign, (float)$alignmentWidth);
  }

}