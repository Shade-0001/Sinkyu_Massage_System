<?php

namespace App\Services\Print\Traits;

use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * あんま・マッサージ療養費支給申請書PDF - 描画ヘルパーメソッド
 */
trait MassageDrawingHelpersTrait
{
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
  /**
   * 楕円をキーで描画
   *
   * @param Fpdi $pdf
   * @param string $key
   * @return void
   */
  protected function drawEllipseByKey(Fpdi $pdf, string $key): void
  {
    // キーが存在しない場合は何もしない
    if (!$this->hasCoord($key)) {
      \Log::warning("楕円描画スキップ：座標なし", ['key' => $key]);
      return;
    }

    // ellipseX/ellipseY を優先、存在しない場合は x/y を使用
    $x = $this->coordinates[$key]['ellipseX'] ?? $this->coordinates[$key]['x'] ?? 0;
    $y = $this->coordinates[$key]['ellipseY'] ?? $this->coordinates[$key]['y'] ?? 0;
    $ellipseWidth = $this->coordinates[$key]['ellipseWidth'] ?? 2.5;
    $ellipseHeight = $this->coordinates[$key]['ellipseHeight'] ?? 2.5;
    $lineWidth = $this->coordinates[$key]['lineWidth'] ?? 0.5;

    // デバッグ：楕円描画成功ログ
    \Log::info("楕円描画実行", [
      'key' => $key,
      'x' => $x,
      'y' => $y,
      'width' => $ellipseWidth,
      'height' => $ellipseHeight,
      'coordinates' => $this->coordinates[$key] ?? 'なし'
    ]);

    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth($lineWidth);
    $pdf->Ellipse($x, $y, $ellipseWidth, $ellipseHeight, 0, 0, 360, 'D');
  }
  protected function drawTextWithSpacing(Fpdi $pdf, float $startX, float $y, string $text, float $letterSpacing, string $textAlign = 'left', float $alignmentWidth = 0): void
  {
    $chars = preg_split('//u', (string)$text, -1, PREG_SPLIT_NO_EMPTY);

    $totalWidth = 0;
    foreach ($chars as $char) {
      $width = $pdf->GetStringWidth($char);
      $totalWidth += $width + $letterSpacing;
    }
    $totalWidth -= $letterSpacing;

    $x = $startX;
    if ($textAlign === 'center' && $alignmentWidth > 0) {
      $x = $startX + ($alignmentWidth - $totalWidth) / 2;
    } elseif ($textAlign === 'right' && $alignmentWidth > 0) {
      $x = $startX + ($alignmentWidth - $totalWidth);
    }

    foreach ($chars as $char) {
      $pdf->Text($x, $y, $char);
      $width = $pdf->GetStringWidth($char);
      $x += $width + $letterSpacing;
    }
  }
  protected function convertToJapaneseYear(int $year, int $month): array
  {
    if ($year >= 2019 && ($year > 2019 || $month >= 5)) {
      return ['era' => '令和', 'year' => $year - 2018];
    } elseif ($year >= 1989) {
      return ['era' => '平成', 'year' => $year - 1988];
    } elseif ($year >= 1926) {
      return ['era' => '昭和', 'year' => $year - 1925];
    }
    return ['era' => '', 'year' => $year];
  }
}
