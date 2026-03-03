<?php

namespace App\Services\Print\Traits;

use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * はり・きゅう療養費支給申請書PDF - 描画ヘルパーメソッド
 */
trait AcupunctureDrawingHelpersTrait
{
  /**
   * 施術日をカレンダーに記入
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param mixed $records 施術記録
   * @return void
   */
  protected function fillServiceDates(Fpdi $pdf, $records): void
  {
    $letterSpacing = 0; // 追加間隔（現在は使用しない）
    $cellWidth = $this->coord('treatment_days', 'circleSpacing') ?? 6.45; // 円の間隔
    $circleRadius = $this->coord('treatment_days', 'circleRadius') ?? 1.8;
    $innerRadius = $this->coord('treatment_days', 'doubleCircleInnerRadius') ?? 2.5;

    // はり･きゅう版：therapy_content_id 11-16のみ描画
    $acupunctureContentIds = [11, 12, 13, 14, 15, 16];

    foreach ($records as $record) {
      // 施術内容がはり･きゅう関連でない場合はスキップ
      if (!in_array($record->therapy_content_id, $acupunctureContentIds)) {
        continue;
      }

      $day = (int)date('d', strtotime($record->date));

      $x = $this->coord('treatment_days', 'x') + ($day - 1) * ($cellWidth + $letterSpacing);
      $y = $this->coord('treatment_days', 'y');

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
   * 楕円を描画（ellipseX/ellipseY対応）
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param string $key 座標キー
   * @return void
   */
  protected function drawEllipseByKey(Fpdi $pdf, string $key): void
  {
    // キーが存在しない場合は何もしない
    if (!$this->hasCoord($key)) {
      return;
    }

    // ellipseX/ellipseY を優先、存在しない場合は x/y を使用
    $x = $this->coordinates[$key]['ellipseX'] ?? $this->coordinates[$key]['x'] ?? 0;
    $y = $this->coordinates[$key]['ellipseY'] ?? $this->coordinates[$key]['y'] ?? 0;
    $ellipseWidth = $this->coordinates[$key]['ellipseWidth'] ?? 2.5;
    $ellipseHeight = $this->coordinates[$key]['ellipseHeight'] ?? 2.5;
    $lineWidth = $this->coordinates[$key]['lineWidth'] ?? 0.4;

    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth($lineWidth);
    $pdf->Ellipse($x, $y, $ellipseWidth, $ellipseHeight, 0, 0, 360, 'D');
  }

  /**
   * デバッグ用グリッド表示
   *
   * @param Fpdi $pdf PDFオブジェクト
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
   * 座標キーに基づいて文字間隔設定を反映してテキストを描画する（主に既存コードの置換用）
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param string $key 座標キー
   * @param string $text 描画するテキスト
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
      $verticalAlign = $this->coordinates[$key]['verticalAlign'] ?? 'top';

      // verticalAlign: 'middle' の場合、yを中心点として上方向にオフセット
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
