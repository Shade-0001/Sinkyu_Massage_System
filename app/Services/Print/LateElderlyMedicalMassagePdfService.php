<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * あんま・マッサージ後期高齢者医療療養費支給申請書PDF生成サービス
 *
 * 注：このサービスクラスは暫定実装。
 * 医療助成費支給申請書を参考に、後期高齢者医療用のPDFテンプレートとフォームフィールドを実装する必要がある。
 */
class LateElderlyMedicalMassagePdfService extends BasePdfService
{
  /**
   * 署名オプション
   */
  protected $signatureOption = null;

  /**
   * 署名オプションを設定
   */
  public function setSignatureOption(?string $option): void
  {
    $this->signatureOption = $option;
  }

  /**
   * デフォルト座標ファイルパスを取得
   */
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/late_elderly_medical_massage_coordinates.json');
  }

  /**
   * デフォルト座標を取得
   */
  protected function getDefaultCoordinates(): array
  {
    $configPath = $this->getDefaultCoordinatesPath();
    if (file_exists($configPath)) {
      $json = file_get_contents($configPath);
      return json_decode($json, true);
    }
    return [];
  }

  /**
   * PDF生成
   *
   * @param array $clinicUserIds 利用者ID配列
   * @param string $serviceYearMonth サービス提供年月 (YYYY-MM)
   * @param string $submissionDate 提出年月日 (YYYY-MM-DD)
   * @return string PDFバイナリデータ
   */
  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = ''): string
  {
    // 暫定実装：簡易的なPDFを生成
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);

    // 利用者数分のページを追加
    foreach ($clinicUserIds as $clinicUserId) {
      $pdf->AddPage();
      $pdf->SetFont('kozgopromedium', '', 12);

      // 暫定メッセージを表示
      $pdf->SetXY(20, 50);
      $pdf->MultiCell(170, 10,
        "後期高齢者医療療養費支給申請書（あんま・マッサージ）\n\n" .
        "この機能は現在開発中です。\n\n" .
        "利用者ID: {$clinicUserId}\n" .
        "サービス提供年月: {$serviceYearMonth}\n" .
        "提出年月: " . substr($submissionDate, 0, 7),
        0, 'L'
      );
    }

    return $pdf->Output('', 'S');
  }
}
