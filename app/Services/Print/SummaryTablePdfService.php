<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 総括表PDF生成サービス
 *
 * 指定サービス提供年月の施術件数・費用額を保険機関ごとに集計して出力する。
 * 保険機関（insurer）が複数存在する場合、保険機関ごとにページを生成する。
 */
class SummaryTablePdfService extends BasePdfService
{
  /**
   * 施術カテゴリ（'acupuncture' or 'massage'）
   */
  protected string $therapyType = 'acupuncture';

  /**
   * 施術カテゴリを設定
   */
  public function setTherapyType(string $type): void
  {
    $this->therapyType = $type;
  }

  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/summary_table_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    $configPath = storage_path('app/config/summary_table_coordinates.json');
    if (file_exists($configPath)) {
      $json = file_get_contents($configPath);
      return json_decode($json, true) ?? [];
    }
    return [];
  }

  /**
   * PDF生成
   *
   * @param array  $clinicUserIds    利用者ID配列（未使用：保険機関ベースで集計）
   * @param string $serviceYearMonth サービス提供年月 (YYYY-MM)
   * @param string $submissionDate   提出年月日 (YYYY-MM-DD)
   * @param string $remarks          備考（未使用）
   * @return string PDFバイナリデータ
   */
  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate, string $remarks = ''): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);

    if ($this->sampleDataMode) {
      // サンプルモード：ダミーデータで1ページ生成
      $this->addPage($pdf, $this->getSampleData($serviceYearMonth), $submissionDate);
    } else {
      // 実データモード：保険機関ごとにページ生成
      $insurerGroups = $this->fetchInsurerGroups($serviceYearMonth);

      if (empty($insurerGroups)) {
        // データなしの場合：集計情報は空、事業所情報等は描画
        $clinicInfo = DB::table('clinic_info')->first();
        $this->addPage($pdf, [
          'insurer_name'       => '',
          'service_year_month' => $serviceYearMonth,
          'clinic_info'        => $clinicInfo,
          'cost_summary'       => [],
        ], $submissionDate);
      } else {
        foreach ($insurerGroups as $insurerData) {
          $this->addPage($pdf, $insurerData, $submissionDate);
        }
      }
    }

    return $pdf->Output('', 'S');
  }

  /**
   * サービス提供年月に対応する保険機関ごとのデータを取得
   *
   * @param string $serviceYearMonth YYYY-MM
   * @return array 保険機関ごとのデータ配列
   */
  protected function fetchInsurerGroups(string $serviceYearMonth): array
  {
    // 施術カテゴリに応じた therapy_content_id を決定
    $therapyContentIds = $this->therapyType === 'massage'
      ? [18, 19, 20, 21]      // あんま・マッサージ（マッサージ/変形徒手矯正術/温罨法/温罨法･電気光線器具）
      : [11, 12, 13, 14, 15, 16];            // はり・きゅう

    // 対象月の施術実績を取得（利用者・保険情報JOIN）
    $records = DB::table('records')
      ->whereRaw("DATE_FORMAT(records.date, '%Y-%m') = ?", [$serviceYearMonth])
      ->whereIn('records.therapy_content_id', $therapyContentIds)
      ->leftJoin('insurances', 'records.clinic_user_id', '=', 'insurances.clinic_user_id')
      ->leftJoin('insurers', 'insurances.insurers_id', '=', 'insurers.id')
      ->leftJoin('expenses_borne_ratios', 'insurances.expenses_borne_ratio_id', '=', 'expenses_borne_ratios.id')
      ->select(
        'records.*',
        'insurances.expenses_borne_ratio_id',
        'insurances.insurers_id',
        'expenses_borne_ratios.expenses_borne_ratio',
        'insurers.insurer_name',
        'insurers.insurer_number'
      )
      ->get();

    if ($records->isEmpty()) {
      return [];
    }

    // 施術所情報
    $clinicInfo = DB::table('clinic_info')->first();

    // 施術料金データ（最新）
    $treatmentFees = DB::table('treatment_fees')
      ->orderBy('created_at', 'desc')
      ->first();

    // あんま・マッサージの場合、部位情報を一括取得（record_id => [bodypart_id, ...] のマップ）
    $bodypartsMap = [];
    if ($this->therapyType === 'massage') {
      $recordIds = $records->pluck('id')->toArray();
      $bodypartRows = DB::select(
        'SELECT `records_id`, `therapy_type_bodyparts_id` FROM `bodyparts-records` WHERE `records_id` IN (' . implode(',', array_fill(0, count($recordIds), '?')) . ')',
        $recordIds
      );
      foreach ($bodypartRows as $row) {
        $bodypartsMap[$row->records_id][] = $row->therapy_type_bodyparts_id;
      }
    }

    // 保険機関ごとにグループ化
    $grouped = [];
    foreach ($records as $record) {
      $insurerId = $record->insurers_id ?? 0;
      $key = $insurerId;

      if (!isset($grouped[$key])) {
        $grouped[$key] = [
          'insurer_name'   => $record->insurer_name ?? '不明',
          'insurer_id'     => $insurerId,
          'records'        => [],
          'clinic_info'    => $clinicInfo,
          'treatment_fees' => $treatmentFees,
          'service_year_month' => $serviceYearMonth,
        ];
      }
      $grouped[$key]['records'][] = $record;
    }

    // 各グループの費用額を計算
    $result = [];
    foreach ($grouped as $group) {
      $group['cost_summary'] = $this->calculateCostSummary($group['records'], $group['treatment_fees'], $bodypartsMap);
      $result[] = $group;
    }

    return $result;
  }

  /**
   * 支給割合区分ごとの施術件数・費用額を計算
   *
   * @param array       $records       施術実績レコード配列
   * @param object|null $treatmentFees 施術料金マスタ
   * @param array       $bodypartsMap  record_id => [bodypart_id, ...] のマップ（マッサージ用）
   * @return array [ ratio => [ count, cost, claim ], ... ] 降順ソート済み
   */
  protected function calculateCostSummary(array $records, ?object $treatmentFees, array $bodypartsMap = []): array
  {
    // 支給割合区分ごとに集計
    // expenses_borne_ratio（負担割合テキスト）→ 支給割合（10 - 負担割合）
    $byRatio = [];

    foreach ($records as $record) {
      $ratioText = (string)($record->expenses_borne_ratio ?? '3割');
      $bornePercent = 30; // デフォルト3割負担

      if (preg_match('/(\d+)割/', $ratioText, $matches)) {
        $bornePercent = (int)$matches[1] * 10;
      }

      $benefitPercent = 100 - $bornePercent; // 支給割合（%）

      if (!isset($byRatio[$benefitPercent])) {
        $byRatio[$benefitPercent] = [
          'benefit_percent' => $benefitPercent,
          'count'           => 0,
          'cost'            => 0,
        ];
      }

      $byRatio[$benefitPercent]['count']++;

      // 1件あたりの費用額を計算
      $bodypartIds = $bodypartsMap[$record->id] ?? [];
      $recordFee = $this->calculateRecordFee($record, $treatmentFees, $bodypartIds);
      $byRatio[$benefitPercent]['cost'] += $recordFee;
    }

    // 申請額 = 費用額 × 支給割合
    foreach ($byRatio as $percent => &$data) {
      $data['claim'] = (int)round($data['cost'] * $percent / 100);
    }
    unset($data);

    // 降順ソート（支給割合が高い順）
    krsort($byRatio);

    return array_values($byRatio);
  }

  /**
   * 1施術レコードの費用額を計算
   *
   * @param object      $record        施術実績
   * @param object|null $treatmentFees 施術料金マスタ
   * @param array       $bodypartIds   部位IDリスト（マッサージ用）
   * @return int 費用額
   */
  protected function calculateRecordFee(object $record, ?object $treatmentFees, array $bodypartIds = []): int
  {
    if (!$treatmentFees) {
      return 0;
    }

    $therapyContentId = (int)($record->therapy_content_id ?? 0);
    $housecallDistance = (float)($record->housecall_distance ?? 0);

    // はり・きゅう系
    $acupunctureMap = [
      11 => 'hari_normal',           // はり
      12 => 'kyu_normal',            // きゅう
      13 => 'hari_and_kyu_normal',   // はり・きゅう併用
      14 => 'hari_and_elec_needle_normal', // 電気鍼
      15 => 'kyu_and_elec_moxa_heater_normal', // 電気温灸器
      16 => 'fomentation_and_elec_ray_normal', // 電気光線器具
    ];

    // あんま・マッサージ系（マッサージ以外は部位不問）
    $massageMap = [
      19 => 'manual_correction_normal',        // 変形徒手矯正術
      20 => 'fomentation_normal',              // 温罨法
      21 => 'fomentation_and_elec_ray_normal', // 温罨法･電気光線器具
    ];

    // マッサージ部位（bodypart.id）→ treatment_fees カラム
    $massageBodypartFeeMap = [
      1 => 'massage_trunk_normal',       // trunk（体幹）
      2 => 'massage_upper_limb_r_normal', // upper_limb_r（上肢右）
      3 => 'massage_upper_limb_l_normal', // upper_limb_l（上肢左）
      4 => 'massage_lower_limb_r_normal', // lower_limb_r（下肢右）
      5 => 'massage_lower_limb_l_normal', // lower_limb_l（下肢左）
    ];

    $fee = 0;

    if (isset($acupunctureMap[$therapyContentId])) {
      $feeKey = $acupunctureMap[$therapyContentId];
      $fee = (int)($treatmentFees->$feeKey ?? 0);
    } elseif ($therapyContentId === 18) {
      // マッサージ：部位ごとの料金を加算
      foreach ($bodypartIds as $bodypartId) {
        if (isset($massageBodypartFeeMap[$bodypartId])) {
          $feeKey = $massageBodypartFeeMap[$bodypartId];
          $fee += (int)($treatmentFees->$feeKey ?? 0);
        }
      }
    } elseif (isset($massageMap[$therapyContentId])) {
      $feeKey = $massageMap[$therapyContentId];
      $fee = (int)($treatmentFees->$feeKey ?? 0);
    }

    // 往療料（保険適用内）
    if ($housecallDistance > 0) {
      if ($housecallDistance <= 4) {
        $fee += (int)($treatmentFees->housecall_max_2km_normal ?? 0);
      } else {
        $fee += (int)($treatmentFees->housecall_max_2km_normal ?? 0);
        $fee += (int)($treatmentFees->housecall_additional_max_4km_normal ?? 0);
      }
    }

    return $fee;
  }

  /**
   * PDFページを追加
   *
   * @param Fpdi       $pdf
   * @param array|null $data
   * @param string     $submissionDate
   */
  protected function addPage(Fpdi $pdf, ?array $data, string $submissionDate): void
  {
    $pdf->AddPage();

    // テンプレートPDF読み込み
    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/acupuncture_and_massage/総括表.pdf');

    if (file_exists($templatePath)) {
      try {
        $pageCount = $pdf->setSourceFile($templatePath);
        $tplId = $pdf->importPage(1);
        $pdf->useTemplate($tplId, 0, 0, null, null, true);
      } catch (\Exception $e) {
        \Log::warning('総括表テンプレート読み込みエラー', ['message' => $e->getMessage()]);
      }
    }

    $pdf->SetFont('kozminproregular', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    $this->fillFormFields($pdf, $data, $submissionDate);
  }

  /**
   * フォームフィールドを埋め込む
   *
   * @param Fpdi       $pdf
   * @param array|null $data
   * @param string     $submissionDate
   */
  protected function fillFormFields(Fpdi $pdf, ?array $data, string $submissionDate): void
  {
    $clinicInfo = $data['clinic_info'] ?? null;
    $serviceYearMonth = $data['service_year_month'] ?? date('Y-m');
    $costSummary = $data['cost_summary'] ?? [];
    $insurerName = $data['insurer_name'] ?? '';

    // 提出年月日を和暦変換
    $submissionDateObj = new \DateTime($submissionDate);
    $subYear  = (int)$submissionDateObj->format('Y');
    $subMonth = (int)$submissionDateObj->format('m');
    $subDay   = (int)$submissionDateObj->format('d');
    $subJa    = $this->convertToJapaneseYear($subYear, $subMonth);

    // サービス提供年月を和暦変換
    [$svcYear, $svcMonth] = explode('-', $serviceYearMonth);
    $svcJa = $this->convertToJapaneseYear((int)$svcYear, (int)$svcMonth);

    // 施術カテゴリ文字列
    $categoryText = $this->therapyType === 'massage'
      ? '（あんま･マッサージ）'
      : '（はり･きゅう）';

    // === 提出年月日 ===
    $submissionDateText = sprintf(
      '%s%d年 %d月 %d日',
      $subJa['era'],
      $subJa['year'],
      $subMonth,
      $subDay
    );
    $this->drawField($pdf, 'submission_date', $submissionDateText);

    // === サービス提供年月 ===
    $serviceYearMonthText = sprintf(
      '%s%d年 %d月分',
      $svcJa['era'],
      $svcJa['year'],
      (int)$svcMonth
    );
    $this->drawField($pdf, 'service_year_month', $serviceYearMonthText);

    // === 施術カテゴリ ===
    $this->drawField($pdf, 'therapy_category', $categoryText);

    // === 保険機関名 ===
    $this->drawField($pdf, 'insurer_name', $insurerName);

    // === 事業所情報（clinic_info参照） ===
    if ($clinicInfo) {
      // 郵便番号（XXX-XXXX形式）
      $postalCode = $clinicInfo->postal_code ?? '';
      if (strlen((string)$postalCode) === 7 && ctype_digit((string)$postalCode)) {
        $postalCode = substr($postalCode, 0, 3) . '-' . substr($postalCode, 3);
      }
      $this->drawField($pdf, 'clinic_postal_code', $postalCode);

      // 住所（maxCharsPerLineで折り返し）
      $address = ($clinicInfo->address_1 ?? '') . ($clinicInfo->address_2 ?? '') . ($clinicInfo->address_3 ?? '');
      $this->drawMultilineField($pdf, 'clinic_address', $address);

      // 事業所名
      $this->drawField($pdf, 'clinic_name', $clinicInfo->clinic_name ?? '');

      // 代表者氏名
      $ownerName = ($clinicInfo->owner_last_name ?? '') . ' ' . ($clinicInfo->owner_first_name ?? '');
      $this->drawField($pdf, 'clinic_owner_name', trim($ownerName));

      // 電話番号
      $phone = $clinicInfo->phone ?? $clinicInfo->cellphone ?? '';
      $this->drawField($pdf, 'clinic_phone', $this->formatPhoneNumber($phone));

      // 金融機関名（コード）
      $bankName = ($clinicInfo->bank_name ?? '') . '（' . ($clinicInfo->bank_code ?? '') . '）';
      $this->drawField($pdf, 'bank_name', $bankName);

      // 支店名（コード）
      $branchName = ($clinicInfo->bank_branch_name ?? '') . '（' . ($clinicInfo->bank_branch_code ?? '') . '）';
      $this->drawField($pdf, 'bank_branch_name', $branchName);

      // 預金種別
      $this->drawField($pdf, 'bank_account_type', $clinicInfo->bank_account_type ?? '');

      // 口座番号
      $this->drawField($pdf, 'bank_account_number', (string)($clinicInfo->bank_account_number ?? ''));

      // 口座名義（カナ優先）
      $accountName = $clinicInfo->bank_account_name_kana ?? $clinicInfo->bank_account_name ?? '';
      $this->drawField($pdf, 'bank_account_name', $accountName);
    }

    // === 支給割合区分・施術件数・費用額・申請額（複数行対応） ===
    $this->drawCostSummaryRows($pdf, $costSummary);
  }

  /**
   * 支給割合区分・施術件数・費用額・申請額を複数行描画
   *
   * @param Fpdi  $pdf
   * @param array $costSummary
   */
  protected function drawCostSummaryRows(Fpdi $pdf, array $costSummary): void
  {
    if (empty($costSummary)) {
      // サンプルデータまたはデータなし：ダミー行を描画
      if ($this->sampleDataMode) {
        $costSummary = [
          ['benefit_percent' => 90, 'count' => 5, 'cost' => 15000, 'claim' => 13500],
          ['benefit_percent' => 80, 'count' => 3, 'cost' => 9000,  'claim' => 7200],
        ];
      } else {
        return;
      }
    }

    // 行間（benefit_ratioフィールドのrowLineHeightプロパティから取得、デフォルト7mm）
    $rowLineHeight = $this->coord('benefit_ratio', 'rowLineHeight') ?: 7;

    $totalCount = 0;
    $totalCost  = 0;
    $totalClaim = 0;

    foreach ($costSummary as $index => $row) {
      $offsetY = $index * $rowLineHeight;

      // 支給割合区分（〇割）
      $ratioLabel = (int)($row['benefit_percent'] / 10) . '割';
      $this->drawFieldOffset($pdf, 'benefit_ratio', $ratioLabel, 0, $offsetY);

      // 施術件数
      $this->drawFieldOffset($pdf, 'treatment_count', (string)$row['count'], 0, $offsetY);

      // 費用額
      $this->drawFieldOffset($pdf, 'cost_amount', (string)$row['cost'], 0, $offsetY);

      // 申請額
      $this->drawFieldOffset($pdf, 'claim_amount', (string)$row['claim'], 0, $offsetY);

      $totalCount += $row['count'];
      $totalCost  += $row['cost'];
      $totalClaim += $row['claim'];
    }

    // 合計行
    $this->drawField($pdf, 'total_treatment_count', (string)$totalCount);
    $this->drawField($pdf, 'total_cost_amount',     (string)$totalCost);
    $this->drawField($pdf, 'total_claim_amount',    (string)$totalClaim);
  }

  /**
   * 座標キーでテキストを描画（フォントサイズ自動設定）
   *
   * @param Fpdi   $pdf
   * @param string $key
   * @param string $text
   */
  protected function drawField(Fpdi $pdf, string $key, string $text): void
  {
    if (!$this->hasCoord($key) || $text === '') {
      return;
    }

    $fontSize = $this->coord($key, 'fontSize') ?: 10;
    $pdf->SetFont('kozminproregular', '', $fontSize);
    $this->drawTextByKey($pdf, $key, $text);
  }

  /**
   * maxCharsPerLine・lineHeightを参照して複数行テキストを描画
   *
   * @param Fpdi   $pdf
   * @param string $key
   * @param string $text
   */
  protected function drawMultilineField(Fpdi $pdf, string $key, string $text): void
  {
    if (!$this->hasCoord($key) || $text === '') {
      return;
    }

    $x              = $this->coord($key, 'x');
    $y              = $this->coord($key, 'y');
    $fontSize       = $this->coord($key, 'fontSize') ?: 10;
    $lineHeight     = $this->coord($key, 'lineHeight') ?: 5;
    $maxCharsPerLine = $this->coord($key, 'maxCharsPerLine') ?: 20;

    $pdf->SetFont('kozminproregular', '', $fontSize);

    // 改行で分割した上で、各行が maxCharsPerLine を超える場合はさらに分割
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
   * 座標キーにY方向オフセットを加えてテキストを描画（複数行用）
   *
   * @param Fpdi   $pdf
   * @param string $key
   * @param string $text
   * @param float  $offsetX
   * @param float  $offsetY
   */
  protected function drawFieldOffset(Fpdi $pdf, string $key, string $text, float $offsetX, float $offsetY): void
  {
    if (!$this->hasCoord($key)) {
      return;
    }

    $x = $this->coord($key, 'x') + $offsetX;
    $y = $this->coord($key, 'y') + $offsetY;
    $fontSize = $this->coord($key, 'fontSize') ?: 10;
    $textAlign = $this->coord($key, 'textAlign') ?: 'left';
    $letterSpacing = $this->coord($key, 'letterSpacing') ?: 0;

    $pdf->SetFont('kozminproregular', '', $fontSize);

    if ($letterSpacing > 0) {
      $this->drawTextWithSpacing($pdf, $x, $y, $text, $letterSpacing, $textAlign, 210);
    } else {
      $textWidth = $pdf->GetStringWidth($text);
      $alignedX = $x;

      if ($textAlign === 'right') {
        $alignedX = $x - $textWidth;
      } elseif ($textAlign === 'center') {
        // drawTextByKey（BasePdfService）と同じロジック：X座標を左端として右端(210mm)までの範囲で中央配置
        $alignmentWidth = 210 - $x;
        $alignedX = $x + ($alignmentWidth - $textWidth) / 2;
      }

      $pdf->SetXY($alignedX, $y);
      $pdf->Cell(0, 0, $text, 0, 0, 'L', false);
    }
  }

  /**
   * サンプルデータを取得
   *
   * @param string $serviceYearMonth
   * @return array
   */
  protected function getSampleData(string $serviceYearMonth): array
  {
    [$year, $month] = explode('-', $serviceYearMonth);
    $japaneseYear = $this->convertToJapaneseYear((int)$year, (int)$month);

    return [
      'insurer_name'       => 'サンプル健康保険組合',
      'service_year_month' => $serviceYearMonth,
      'clinic_info'        => (object)[
        'postal_code'           => '1234567',
        'address_1'             => '東京都',
        'address_2'             => 'サンプル市サンプル町1-2-3',
        'address_3'             => '',
        'clinic_name'           => 'サンプル施術所',
        'owner_last_name'       => 'サンプル',
        'owner_first_name'      => '太郎',
        'phone'                 => '03-0000-0000',
        'bank_name'             => 'サンプル銀行',
        'bank_code'             => '0000',
        'bank_branch_name'      => 'サンプル支店',
        'bank_branch_code'      => '000',
        'bank_account_type'     => '普通',
        'bank_account_number'   => '0000000',
        'bank_account_name'     => 'サンプル タロウ',
        'bank_account_name_kana' => 'サンプル タロウ',
      ],
      'cost_summary' => [
        ['benefit_percent' => 90, 'count' => 5, 'cost' => 15000, 'claim' => 13500],
        ['benefit_percent' => 80, 'count' => 3, 'cost' => 9000,  'claim' => 7200],
        ['benefit_percent' => 70, 'count' => 2, 'cost' => 6000,  'claim' => 4200],
      ],
    ];
  }

  /**
   * 電話番号をXXX-XXXX-XXXX形式にフォーマット
   */
  protected function formatPhoneNumber(string $phone): string
  {
    $digitsOnly = preg_replace('/[^0-9]/', '', $phone);

    if (empty($digitsOnly)) {
      return '';
    }

    // 10桁: 03始まりは2-4-4、それ以外は3-3-4
    if (strlen($digitsOnly) === 10) {
      if (substr($digitsOnly, 0, 2) === '03') {
        return substr($digitsOnly, 0, 2) . ' - ' . substr($digitsOnly, 2, 4) . ' - ' . substr($digitsOnly, 6);
      }
      return substr($digitsOnly, 0, 3) . ' - ' . substr($digitsOnly, 3, 3) . ' - ' . substr($digitsOnly, 6);
    }

    // 11桁: 3-4-4
    if (strlen($digitsOnly) === 11) {
      return substr($digitsOnly, 0, 3) . ' - ' . substr($digitsOnly, 3, 4) . ' - ' . substr($digitsOnly, 7);
    }

    return $phone;
  }
}
