<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * 施術料金領収書PDF生成サービス
 */
class TreatmentReceiptPdfService
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
   * 座標設定を読み込む
   */
  protected function loadCoordinates(): void
  {
    $configPath = storage_path('app/config/treatment_receipt_coordinates.json');

    if (file_exists($configPath)) {
      $json = file_get_contents($configPath);
      $this->coordinates = json_decode($json, true) ?? [];
    } else {
      $this->coordinates = [];
    }
  }

  /**
   * PDF生成
   *
   * @param array $clinicUserIds 利用者ID配列
   * @param string $serviceYearMonth サービス提供年月（Y-m形式）
   * @param string $submissionDate 提出日（Y-m-d形式）
   * @return string PDFバイナリ
   */
  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // テンプレートPDFを読み込み
    $templatePath = storage_path('app/templates/施術料金領収書.pdf');

    if (!file_exists($templatePath)) {
      throw new \Exception('テンプレートファイルが見つかりません: ' . $templatePath);
    }

    try {
      $pdf->setSourceFile($templatePath);
      $tplId = $pdf->importPage(1);
    } catch (\Exception $e) {
      // PDF形式がサポートされていない場合（例：PDF 1.5以上）
      throw new \Exception('PDFテンプレートの読み込みに失敗しました。PDF 1.4形式に変換してください。エラー: ' . $e->getMessage());
    }

    // テンプレートのみ表示（1ページ）
    $pdf->AddPage();
    $pdf->useTemplate($tplId, 0, 0, null, null, true);

    // 座標設定があればフィールドを描画
    if (!empty($this->coordinates)) {
      $pdf->SetFont('kozgopromedium', '', 10);
      $pdf->SetTextColor(0, 0, 0);
      $this->renderFields($pdf);
    }

    return $pdf->Output('', 'S');
  }

  /**
   * フィールド描画
   */
  protected function renderFields($pdf): void
  {
    // 座標設定に基づいてテキストを配置（将来の実装用）
  }
}
