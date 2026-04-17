<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

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
   * 施術タイプ（acupuncture or massage）
   */
  protected $receiptType = 'acupuncture';

  /**
   * 施術報告書交付料フラグ
   */
  protected $includeReportFee = false;

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
   * 施術タイプを設定
   */
  public function setReceiptType(string $type): void
  {
    $this->receiptType = $type;
  }

  /**
   * 施術報告書交付料フラグを設定
   */
  public function setIncludeReportFee(bool $include): void
  {
    $this->includeReportFee = $include;
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

  protected function getClinicInfoForDate(string $referenceDate): ?object
  {
    $info = DB::table('clinic_info')
      ->where('created_at', '<=', $referenceDate . ' 23:59:59')
      ->orderByDesc('created_at')
      ->first();

    if (!$info) {
      $info = DB::table('clinic_info')->orderBy('created_at')->first();
    }

    return $info;
  }

  /**
   * PDF生成（複数利用者対応）
   *
   * @param array $clinicUserIds 利用者ID配列
   * @param string $serviceYearMonth サービス提供年月（Y-m形式）
   * @param string $submissionDate 提出日（Y-m-d形式）
   * @param string $remarks 備考
   * @return string PDFバイナリ
   */
  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = ''): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // テンプレートPDFを読み込み
    $templatePath = storage_path('app/templates/acupuncture_and_massage/施術料金領収書.pdf');

    if (!file_exists($templatePath)) {
      throw new \Exception('テンプレートファイルが見つかりません: ' . $templatePath);
    }

    try {
      $pdf->setSourceFile($templatePath);
      $tplId = $pdf->importPage(1);
    } catch (\Exception $e) {
      throw new \Exception('PDFテンプレートの読み込みに失敗しました。PDF 1.4形式に変換してください。エラー: ' . $e->getMessage());
    }

    // 各利用者ごとにページを生成
    foreach ($clinicUserIds as $clinicUserId) {
      $pdf->AddPage();
      $pdf->useTemplate($tplId, 0, 0, null, null, true);

      // 座標設定があればフィールドを描画
      if (!empty($this->coordinates)) {
        $pdf->SetFont('kozgopromedium', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        if ($this->sampleDataMode) {
          // サンプルデータモード: customSampleDataから値を取得して描画
          $this->renderSampleTexts($pdf);
        } else {
          $data = $this->fetchData($clinicUserId, $serviceYearMonth);
          $this->renderFields($pdf, $data, $serviceYearMonth, $submissionDate, $remarks);
        }
      }
    }

    return $pdf->Output('', 'S');
  }

  /**
   * データベースからデータを取得
   */
  protected function fetchData(int $clinicUserId, string $serviceYearMonth): array
  {
    // 利用者情報
    $clinicUser = DB::table('clinic_users')
      ->leftJoin('gender', 'clinic_users.gender_id', '=', 'gender.id')
      ->where('clinic_users.id', $clinicUserId)
      ->select('clinic_users.*', 'gender.gender')
      ->first();

    // 最新の保険情報
    $insurance = DB::table('insurances')
      ->leftJoin('expenses_borne_ratios', 'insurances.expenses_borne_ratio_id', '=', 'expenses_borne_ratios.id')
      ->where('insurances.clinic_user_id', $clinicUserId)
      ->orderBy('insurances.id', 'desc')
      ->select('insurances.*', 'expenses_borne_ratios.expenses_borne_ratio')
      ->first();

    // 同意書情報（はり・きゅう or マッサージ）
    $consentTable = $this->receiptType === 'acupuncture' ? 'consents_acupuncture' : 'consents_massage';
    $illnessTable = $this->receiptType === 'acupuncture' ? 'illnesses_acupuncture' : 'illnesses_massage';
    $illnessIdColumn = $this->receiptType === 'acupuncture' ? 'illness_name_acupuncture_id' : 'injury_and_illness_name_id';
    $illnessNameColumn = $this->receiptType === 'acupuncture' ? 'illness_name_acupuncture' : 'illness_name';

    $consent = DB::table($consentTable)
      ->leftJoin('bill_categories', $consentTable . '.bill_category_id', '=', 'bill_categories.id')
      ->leftJoin('outcomes', $consentTable . '.outcome_id', '=', 'outcomes.id')
      ->leftJoin($illnessTable, $consentTable . '.' . $illnessIdColumn, '=', $illnessTable . '.id')
      ->where($consentTable . '.clinic_user_id', $clinicUserId)
      ->orderBy($consentTable . '.id', 'desc')
      ->select(
        $consentTable . '.*',
        'bill_categories.bill_category',
        'outcomes.outcome',
        $illnessTable . '.' . $illnessNameColumn . ' as illness_name'
      )
      ->first();

    // 施術タイプに応じたtherapy_content_id
    $therapyType = $this->receiptType === 'acupuncture' ? 1 : 2;

    // 該当するtherapy_contents
    $therapyContents = DB::table('therapy_contents')
      ->where('therapy_type', $therapyType)
      ->get();

    // 当該月の施術記録
    $records = DB::table('records')
      ->where('clinic_user_id', $clinicUserId)
      ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$serviceYearMonth])
      ->orderBy('date')
      ->get();

    // 施術料金マスタ
    $treatmentFees = DB::table('treatment_fees')
      ->orderBy('id', 'desc')
      ->first();

    // 施術所情報
    $clinicInfo = $this->getClinicInfoForDate($serviceYearMonth . '-01');

    return [
      'clinicUser' => $clinicUser,
      'insurance' => $insurance,
      'consent' => $consent,
      'therapyContents' => $therapyContents,
      'records' => $records,
      'treatmentFees' => $treatmentFees,
      'clinicInfo' => $clinicInfo,
    ];
  }

  /**
   * フィールド描画
   */
  protected function renderFields(Fpdi $pdf, array $data, string $serviceYearMonth, string $submissionDate = '', string $remarks = ''): void
  {
    $clinicUser = $data['clinicUser'];
    $insurance = $data['insurance'];
    $consent = $data['consent'];
    $therapyContents = $data['therapyContents'];
    $records = $data['records'];
    $treatmentFees = $data['treatmentFees'];
    $clinicInfo = $data['clinicInfo'];

    // 1. 元号年月
    $titleYearMonth = $this->formatJapaneseYearMonth($serviceYearMonth);
    $this->drawText($pdf, 'title_year_month', $titleYearMonth);

    // 2. 書類区分
    $documentType = $this->receiptType === 'acupuncture' ? '（はり・きゅう用）' : '（あんま・マッサージ用）';
    $this->drawText($pdf, 'document_type', $documentType);

    // 3. 利用者氏名
    if ($clinicUser) {
      $fullName = ($clinicUser->last_name ?? '') . '  ' . ($clinicUser->first_name ?? '');
      $this->drawText($pdf, 'patient_name', $fullName);

      // 4. 性別（サークル描画）
      $gender = $clinicUser->gender ?? '';
      \Log::info("性別チェック", [
        'gender' => $gender,
        'clinic_user_id' => $clinicUser->id ?? 'なし',
        'is_male' => ($gender === '男' || $gender === '男性'),
        'is_female' => ($gender === '女' || $gender === '女性'),
      ]);
      if ($gender === '男' || $gender === '男性') {
        $this->drawEllipseByKey($pdf, 'patient_gender_male');
      } elseif ($gender === '女' || $gender === '女性') {
        $this->drawEllipseByKey($pdf, 'patient_gender_female');
      }

      // 5. 年齢
      $age = $this->calculateAge($clinicUser->birthday ?? null);
      $this->drawText($pdf, 'patient_age', $age !== null ? $age : '');
    }

    // 6. 病名
    if ($consent) {
      $this->drawText($pdf, 'illness_name', $consent->illness_name ?? '');

      // 7. 発病・負傷年月日
      if (!empty($consent->onset_and_injury_date)) {
        $onsetDateFormatted = $this->formatJapaneseDate($consent->onset_and_injury_date);
        $this->drawText($pdf, 'onset_date', $onsetDateFormatted);
      }

      // 8. 保険医同意年月日
      if (!empty($consent->consenting_date)) {
        $consentDateFormatted = $this->formatJapaneseDate($consent->consenting_date);
        $this->drawText($pdf, 'consent_date', $consentDateFormatted);
      }

      // 9. 施術開始年月日
      if (!empty($consent->first_care_date)) {
        $startDateFormatted = $this->formatJapaneseDate($consent->first_care_date);
        $this->drawText($pdf, 'treatment_start_date', $startDateFormatted);
      }

      // 10. 施術終了年月日（当月最終施術日）
      if ($records->isNotEmpty()) {
        $lastRecord = $records->last();
        $endDateFormatted = $this->formatJapaneseDate($lastRecord->date);
        $this->drawText($pdf, 'treatment_end_date', $endDateFormatted);
      }

      // 11. 請求区分
      $this->drawText($pdf, 'bill_category', $consent->bill_category ?? '');

      // 12. 転帰
      $this->drawText($pdf, 'outcome', $consent->outcome ?? '');
    }

    // 13-18. 施術の種類・回数・料金・計・期間
    $this->renderTherapyDetails($pdf, $data, $serviceYearMonth);

    // 22. 施術月
    $month = (int)date('n', strtotime($serviceYearMonth . '-01'));
    $this->drawText($pdf, 'treatment_month', $month);

    // 23. 施術日（カレンダー形式）
    $this->renderTreatmentDays($pdf, $records);

    // 24. 負担割合
    if ($insurance) {
      $ratioId = $insurance->expenses_borne_ratio_id ?? 1;
      $this->drawText($pdf, 'copayment_ratio', $ratioId);
    }

    // 25. 備考
    if (!empty($remarks)) {
      // 先頭に全角スペースを追加、末尾に"。"を追加
      $remarksText = '　' . $remarks;
      if (mb_substr($remarksText, -1) !== '。') {
        $remarksText .= '。';
      }
      $this->drawText($pdf, 'remarks', $remarksText);
    }

    // 26. 提出年月日
    $creationDate = $this->formatJapaneseDate($submissionDate);
    $this->drawText($pdf, 'creation_date', $creationDate);

    // 27-31. 作成者情報
    if ($clinicInfo) {
      // 郵便番号
      $postalCode = $clinicInfo->postal_code ?? '';
      if (!empty($postalCode)) {
        // ハイフンが含まれていない場合のみフォーマット
        if (strpos($postalCode, '-') === false) {
          $formattedPostal = '〒 ' . substr($postalCode, 0, 3) . ' - ' . substr($postalCode, 3);
        } else {
          $formattedPostal = '〒 ' . $postalCode;
        }
        $this->drawText($pdf, 'clinic_postal_code', $formattedPostal);
      }

      // 住所
      $address = ($clinicInfo->address_1 ?? '') . ($clinicInfo->address_2 ?? '') . ($clinicInfo->address_3 ?? '');
      $this->drawText($pdf, 'clinic_address', $address);

      // 名称
      $this->drawText($pdf, 'clinic_name', $clinicInfo->clinic_name ?? '');

      // 代表者氏名（姓名を半角スペース2つ区切りで連結）
      $ownerName = trim(($clinicInfo->owner_last_name ?? '') . '  ' . ($clinicInfo->owner_first_name ?? ''));
      $this->drawText($pdf, 'clinic_manager', $ownerName);

      // 電話番号
      $phone = $clinicInfo->phone ?? '';
      $formattedPhone = $this->formatPhoneNumber($phone);
      $this->drawText($pdf, 'clinic_phone', $formattedPhone);
    }
  }

  /**
   * 施術の種類・回数・料金・計・期間を描画
   */
  protected function renderTherapyDetails(Fpdi $pdf, array $data, string $serviceYearMonth): void
  {
    $therapyContents = $data['therapyContents'];
    $records = $data['records'];
    $treatmentFees = $data['treatmentFees'];

    $baseY = $this->coord('therapy_types', 'y');
    $verticalSpacing = $this->coord('therapy_types', 'verticalSpacing') ?? 5;
    $fontSize = $this->coord('therapy_types', 'fontSize') ?? 9;

    $insuranceTotal = 0;
    $selfPayTotal = 0;
    $rowIndex = 0;

    // 各施術内容について集計
    foreach ($therapyContents as $content) {
      // 該当施術の記録を抽出
      $contentRecords = $records->filter(function ($record) use ($content) {
        return $record->therapy_content_id == $content->id;
      });

      if ($contentRecords->isEmpty()) {
        continue;
      }

      $count = $contentRecords->count();
      $unitPrice = $this->getUnitPrice($treatmentFees, $content->id);
      $total = $count * $unitPrice;

      // 自費施術かどうかを判定（therapy_content名に「自費」が含まれる場合）
      $isSelfPay = strpos($content->therapy_content, '自費') !== false;

      if ($isSelfPay) {
        $selfPayTotal += $total;
      } else {
        $insuranceTotal += $total;
      }

      $y = $baseY + ($rowIndex * $verticalSpacing);

      // 施術の種類
      $pdf->SetFont('kozgopromedium', '', $fontSize);
      // 列幅を渡さず、X座標を中心として中央揃え
      $this->drawTextAt($pdf, $this->coord('therapy_types', 'x'), $y, $content->therapy_content, $this->coord('therapy_types', 'textAlign') ?? 'left');

      // 回数
      $this->drawTextAt($pdf, $this->coord('therapy_counts', 'x'), $y, $count, $this->coord('therapy_counts', 'textAlign') ?? 'left');

      // １回の料金
      $this->drawTextAt($pdf, $this->coord('therapy_unit_prices', 'x'), $y, number_format($unitPrice), $this->coord('therapy_unit_prices', 'textAlign') ?? 'left');

      // 計
      $this->drawTextAt($pdf, $this->coord('therapy_totals', 'x'), $y, number_format($total), $this->coord('therapy_totals', 'textAlign') ?? 'left');

      // 施術を行った期間（開始・終了）
      $firstDate = $contentRecords->first();
      $lastDate = $contentRecords->last();
      $periodStart = date('n月j日', strtotime($firstDate->date));
      $periodEnd = date('n月j日', strtotime($lastDate->date));
      $this->drawTextAt($pdf, $this->coord('therapy_period_start', 'x'), $y, $periodStart, $this->coord('therapy_period_start', 'textAlign') ?? 'left');
      $this->drawTextAt($pdf, $this->coord('therapy_period_end', 'x'), $y, $periodEnd, $this->coord('therapy_period_end', 'textAlign') ?? 'left');

      $rowIndex++;
    }

    // 施術タイプに応じたtherapy_type（1=はり・きゅう、2=あんま・マッサージ）
    $therapyType = $this->receiptType === 'acupuncture' ? 1 : 2;

    // 自費施術の集計（モードに応じてフィルタリング）
    $selfFeeRecords = $records->filter(function ($record) use ($therapyType) {
      return !empty($record->self_fee_id) && $record->therapy_type == $therapyType;
    });

    // 自費施術IDごとにグループ化して集計
    $selfFeeGroups = $selfFeeRecords->groupBy('self_fee_id');

    foreach ($selfFeeGroups as $selfFeeId => $groupRecords) {
      // 自費施術マスタから情報を取得
      $selfFee = DB::table('self_fees')->where('id', $selfFeeId)->first();

      if (!$selfFee) {
        continue;
      }

      $count = $groupRecords->count();
      $unitPrice = (int)$selfFee->amount;
      $total = $count * $unitPrice;
      $selfPayTotal += $total;

      $y = $baseY + ($rowIndex * $verticalSpacing);

      // 施術の種類
      $pdf->SetFont('kozgopromedium', '', $fontSize);
      $this->drawTextAt($pdf, $this->coord('therapy_types', 'x'), $y, $selfFee->self_fee_name, $this->coord('therapy_types', 'textAlign') ?? 'left');

      // 回数
      $this->drawTextAt($pdf, $this->coord('therapy_counts', 'x'), $y, $count, $this->coord('therapy_counts', 'textAlign') ?? 'left');

      // １回の料金
      $this->drawTextAt($pdf, $this->coord('therapy_unit_prices', 'x'), $y, number_format($unitPrice), $this->coord('therapy_unit_prices', 'textAlign') ?? 'left');

      // 計
      $this->drawTextAt($pdf, $this->coord('therapy_totals', 'x'), $y, number_format($total), $this->coord('therapy_totals', 'textAlign') ?? 'left');

      // 施術を行った期間（開始・終了）
      $firstDate = $groupRecords->first();
      $lastDate = $groupRecords->last();
      $periodStart = date('n月j日', strtotime($firstDate->date));
      $periodEnd = date('n月j日', strtotime($lastDate->date));
      $this->drawTextAt($pdf, $this->coord('therapy_period_start', 'x'), $y, $periodStart, $this->coord('therapy_period_start', 'textAlign') ?? 'left');
      $this->drawTextAt($pdf, $this->coord('therapy_period_end', 'x'), $y, $periodEnd, $this->coord('therapy_period_end', 'textAlign') ?? 'left');

      $rowIndex++;
    }

    // 施術報告書交付料
    if ($this->includeReportFee) {
      $y = $baseY + ($rowIndex * $verticalSpacing);
      $reportFeeUnitPrice = 500;
      $reportFeeCount = 1;
      $reportFeeTotal = $reportFeeUnitPrice * $reportFeeCount;
      $insuranceTotal += $reportFeeTotal;

      $pdf->SetFont('kozgopromedium', '', $fontSize);
      // 列幅を渡さず、X座標を中心として中央揃え
      $this->drawTextAt($pdf, $this->coord('therapy_types', 'x'), $y, '施術報告書交付料', $this->coord('therapy_types', 'textAlign') ?? 'left');
      $this->drawTextAt($pdf, $this->coord('therapy_counts', 'x'), $y, $reportFeeCount, $this->coord('therapy_counts', 'textAlign') ?? 'left');
      $this->drawTextAt($pdf, $this->coord('therapy_unit_prices', 'x'), $y, number_format($reportFeeUnitPrice), $this->coord('therapy_unit_prices', 'textAlign') ?? 'left');
      $this->drawTextAt($pdf, $this->coord('therapy_totals', 'x'), $y, number_format($reportFeeTotal), $this->coord('therapy_totals', 'textAlign') ?? 'left');
      // 施術報告書交付料の期間は空欄

      $rowIndex++;
    }

    // 19. 保険対象合計金額
    $this->drawText($pdf, 'insurance_total', number_format($insuranceTotal));

    // 20. 自費対象合計金額
    $this->drawText($pdf, 'self_pay_total', number_format($selfPayTotal));

    // 21. 一部負担金額（保険対象合計金額 × 負担割合、1桁目四捨五入）
    $insurance = $data['insurance'];
    $ratioId = $insurance->expenses_borne_ratio_id ?? 1;
    $ratioMap = [1 => 0.1, 2 => 0.2, 3 => 0.3];
    $ratio = $ratioMap[$ratioId] ?? 0.1;
    $copaymentAmount = round($insuranceTotal * $ratio, -1);
    $this->drawText($pdf, 'copayment_amount', number_format($copaymentAmount));

    // 25. 領収金額（一部負担金額 + 自費対象合計金額）
    $receiptAmount = $copaymentAmount + $selfPayTotal;
    $this->drawText($pdf, 'receipt_amount', number_format($receiptAmount));
  }

  /**
   * 施術日をカレンダー形式で描画
   */
  protected function renderTreatmentDays(Fpdi $pdf, $records): void
  {
    if (!isset($this->coordinates['treatment_days'])) {
      return;
    }

    $baseX = $this->coord('treatment_days', 'x');
    $baseY = $this->coord('treatment_days', 'y');
    $circleSpacing = $this->coord('treatment_days', 'circleSpacing') ?? 4.91;
    $circleRadius = $this->coord('treatment_days', 'circleRadius') ?? 2.6;
    $innerRadius = $this->coord('treatment_days', 'doubleCircleInnerRadius') ?? 1.9;

    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.3);

    foreach ($records as $record) {
      $day = (int)date('j', strtotime($record->date));
      $x = $baseX + ($day - 1) * $circleSpacing;
      $y = $baseY;

      // therapy_category: 1=通院（○）、2=往療（◎）
      if (isset($record->therapy_category) && $record->therapy_category == 2) {
        // 二重円（◎）
        $pdf->Ellipse($x, $y, $circleRadius, $circleRadius, 0, 0, 360, 'D');
        $pdf->Ellipse($x, $y, $innerRadius, $innerRadius, 0, 0, 360, 'D');
      } else {
        // 単円（○）
        $pdf->Ellipse($x, $y, $circleRadius, $circleRadius, 0, 0, 360, 'D');
      }
    }
  }

  /**
   * 施術内容IDから単価を取得
   */
  protected function getUnitPrice($treatmentFees, int $therapyContentId): int
  {
    if (!$treatmentFees) {
      return 0;
    }

    // treatment_feesテーブルのカラム名とtherapy_content_idのマッピング
    // 通常料金を使用（初療料金は初回のみのため）
    $columnMap = [
      11 => 'hari_normal',                    // はり
      12 => 'kyu_normal',                     // きゅう
      13 => 'hari_and_kyu_normal',            // はり・きゅう併用
      14 => 'hari_and_elec_needle_normal',    // はり＋電療（電気針）
      15 => 'kyu_and_elec_moxa_heater_normal', // きゅう＋電療（電気温灸器）
      16 => 'hari_and_kyu_elec_ray_normal',   // 電療（電気光線器具）
      18 => 'massage_trunk_normal',           // マッサージ（体幹）
      19 => 'manual_correction_normal',       // 変形徒手矯正術
      20 => 'fomentation_normal',             // 温罨法
      21 => 'fomentation_and_elec_ray_normal', // 温罨法・電気光線器具
    ];

    $column = $columnMap[$therapyContentId] ?? null;
    if ($column && isset($treatmentFees->$column)) {
      return (int)$treatmentFees->$column;
    }

    return 0;
  }

  /**
   * 座標を取得
   */
  protected function coord(string $key, string $prop)
  {
    return $this->coordinates[$key][$prop] ?? null;
  }

  /**
   * テキストを描画
   */
  protected function drawText(Fpdi $pdf, string $key, $value): void
  {
    if (!isset($this->coordinates[$key]) || $value === null || $value === '') {
      return;
    }

    $x = $this->coord($key, 'x');
    $y = $this->coord($key, 'y');
    $fontSize = $this->coord($key, 'fontSize') ?? 10;
    $textAlign = $this->coord($key, 'textAlign') ?? 'left';
    $letterSpacing = $this->coord($key, 'letterSpacing') ?? 0;
    $maxCharsPerLine = $this->coord($key, 'maxCharsPerLine');
    $lineHeightCoord = $this->coord($key, 'lineHeight');
    $verticalAlign   = $this->coord($key, 'verticalAlign') ?? 'middle';

    $pdf->SetFont('kozgopromedium', '', $fontSize);

    $text = (string)$value;

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

      // 複数行を描画
      $lineHeight = $lineHeightCoord ?? ($fontSize * 0.4); // 座標JSONのlineHeightを優先、なければフォントサイズの40%
      $totalLines = count($lines);
      $totalHeight = ($totalLines - 1) * $lineHeight;
      $startY = ($verticalAlign === 'top') ? $y : $y - ($totalHeight / 2); // verticalAlignに応じて開始Y座標を決定

      foreach ($lines as $index => $line) {
        $currentY = $startY + ($index * $lineHeight);

        if ($letterSpacing > 0) {
          $this->drawTextWithSpacing($pdf, $x, $currentY, $line, (float)$letterSpacing, $textAlign);
        } else {
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

    // letterSpacingがある場合は文字間隔付き描画
    if ($letterSpacing > 0) {
      $this->drawTextWithSpacing($pdf, $x, $y, $text, (float)$letterSpacing, $textAlign);
      return;
    }

    // textAlignに応じてX座標を調整（Textメソッドを使用）
    if ($textAlign === 'center') {
      // テキスト幅を計算し、中央揃えの位置を計算
      $textWidth = $pdf->GetStringWidth($text);
      $x = $x - ($textWidth / 2);
    } elseif ($textAlign === 'right') {
      // テキスト幅を計算し、右揃えの位置を計算
      $textWidth = $pdf->GetStringWidth($text);
      $x = $x - $textWidth;
    }

    $pdf->Text($x, $y, $text);
  }

  /**
   * 文字間隔付きテキストを描画
   */
  protected function drawTextWithSpacing(Fpdi $pdf, float $startX, float $y, string $text, float $letterSpacing, string $textAlign = 'left'): void
  {
    // マルチバイト対応で1文字ずつに分割
    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

    // 全テキストの幅を計算
    $totalWidth = 0;
    foreach ($chars as $char) {
      $width = $pdf->GetStringWidth($char);
      $totalWidth += $width + $letterSpacing;
    }
    // 最後の文字間隔は不要
    $totalWidth -= $letterSpacing;

    // テキスト配置に基づいて開始位置を調整
    $x = $startX;
    if ($textAlign === 'center') {
      $x = $startX - ($totalWidth / 2);
    } elseif ($textAlign === 'right') {
      $x = $startX - $totalWidth;
    }

    // 各文字を描画
    foreach ($chars as $char) {
      $pdf->Text($x, $y, $char);
      $width = $pdf->GetStringWidth($char);
      $x += $width + $letterSpacing;
    }
  }

  /**
   * 指定座標にテキストを描画
   */
  protected function drawTextAt(Fpdi $pdf, $x, $y, $value, string $textAlign = 'left', float $width = 0): void
  {
    if ($x === null || $y === null || $value === null || $value === '') {
      return;
    }

    $text = (string)$value;

    // textAlignに応じてX座標を調整
    if ($textAlign === 'center') {
      // 中央揃え：widthが指定されている場合はその範囲内で中央揃え
      if ($width > 0) {
        $textWidth = $pdf->GetStringWidth($text);
        $x = $x + ($width - $textWidth) / 2;
      } else {
        // widthが未指定の場合はテキストの中心をxに配置
        $textWidth = $pdf->GetStringWidth($text);
        $x = $x - ($textWidth / 2);
      }
    } elseif ($textAlign === 'right') {
      // 右揃え：widthが指定されている場合はその範囲内で右揃え
      if ($width > 0) {
        $textWidth = $pdf->GetStringWidth($text);
        $x = $x + $width - $textWidth;
      } else {
        // widthが未指定の場合はテキストの右端をxに配置
        $textWidth = $pdf->GetStringWidth($text);
        $x = $x - $textWidth;
      }
    }
    // 左揃え（textAlign === 'left'）はそのまま

    $pdf->Text($x, $y, $text);
  }

  /**
   * 楕円を描画
   */
  protected function drawEllipseByKey(Fpdi $pdf, string $key): void
  {
    if (!isset($this->coordinates[$key])) {
      \Log::warning("drawEllipseByKey: キーが存在しない", ['key' => $key]);
      return;
    }

    $x = $this->coordinates[$key]['ellipseX'] ?? $this->coord($key, 'x');
    $y = $this->coordinates[$key]['ellipseY'] ?? $this->coord($key, 'y');
    $ellipseWidth = $this->coordinates[$key]['ellipseWidth'] ?? 2.5;
    $ellipseHeight = $this->coordinates[$key]['ellipseHeight'] ?? 2.5;
    $lineWidth = $this->coordinates[$key]['lineWidth'] ?? 0.4;

    \Log::info("drawEllipseByKey", [
      'key' => $key,
      'x' => $x,
      'y' => $y,
      'ellipseWidth' => $ellipseWidth,
      'ellipseHeight' => $ellipseHeight,
      'lineWidth' => $lineWidth,
      'has_ellipseX' => isset($this->coordinates[$key]['ellipseX']),
      'has_ellipseY' => isset($this->coordinates[$key]['ellipseY']),
      'sample_mode' => $this->sampleDataMode,
    ]);

    if ($x === null || $y === null) {
      \Log::warning("drawEllipseByKey: 座標がnull", ['key' => $key, 'x' => $x, 'y' => $y]);
      return;
    }

    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth($lineWidth);
    $pdf->Ellipse($x, $y, $ellipseWidth, $ellipseHeight, 0, 0, 360, 'D');
  }

  /**
   * 年齢を計算
   */
  protected function calculateAge(?string $birthday): ?int
  {
    if (empty($birthday)) {
      return null;
    }

    $birthDate = new \DateTime($birthday);
    $today = new \DateTime();
    $age = $today->diff($birthDate)->y;

    return $age;
  }

  /**
   * 和暦年月フォーマット（例：令和6年12月分）
   */
  protected function formatJapaneseYearMonth(string $yearMonth): string
  {
    $date = $yearMonth . '-01';
    $timestamp = strtotime($date);
    $year = (int)date('Y', $timestamp);
    $month = (int)date('n', $timestamp);

    $era = $this->getJapaneseEra($year, $month, 1);

    return $era['era'] . ' ' . $era['year'] . '年 ' . $month . '月分';
  }

  /**
   * 和暦日付フォーマット（例：令和6年12月15日）
   */
  protected function formatJapaneseDate(string $date): string
  {
    $timestamp = strtotime($date);
    $year = (int)date('Y', $timestamp);
    $month = (int)date('n', $timestamp);
    $day = (int)date('j', $timestamp);

    $era = $this->getJapaneseEra($year, $month, $day);

    return $era['era'] . ' ' . $era['year'] . '年 ' . $month . '月 ' . $day . '日';
  }

  /**
   * 和暦情報を取得
   */
  protected function getJapaneseEra(int $year, int $month, int $day): array
  {
    $date = sprintf('%04d%02d%02d', $year, $month, $day);

    if ($date >= '20190501') {
      return ['era' => '令和', 'year' => $year - 2018];
    } elseif ($date >= '19890108') {
      return ['era' => '平成', 'year' => $year - 1988];
    } elseif ($date >= '19261225') {
      return ['era' => '昭和', 'year' => $year - 1925];
    } elseif ($date >= '19120730') {
      return ['era' => '大正', 'year' => $year - 1911];
    } else {
      return ['era' => '明治', 'year' => $year - 1867];
    }
  }

  /**
   * サンプルテキストを描画（サンプルデータモード用）
   */
  protected function renderSampleTexts(Fpdi $pdf): void
  {
    // therapy_types関連のY座標計算用変数
    $therapyTypesBaseY = $this->coord('therapy_types', 'y');
    $verticalSpacing = $this->coord('therapy_types', 'verticalSpacing') ?? 5;

    // therapy関連フィールドのキー一覧
    $therapyRelatedKeys = [
      'therapy_counts',
      'therapy_unit_prices',
      'therapy_totals',
      'therapy_period_start',
      'therapy_period_end'
    ];

    foreach ($this->coordinates as $key => $field) {
      // customSampleDataに値がある場合はそれを使用
      if (isset($this->customSampleData[$key])) {
        $value = $this->customSampleData[$key];

        // clinic_phoneの場合は電話番号フォーマットを適用
        if ($key === 'clinic_phone') {
          $value = $this->formatPhoneNumber($value);
        }

        // remarksの場合は先頭に全角スペースを追加、末尾に"。"を追加
        if ($key === 'remarks' && !empty($value)) {
          $value = '　' . $value;
          if (mb_substr($value, -1) !== '。') {
            $value .= '。';
          }
        }

        // therapy関連フィールドの場合、Y座標をtherapy_typesから計算
        if (in_array($key, $therapyRelatedKeys) && $therapyTypesBaseY !== null) {
          // 元のY座標を一時保存
          $originalY = $this->coordinates[$key]['y'] ?? null;
          // therapy_typesと同じY座標を使用（verticalSpacingは行ごとに適用されるため、サンプルでは0行目として扱う）
          $this->coordinates[$key]['y'] = $therapyTypesBaseY;
          $this->drawText($pdf, $key, $value);
          // 元のY座標を復元
          if ($originalY !== null) {
            $this->coordinates[$key]['y'] = $originalY;
          }
        } else {
          $this->drawText($pdf, $key, $value);
        }
      }

      // type=selectのフィールドは楕円を描画（isSelectedがtrueの場合）
      if (isset($field['type']) && $field['type'] === 'select') {
        if (isset($field['isSelected']) && $field['isSelected']) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }

      // type=calendarのフィールドはサンプル日付で描画
      if (isset($field['type']) && $field['type'] === 'calendar') {
        $this->renderSampleCalendar($pdf, $key);
      }
    }
  }

  /**
   * サンプルカレンダーを描画
   */
  protected function renderSampleCalendar(Fpdi $pdf, string $key): void
  {
    if (!isset($this->coordinates[$key])) {
      return;
    }

    $baseX = $this->coord($key, 'x');
    $baseY = $this->coord($key, 'y');
    $circleSpacing = $this->coord($key, 'circleSpacing') ?? 4.91;
    $circleRadius = $this->coord($key, 'circleRadius') ?? 2.6;
    $innerRadius = $this->coord($key, 'doubleCircleInnerRadius') ?? 1.9;

    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.3);

    // サンプル日付: 1日, 5日, 10日, 15日, 20日
    $sampleDays = [
      1 => 1,   // 通院
      5 => 2,   // 往療
      10 => 1,  // 通院
      15 => 1,  // 通院
      20 => 2,  // 往療
    ];

    foreach ($sampleDays as $day => $category) {
      $x = $baseX + ($day - 1) * $circleSpacing;
      $y = $baseY;

      if ($category == 2) {
        // 二重円（◎）
        $pdf->Ellipse($x, $y, $circleRadius, $circleRadius, 0, 0, 360, 'D');
        $pdf->Ellipse($x, $y, $innerRadius, $innerRadius, 0, 0, 360, 'D');
      } else {
        // 単円（○）
        $pdf->Ellipse($x, $y, $circleRadius, $circleRadius, 0, 0, 360, 'D');
      }
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
