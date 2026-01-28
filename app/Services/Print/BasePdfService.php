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
  protected function coord(string $key, string $property = 'x')
  {
    return $this->coordinates[$key][$property] ?? 0;
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

    $this->fillBoxes(
      $pdf,
      $this->coord($key, 'x'),
      $this->coord($key, 'y'),
      $text,
      $boxCount,
      $boxWidth
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

    // 配置開始位置を計算（右揃えの場合）
    if ($textAlign === 'right') {
      $offset = $boxCount - $charCount;
      $currentX = $startX + ($offset * $boxWidth);
    } else {
      $currentX = $startX;
    }

    // 各文字をボックスに配置
    for ($i = 0; $i < min($charCount, $boxCount); $i++) {
      $char = $chars[$i];

      // ボックスの中央に文字を配置
      $charWidth = $pdf->GetStringWidth($char);
      $xOffset = ($boxWidth - $charWidth) / 2;

      $pdf->SetXY($currentX + $xOffset, $y);
      $pdf->Cell($charWidth, 0, $char, 0, 0, 'L', false);

      $currentX += $boxWidth + $letterSpacing;
    }
  }

  /**
   * 座標キーで指定された位置にテキストを描画
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

    // フォント設定
    $pdf->SetFont('kozgopromedium', $fontWeight === 'bold' ? 'B' : '', $fontSize);

    // テキスト描画
    $pdf->SetXY($x, $y);
    $pdf->Cell(0, 0, $text, 0, 0, 'L', false);
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
   */
  protected function drawEllipseByKey(Fpdi $pdf, string $key): void
  {
    if (!$this->hasCoord($key)) {
      return;
    }

    $x = $this->coord($key, 'x');
    $y = $this->coord($key, 'y');
    $radiusX = $this->coord($key, 'radiusX') ?: 1;
    $radiusY = $this->coord($key, 'radiusY') ?: 1;

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
      '%s%d年%d月%d日',
      $japanese['era'],
      $japanese['year'],
      $month,
      $day
    );
  }

  /**
   * PDF生成（サブクラスで実装）
   */
  abstract public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate): string;
}
