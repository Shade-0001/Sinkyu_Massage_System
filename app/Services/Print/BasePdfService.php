<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\Storage;

/**
 * PDF生成サービスの基底クラス
 *
 * 共通機能：
 * - 座標管理
 * - サンプルデータモード
 * - 描画ヘルパーメソッド
 * - 和暦変換
 */
abstract class BasePdfService
{
  /**
   * 座標設定
   */
  protected $coordinates;

  /**
   * サンプルデータ表示モード
   */
  protected $sampleDataMode = false;

  /**
   * カスタムサンプルデータ
   */
  protected $customSampleData = null;

  /**
   * カスタムタイトルテキスト（描画テキスト用）
   */
  protected $customTitleText = null;

  /**
   * 座標ファイルパス（カスタム）
   */
  protected $customCoordinatesPath = null;

  /**
   * テンプレートファイルパス（カスタム）
   */
  protected $customTemplatePath = null;

  /**
   * コンストラクタ
   */
  public function __construct()
  {
    $this->loadCoordinates();
  }

  /**
   * サンプルデータ表示モードを設定
   */
  public function setSampleDataMode(bool $enabled): void
  {
    $this->sampleDataMode = $enabled;
  }

  /**
   * カスタムサンプルデータを設定
   */
  public function setCustomSampleData(array $data): void
  {
    $this->customSampleData = $data;
  }

  /**
   * カスタムタイトルテキストを設定
   */
  public function setCustomTitleText(?string $text): void
  {
    $this->customTitleText = $text;
  }

  /**
   * 座標ファイルパスを設定
   */
  public function setCoordinatesPath(string $path): void
  {
    $this->customCoordinatesPath = $path;
    $this->loadCoordinates();
  }

  /**
   * テンプレートファイルパスを設定
   */
  public function setTemplatePath(string $path): void
  {
    $this->customTemplatePath = $path;
  }

  /**
   * 座標設定を読み込む
   */
  protected function loadCoordinates(): void
  {
    $configPath = $this->customCoordinatesPath ?? $this->getDefaultCoordinatesPath();

    if (file_exists($configPath)) {
      $json = file_get_contents($configPath);
      $this->coordinates = json_decode($json, true);

      // ラジオグループの整合性を確保（サブクラスで必要な場合）
      if (method_exists($this, 'ensureRadioGroupIntegrity')) {
        $this->ensureRadioGroupIntegrity();
      }
    } else {
      // デフォルト座標（JSONファイルがない場合のフォールバック）
      $this->coordinates = $this->getDefaultCoordinates();
    }
  }

  /**
   * ラジオグループの整合性を確保（各グループで最大1つだけ isSelected: true）
   */
  protected function ensureRadioGroupIntegrity(): void
  {
    $processedGroups = [];

    foreach ($this->coordinates as $key => $field) {
      if (!isset($field['radioGroup'])) {
        continue;
      }

      $groupName = $field['radioGroup'];

      // グループ内で複数の isSelected: true がないか確認
      if (!isset($processedGroups[$groupName])) {
        $processedGroups[$groupName] = false;

        // グループ内のすべてのフィールドをチェック
        $firstSelectedKey = null;
        foreach ($this->coordinates as $k => $f) {
          if (isset($f['radioGroup']) && $f['radioGroup'] === $groupName) {
            if (isset($f['isSelected']) && $f['isSelected']) {
              if ($firstSelectedKey === null) {
                $firstSelectedKey = $k;
              } else {
                // 複数の isSelected: true がある場合は、2番目以降を false にする
                $this->coordinates[$k]['isSelected'] = false;
              }
            }
          }
        }
      }
    }
  }

  /**
   * デフォルト座標ファイルパスを取得（サブクラスで実装）
   */
  abstract protected function getDefaultCoordinatesPath(): string;

  /**
   * デフォルト座標を取得（サブクラスで実装）
   */
  abstract protected function getDefaultCoordinates(): array;

  /**
   * 座標値を取得
   */
  protected function coord(string $key, string $property = 'x', $default = 0)
  {
    return $this->coordinates[$key][$property] ?? $default;
  }

  /**
   * 座標キーが存在するか確認
   */
  protected function hasCoord(string $key): bool
  {
    return isset($this->coordinates[$key]);
  }

  /**
   * ボックスにテキストを埋め込む（座標キー指定版）
   */
  protected function fillBoxesByKey(Fpdi $pdf, string $key, string $text, int $boxCount, float $boxWidth): void
  {
    if (!$this->hasCoord($key)) {
      return;
    }

    // 座標設定にletterSpacingが定義されている場合は、それを文字間の総間隔として使用
    $configLetterSpacing = $this->coord($key, 'letterSpacing', null);
    if ($configLetterSpacing !== null) {
      // letterSpacingが定義されている場合は、それを総間隔として使用（boxWidthは無視）
      $actualBoxWidth = $configLetterSpacing;
      $additionalSpacing = 0;
    } else {
      // letterSpacingが未定義の場合は、従来通りboxWidthを使用
      $actualBoxWidth = $boxWidth;
      $additionalSpacing = 0;
    }

    $this->fillBoxes(
      $pdf,
      $this->coord($key, 'x'),
      $this->coord($key, 'y'),
      $text,
      $boxCount,
      $actualBoxWidth,
      $additionalSpacing,
      $this->coord($key, 'textAlign', 'left')
    );
  }

  /**
   * ボックスにテキストを埋め込む（座標直接指定版）
   */
  protected function fillBoxes(Fpdi $pdf, float $startX, float $y, string $text, int $boxCount, float $boxWidth, float $letterSpacing = 0, string $textAlign = 'left'): void
  {
    // テキストを分解
    $chars = mb_str_split($text);
    $charCount = count($chars);

    // 配置開始位置を計算
    $currentX = $startX;

    // boxWidthが0の場合（自動幅）のテキスト配置計算
    if ($boxWidth == 0 && ($textAlign === 'right' || $textAlign === 'center')) {
      // 文字列全体の幅を計算
      $totalWidth = 0;
      foreach ($chars as $index => $char) {
        $totalWidth += $pdf->GetStringWidth($char);
        if ($index < count($chars) - 1) {
          $totalWidth += $letterSpacing;
        }
      }

      // 配置位置を調整
      if ($textAlign === 'right') {
        $currentX = $startX - $totalWidth;
      } elseif ($textAlign === 'center') {
        $currentX = $startX - ($totalWidth / 2);
      }
    } elseif ($boxWidth > 0 && $textAlign === 'right') {
      // 固定幅ボックスの場合の右揃え
      $offset = $boxCount - $charCount;
      $currentX = $startX + ($offset * $boxWidth);
    } elseif ($boxWidth > 0 && $textAlign === 'center') {
      // 固定幅ボックスの場合の中央揃え
      $offset = ($boxCount - $charCount) / 2;
      $currentX = $startX + ($offset * $boxWidth);
    }

    // 各文字をボックスに配置
    for ($i = 0; $i < min($charCount, $boxCount); $i++) {
      $char = $chars[$i];

      // 文字幅を取得
      $charWidth = $pdf->GetStringWidth($char);

      // boxWidthが0の場合は文字の実際の幅を使用（自動間隔調整）
      if ($boxWidth == 0) {
        $pdf->SetXY($currentX, $y);
        $pdf->Cell($charWidth, 0, $char, 0, 0, 'L', false);
        $currentX += $charWidth + $letterSpacing;
      } else {
        // ボックスの中央に文字を配置
        $xOffset = ($boxWidth - $charWidth) / 2;
        $pdf->SetXY($currentX + $xOffset, $y);
        $pdf->Cell($charWidth, 0, $char, 0, 0, 'L', false);
        $currentX += $boxWidth + $letterSpacing;
      }
    }
  }

  /**
   * 座標キーで指定された位置にテキストを描画（文字間隔・配置・複数行対応）
   */
  protected function drawTextByKey(Fpdi $pdf, string $key, string $text): void
  {
    if (!$this->hasCoord($key)) {
      return;
    }

    $x = $this->coord($key, 'x');
    $y = $this->coord($key, 'y');
    $fontSize = $this->coord($key, 'fontSize') ?: 10;
    $fontWeight = $this->coord($key, 'fontWeight') ?: 'normal';
    $letterSpacing = $this->coord($key, 'letterSpacing') ?: 0;
    $textAlign = $this->coord($key, 'textAlign') ?: 'left';
    $maxCharsPerLine = $this->coord($key, 'maxCharsPerLine') ?: 0;
    $lineHeight = $this->coord($key, 'lineHeight') ?: 5;

    // フォント設定
    $pdf->SetFont('kozgopromedium', $fontWeight === 'bold' ? 'B' : '', $fontSize);

    // maxCharsPerLineが設定されている場合は折り返し処理
    if ($maxCharsPerLine > 0 && mb_strlen($text) > $maxCharsPerLine) {
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
      foreach ($lines as $i => $line) {
        $currentY = $y + ($i * $lineHeight);
        $this->drawSingleLineText($pdf, $x, $currentY, $line, $letterSpacing, $textAlign);
      }
      return;
    }

    // 通常の1行描画
    $this->drawSingleLineText($pdf, $x, $y, $text, $letterSpacing, $textAlign);
  }

  /**
   * 1行テキストを描画
   */
  protected function drawSingleLineText(Fpdi $pdf, float $x, float $y, string $text, float $letterSpacing, string $textAlign): void
  {
    // 文字間隔が指定されている場合は文字ごとに描画（配置対応）
    if ($letterSpacing > 0) {
      // A4幅を配置幅として使用（210mm）
      $alignmentWidth = 210;
      $this->drawTextWithSpacing($pdf, $x, $y, $text, $letterSpacing, $textAlign, $alignmentWidth);
    } else {
      // 通常描画（配置対応）
      $textWidth = $pdf->GetStringWidth($text);
      $alignedX = $x;
      $pageWidth = 210; // A4幅（mm）

      if ($textAlign === 'center') {
        // 中央揃え：X座標を起点としてページ幅内で中央配置
        $alignmentWidth = $pageWidth - $x;
        $alignedX = $x + ($alignmentWidth - $textWidth) / 2;
      } elseif ($textAlign === 'right') {
        // 右揃え：X座標を右端として、そこから左にテキスト幅分配置
        $alignedX = $x - $textWidth;
      }

      $pdf->SetXY($alignedX, $y);
      $pdf->Cell(0, 0, $text, 0, 0, 'L', false);
    }
  }

  /**
   * 文字間隔を指定してテキストを描画
   */
  protected function drawTextWithSpacing(Fpdi $pdf, float $startX, float $y, string $text, float $letterSpacing, string $textAlign = 'left', float $alignmentWidth = 0): void
  {
    $chars = mb_str_split($text);
    $totalWidth = 0;

    // 総幅を計算
    foreach ($chars as $char) {
      $totalWidth += $pdf->GetStringWidth($char);
    }
    $totalWidth += $letterSpacing * (count($chars) - 1);

    // 配置開始位置を計算
    $currentX = $startX;
    if ($textAlign === 'center') {
      $currentX = $startX + ($alignmentWidth - $totalWidth) / 2;
    } elseif ($textAlign === 'right') {
      $currentX = $startX + $alignmentWidth - $totalWidth;
    }

    // 各文字を描画
    foreach ($chars as $char) {
      $charWidth = $pdf->GetStringWidth($char);
      $pdf->SetXY($currentX, $y);
      $pdf->Cell($charWidth, 0, $char, 0, 0, 'L', false);
      $currentX += $charWidth + $letterSpacing;
    }
  }

  /**
   * 楕円を描画
   *
   * 【重要】座標JSONでellipseX/ellipseYとx/yの両方を定義しないこと
   * - 楕円描画にはellipseX/ellipseYを使用（推奨）
   * - x/yは後方互換性のためのフォールバック
   * - 両方定義すると、coord()がellipseX不在時に0を返すため、
   *   ??演算子でフォールバックが機能せず、座標(0,0)に描画される不具合が発生する
   * - 修正後はisset()で実際の存在確認を行い、この問題を回避
   */
  protected function drawEllipseByKey(Fpdi $pdf, string $key): void
  {
    if (!$this->hasCoord($key)) {
      return;
    }

    // ellipseX/ellipseYが定義されている場合は優先、なければx/yを使用
    // 注: coord()は存在しないプロパティに対してデフォルト値0を返すため、
    //     ??演算子は機能しない。そのためisset()で明示的に存在確認を行う
    $x = isset($this->coordinates[$key]['ellipseX']) ? $this->coordinates[$key]['ellipseX'] : $this->coord($key, 'x');
    $y = isset($this->coordinates[$key]['ellipseY']) ? $this->coordinates[$key]['ellipseY'] : $this->coord($key, 'y');
    // ellipseWidthとellipseHeightを使用（radiusXとradiusYは後方互換性のため維持）
    $radiusX = $this->coord($key, 'ellipseWidth') ?: $this->coord($key, 'radiusX') ?: 1;
    $radiusY = $this->coord($key, 'ellipseHeight') ?: $this->coord($key, 'radiusY') ?: 1;

    $pdf->Ellipse($x, $y, $radiusX, $radiusY, 0, 0, 360, 'D');
  }

  /**
   * 円を描画
   */
  protected function drawCircleByKey(Fpdi $pdf, string $key): void
  {
    if (!$this->hasCoord($key)) {
      return;
    }

    $x = $this->coord($key, 'x');
    $y = $this->coord($key, 'y');
    $radius = $this->coord($key, 'radius') ?: 1;

    $pdf->Circle($x, $y, $radius, 0, 360, 'D');
  }

  /**
   * デバッグ用グリッド描画
   */
  protected function drawDebugGrid(Fpdi $pdf): void
  {
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.1);

    // 縦線（10mm間隔）
    for ($x = 0; $x <= 210; $x += 10) {
      $pdf->Line($x, 0, $x, 297);
    }

    // 横線（10mm間隔）
    for ($y = 0; $y <= 297; $y += 10) {
      $pdf->Line(0, $y, 210, $y);
    }

    // 座標数値を表示
    $pdf->SetFont('kozgopromedium', '', 6);
    $pdf->SetTextColor(150, 150, 150);

    for ($x = 0; $x <= 200; $x += 10) {
      for ($y = 0; $y <= 290; $y += 10) {
        $pdf->SetXY($x + 0.5, $y + 0.5);
        $pdf->Cell(0, 0, "({$x},{$y})", 0, 0, 'L', false);
      }
    }

    // 色をリセット
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetTextColor(0, 0, 0);
  }

  /**
   * 和暦変換（年月から）
   */
  protected function convertToJapaneseYear(int $year, int $month): array
  {
    // 令和の開始日（2019年5月1日）
    if ($year > 2019 || ($year === 2019 && $month >= 5)) {
      return [
        'era' => '令和',
        'year' => $year - 2018,
      ];
    }

    // 平成の開始日（1989年1月8日）
    if ($year > 1989 || ($year === 1989 && $month >= 1)) {
      return [
        'era' => '平成',
        'year' => $year - 1988,
      ];
    }

    // 昭和
    return [
      'era' => '昭和',
      'year' => $year - 1925,
    ];
  }

  /**
   * 和暦変換（日付文字列から）
   */
  protected function convertToJapaneseDate(string $date): string
  {
    if (empty($date)) {
      return '';
    }

    $dateObj = new \DateTime($date);
    $year = (int) $dateObj->format('Y');
    $month = (int) $dateObj->format('m');
    $day = (int) $dateObj->format('d');

    $japanese = $this->convertToJapaneseYear($year, $month);

    return sprintf(
      '%s%d年 %d月 %d日',
      $japanese['era'],
      $japanese['year'],
      $month,
      $day
    );
  }

  /**
   * テンプレートのみのダミーPDFを生成
   *
   * @param string|null $templatePath テンプレートファイルパス
   * @return string PDFバイナリデータ
   */
  protected function generateTemplatePdf(?string $templatePath = null): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);

    $compressionError = false;
    $noTemplateFile = false;
    $pageCount = 0;

    // テンプレートPDF読み込み（存在する場合）
    $path = $templatePath ?? $this->customTemplatePath;
    if (!$path || empty($path)) {
      $noTemplateFile = true;
      $pdf->AddPage();
    } elseif (file_exists($path)) {
      try {
        $pageCount = $pdf->setSourceFile($path);

        // 全ページを読み込み
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
          $pdf->AddPage();
          $tplId = $pdf->importPage($pageNo);
          $pdf->useTemplate($tplId, 0, 0, null, null, true);
        }
      } catch (\Exception $e) {
        // FPDI無料版で非対応の圧縮形式の場合
        if (strpos($e->getMessage(), 'compression technique') !== false) {
          $compressionError = true;
          $pdf->AddPage();
          \Log::warning('PDFテンプレート読み込みエラー（非対応の圧縮形式）', [
            'path' => $path,
            'message' => $e->getMessage()
          ]);
        } else {
          throw $e;
        }
      }
    }

    // エラーメッセージまたは情報メッセージを表示
    if ($compressionError || $noTemplateFile) {
      $pdf->SetFont('kozminproregular', '', 12);
      $pdf->SetXY(20, 100);

      if ($compressionError) {
        $pdf->SetTextColor(255, 0, 0);
        $pdf->MultiCell(170, 10,
          "【エラー】\n\n" .
          "このPDFテンプレートはFPDI非対応の圧縮形式を使用しています。\n\n" .
          "対処方法：\n" .
          "・Adobe AcrobatなどでPDF 1.4形式（Acrobat 5.0互換）に変換\n" .
          "・または空白ページのまま座標設定を実施",
          0, 'L'
        );
      } elseif ($noTemplateFile) {
        $pdf->SetTextColor(100, 100, 100);
        $pdf->MultiCell(170, 10,
          "【情報】\n\n" .
          "このPDFタイプにはテンプレートファイルが設定されていません。\n\n"
        );
      }
    }

    return $pdf->Output('', 'S');
  }

  /**
   * PDF生成（サブクラスで実装）
   */
  abstract public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = ''): string;
}
