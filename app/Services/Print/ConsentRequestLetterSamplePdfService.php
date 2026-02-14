<?php

namespace App\Services\Print;

/**
 * 同意書依頼状（サンプル版）PDF生成サービス（鍼灸・マッサージ両対応）
 *
 * consent_request_typeに応じて適切なサービスクラスに処理を委譲
 */
class ConsentRequestLetterSamplePdfService
{
  /**
   * PDF生成
   *
   * @param array $clinicUserIds 利用者IDの配列
   * @param string $serviceYearMonth サービス年月（YYYY-MM形式）
   * @param string $submissionDate 提出日（YYYY-MM-DD形式）
   * @param string $consentRequestType 同意書タイプ（'acupuncture' or 'massage'）
   * @param string $remarks 備考（オプション）
   * @return string PDFバイナリ
   */
  public function generate(
    array $clinicUserIds,
    string $serviceYearMonth,
    string $submissionDate,
    string $consentRequestType = 'acupuncture',
    string $remarks = ''
  ): string {
    // タイプに応じて適切なサービスクラスを選択
    if ($consentRequestType === 'massage') {
      $service = new ConsentRequestLetterSampleMassagePdfService();
      $defaultTitle = '同意書依頼（サンプル版）あんま・マッサージ';
    } else {
      $service = new ConsentRequestLetterSampleAcupuncturePdfService();
      $defaultTitle = '同意書依頼（サンプル版）はり・きゅう';
    }

    // タイトルを設定
    $service->setCustomTitleText($defaultTitle);

    // 生成処理を委譲
    return $service->generate($clinicUserIds, $serviceYearMonth, $submissionDate, $remarks);
  }
}
