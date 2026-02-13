<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 同意書依頼状（サンプル版）（はり･きゅう）PDF生成サービス
 */
class ConsentRequestLetterSampleAcupuncturePdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/consent_request_letter_sample_acupuncture_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate, string $remarks = ''): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);

    foreach ($clinicUserIds as $clinicUserId) {
      $data = $this->fetchData($clinicUserId, $submissionDate);

      if ($data) {
        $this->addPage($pdf, $data, $submissionDate);
      }
    }

    return $pdf->Output('', 'S');
  }

  /**
   * データ取得
   */
  protected function fetchData(int $clinicUserId, string $submissionDate): ?array
  {
    // 利用者情報取得
    $clinicUser = DB::table('clinic_users')
      ->where('id', $clinicUserId)
      ->first();

    if (!$clinicUser) {
      \Log::error('利用者情報が見つかりません', ['clinic_user_id' => $clinicUserId]);
      return null;
    }

    // はり・きゅう同意書情報取得（最新）
    $consent = DB::table('consents_acupuncture')
      ->leftJoin('illnesses_acupuncture', 'consents_acupuncture.illness_name_acupuncture_id', '=', 'illnesses_acupuncture.id')
      ->where('consents_acupuncture.clinic_user_id', $clinicUserId)
      ->orderBy('consents_acupuncture.consenting_date', 'desc')
      ->select('consents_acupuncture.*', 'illnesses_acupuncture.illness_name_acupuncture')
      ->first();

    // 施術所情報取得
    $clinicInfo = DB::table('clinic_info')->first();

    if (!$clinicInfo) {
      \Log::error('施術所情報が見つかりません');
    }

    // 文書関連付け情報取得（document_id_1 = 1: 同意書依頼状サンプル版はり･きゅう）
    $documentAssociation = DB::table('document_association')
      ->where('document_id_1', 1)
      ->first();

    $documentContent = '';
    if ($documentAssociation && $documentAssociation->document_id_2) {
      $document = DB::table('documents')
        ->where('id', $documentAssociation->document_id_2)
        ->first();

      if ($document) {
        $documentContent = $document->content ?? '';
      }
    }

    return [
      'clinic_user' => $clinicUser,
      'consent' => $consent,
      'clinic_info' => $clinicInfo,
      'document_content' => $documentContent,
      'submission_date' => $submissionDate,
    ];
  }

  /**
   * PDFページ追加
   */
  protected function addPage(Fpdi $pdf, array $data, string $submissionDate): void
  {
    $pdf->AddPage();

    // テンプレートPDF読み込み
    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/汎用文書.pdf');

    if (file_exists($templatePath)) {
      $pageCount = $pdf->setSourceFile($templatePath);
      $tplId = $pdf->importPage(1);
      $pdf->useTemplate($tplId, 0, 0, null, null, true);
    }

    // フォント設定
    $pdf->SetFont('kozminproregular', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // データ埋め込み
    $this->fillFormFields($pdf, $data, $submissionDate);
  }

  /**
   * フォームフィールド埋め込み
   */
  protected function fillFormFields(Fpdi $pdf, array $data, string $submissionDate): void
  {
    $clinicUser = $data['clinic_user'];
    $consent = $data['consent'];
    $clinicInfo = $data['clinic_info'];
    $documentContent = $data['document_content'];

    // 1. タイトル（医療助成費支給申請書のタイトルフィールドと同じ仕様）
    if (!empty($this->customTitleText)) {
      $pdf->SetFontSize($this->coord('custom_title_text', 'fontSize'));
      $this->drawTextByKey($pdf, 'custom_title_text', (string)$this->customTitleText);
    }

    // 2. 提出年月日（元号*年 *月 *日形式）
    if ($submissionDate) {
      [$year, $month, $day] = explode('-', $submissionDate);
      $japaneseDate = $this->convertToJapaneseYear((int)$year, (int)$month);
      $dateText = $japaneseDate['era'] . $japaneseDate['year'] . '年 ' . (int)$month . '月 ' . (int)$day . '日';
      $pdf->SetFontSize($this->coord('submission_date', 'fontSize'));
      $this->drawTextByKey($pdf, 'submission_date', $dateText);
    }

    // 3. 本文（文面関連付けで取得した内容）
    if ($documentContent) {
      $pdf->SetFontSize($this->coord('document_content', 'fontSize'));
      $this->drawMultilineTextByKey($pdf, 'document_content', $documentContent);
    }

    // 4. 利用者氏名（姓 名形式）
    $userName = ($clinicUser->last_name ?? '') . ' ' . ($clinicUser->first_name ?? '');
    $pdf->SetFontSize($this->coord('user_name', 'fontSize'));
    $this->drawTextByKey($pdf, 'user_name', $userName);

    // 5. 傷病名
    $illnessName = $consent->illness_name_acupuncture ?? '';
    if ($illnessName) {
      $pdf->SetFontSize($this->coord('illness_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'illness_name', $illnessName);
    }

    // 6. 施設郵便番号（医療助成費支給申請書の代理人郵便番号と同じ仕様）
    if ($clinicInfo && $clinicInfo->postal_code) {
      $postalCode = str_replace('-', '', $clinicInfo->postal_code);
      $pdf->SetFontSize($this->coord('clinic_postal_code', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'clinic_postal_code', $postalCode, 7, 5.6);
    }

    // 7. 施設住所
    if ($clinicInfo) {
      $address = ($clinicInfo->address_1 ?? '') . ($clinicInfo->address_2 ?? '') . ($clinicInfo->address_3 ?? '');
      $pdf->SetFontSize($this->coord('clinic_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_address', $address);
    }

    // 8. 施設電話番号（TEL：*形式、therapy_benefit_acupunctureの申請欄カテゴリの電話番号フィールドと同じ仕様）
    if ($clinicInfo && $clinicInfo->phone) {
      $phoneText = 'TEL：' . $clinicInfo->phone;
      $pdf->SetFontSize($this->coord('clinic_phone', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_phone', $phoneText);
    }

    // 9. 施設名
    if ($clinicInfo && $clinicInfo->clinic_name) {
      $pdf->SetFontSize($this->coord('clinic_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_name', $clinicInfo->clinic_name);
    }

    // 10. 施設代表者氏名（姓 名形式）
    if ($clinicInfo) {
      $ownerName = ($clinicInfo->owner_last_name ?? '') . ' ' . ($clinicInfo->owner_first_name ?? '');
      $pdf->SetFontSize($this->coord('clinic_owner_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_owner_name', $ownerName);
    }
  }

  /**
   * 複数行テキスト描画（1行あたり文字数調節機能付き）
   */
  protected function drawMultilineTextByKey(Fpdi $pdf, string $key, string $text): void
  {
    if (!$this->hasCoord($key)) {
      return;
    }

    $x = $this->coord($key, 'x');
    $y = $this->coord($key, 'y');
    $fontSize = $this->coord($key, 'fontSize') ?: 10;
    $lineHeight = $this->coord($key, 'lineHeight') ?: 5;
    $maxCharsPerLine = $this->coord($key, 'maxCharsPerLine') ?: 40;

    $pdf->SetFontSize($fontSize);

    // テキストを指定文字数で改行
    $lines = mb_str_split($text, $maxCharsPerLine);

    $currentY = $y;
    foreach ($lines as $line) {
      $pdf->SetXY($x, $currentY);
      $pdf->Cell(0, 0, $line, 0, 0, 'L', false);
      $currentY += $lineHeight;
    }
  }
}
