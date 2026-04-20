<?php
//-- app/Services/Print/GenericDocumentPdfService.php --//

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * 汎用文書PDF生成サービス
 * document_association未設定の新規文書プレビュー用
 * 本文が複数ページにわたる場合は自動的にページ追加
 * - 1ページ目：ヘッダー（タイトル・日付）を描画
 * - 最終ページ：フッター（患者情報・自社情報）を描画
 * - 中間ページ：本文のみ、ヘッダー・フッター領域も本文として活用
 */
class GenericDocumentPdfService extends BasePdfService
{
  protected bool $showPatientInfo = false;
  protected string $patientName = '';
  protected string $patientIllness = '';

  // 各領域の座標定数
  const HEADER_CONTENT_Y = 65.0;   // ヘッダーあり時の本文開始Y
  const BODY_TOP_Y       = 10.0;   // ヘッダーなし時の本文開始Y
  const FOOTER_START_Y   = 238.0;  // フッター開始Y（本文終端）
  const PAGE_BOTTOM_Y    = 290.0;  // ページ下端（余白込み）

  public function setShowPatientInfo(bool $value): void  { $this->showPatientInfo = $value; }
  public function setPatientName(string $value): void    { $this->patientName = $value; }
  public function setPatientIllness(string $value): void { $this->patientIllness = $value; }

  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/report_greeting_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = '', array $doctorIds = [], array $caremanagerIds = []): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);

    $data = $this->fetchData($submissionDate);
    $this->generatePages($pdf, $data, $submissionDate);

    return $pdf->Output('', 'S');
  }

  protected function fetchData(string $submissionDate): array
  {
    if ($this->sampleDataMode) {
      return $this->getSampleData($submissionDate);
    }

    $yearMonth  = $submissionDate ? substr($submissionDate, 0, 7) : date('Y-m');
    $clinicInfo = $this->getClinicInfoForDate($yearMonth . '-01');

    return [
      'clinic_info'      => $clinicInfo,
      'document_content' => $this->overrideDocumentContent ?? '',
      'submission_date'  => $submissionDate,
    ];
  }

  protected function getSampleData(string $submissionDate): array
  {
    $custom = $this->customSampleData;

    $clinicInfo = (object)[
      'postal_code'      => $custom['clinic_postal_code'] ?? '100-0001',
      'address_1'        => $custom['clinic_address'] ?? '東京都千代田区千代田1-1-1',
      'address_2'        => '',
      'address_3'        => '',
      'phone'            => $custom['clinic_phone'] ?? '03-1234-5678',
      'clinic_name'      => $custom['clinic_name'] ?? 'サンプル鍼灸院',
      'owner_last_name'  => $custom['clinic_owner_last_name'] ?? '鈴木',
      'owner_first_name' => $custom['clinic_owner_first_name'] ?? '一郎',
    ];

    return [
      'clinic_info'      => $clinicInfo,
      'document_content' => $custom['document_content'] ?? '',
      'submission_date'  => $submissionDate,
    ];
  }

  /**
   * 本文を行に分割してページ割り付けし、各ページを描画する
   */
  protected function generatePages(Fpdi $pdf, array $data, string $submissionDate): void
  {
    $documentContent = $data['document_content'] ?? '';
    $rawFontSize = $this->overrideFontSize ?? $this->coord('document_content', 'fontSize');
    $fontSize    = ($rawFontSize !== null && $rawFontSize !== 0.0) ? $rawFontSize : 12;
    $rawLineHeight = $this->overrideLineHeight ?? $this->coord('document_content', 'lineHeight');
    // lineHeightは描画ステップ幅の絶対値(mm)。ReportGreetingPdfServiceと同じ扱い。
    // 最小値はフォント高さの50%（極端な値でのページ溢れ防止）
    $fontSizeMm = $fontSize * 0.3528;
    $lineHeight = ($rawLineHeight !== null) ? max($fontSizeMm * 0.5, (float)$rawLineHeight) : 6.5;

    // 本文を全行に展開（改行＋幅超過の折り返しも行展開）
    $x        = $this->coord('document_content', 'x') ?: 15;
    $maxWidth = 210 - $x - 15; // 右余白15mm
    // 全角1文字 ≈ fontSizeMm幅で1行あたりの最大文字数を推定
    $maxChars = (int)($maxWidth / $fontSizeMm);
    $allLines = $this->expandLines($documentContent, $maxChars);

    // 各ページタイプで収容できる行数を計算
    $linesInFirstWithFooter  = $this->calcLines(self::HEADER_CONTENT_Y, self::FOOTER_START_Y, $lineHeight); // 1ページ完結
    $linesInFirstNoFooter    = $this->calcLines(self::HEADER_CONTENT_Y, self::PAGE_BOTTOM_Y, $lineHeight);  // 1ページ目（続きあり）
    $linesInMiddle           = $this->calcLines(self::BODY_TOP_Y, self::PAGE_BOTTOM_Y, $lineHeight);        // 中間ページ
    $linesInLastNoHeader     = $this->calcLines(self::BODY_TOP_Y, self::FOOTER_START_Y, $lineHeight);       // 最終ページ

    // ページ割り付け
    $pages = $this->paginateLines(
      $allLines,
      $linesInFirstWithFooter,
      $linesInFirstNoFooter,
      $linesInMiddle,
      $linesInLastNoHeader
    );

    $totalPages = count($pages);

    foreach ($pages as $pageIndex => $pageLines) {
      $isFirst = ($pageIndex === 0);
      $isLast  = ($pageIndex === $totalPages - 1);

      $this->addPage($pdf, $data, $submissionDate, $pageLines, $isFirst, $isLast, $fontSize, $lineHeight);
    }

    // 複数ページの場合、全ページにページ番号を後処理で描画
    if ($totalPages >= 2) {
      for ($p = 1; $p <= $totalPages; $p++) {
        $pdf->setPage($p);
        $pageText = '-' . "\u{2002}" . "\u{2002}" . $p . ' / ' . $totalPages . "\u{2002}" . "\u{2002}" . '-';
        $pdf->SetFont('kozgopromedium', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(0, 290);
        $pdf->Cell(210, 0, $pageText, 0, 0, 'C');
      }
    }
  }

  /**
   * テキストを改行＋maxChars文字数で折り返した全行配列を返す
   * 半角2文字=全角1文字換算でカウント
   */
  protected function expandLines(string $text, int $maxChars = 0): array
  {
    $paragraphs = preg_split('/\r\n|\r|\n/', $text) ?: [];
    if ($maxChars <= 0) {
      return $paragraphs;
    }

    $result = [];
    foreach ($paragraphs as $para) {
      if ($para === '') {
        $result[] = '';
        continue;
      }
      // 半角=0.5文字、全角=1文字換算で折り返し
      $chars    = mb_str_split($para);
      $line     = '';
      $lineWidth = 0.0;
      foreach ($chars as $ch) {
        $w = mb_strwidth($ch) === 1 ? 0.5 : 1.0;
        if ($lineWidth + $w > $maxChars && $line !== '') {
          $result[] = $line;
          $line      = $ch;
          $lineWidth = $w;
        } else {
          $line      .= $ch;
          $lineWidth += $w;
        }
      }
      $result[] = $line;
    }
    return $result;
  }

  /**
   * 高さと行高から収容行数を計算
   */
  protected function calcLines(float $startY, float $endY, float $lineHeight): int
  {
    if ($lineHeight < 0.1) return 1;
    // 1行分の安全マージンを引く（フォント描画高さとlineHeightの誤差吸収）
    return max(1, (int)(($endY - $startY) / $lineHeight) - 1);
  }

  /**
   * 全行をページ配列に割り付ける
   * 戻り値: [ [行, 行, ...], [行, 行, ...], ... ]
   */
  protected function paginateLines(
    array $allLines,
    int $linesInFirstWithFooter,
    int $linesInFirstNoFooter,
    int $linesInMiddle,
    int $linesInLastNoHeader
  ): array {
    if (empty($allLines)) {
      return [[]];
    }

    // 1ページに収まるか試みる
    if (count($allLines) <= $linesInFirstWithFooter) {
      return [$allLines];
    }

    // 複数ページ：1ページ目を確定してから残りを割り付け
    $pages    = [];
    $pages[]  = array_slice($allLines, 0, $linesInFirstNoFooter);
    $remaining = array_slice($allLines, $linesInFirstNoFooter);

    while (!empty($remaining)) {
      // 残りが最終ページ1枚に収まるか確認
      if (count($remaining) <= $linesInLastNoHeader) {
        $pages[] = $remaining;
        break;
      }
      // 中間ページ（linesInMiddleが1未満なら強制的に残り全部を1ページに押し込んで終了）
      if ($linesInMiddle < 1) {
        $pages[] = $remaining;
        break;
      }
      $pages[]   = array_slice($remaining, 0, $linesInMiddle);
      $remaining = array_slice($remaining, $linesInMiddle);
    }

    return $pages;
  }

  /**
   * 1ページ分を追加して描画する
   */
  protected function addPage(
    Fpdi $pdf,
    array $data,
    string $submissionDate,
    array $lines,
    bool $isFirst,
    bool $isLast,
    float $fontSize,
    float $lineHeight
  ): void {
    $pdf->AddPage();

    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/汎用文書.pdf');
    if (file_exists($templatePath)) {
      $pdf->setSourceFile($templatePath);
      $tplId = $pdf->importPage(1);
      $pdf->useTemplate($tplId, 0, 0, null, null, true);
    }

    $pdf->SetFont('kozminproregular', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // 最終ページ以外：テンプレートの患者情報エリアを白矩形で隠す（本文描画前）
    if (!$isLast) {
      $pdf->SetFillColor(255, 255, 255);
      $pdf->Rect(10, 225, 45, 45, 'F');
    }

    // ヘッダー（1ページ目のみ）
    if ($isFirst) {
      $this->drawHeader($pdf, $submissionDate);
    }

    // 本文
    $contentStartY = $isFirst ? self::HEADER_CONTENT_Y : self::BODY_TOP_Y;
    $rawEndY       = $isLast  ? self::FOOTER_START_Y   : self::PAGE_BOTTOM_Y;
    // calcLinesと同じマージン計算：(int)(高さ/lineHeight)-1 行分のみ描画
    $maxLines      = max(1, (int)(($rawEndY - $contentStartY) / $lineHeight) - 1);
    $x = $this->coord('document_content', 'x') ?: 15;
    $pdf->SetFontSize($fontSize);
    $currentY  = $contentStartY;
    $lineCount = 0;
    foreach ($lines as $line) {
      if ($lineCount >= $maxLines) break;
      $pdf->SetXY($x, $currentY);
      $pdf->Cell(0, 0, $line, 0, 0, 'L', false);
      $currentY += $lineHeight;
      $lineCount++;
    }

    // フッター（最終ページのみ）
    if ($isLast) {
      $this->drawFooter($pdf, $data['clinic_info'] ?? null);
    }
  }

  protected function drawHeader(Fpdi $pdf, string $submissionDate): void
  {
    // タイトル（幅超過時は文字間隔→フォントサイズの順で調整）
    if ($this->hasCoord('custom_title_text')) {
      $titleText     = (string)($this->customTitleText ?? '');
      $baseFontSize  = $this->coord('custom_title_text', 'fontSize') ?: 28;
      $baseSpacing   = $this->coord('custom_title_text', 'letterSpacing') ?: 4;
      $maxWidth      = 180.0; // 印字可能幅（mm）
      $chars         = mb_str_split($titleText);
      $charCount     = count($chars);

      $pdf->SetFont('kozgopromedium', '', $baseFontSize);
      $charsWidth = array_sum(array_map(fn($c) => $pdf->GetStringWidth($c), $chars));
      $totalWidth = $charsWidth + $baseSpacing * max(0, $charCount - 1);

      $spacing  = $baseSpacing;
      $fontSize = $baseFontSize;

      if ($totalWidth > $maxWidth) {
        // Step1: 文字間隔を0まで段階的に縮小
        if ($charCount > 1) {
          $spacing = max(0, ($maxWidth - $charsWidth) / ($charCount - 1));
        } else {
          $spacing = 0;
        }
        $totalWidth = $charsWidth + $spacing * max(0, $charCount - 1);

        // Step2: それでも収まらなければフォントサイズを縮小
        if ($totalWidth > $maxWidth) {
          $spacing  = 0;
          $fontSize = $baseFontSize;
          while ($fontSize > 8) {
            $fontSize -= 0.5;
            $pdf->SetFont('kozgopromedium', '', $fontSize);
            $charsWidth = array_sum(array_map(fn($c) => $pdf->GetStringWidth($c), $chars));
            if ($charsWidth <= $maxWidth) {
              break;
            }
          }
        }
      }

      $pdf->SetFontSize($fontSize);
      $y = $this->coord('custom_title_text', 'y');
      $this->drawTextWithSpacing($pdf, 0, $y, $titleText, $spacing, 'center', 210);
    }

    // 提出年月日
    if ($submissionDate) {
      [$year, $month, $day] = explode('-', $submissionDate);
      $japaneseDate = $this->convertToJapaneseYear((int)$year, (int)$month);
      $dateText     = $japaneseDate['era'] . $japaneseDate['year'] . '年 ' . (int)$month . '月 ' . (int)$day . '日';
      $pdf->SetFontSize($this->coord('submission_date', 'fontSize'));
      $this->drawTextByKey($pdf, 'submission_date', $dateText);
    }
  }

  protected function drawFooter(Fpdi $pdf, $clinicInfo): void
  {
    // 患者情報エリア
    if (!$this->showPatientInfo) {
      $pdf->SetFillColor(255, 255, 255);
      $pdf->Rect(10, 238, 70, 22, 'F');
    } else {
      if ($this->hasCoord('user_name')) {
        $pdf->SetFontSize($this->coord('user_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'user_name', $this->patientName);
      }
      if ($this->hasCoord('illness_name')) {
        $pdf->SetFontSize($this->coord('illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'illness_name', $this->patientIllness);
      }
    }

    // 施設郵便番号
    if ($clinicInfo && $clinicInfo->postal_code) {
      $postalCodeNumbers   = preg_replace('/[^0-9]/', '', $clinicInfo->postal_code);
      $formattedPostalCode = \strlen($postalCodeNumbers) === 7
        ? substr($postalCodeNumbers, 0, 3) . '-' . substr($postalCodeNumbers, 3, 4)
        : $postalCodeNumbers;
      $pdf->SetFontSize($this->coord('clinic_postal_code', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_postal_code', '〒 ' . $formattedPostalCode);
    }

    // 施設住所
    if ($clinicInfo) {
      $address = ($clinicInfo->address_1 ?? '') . ($clinicInfo->address_2 ?? '') . ($clinicInfo->address_3 ?? '');
      $pdf->SetFontSize($this->coord('clinic_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_address', $address);
    }

    // 施設電話番号
    if ($clinicInfo && $clinicInfo->phone) {
      $pdf->SetFontSize($this->coord('clinic_phone', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_phone', 'TEL∶ ' . $this->formatPhoneNumber($clinicInfo->phone));
    }

    // 施設名
    if ($clinicInfo && $clinicInfo->clinic_name) {
      $pdf->SetFontSize($this->coord('clinic_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_name', $clinicInfo->clinic_name);
    }

    // 施設代表者氏名
    if ($clinicInfo) {
      $ownerName = ($clinicInfo->owner_last_name ?? '') . '  ' . ($clinicInfo->owner_first_name ?? '');
      $pdf->SetFontSize($this->coord('clinic_owner_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_owner_name', $ownerName);
    }
  }

  protected function formatPhoneNumber(string $phone): string
  {
    $d = preg_replace('/[^0-9]/', '', $phone);
    if (empty($d)) return '';
    if (\strlen($d) === 10) {
      return substr($d, 0, 2) === '03'
        ? substr($d, 0, 2) . ' - ' . substr($d, 2, 4) . ' - ' . substr($d, 6)
        : substr($d, 0, 3) . ' - ' . substr($d, 3, 3) . ' - ' . substr($d, 6);
    }
    if (\strlen($d) === 11) {
      return substr($d, 0, 3) . ' - ' . substr($d, 3, 4) . ' - ' . substr($d, 7);
    }
    return $phone;
  }
}
