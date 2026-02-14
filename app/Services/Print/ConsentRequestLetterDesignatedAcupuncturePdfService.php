<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 同意書依頼状（医師指定）（はり･きゅう）PDF生成サービス
 *
 * 【重要】document_associationテーブルとの関連付け
 * ============================================================
 * このサービスは document_id_1 = 3（同意書依頼状医師指定版はり・きゅう）
 * の文面を参照する。
 *
 * DocumentAssociationController.phpのfixedDocuments定義：
 * - id=1: 同意書依頼（サンプル版）はり・きゅう
 * - id=2: 同意書依頼（サンプル版）あんま・マッサージ
 * - id=3: 同意書依頼（医師指定）はり・きゅう ← このサービスが参照
 * - id=4: 同意書依頼（医師指定）あんま・マッサージ
 *
 * ※fetchData()メソッド内のdocument_id_1の値を変更する場合は、
 *   上記の定義と一致していることを必ず確認すること！
 * ============================================================
 */
class ConsentRequestLetterDesignatedAcupuncturePdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/consent_request_letter_designated_acupuncture_coordinates.json');
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
    // サンプルデータ表示モードの場合
    if ($this->sampleDataMode) {
      return $this->getSampleData($submissionDate);
    }

    // 利用者情報取得
    $clinicUser = DB::table('clinic_users')
      ->where('id', $clinicUserId)
      ->first();

    if (!$clinicUser) {
      \Log::error('利用者情報が見つかりません', ['clinic_user_id' => $clinicUserId]);
      return null;
    }

    // はり・きゅう同意書情報取得（最新）
    // 医師名から医療機関情報を取得するため、doctors→medical_institutionsをJOIN
    $consent = DB::table('consents_acupuncture')
      ->leftJoin('illnesses_acupuncture', 'consents_acupuncture.illness_name_acupuncture_id', '=', 'illnesses_acupuncture.id')
      ->leftJoin('doctors', function($join) {
        $join->on(DB::raw('CONCAT(doctors.last_name, " ", doctors.first_name)'), '=', 'consents_acupuncture.consenting_doctor_name');
      })
      ->leftJoin('medical_institutions', 'doctors.medical_institutions_id', '=', 'medical_institutions.id')
      ->where('consents_acupuncture.clinic_user_id', $clinicUserId)
      ->orderBy('consents_acupuncture.consenting_date', 'desc')
      ->select(
        'consents_acupuncture.*',
        'illnesses_acupuncture.illness_name_acupuncture',
        'medical_institutions.medical_institution_name',
        'consents_acupuncture.consenting_doctor_name as doctor_name'
      )
      ->first();

    // 施術所情報取得
    $clinicInfo = DB::table('clinic_info')->first();

    if (!$clinicInfo) {
      \Log::error('施術所情報が見つかりません');
    }

    // 文書関連付け情報取得（document_id_1 = 3: 同意書依頼状医師指定版はり･きゅう）
    $documentAssociation = DB::table('document_association')
      ->where('document_id_1', 3)
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
      'medical_institution_name' => $consent->medical_institution_name ?? '',
      'doctor_name' => $consent->doctor_name ?? '',
    ];
  }

  /**
   * サンプルデータ取得
   */
  protected function getSampleData(string $submissionDate): array
  {
    // カスタムサンプルデータがあればそれを優先的に使用
    $custom = $this->customSampleData;

    \Log::info('ConsentRequestLetterDesignatedAcupuncture getSampleData実行', [
      'custom_exists' => !empty($custom),
      'document_content' => isset($custom['document_content']) ? '存在' : 'なし',
      'medical_institution_name' => $custom['medical_institution_name'] ?? 'なし',
      'doctor_name' => $custom['doctor_name'] ?? 'なし',
      'user_name' => $custom['user_name'] ?? 'なし',
      'illness_name' => $custom['illness_name'] ?? 'なし',
    ]);

    // サンプル利用者情報
    $clinicUser = (object)[
      'id' => 999,
      'last_name' => $custom['last_name'] ?? '山田',
      'first_name' => $custom['first_name'] ?? '太郎',
      'last_kana' => 'ヤマダ',
      'first_kana' => 'タロウ',
    ];

    // サンプル同意書情報
    $consent = (object)[
      'illness_name_acupuncture' => $custom['illness_name'] ?? '腰痛症',
    ];

    // サンプル施術所情報
    $clinicInfo = (object)[
      'postal_code' => $custom['clinic_postal_code'] ?? '100-0001',
      'address_1' => $custom['clinic_address'] ?? '東京都千代田区千代田1-1-1',
      'address_2' => '',
      'address_3' => '',
      'phone' => $custom['clinic_phone'] ?? '03-1234-5678',
      'clinic_name' => $custom['clinic_name'] ?? 'サンプル鍼灸院',
      'owner_last_name' => $custom['clinic_owner_last_name'] ?? '鈴木',
      'owner_first_name' => $custom['clinic_owner_first_name'] ?? '一郎',
    ];

    // サンプル文書内容
    $documentContent = $custom['document_content'] ?? "拝啓　時下ますますご清栄のこととお慶び申し上げます。\n\nさて、このたび下記の方より、はり・きゅう施術の同意書交付についてご依頼がございました。\nつきましては、ご多忙中誠に恐縮ではございますが、ご高診の上、同意書をご交付いただきますよう、何卒よろしくお願い申し上げます。\n\n敬具";

    // 医療機関名・医師氏名を追加
    $medicalInstitutionName = $custom['medical_institution_name'] ?? '〇〇病院';
    $doctorName = $custom['doctor_name'] ?? '田中医師';

    return [
      'clinic_user' => $clinicUser,
      'consent' => $consent,
      'clinic_info' => $clinicInfo,
      'document_content' => $documentContent,
      'submission_date' => $submissionDate,
      'medical_institution_name' => $medicalInstitutionName,
      'doctor_name' => $doctorName,
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

    // 3. 医療機関名
    if (isset($data['medical_institution_name']) && $this->hasCoord('medical_institution_name')) {
      $pdf->SetFontSize($this->coord('medical_institution_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'medical_institution_name', $data['medical_institution_name']);
    }

    // 4. 医師氏名
    if (isset($data['doctor_name']) && $this->hasCoord('doctor_name')) {
      $pdf->SetFontSize($this->coord('doctor_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'doctor_name', $data['doctor_name']);
    }

    // 5. 本文（文面関連付けで取得した内容）
    if ($documentContent) {
      $pdf->SetFontSize($this->coord('document_content', 'fontSize'));
      $this->drawMultilineTextByKey($pdf, 'document_content', $documentContent);
    }

    // 6. 利用者氏名（姓 名形式）
    $userName = ($clinicUser->last_name ?? '') . ' ' . ($clinicUser->first_name ?? '');
    $pdf->SetFontSize($this->coord('user_name', 'fontSize'));
    $this->drawTextByKey($pdf, 'user_name', $userName);

    // 7. 傷病名
    $illnessName = $consent->illness_name_acupuncture ?? '';
    if ($illnessName) {
      $pdf->SetFontSize($this->coord('illness_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'illness_name', $illnessName);
    }

    // 8. 施設郵便番号（〒***-****形式、医療助成費支給申請書の代理人郵便番号と同じ仕様）
    if ($clinicInfo && $clinicInfo->postal_code) {
      // ハイフンを削除して数字のみにする
      $postalCodeNumbers = preg_replace('/[^0-9]/', '', $clinicInfo->postal_code);
      // 3桁-4桁の形式にフォーマット
      if (strlen($postalCodeNumbers) === 7) {
        $formattedPostalCode = substr($postalCodeNumbers, 0, 3) . '-' . substr($postalCodeNumbers, 3, 4);
      } else {
        $formattedPostalCode = $postalCodeNumbers;
      }
      $pdf->SetFontSize($this->coord('clinic_postal_code', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_postal_code', '〒 ' . $formattedPostalCode);
    }

    // 9. 施設住所
    if ($clinicInfo) {
      $address = ($clinicInfo->address_1 ?? '') . ($clinicInfo->address_2 ?? '') . ($clinicInfo->address_3 ?? '');
      $pdf->SetFontSize($this->coord('clinic_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_address', $address);
    }

    // 10. 施設電話番号（TEL∶ *形式、therapy_benefit_acupunctureの申請欄カテゴリの電話番号フィールドと同じ仕様）
    if ($clinicInfo && $clinicInfo->phone) {
      $formattedPhone = $this->formatPhoneNumber($clinicInfo->phone);
      $phoneText = 'TEL∶ ' . $formattedPhone;  // U+2236 (Ratio)
      $pdf->SetFontSize($this->coord('clinic_phone', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_phone', $phoneText);
    }

    // 11. 施設名
    if ($clinicInfo && $clinicInfo->clinic_name) {
      $pdf->SetFontSize($this->coord('clinic_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_name', $clinicInfo->clinic_name);
    }

    // 12. 施設代表者氏名（姓 名形式）
    if ($clinicInfo) {
      $ownerName = ($clinicInfo->owner_last_name ?? '') . ' ' . ($clinicInfo->owner_first_name ?? '');
      $pdf->SetFontSize($this->coord('clinic_owner_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_owner_name', $ownerName);
    }
  }

  /**
   * 複数行テキスト描画（1行あたり文字数調節機能付き、改行保持）
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

    // まず元のテキストの改行で分割
    $originalLines = preg_split('/\r\n|\r|\n/', $text);

    $allLines = [];
    foreach ($originalLines as $originalLine) {
      // 各行が最大文字数を超える場合は自動分割
      if (mb_strlen($originalLine) > $maxCharsPerLine) {
        $chunks = mb_str_split($originalLine, $maxCharsPerLine);
        foreach ($chunks as $chunk) {
          $allLines[] = $chunk;
        }
      } else {
        $allLines[] = $originalLine;
      }
    }

    // 描画
    $currentY = $y;
    foreach ($allLines as $line) {
      $pdf->SetXY($x, $currentY);
      $pdf->Cell(0, 0, $line, 0, 0, 'L', false);
      $currentY += $lineHeight;
    }
  }

  /**
   * 電話番号フォーマット
   */
  protected function formatPhoneNumber(string $phone): string
  {
    // ハイフンや空白を除去して数字のみにする
    $digitsOnly = preg_replace('/[^0-9]/', '', $phone);

    if (empty($digitsOnly)) {
      return '';
    }

    // 10桁の場合
    if (strlen($digitsOnly) === 10) {
      // 市外局番が03の場合: 2桁 - 4桁 - 4桁
      if (substr($digitsOnly, 0, 2) === '03') {
        return substr($digitsOnly, 0, 2) . ' - ' . substr($digitsOnly, 2, 4) . ' - ' . substr($digitsOnly, 6);
      }
      // 市外局番が03以外の場合: 3桁 - 3桁 - 4桁
      else {
        return substr($digitsOnly, 0, 3) . ' - ' . substr($digitsOnly, 3, 3) . ' - ' . substr($digitsOnly, 6);
      }
    }

    // 11桁の場合: 3桁 - 4桁 - 4桁
    if (strlen($digitsOnly) === 11) {
      return substr($digitsOnly, 0, 3) . ' - ' . substr($digitsOnly, 3, 4) . ' - ' . substr($digitsOnly, 7);
    }

    // その他の桁数はそのまま返す
    return $phone;
  }
}
