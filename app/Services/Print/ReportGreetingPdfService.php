<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 報告書挨拶状PDF生成サービス
 *
 * 【重要】document_associationテーブルとの関連付け
 * ============================================================
 * このサービスは greeting_type に応じて以下のdocument_id_1を参照する。
 *
 * DocumentAssociationController.phpのfixedDocuments定義：
 * - id=8: 報告書挨拶状（医師向け）    → greeting_type=doctor
 * - id=9: 報告書挨拶状（ケアマネ向け）→ greeting_type=caremanager
 * - id=10: 報告書挨拶状（利用者向け） → greeting_type=user
 * ============================================================
 *
 * 【宛名フォーマット】
 * - doctor      : medical_institution_name + doctor_name「先生御侍史」
 * - caremanager : medical_institution_name（事業所名）+ doctor_name位置に「ケアマネ名　様」
 * - user        : doctor_name位置に「利用者名　様」（medical_institution_nameは空）
 * ============================================================
 */
class ReportGreetingPdfService extends BasePdfService
{
  /**
   * 挨拶タイプ（doctor: 医師向け / caremanager: ケアマネ向け / user: 利用者向け）
   */
  protected string $greetingType = 'doctor';

  public function setGreetingType(string $type): void
  {
    $this->greetingType = $type;
  }

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

    if ($this->greetingType === 'doctor') {
      // 医師向け：利用者 × 医師のペアでページ生成
      foreach ($clinicUserIds as $clinicUserId) {
        foreach ($doctorIds as $doctorId) {
          $data = $this->fetchData($clinicUserId, $submissionDate, $doctorId, null);
          if ($data) {
            $this->addPage($pdf, $data, $submissionDate);
          }
        }
      }
    } elseif ($this->greetingType === 'caremanager') {
      // ケアマネ向け：利用者 × ケアマネのペアでページ生成
      foreach ($clinicUserIds as $clinicUserId) {
        foreach ($caremanagerIds as $caremanagerId) {
          $data = $this->fetchData($clinicUserId, $submissionDate, null, $caremanagerId);
          if ($data) {
            $this->addPage($pdf, $data, $submissionDate);
          }
        }
      }
    } else {
      // 利用者向け：利用者ごとにページ生成
      foreach ($clinicUserIds as $clinicUserId) {
        $data = $this->fetchData($clinicUserId, $submissionDate, null, null);
        if ($data) {
          $this->addPage($pdf, $data, $submissionDate);
        }
      }
    }

    return $pdf->Output('', 'S');
  }

  /**
   * データ取得
   */
  protected function fetchData(int $clinicUserId, string $submissionDate, ?int $doctorId = null, ?int $caremanagerId = null): ?array
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

    // 宛先情報取得
    $medicalInstitutionName = '';
    $doctorName             = '';
    $serviceProviderName    = '';
    $caremanagerName        = '';

    if ($this->greetingType === 'doctor' && $doctorId) {
      $doctor = DB::table('doctors')
        ->leftJoin('medical_institutions', 'doctors.medical_institutions_id', '=', 'medical_institutions.id')
        ->where('doctors.id', $doctorId)
        ->select('doctors.*', 'medical_institutions.medical_institution_name')
        ->first();

      if ($doctor) {
        $medicalInstitutionName = $doctor->medical_institution_name ?? '';
        $doctorName             = trim(($doctor->last_name ?? '') . '  ' . ($doctor->first_name ?? ''));
      }
    } elseif ($this->greetingType === 'caremanager' && $caremanagerId) {
      $caremanager = DB::table('caremanagers')
        ->leftJoin('service_providers', 'caremanagers.service_providers_id', '=', 'service_providers.id')
        ->where('caremanagers.id', $caremanagerId)
        ->select('caremanagers.*', 'service_providers.service_provider_name')
        ->first();

      if ($caremanager) {
        $serviceProviderName = $caremanager->service_provider_name ?? '';
        $caremanagerName     = trim(($caremanager->last_name ?? '') . '  ' . ($caremanager->first_name ?? ''));
      }
    }
    // user の場合は先方情報なし

    // 傷病名取得（はり・きゅう同意書から最新のものを優先、なければマッサージ同意書から取得）
    $illnessName = '';

    $consentAcupuncture = DB::table('consents_acupuncture')
      ->leftJoin('illnesses_acupuncture', 'consents_acupuncture.illness_name_acupuncture_id', '=', 'illnesses_acupuncture.id')
      ->where('consents_acupuncture.clinic_user_id', $clinicUserId)
      ->orderBy('consents_acupuncture.consenting_date', 'desc')
      ->select('illnesses_acupuncture.illness_name_acupuncture', 'consents_acupuncture.consenting_date')
      ->first();

    $consentMassage = DB::table('consents_massage')
      ->leftJoin('illnesses_massage', 'consents_massage.injury_and_illness_name_id', '=', 'illnesses_massage.id')
      ->where('consents_massage.clinic_user_id', $clinicUserId)
      ->orderBy('consents_massage.consenting_date', 'desc')
      ->select('illnesses_massage.illness_name', 'consents_massage.consenting_date')
      ->first();

    if ($consentAcupuncture && $consentMassage) {
      if ($consentAcupuncture->consenting_date >= $consentMassage->consenting_date) {
        $illnessName = $consentAcupuncture->illness_name_acupuncture ?? '';
      } else {
        $illnessName = $consentMassage->illness_name ?? '';
      }
    } elseif ($consentAcupuncture) {
      $illnessName = $consentAcupuncture->illness_name_acupuncture ?? '';
    } elseif ($consentMassage) {
      $illnessName = $consentMassage->illness_name ?? '';
    }

    // 施術所情報取得（submissionDateから年月を算出）
    $yearMonth  = $submissionDate ? substr($submissionDate, 0, 7) : date('Y-m');
    $clinicInfo = $this->getClinicInfoForDate($yearMonth . '-01');

    if (!$clinicInfo) {
      \Log::error('施術所情報が見つかりません');
    }

    // 文書関連付け情報取得
    // doctor=8 / caremanager=9 / user=10
    $documentId1Map = ['doctor' => 8, 'caremanager' => 9, 'user' => 10];
    $documentId1    = $documentId1Map[$this->greetingType] ?? 8;

    $documentAssociation = DB::table('document_association')
      ->where('document_id_1', $documentId1)
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

    if ($this->overrideDocumentContent !== null) {
      $documentContent = $this->overrideDocumentContent;
    }

    return [
      'clinic_user'              => $clinicUser,
      'illness_name'             => $illnessName,
      'clinic_info'              => $clinicInfo,
      'document_content'         => $documentContent,
      'submission_date'          => $submissionDate,
      'medical_institution_name' => $medicalInstitutionName,
      'doctor_name'              => $doctorName,
      'service_provider_name'    => $serviceProviderName,
      'caremanager_name'         => $caremanagerName,
    ];
  }

  /**
   * サンプルデータ取得
   */
  protected function getSampleData(string $submissionDate): array
  {
    $custom = $this->customSampleData;

    $clinicUser = (object)[
      'id'         => 999,
      'last_name'  => $custom['last_name'] ?? '山田',
      'first_name' => $custom['first_name'] ?? '太郎',
      'last_kana'  => 'ヤマダ',
      'first_kana' => 'タロウ',
    ];

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

    $documentContent = $custom['document_content'] ?? "拝啓　時下ますますご清栄のこととお慶び申し上げます。\n\nさて、このたびは下記の方の報告書をお送りいたします。\nご査収のほどよろしくお願い申し上げます。\n\n敬具";

    // greeting_typeに応じたサンプル宛先
    $medicalInstitutionName = '';
    $doctorName             = '';
    $serviceProviderName    = '';
    $caremanagerName        = '';

    if ($this->greetingType === 'caremanager') {
      $serviceProviderName = $custom['service_provider_name'] ?? '〇〇居宅介護支援事業所';
      $caremanagerName     = $custom['caremanager_name'] ?? '佐藤 花子';
    } elseif ($this->greetingType === 'user') {
      // 利用者向けは宛先情報なし
    } else {
      $medicalInstitutionName = $custom['medical_institution_name'] ?? '〇〇病院';
      $doctorName             = $custom['doctor_name'] ?? '田中医師';
    }

    return [
      'clinic_user'              => $clinicUser,
      'illness_name'             => $custom['illness_name'] ?? '腰痛症',
      'clinic_info'              => $clinicInfo,
      'document_content'         => $documentContent,
      'submission_date'          => $submissionDate,
      'medical_institution_name' => $medicalInstitutionName,
      'doctor_name'              => $doctorName,
      'service_provider_name'    => $serviceProviderName,
      'caremanager_name'         => $caremanagerName,
    ];
  }

  /**
   * PDFページ追加
   */
  protected function addPage(Fpdi $pdf, array $data, string $submissionDate): void
  {
    $pdf->AddPage();

    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/汎用文書.pdf');

    if (file_exists($templatePath)) {
      $pageCount = $pdf->setSourceFile($templatePath);
      $tplId     = $pdf->importPage(1);
      $pdf->useTemplate($tplId, 0, 0, null, null, true);
    }

    $pdf->SetFont('kozminproregular', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    $this->fillFormFields($pdf, $data, $submissionDate);
  }

  /**
   * フォームフィールド埋め込み
   */
  protected function fillFormFields(Fpdi $pdf, array $data, string $submissionDate): void
  {
    $clinicUser      = $data['clinic_user'];
    $illnessName     = $data['illness_name'];
    $clinicInfo      = $data['clinic_info'];
    $documentContent = $data['document_content'];

    // 1. タイトル
    $titleText = !empty($this->customTitleText) ? $this->customTitleText : '挨拶状';
    if ($this->hasCoord('custom_title_text')) {
      $pdf->SetFontSize($this->coord('custom_title_text', 'fontSize'));
      $this->drawTextByKey($pdf, 'custom_title_text', (string)$titleText);
    }

    // 2. 提出年月日（元号*年 *月 *日形式）
    if ($submissionDate) {
      [$year, $month, $day] = explode('-', $submissionDate);
      $japaneseDate = $this->convertToJapaneseYear((int)$year, (int)$month);
      $dateText     = $japaneseDate['era'] . $japaneseDate['year'] . '年 ' . (int)$month . '月 ' . (int)$day . '日';
      $pdf->SetFontSize($this->coord('submission_date', 'fontSize'));
      $this->drawTextByKey($pdf, 'submission_date', $dateText);
    }

    // 3. 医療機関名（医師向け）
    if ($this->greetingType === 'doctor') {
      if (!empty($data['medical_institution_name']) && $this->hasCoord('medical_institution_name')) {
        $pdf->SetFontSize($this->coord('medical_institution_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'medical_institution_name', $data['medical_institution_name']);
      }
    }

    // 3b. 事業所名（ケアマネ向け）
    if ($this->greetingType === 'caremanager') {
      if (!empty($data['service_provider_name']) && $this->hasCoord('service_provider_name')) {
        $pdf->SetFontSize($this->coord('service_provider_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'service_provider_name', $data['service_provider_name']);
      }
    }

    // 4. 宛名
    //    - doctor:      医師名「先生御侍史」（doctor_nameフィールド）
    //    - caremanager: ケアマネ名「　様」（caremanager_nameフィールド）
    //    - user:        利用者名「　様」（doctor_nameフィールド位置）
    if ($this->greetingType === 'doctor') {
      if (!empty($data['doctor_name']) && $this->hasCoord('doctor_name')) {
        $pdf->SetFontSize($this->coord('doctor_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'doctor_name', $data['doctor_name'] . '　先生御侍史');
      }
    } elseif ($this->greetingType === 'caremanager') {
      if ($this->hasCoord('caremanager_name')) {
        $nameText = (!empty($data['caremanager_name']) ? $data['caremanager_name'] : '') . '  様';
        $pdf->SetFontSize($this->coord('caremanager_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'caremanager_name', $nameText);
      }
    } else {
      // user → 利用者名「　様」（doctor_nameフィールド位置）
      if ($this->hasCoord('doctor_name')) {
        $userDisplayName = ($clinicUser->last_name ?? '') . '  ' . ($clinicUser->first_name ?? '');
        $pdf->SetFontSize($this->coord('doctor_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'doctor_name', $userDisplayName . '  様');
      }
    }

    // 5. 本文
    if ($documentContent) {
      $pdf->SetFontSize($this->coord('document_content', 'fontSize'));
      $this->drawMultilineTextByKey($pdf, 'document_content', $documentContent);
    }

    // 6. 利用者氏名（姓 名形式）
    $userName = ($clinicUser->last_name ?? '') . '  ' . ($clinicUser->first_name ?? '');
    $pdf->SetFontSize($this->coord('user_name', 'fontSize'));
    $this->drawTextByKey($pdf, 'user_name', $userName);

    // 7. 傷病名
    if ($illnessName && $this->hasCoord('illness_name')) {
      $pdf->SetFontSize($this->coord('illness_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'illness_name', $illnessName);
    }

    // 8. 施設郵便番号（〒***-****形式）
    if ($clinicInfo && $clinicInfo->postal_code) {
      $postalCodeNumbers = preg_replace('/[^0-9]/', '', $clinicInfo->postal_code);
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

    // 10. 施設電話番号（TEL∶ *形式）
    if ($clinicInfo && $clinicInfo->phone) {
      $formattedPhone = $this->formatPhoneNumber($clinicInfo->phone);
      $phoneText      = 'TEL∶ ' . $formattedPhone;  // U+2236 (Ratio)
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
      $ownerName = ($clinicInfo->owner_last_name ?? '') . '  ' . ($clinicInfo->owner_first_name ?? '');
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

    $x               = $this->coord($key, 'x');
    $y               = $this->coord($key, 'y');
    $fontSize        = $this->coord($key, 'fontSize') ?: 10;
    $lineHeight      = $this->coord($key, 'lineHeight') ?: 5;
    $maxCharsPerLine = $this->coord($key, 'maxCharsPerLine') ?: 40;

    $pdf->SetFontSize($fontSize);

    $originalLines = preg_split('/\r\n|\r|\n/', $text);

    $allLines = [];
    foreach ($originalLines as $originalLine) {
      if (mb_strlen($originalLine) > $maxCharsPerLine) {
        $chunks = mb_str_split($originalLine, $maxCharsPerLine);
        foreach ($chunks as $chunk) {
          $allLines[] = $chunk;
        }
      } else {
        $allLines[] = $originalLine;
      }
    }

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
    $digitsOnly = preg_replace('/[^0-9]/', '', $phone);

    if (empty($digitsOnly)) {
      return '';
    }

    if (strlen($digitsOnly) === 10) {
      if (substr($digitsOnly, 0, 2) === '03') {
        return substr($digitsOnly, 0, 2) . ' - ' . substr($digitsOnly, 2, 4) . ' - ' . substr($digitsOnly, 6);
      } else {
        return substr($digitsOnly, 0, 3) . ' - ' . substr($digitsOnly, 3, 3) . ' - ' . substr($digitsOnly, 6);
      }
    }

    if (strlen($digitsOnly) === 11) {
      return substr($digitsOnly, 0, 3) . ' - ' . substr($digitsOnly, 3, 4) . ' - ' . substr($digitsOnly, 7);
    }

    return $phone;
  }
}
