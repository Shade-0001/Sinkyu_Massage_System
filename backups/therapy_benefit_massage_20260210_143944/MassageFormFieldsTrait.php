<?php

namespace App\Services\Print\Traits;

use Illuminate\Support\Facades\DB;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * あんま・マッサージ療養費支給申請書PDF - フォームフィールド関連メソッド
 */
trait MassageFormFieldsTrait
{
  /**
   * 施術期間セクションを埋める（開始日、終了日、実日数）
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param \Illuminate\Support\Collection $records 施術記録
   * @param string $serviceYearMonth サービス提供年月
   */
  private function fillTreatmentPeriodSection(Fpdi $pdf, \Illuminate\Support\Collection $records, string $serviceYearMonth): void
  {
    // === 施術期間 ===
    if ($this->sampleDataMode && $this->customSampleData) {
      // サンプルデータモード：customSampleDataから取得
      $startYear = $this->customSampleData['treatment_start_year'] ?? '';
      $startMonth = $this->customSampleData['treatment_start_month'] ?? '';
      $startDay = $this->customSampleData['treatment_start_day'] ?? '';
      $endYear = $this->customSampleData['treatment_end_year'] ?? '';
      $endMonth = $this->customSampleData['treatment_end_month'] ?? '';
      $endDay = $this->customSampleData['treatment_end_day'] ?? '';
      $treatmentDays = $this->customSampleData['treatment_days'] ?? '';

      // 自：開始日
      if ($startYear && $this->hasCoord('treatment_start_year')) {
        $pdf->SetFontSize($this->coord('treatment_start_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_start_year', (string)$startYear);
      }

      if ($startMonth && $this->hasCoord('treatment_start_month')) {
        $pdf->SetFontSize($this->coord('treatment_start_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_start_month', (string)$startMonth);
      }

      if ($startDay && $this->hasCoord('treatment_start_day')) {
        $pdf->SetFontSize($this->coord('treatment_start_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_start_day', (string)$startDay);
      }

      // 至：終了日
      if ($endYear && $this->hasCoord('treatment_end_year')) {
        $pdf->SetFontSize($this->coord('treatment_end_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_end_year', (string)$endYear);
      }

      if ($endMonth && $this->hasCoord('treatment_end_month')) {
        $pdf->SetFontSize($this->coord('treatment_end_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_end_month', (string)$endMonth);
      }

      if ($endDay && $this->hasCoord('treatment_end_day')) {
        $pdf->SetFontSize($this->coord('treatment_end_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_end_day', (string)$endDay);
      }

      // 実日数
      if ($treatmentDays && $this->hasCoord('treatment_days')) {
        $pdf->SetFontSize($this->coord('treatment_days', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_days', (string)$treatmentDays);
      }

      $pdf->SetFontSize(10);
    } elseif ($records->isNotEmpty()) {
      // 通常モード：実データから取得
      $firstDate = $records->first()->date;
      $lastDate = $records->last()->date;

      [$startYear, $startMonth, $startDay] = explode('-', $firstDate);
      [$endYear, $endMonth, $endDay] = explode('-', $lastDate);

      $startJapaneseYear = $this->convertToJapaneseYear((int)$startYear, (int)$startMonth);
      $endJapaneseYear = $this->convertToJapaneseYear((int)$endYear, (int)$endMonth);

      // 自：開始日
      if ($this->hasCoord('treatment_start_year')) {
        $pdf->SetFontSize($this->coord('treatment_start_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_start_year', (string)$startJapaneseYear['year']);
      }

      if ($this->hasCoord('treatment_start_month')) {
        $pdf->SetFontSize($this->coord('treatment_start_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_start_month', (string)(int)$startMonth);
      }

      if ($this->hasCoord('treatment_start_day')) {
        $pdf->SetFontSize($this->coord('treatment_start_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_start_day', (string)(int)$startDay);
      }

      // 至：終了日
      if ($this->hasCoord('treatment_end_year')) {
        $pdf->SetFontSize($this->coord('treatment_end_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_end_year', (string)$endJapaneseYear['year']);
      }

      if ($this->hasCoord('treatment_end_month')) {
        $pdf->SetFontSize($this->coord('treatment_end_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_end_month', (string)(int)$endMonth);
      }

      if ($this->hasCoord('treatment_end_day')) {
        $pdf->SetFontSize($this->coord('treatment_end_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_end_day', (string)(int)$endDay);
      }

      if ($this->hasCoord('treatment_days')) {
        $pdf->SetFontSize($this->coord('treatment_days', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_days', (string)$records->count());
      }

      $pdf->SetFontSize(10);
    }

  }
  /**
   * 傷病名・症状（施術内容欄）セクションを埋める
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param object|null $consent 同意書データ
   */
  private function fillInjuryIllnessSection(Fpdi $pdf, $consent): void
  {
    // === 傷病名・症状（施術内容欄） ===
    // マッサージ版はサークルではなくテキストフィールド
    if ($this->sampleDataMode) {
      if (isset($this->customSampleData['illness_name_symptom']) && $this->hasCoord('illness_name_symptom')) {
        $pdf->SetFontSize($this->coord('illness_name_symptom', 'fontSize'));
        $this->drawTextByKey($pdf, 'illness_name_symptom', (string)$this->customSampleData['illness_name_symptom']);
        $pdf->SetFontSize(10);
      }
    } elseif ($consent) {
      // 実データモード：consents_massageの症状フラグからテキストを生成
      $symptomTexts = [];
      
      if (isset($consent->is_symptom_1) && $consent->is_symptom_1) {
        $symptomTexts[] = '神経痛';
      }
      
      if (isset($consent->is_symptom_2) && $consent->is_symptom_2) {
        $text = 'リウマチ';
        if (isset($consent->symtom_2_addendum) && $consent->symtom_2_addendum) {
          $text .= '（' . $consent->symtom_2_addendum . '）';
        }
        $symptomTexts[] = $text;
      }
      
      if (isset($consent->is_symptom_3) && $consent->is_symptom_3) {
        $text = 'その他';
        if (isset($consent->symtom_3_addendum) && $consent->symtom_3_addendum) {
          $text .= '（' . $consent->symtom_3_addendum . '）';
        }
        $symptomTexts[] = $text;
      }
      
      if (!empty($symptomTexts) && $this->hasCoord('illness_name_symptom')) {
        $symptomText = implode('、', $symptomTexts);
        $pdf->SetFontSize($this->coord('illness_name_symptom', 'fontSize'));
        $this->drawTextByKey($pdf, 'illness_name_symptom', $symptomText);
        $pdf->SetFontSize(10);
      }
    }

  }
  /**
   * 発病負傷年月日セクションを埋める
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param object|null $consent 同意書データ
   */
  private function fillOnsetDateSection(Fpdi $pdf, $consent): void
  {
    // === 発病負傷年月日 ===
    if ($this->sampleDataMode && isset($this->customSampleData['onset_date_year'])) {
      if ($this->hasCoord('onset_date_year')) {
        $pdf->SetFontSize($this->coord('onset_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date_year', (string)$this->customSampleData['onset_date_year']);
      }
      if ($this->hasCoord('onset_date_month')) {
        $pdf->SetFontSize($this->coord('onset_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date_month', (string)$this->customSampleData['onset_date_month']);
      }
      if ($this->hasCoord('onset_date_day')) {
        $pdf->SetFontSize($this->coord('onset_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date_day', (string)$this->customSampleData['onset_date_day']);
      }
      $pdf->SetFontSize(10);
    } elseif ($consent && isset($consent->onset_and_injury_date)) {
      [$onsetYear, $onsetMonth, $onsetDay] = explode('-', $consent->onset_and_injury_date);
      $onsetJapaneseYear = $this->convertToJapaneseYear((int)$onsetYear, (int)$onsetMonth);
      
      if ($this->hasCoord('onset_date_year')) {
        $pdf->SetFontSize($this->coord('onset_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date_year', (string)$onsetJapaneseYear['year']);
      }
      if ($this->hasCoord('onset_date_month')) {
        $pdf->SetFontSize($this->coord('onset_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date_month', (string)(int)$onsetMonth);
      }
      if ($this->hasCoord('onset_date_day')) {
        $pdf->SetFontSize($this->coord('onset_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date_day', (string)(int)$onsetDay);
      }
      $pdf->SetFontSize(10);
    }

  }
  /**
   * 発病負傷の傷病名セクションを埋める
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param object|null $consent 同意書データ
   */
  private function fillOnsetIllnessNameSection(Fpdi $pdf, $consent): void
  {
    // === 発病負傷の傷病名 ===
    if ($this->sampleDataMode && isset($this->customSampleData['onset_illness_name'])) {
      if ($this->hasCoord('onset_illness_name') && $this->customSampleData['onset_illness_name']) {
        $pdf->SetFontSize($this->coord('onset_illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_illness_name', (string)$this->customSampleData['onset_illness_name']);
        $pdf->SetFontSize(10);
      }
    } elseif ($consent && isset($consent->illness_name)) {
      if ($this->hasCoord('onset_illness_name') && $consent->illness_name) {
        $pdf->SetFontSize($this->coord('onset_illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_illness_name', (string)$consent->illness_name);
        $pdf->SetFontSize(10);
      }
    }

  }
  /**
   * 初療年月日セクションを埋める
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param object|null $consent 同意書データ
   */
  private function fillInitialTreatmentDateSection(Fpdi $pdf, $consent): void
  {
    // === 初療年月日 ===
    if ($this->sampleDataMode && $this->customSampleData) {
      // サンプルデータモード：customSampleDataから取得
      $firstEra = $this->customSampleData['first_treatment_era'] ?? '';
      $firstYear = $this->customSampleData['first_treatment_year'] ?? '';
      $firstMonth = $this->customSampleData['first_treatment_month'] ?? '';
      $firstDay = $this->customSampleData['first_treatment_day'] ?? '';

      if ($firstEra && $this->hasCoord('first_treatment_era')) {
        $pdf->SetFontSize($this->coord('first_treatment_era', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_era', (string)$firstEra);
      }

      if ($firstYear && $this->hasCoord('first_treatment_year')) {
        $pdf->SetFontSize($this->coord('first_treatment_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_year', (string)$firstYear);
      }

      if ($firstMonth && $this->hasCoord('first_treatment_month')) {
        $pdf->SetFontSize($this->coord('first_treatment_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_month', (string)$firstMonth);
      }

      if ($firstDay && $this->hasCoord('first_treatment_day')) {
        $pdf->SetFontSize($this->coord('first_treatment_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_day', (string)$firstDay);
      }

      $pdf->SetFontSize(10);
    } elseif ($consent && isset($consent->first_care_date)) {
      // 通常モード：実データから取得
      [$firstYear, $firstMonth, $firstDay] = explode('-', $consent->first_care_date);
      $firstJapaneseYear = $this->convertToJapaneseYear((int)$firstYear, (int)$firstMonth);

      if ($this->hasCoord('first_treatment_era')) {
        $pdf->SetFontSize($this->coord('first_treatment_era', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_era', $firstJapaneseYear['era']);
      }

      if ($this->hasCoord('first_treatment_year')) {
        $pdf->SetFontSize($this->coord('first_treatment_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_year', (string)$firstJapaneseYear['year']);
      }

      if ($this->hasCoord('first_treatment_month')) {
        $pdf->SetFontSize($this->coord('first_treatment_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_month', (string)(int)$firstMonth);
      }

      if ($this->hasCoord('first_treatment_day')) {
        $pdf->SetFontSize($this->coord('first_treatment_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_day', (string)(int)$firstDay);
      }

      $pdf->SetFontSize(10);
    }

  }
  /**
   * 実日数セクションを埋める
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param array $records 施術記録
   */
  private function fillTreatmentDayCountSection(Fpdi $pdf, \Illuminate\Support\Collection $records): void
  {
    // === 実日数 ===
    if ($this->hasCoord('treatment_day_count')) {
      if ($this->sampleDataMode && isset($this->customSampleData['treatment_days'])) {
        $dayCount = $this->customSampleData['treatment_days'];
      } else {
        // マッサージ関連の施術（therapy_content_id: 18-21）のみカウント
        $massageContentIds = [18, 19, 20, 21];
        $dayCount = $records->filter(function ($record) use ($massageContentIds) {
          return in_array($record->therapy_content_id ?? null, $massageContentIds);
        })->count();
      }

      $pdf->SetFontSize($this->coord('treatment_day_count', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_day_count', (string)$dayCount);
      $pdf->SetFontSize(10);
    }

  }
  /**
   * 請求区分セクションを埋める（新規・継続、転帰、業務上・外・第三者行為）
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param object|null $consent 同意書データ
   */
  private function fillBillingCategorySection(Fpdi $pdf, $consent): void
  {
    // === 請求区分（新規・継続） ===
    if ($this->sampleDataMode) {
      if (isset($this->coordinates['bill_category_new']['isSelected']) && $this->coordinates['bill_category_new']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'bill_category_new');
      } elseif (isset($this->coordinates['bill_category_continued']['isSelected']) && $this->coordinates['bill_category_continued']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'bill_category_continued');
      } elseif (isset($this->customSampleData['bill_category'])) {
        if ($this->customSampleData['bill_category'] === '新規') {
          $this->drawEllipseByKey($pdf, 'bill_category_new');
        } elseif ($this->customSampleData['bill_category'] === '継続') {
          $this->drawEllipseByKey($pdf, 'bill_category_continued');
        }
      }
    } elseif ($consent && isset($consent->bill_category)) {
      if ($consent->bill_category === '新規') {
        $this->drawEllipseByKey($pdf, 'bill_category_new');
      } elseif ($consent->bill_category === '継続') {
        $this->drawEllipseByKey($pdf, 'bill_category_continued');
      }
    }

    // === 転帰 ===
    if ($this->sampleDataMode) {
      if (isset($this->coordinates['outcome_continued']['isSelected']) && $this->coordinates['outcome_continued']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'outcome_continued');
      } elseif (isset($this->coordinates['outcome_cured']['isSelected']) && $this->coordinates['outcome_cured']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'outcome_cured');
      } elseif (isset($this->coordinates['outcome_discontinued']['isSelected']) && $this->coordinates['outcome_discontinued']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'outcome_discontinued');
      } elseif (isset($this->coordinates['outcome_transferred']['isSelected']) && $this->coordinates['outcome_transferred']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'outcome_transferred');
      } elseif (isset($this->customSampleData['outcome'])) {
        $outcomeMap = [
          '継続' => 'outcome_continued',
          '治癒' => 'outcome_cured',
          '中止' => 'outcome_discontinued',
          '転医' => 'outcome_transferred',
        ];
        $key = $outcomeMap[$this->customSampleData['outcome']] ?? null;
        if ($key) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }
    } elseif ($consent && isset($consent->outcome)) {
      $outcomeMap = [
        '継続' => 'outcome_continued',
        '治癒' => 'outcome_cured',
        '中止' => 'outcome_discontinued',
        '転医' => 'outcome_transferred',
      ];
      $key = $outcomeMap[$consent->outcome] ?? null;
      if ($key) {
        $this->drawEllipseByKey($pdf, $key);
      }
    }

    // === 業務上・外・第三者行為 ===
    if ($this->sampleDataMode) {
      if (isset($this->coordinates['work_scope_type_1']['isSelected']) && $this->coordinates['work_scope_type_1']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'work_scope_type_1');
      } elseif (isset($this->coordinates['work_scope_type_2']['isSelected']) && $this->coordinates['work_scope_type_2']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'work_scope_type_2');
      } elseif (isset($this->coordinates['work_scope_type_3']['isSelected']) && $this->coordinates['work_scope_type_3']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'work_scope_type_3');
      } elseif (isset($this->customSampleData['work_scope_type'])) {
        $workScopeMap = [
          '業務上' => 'work_scope_type_1',
          '第三者行為' => 'work_scope_type_2',
          'その他' => 'work_scope_type_3',
        ];
        $key = $workScopeMap[$this->customSampleData['work_scope_type']] ?? null;
        if ($key) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }
    } elseif ($consent && isset($consent->work_scope_type)) {
      $workScopeMap = [
        '業務上' => 'work_scope_type_1',
        '第三者行為' => 'work_scope_type_2',
        'その他' => 'work_scope_type_3',
      ];
      $key = $workScopeMap[$consent->work_scope_type] ?? null;
      if ($key) {
        $this->drawEllipseByKey($pdf, $key);
      }
    }

  }
  /**
   * 発病負傷の原因･経過、施術月、傷病名セクションを埋める
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param object|null $consent 同意書データ
   * @param int $month 月
   */
  private function fillConditionAndMonthSection(Fpdi $pdf, $consent, int $month): void
  {
    // === 発病負傷の原因･経過 ===
    if ($this->sampleDataMode && isset($this->customSampleData['condition'])) {
      if ($this->hasCoord('condition') && $this->customSampleData['condition']) {
        $pdf->SetFontSize($this->coord('condition', 'fontSize'));
        $this->drawTextByKey($pdf, 'condition', (string)$this->customSampleData['condition']);
        $pdf->SetFontSize(10);
      }
    } elseif ($consent && isset($consent->condition_name)) {
      if ($this->hasCoord('condition') && $consent->condition_name) {
        $pdf->SetFontSize($this->coord('condition', 'fontSize'));
        $this->drawTextByKey($pdf, 'condition', (string)$consent->condition_name);
        $pdf->SetFontSize(10);
      }
    }

    // === 施術月 ===
    if ($this->hasCoord('treatment_month')) {
      $treatmentMonth = $this->sampleDataMode && isset($this->customSampleData['treatment_month'])
        ? $this->customSampleData['treatment_month']
        : (int)$month;
      
      $pdf->SetFontSize($this->coord('treatment_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_month', (string)$treatmentMonth);
      $pdf->SetFontSize(10);
    }

    // === 傷病名・症状（マッサージ用） ===
    if ($this->sampleDataMode && isset($this->customSampleData['illness_name_symptom'])) {
      if ($this->hasCoord('illness_name_symptom') && $this->customSampleData['illness_name_symptom']) {
        $pdf->SetFontSize($this->coord('illness_name_symptom', 'fontSize'));
        $this->drawTextByKey($pdf, 'illness_name_symptom', (string)$this->customSampleData['illness_name_symptom']);
        $pdf->SetFontSize(10);
      }
    }

    // === 傷病名（施術内容欄のチェックボックス） ===
    if ($this->hasCoord('treatment_month_label_1')) {
      $pdf->SetFontSize($this->coord('treatment_month_label_1', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_month_label_1', '月');
      $pdf->SetFontSize(10);
    }
    if ($this->hasCoord('treatment_month_label_2')) {
      $pdf->SetFontSize($this->coord('treatment_month_label_2', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_month_label_2', '日');
      $pdf->SetFontSize(10);
    }

  }
  /**
   * 基本情報セクション（タイトル年月）を埋める
   */
  protected function fillBasicInfoSection(Fpdi $pdf, array $data): void
  {
    // サービス提供年月を分解
    [$year, $month] = explode('-', $data['service_year_month']);
    $japaneseYear = $this->convertToJapaneseYear($year, $month);

    // === 上部：年月 ===
    $pdf->SetFontSize($this->coord('title_year_era', 'fontSize'));
    $this->drawTextByKey($pdf, 'title_year_era', (string)$japaneseYear['era']);
    $pdf->SetFontSize($this->coord('title_year_number', 'fontSize'));
    $this->drawTextByKey($pdf, 'title_year_number', (string)$japaneseYear['year']);
    $pdf->SetFontSize($this->coord('title_month', 'fontSize'));
    $this->drawTextByKey($pdf, 'title_month', (string)(int)$month);
  }
  /**
   * 公費情報セクション（機関コード、公費負担者番号等）を埋める
   */
  protected function fillPublicFundsSection(Fpdi $pdf, $insurance, $clinicInfo): void
  {
    // === 機関コード ===
    if ($this->sampleDataMode && isset($this->customSampleData['institution_code'])) {
      $pdf->SetFontSize($this->coord('institution_code', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'institution_code', (string)$this->customSampleData['institution_code'], 7, 5.6);
      $pdf->SetFontSize(10);
    } elseif ($clinicInfo && isset($clinicInfo->medical_institution_number)) {
      $pdf->SetFontSize($this->coord('institution_code', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'institution_code', (string)$clinicInfo->medical_institution_number, 7, 5.6);
      $pdf->SetFontSize(10);
    }

    // === 公費負担者番号 ===
    if ($this->sampleDataMode && isset($this->customSampleData['public_funds_payer_number'])) {
      if ($this->customSampleData['public_funds_payer_number']) {
        $pdf->SetFontSize($this->coord('public_funds_payer_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'public_funds_payer_number', (string)$this->customSampleData['public_funds_payer_number']);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->public_funds_payer_code) && $insurance->public_funds_payer_code) {
      $pdf->SetFontSize($this->coord('public_funds_payer_number', 'fontSize'));
      $this->drawTextByKey($pdf, 'public_funds_payer_number', (string)$insurance->public_funds_payer_code);
      $pdf->SetFontSize(10);
    }

    // === 公費受給者番号 ===
    if ($this->sampleDataMode && isset($this->customSampleData['public_funds_recipient_number'])) {
      if ($this->customSampleData['public_funds_recipient_number']) {
        $pdf->SetFontSize($this->coord('public_funds_recipient_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'public_funds_recipient_number', (string)$this->customSampleData['public_funds_recipient_number']);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->public_funds_recipient_code) && $insurance->public_funds_recipient_code) {
      $pdf->SetFontSize($this->coord('public_funds_recipient_number', 'fontSize'));
      $this->drawTextByKey($pdf, 'public_funds_recipient_number', (string)$insurance->public_funds_recipient_code);
      $pdf->SetFontSize(10);
    }

    // === 区市町村番号 ===
    if ($this->sampleDataMode && isset($this->customSampleData['locality_code'])) {
      if ($this->customSampleData['locality_code']) {
        $pdf->SetFontSize($this->coord('locality_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'locality_code', (string)$this->customSampleData['locality_code']);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->locality_code) && $insurance->locality_code) {
      $pdf->SetFontSize($this->coord('locality_code', 'fontSize'));
      $this->drawTextByKey($pdf, 'locality_code', (string)$insurance->locality_code);
      $pdf->SetFontSize(10);
    }

    // === 受給者番号 ===
    if ($this->sampleDataMode && isset($this->customSampleData['recipient_number'])) {
      if ($this->customSampleData['recipient_number']) {
        $pdf->SetFontSize($this->coord('recipient_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'recipient_number', (string)$this->customSampleData['recipient_number']);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->recipient_code) && $insurance->recipient_code) {
      $pdf->SetFontSize($this->coord('recipient_number', 'fontSize'));
      $this->drawTextByKey($pdf, 'recipient_number', (string)$insurance->recipient_code);
      $pdf->SetFontSize(10);
    }
  }
  /**
   * 保険情報セクションを埋める
   */
  protected function fillInsuranceSection(Fpdi $pdf, $insurance): void
  {
    // === 保険種別１ ===
    if ($insurance && isset($insurance->insurance_type_1)) {
      $insuranceType1Map = [
        '社･国･組' => 'insurance_type_1_shakoku',
        '公費' => 'insurance_type_1_kouhi',
        '後期' => 'insurance_type_1_kouki',
        '退職' => 'insurance_type_1_taishoku',
      ];
      $key = $insuranceType1Map[$insurance->insurance_type_1] ?? null;
      if ($key) {
        $this->drawEllipseByKey($pdf, $key);
      }
    }

    // === 保険種別３ ===
    if ($insurance && isset($insurance->insurance_type_3)) {
      $insuranceType3Map = [
        '本外' => 'insurance_type_3_hongai',
        '三外' => 'insurance_type_3_sangai',
        '家外' => 'insurance_type_3_kagai',
        '高外９' => 'insurance_type_3_kougai9',
        '高外８' => 'insurance_type_3_kougai8',
      ];
      $key = $insuranceType3Map[$insurance->insurance_type_3] ?? null;
      if ($key) {
        $this->drawEllipseByKey($pdf, $key);
      }
    }

    // === 給付割合（楕円） ===
    $benefitRatioKey = null;

    // isSelectedフラグをチェック（座標調整モードの場合）
    if (isset($this->coordinates['benefit_ratio_80']['isSelected']) && $this->coordinates['benefit_ratio_80']['isSelected']) {
      $benefitRatioKey = 'benefit_ratio_80';
    } elseif (isset($this->coordinates['benefit_ratio_90']['isSelected']) && $this->coordinates['benefit_ratio_90']['isSelected']) {
      $benefitRatioKey = 'benefit_ratio_90';
    } elseif (isset($this->coordinates['benefit_ratio_100']['isSelected']) && $this->coordinates['benefit_ratio_100']['isSelected']) {
      $benefitRatioKey = 'benefit_ratio_100';
    } elseif ($insurance) {
      // 保険種別１と保険種別３の組み合わせで給付割合を決定
      $type1 = $insurance->insurance_type_1 ?? '';
      $type3 = $insurance->insurance_type_3 ?? '';

      // 社国 + 高外９ → 8割
      if ($type1 === '社･国･組' && $type3 === '高外９') {
        $benefitRatioKey = 'benefit_ratio_80';
      }
      // 社国 + 三外 → 8割
      elseif ($type1 === '社･国･組' && $type3 === '三外') {
        $benefitRatioKey = 'benefit_ratio_80';
      }
      // 退職 + 六外 → 8割（六外は現状定義されていないため、将来対応）
      elseif ($type1 === '退職' && $type3 === '六外') {
        $benefitRatioKey = 'benefit_ratio_80';
      }
      // 後高 + 高外９ → 9割
      elseif ($type1 === '後期' && $type3 === '高外９') {
        $benefitRatioKey = 'benefit_ratio_90';
      }
      // 上記に該当しない場合、expenses_borne_ratio_idから判定
      elseif (isset($insurance->expenses_borne_ratio_id)) {
        // expenses_borne_ratiosテーブル: id=1→1割負担（9割給付）, id=2→2割負担（8割給付）, id=3→3割負担（7割給付）
        $ratioMap = [
          1 => 'benefit_ratio_90',  // 1割負担 → 9割給付
          2 => 'benefit_ratio_80',  // 2割負担 → 8割給付
          // id=3（3割負担、7割給付）は給付割合フィールドなし
        ];
        $benefitRatioKey = $ratioMap[$insurance->expenses_borne_ratio_id] ?? null;
      }
    }

    if ($benefitRatioKey) {
      $this->drawEllipseByKey($pdf, $benefitRatioKey);
    }

    // === 一部負担金（楕円） ===
    $expensesBorneRatioKey = null;

    \Log::info('[一部負担金] 処理開始', [
      'expenses_borne_ratio_id' => $insurance->expenses_borne_ratio_id ?? 'なし',
      'expenses_borne_ratio' => $insurance->expenses_borne_ratio ?? 'なし',
      'sampleDataMode' => $this->sampleDataMode ?? false,
      'isSelected_10' => isset($this->coordinates['expenses_borne_ratio_10']['isSelected']) ? $this->coordinates['expenses_borne_ratio_10']['isSelected'] : 'なし',
      'isSelected_20' => isset($this->coordinates['expenses_borne_ratio_20']['isSelected']) ? $this->coordinates['expenses_borne_ratio_20']['isSelected'] : 'なし',
      'isSelected_30' => isset($this->coordinates['expenses_borne_ratio_30']['isSelected']) ? $this->coordinates['expenses_borne_ratio_30']['isSelected'] : 'なし',
    ]);

    // 優先順位1: isSelectedフラグをチェック（座標調整UIでの選択状態）
    if (isset($this->coordinates['expenses_borne_ratio_10']['isSelected']) && $this->coordinates['expenses_borne_ratio_10']['isSelected']) {
      $expensesBorneRatioKey = 'expenses_borne_ratio_10';
      \Log::info('[一部負担金] isSelectedフラグ（1割）を検出');
    } elseif (isset($this->coordinates['expenses_borne_ratio_20']['isSelected']) && $this->coordinates['expenses_borne_ratio_20']['isSelected']) {
      $expensesBorneRatioKey = 'expenses_borne_ratio_20';
      \Log::info('[一部負担金] isSelectedフラグ（2割）を検出');
    } elseif (isset($this->coordinates['expenses_borne_ratio_30']['isSelected']) && $this->coordinates['expenses_borne_ratio_30']['isSelected']) {
      $expensesBorneRatioKey = 'expenses_borne_ratio_30';
      \Log::info('[一部負担金] isSelectedフラグ（3割）を検出');
    }
    // 優先順位2: 保険データから取得（ノーマルモード）
    elseif ($insurance) {
      // expenses_borne_ratio_idが存在する場合（推奨）
      if (isset($insurance->expenses_borne_ratio_id) && $insurance->expenses_borne_ratio_id) {
        // expenses_borne_ratios: id=1→1割, id=2→2割, id=3→3割
        $expensesBorneRatioIdMap = [
          1 => 'expenses_borne_ratio_10',
          2 => 'expenses_borne_ratio_20',
          3 => 'expenses_borne_ratio_30',
        ];
        $expensesBorneRatioKey = $expensesBorneRatioIdMap[$insurance->expenses_borne_ratio_id] ?? null;
        \Log::info('[一部負担金] expenses_borne_ratio_idから取得', ['id' => $insurance->expenses_borne_ratio_id, 'key' => $expensesBorneRatioKey]);
      }
      // expenses_borne_ratioから判定（フォールバック：サンプルデータまたはJOINで取得された文字列）
      elseif (isset($insurance->expenses_borne_ratio) && $insurance->expenses_borne_ratio) {
        $ratioValue = (string)$insurance->expenses_borne_ratio;
        \Log::info('[一部負担金] expenses_borne_ratioから取得', ['original' => $ratioValue]);

        // 「１割」「1割」→10、「２割」「2割」→20、「３割」「3割」→30
        if ($ratioValue === '１割' || $ratioValue === '1割') $ratioValue = '10';
        elseif ($ratioValue === '２割' || $ratioValue === '2割') $ratioValue = '20';
        elseif ($ratioValue === '３割' || $ratioValue === '3割') $ratioValue = '30';

        $expensesBorneRatioMap = [
          '10' => 'expenses_borne_ratio_10',
          '20' => 'expenses_borne_ratio_20',
          '30' => 'expenses_borne_ratio_30',
        ];
        $expensesBorneRatioKey = $expensesBorneRatioMap[$ratioValue] ?? null;
        \Log::info('[一部負担金] 変換後', ['converted' => $ratioValue, 'key' => $expensesBorneRatioKey]);
      }
    }

    // 楕円描画
    if ($expensesBorneRatioKey) {
      \Log::info('[一部負担金] 楕円描画実行', ['key' => $expensesBorneRatioKey]);
      $this->drawEllipseByKey($pdf, $expensesBorneRatioKey);
    } else {
      \Log::warning('[一部負担金] 描画キーが決定されませんでした');
    }

    // === 保険者番号 ===
    if ($insurance && isset($insurance->insurer_number) && $insurance->insurer_number) {
      $pdf->SetFontSize($this->coord('insurer_number', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'insurer_number', $insurance->insurer_number, 8, 5.6);
      $pdf->SetFontSize(10);
    }

    // === 被保険者記号 ===
    if ($this->sampleDataMode && isset($this->customSampleData['insurance_symbol_code'])) {
      if ($this->customSampleData['insurance_symbol_code']) {
        $pdf->SetFontSize($this->coord('insurance_symbol_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'insurance_symbol_code', (string)$this->customSampleData['insurance_symbol_code']);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->code_number) && $insurance->code_number) {
      $pdf->SetFontSize($this->coord('insurance_symbol_code', 'fontSize'));
      $this->drawTextByKey($pdf, 'insurance_symbol_code', (string)$insurance->code_number);
      $pdf->SetFontSize(10);
    }

    // === 被保険者番号 ===
    if ($this->sampleDataMode && isset($this->customSampleData['insurance_symbol_number'])) {
      if ($this->customSampleData['insurance_symbol_number']) {
        $pdf->SetFontSize($this->coord('insurance_symbol_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'insurance_symbol_number', (string)$this->customSampleData['insurance_symbol_number']);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->account_number) && $insurance->account_number) {
      $pdf->SetFontSize($this->coord('insurance_symbol_number', 'fontSize'));
      $this->drawTextByKey($pdf, 'insurance_symbol_number', (string)$insurance->account_number);
      $pdf->SetFontSize(10);
    }
  }
  /**
   * 被保険者情報セクションを埋める
   */
  protected function fillInsuredPersonSection(Fpdi $pdf, $clinicUser, $insurance): void
  {
    // === 療養を受けた者の氏名 ===
    $fullName = ($clinicUser->last_name ?? '') . ' ' . ($clinicUser->first_name ?? '');
    $fullNameKana = ($clinicUser->last_kana ?? '') . ' ' . ($clinicUser->first_kana ?? '');

    $pdf->SetFontSize($this->coord('patient_name_kana', 'fontSize'));
    $this->drawTextByKey($pdf, 'patient_name_kana', (string)$fullNameKana);
    $pdf->SetFontSize($this->coord('patient_name', 'fontSize'));
    $this->drawTextByKey($pdf, 'patient_name', (string)$fullName);
    $pdf->SetFontSize(10);

    // === 続柄 ===
    if ($insurance && isset($insurance->relationship) && $insurance->relationship) {
      $pdf->SetFontSize($this->coord('patient_relationship', 'fontSize'));
      $this->drawTextByKey($pdf, 'patient_relationship', (string)$insurance->relationship);
      $pdf->SetFontSize(10);
    }

    // === 性別 ===
    if (isset($clinicUser->gender) && $clinicUser->gender) {
      if ($clinicUser->gender === '男') {
        $this->drawEllipseByKey($pdf, 'patient_gender_male');
      } elseif ($clinicUser->gender === '女') {
        $this->drawEllipseByKey($pdf, 'patient_gender_female');
      }
    }

    // === 生年月日 ===
    if (isset($clinicUser->birthday)) {
      [$birthYear, $birthMonth, $birthDay] = explode('-', $clinicUser->birthday);
      $birthJapaneseYear = $this->convertToJapaneseYear((int)$birthYear, (int)$birthMonth);

      // サンプルデータモードの場合のみisSelectedを使用、それ以外は実データから判定
      if ($this->sampleDataMode) {
        // isSelectedフラグをチェック（サンプルデータの場合）
        $birthdayEraKey = null;
        if (isset($this->coordinates['birthday_era_reiwa']['isSelected']) && $this->coordinates['birthday_era_reiwa']['isSelected']) {
          $birthdayEraKey = 'birthday_era_reiwa';
        } elseif (isset($this->coordinates['birthday_era_heisei']['isSelected']) && $this->coordinates['birthday_era_heisei']['isSelected']) {
          $birthdayEraKey = 'birthday_era_heisei';
        } elseif (isset($this->coordinates['birthday_era_showa']['isSelected']) && $this->coordinates['birthday_era_showa']['isSelected']) {
          $birthdayEraKey = 'birthday_era_showa';
        } elseif (isset($this->coordinates['birthday_era_taisho']['isSelected']) && $this->coordinates['birthday_era_taisho']['isSelected']) {
          $birthdayEraKey = 'birthday_era_taisho';
        } elseif (isset($this->coordinates['birthday_era_meiji']['isSelected']) && $this->coordinates['birthday_era_meiji']['isSelected']) {
          $birthdayEraKey = 'birthday_era_meiji';
        }

        if ($birthdayEraKey) {
          $this->drawEllipseByKey($pdf, $birthdayEraKey);
        }
      } else {
        // 実データから判定
        if ($birthJapaneseYear['era'] === '令和') {
          $this->drawEllipseByKey($pdf, 'birthday_era_reiwa');
        } elseif ($birthJapaneseYear['era'] === '平成') {
          $this->drawEllipseByKey($pdf, 'birthday_era_heisei');
        } elseif ($birthJapaneseYear['era'] === '昭和') {
          $this->drawEllipseByKey($pdf, 'birthday_era_showa');
        } elseif ($birthJapaneseYear['era'] === '大正') {
          $this->drawEllipseByKey($pdf, 'birthday_era_taisho');
        } elseif ($birthJapaneseYear['era'] === '明治') {
          $this->drawEllipseByKey($pdf, 'birthday_era_meiji');
        }
      }

      $pdf->SetFontSize($this->coord('birthday_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'birthday_year', (string)$birthJapaneseYear['year']);
      $pdf->SetFontSize($this->coord('birthday_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'birthday_month', (string)(int)$birthMonth);
      $pdf->SetFontSize($this->coord('birthday_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'birthday_day', (string)(int)$birthDay);
      $pdf->SetFontSize(10);
    }
  }
  /**
   * 施術日をカレンダーに記入
   */
  protected function fillServiceDates(Fpdi $pdf, $records): void
  {
    $letterSpacing = 0; // 追加間隔（現在は使用しない）
    $cellWidth = $this->coord('treatment_days', 'circleSpacing') ?? 6.45; // 円の間隔
    $circleRadius = $this->coord('treatment_days', 'circleRadius') ?? 1.8;
    $innerRadius = $this->coord('treatment_days', 'doubleCircleInnerRadius') ?? 2.5;

    // あんま･マッサージ版：therapy_content_id 18-21のみ描画
    $massageContentIds = [18, 19, 20, 21];

    foreach ($records as $record) {
      // 施術内容があんま･マッサージ関連でない場合はスキップ
      if (!in_array($record->therapy_content_id, $massageContentIds)) {
        continue;
      }
      
      $day = (int)date('d', strtotime($record->date));

      $x = $this->coord('treatment_days', 'x') + ($day - 1) * ($cellWidth + $letterSpacing);
      $y = $this->coord('treatment_days', 'y');

      if ($record->therapy_category == 2) {
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->Ellipse($x, $y, $circleRadius, $circleRadius, 0, 0, 360, 'D');
        $pdf->Ellipse($x, $y, $innerRadius, $innerRadius, 0, 0, 360, 'D');
      } else {
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->Ellipse($x, $y, $circleRadius, $circleRadius, 0, 0, 360, 'D');
      }
    }
  }
  /**
   * 施術料金情報を印字
   *
   * @param Fpdi $pdf
   * @param array $data
   * @return void
   */
  protected function fillTreatmentFees(Fpdi $pdf, array $data): void
  {
    $treatmentFees = $data['treatment_fees'] ?? null;
    $records = $data['records'];
    $insurance = $data['insurance'];

    // デバッグ:施術料金描画開始
    \Log::info('=== 施術料金描画開始 ===', [
      'sample_data_mode' => $this->sampleDataMode,
      'has_treatment_fees' => !empty($treatmentFees),
      'has_custom_sample_data' => $this->sampleDataMode && !empty($this->customSampleData),
      'records_count' => $records->count()
    ]);

    // サンプルデータモードでカスタムサンプルデータがある場合は直接使用
    if ($this->sampleDataMode && $this->customSampleData) {
      $custom = $this->customSampleData;

      // マッサージ料金（躯幹）
      if (isset($custom['fee_massage_trunk_unit']) && $this->hasCoord('fee_massage_trunk_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_trunk_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_trunk_unit', (string)$custom['fee_massage_trunk_unit']);
      }
      if (isset($custom['fee_massage_trunk_count']) && $this->hasCoord('fee_massage_trunk_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_trunk_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_trunk_count', (string)$custom['fee_massage_trunk_count']);
      }
      if (isset($custom['fee_massage_trunk_total']) && $this->hasCoord('fee_massage_trunk_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_trunk_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_trunk_total', (string)$custom['fee_massage_trunk_total']);
      }

      // マッサージ料金（右上肢）
      if (isset($custom['fee_massage_upper_limb_r_unit']) && $this->hasCoord('fee_massage_upper_limb_r_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_r_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_unit', (string)$custom['fee_massage_upper_limb_r_unit']);
      }
      if (isset($custom['fee_massage_upper_limb_r_count']) && $this->hasCoord('fee_massage_upper_limb_r_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_r_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_count', (string)$custom['fee_massage_upper_limb_r_count']);
      }
      if (isset($custom['fee_massage_upper_limb_r_total']) && $this->hasCoord('fee_massage_upper_limb_r_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_r_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_total', (string)$custom['fee_massage_upper_limb_r_total']);
      }

      // マッサージ料金（左上肢）
      if (isset($custom['fee_massage_upper_limb_l_unit']) && $this->hasCoord('fee_massage_upper_limb_l_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_l_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_unit', (string)$custom['fee_massage_upper_limb_l_unit']);
      }
      if (isset($custom['fee_massage_upper_limb_l_count']) && $this->hasCoord('fee_massage_upper_limb_l_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_l_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_count', (string)$custom['fee_massage_upper_limb_l_count']);
      }
      if (isset($custom['fee_massage_upper_limb_l_total']) && $this->hasCoord('fee_massage_upper_limb_l_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_upper_limb_l_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_total', (string)$custom['fee_massage_upper_limb_l_total']);
      }

      // マッサージ料金（右下肢）
      if (isset($custom['fee_massage_lower_limb_r_unit']) && $this->hasCoord('fee_massage_lower_limb_r_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_r_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_unit', (string)$custom['fee_massage_lower_limb_r_unit']);
      }
      if (isset($custom['fee_massage_lower_limb_r_count']) && $this->hasCoord('fee_massage_lower_limb_r_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_r_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_count', (string)$custom['fee_massage_lower_limb_r_count']);
      }
      if (isset($custom['fee_massage_lower_limb_r_total']) && $this->hasCoord('fee_massage_lower_limb_r_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_r_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_total', (string)$custom['fee_massage_lower_limb_r_total']);
      }

      // マッサージ料金（左下肢）
      if (isset($custom['fee_massage_lower_limb_l_unit']) && $this->hasCoord('fee_massage_lower_limb_l_unit')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_l_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_unit', (string)$custom['fee_massage_lower_limb_l_unit']);
      }
      if (isset($custom['fee_massage_lower_limb_l_count']) && $this->hasCoord('fee_massage_lower_limb_l_count')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_l_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_count', (string)$custom['fee_massage_lower_limb_l_count']);
      }
      if (isset($custom['fee_massage_lower_limb_l_total']) && $this->hasCoord('fee_massage_lower_limb_l_total')) {
        $pdf->SetFontSize($this->coord('fee_massage_lower_limb_l_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_total', (string)$custom['fee_massage_lower_limb_l_total']);
      }

      // 変形徒手矯正術
      if (isset($custom['fee_manual_correction_unit']) && $this->hasCoord('fee_manual_correction_unit')) {
        $pdf->SetFontSize($this->coord('fee_manual_correction_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_manual_correction_unit', (string)$custom['fee_manual_correction_unit']);
      }
      if (isset($custom['fee_manual_correction_count']) && $this->hasCoord('fee_manual_correction_count')) {
        $pdf->SetFontSize($this->coord('fee_manual_correction_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_manual_correction_count', (string)$custom['fee_manual_correction_count']);
      }
      if (isset($custom['fee_manual_correction_total']) && $this->hasCoord('fee_manual_correction_total')) {
        $pdf->SetFontSize($this->coord('fee_manual_correction_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_manual_correction_total', (string)$custom['fee_manual_correction_total']);
      }

      // 温罨法
      if (isset($custom['fee_fomentation_unit']) && $this->hasCoord('fee_fomentation_unit')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_unit', (string)$custom['fee_fomentation_unit']);
      }
      if (isset($custom['fee_fomentation_count']) && $this->hasCoord('fee_fomentation_count')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_count', (string)$custom['fee_fomentation_count']);
      }
      if (isset($custom['fee_fomentation_total']) && $this->hasCoord('fee_fomentation_total')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_total', (string)$custom['fee_fomentation_total']);
      }

      // 温罨法・電光線器具
      if (isset($custom['fee_fomentation_electric_light_unit']) && $this->hasCoord('fee_fomentation_electric_light_unit')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_electric_light_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_unit', (string)$custom['fee_fomentation_electric_light_unit']);
      }
      if (isset($custom['fee_fomentation_electric_light_count']) && $this->hasCoord('fee_fomentation_electric_light_count')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_electric_light_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_count', (string)$custom['fee_fomentation_electric_light_count']);
      }
      if (isset($custom['fee_fomentation_electric_light_total']) && $this->hasCoord('fee_fomentation_electric_light_total')) {
        $pdf->SetFontSize($this->coord('fee_fomentation_electric_light_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_total', (string)$custom['fee_fomentation_electric_light_total']);
      }

      // 往療料
      if (isset($custom['fee_housecall_unit']) && $this->hasCoord('fee_housecall_unit')) {
        $pdf->SetFontSize($this->coord('fee_housecall_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_unit', (string)$custom['fee_housecall_unit']);
      }
      if (isset($custom['fee_housecall_count']) && $this->hasCoord('fee_housecall_count')) {
        $pdf->SetFontSize($this->coord('fee_housecall_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_count', (string)$custom['fee_housecall_count']);
      }
      if (isset($custom['fee_housecall_total']) && $this->hasCoord('fee_housecall_total')) {
        $pdf->SetFontSize($this->coord('fee_housecall_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_total', (string)$custom['fee_housecall_total']);
      }

      // 往療料4km超
      if (isset($custom['fee_housecall_additional_unit']) && $this->hasCoord('fee_housecall_additional_unit')) {
        $pdf->SetFontSize($this->coord('fee_housecall_additional_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_additional_unit', (string)$custom['fee_housecall_additional_unit']);
      }
      if (isset($custom['fee_housecall_additional_count']) && $this->hasCoord('fee_housecall_additional_count')) {
        $pdf->SetFontSize($this->coord('fee_housecall_additional_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_additional_count', (string)$custom['fee_housecall_additional_count']);
      }
      if (isset($custom['fee_housecall_additional_total']) && $this->hasCoord('fee_housecall_additional_total')) {
        $pdf->SetFontSize($this->coord('fee_housecall_additional_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_additional_total', (string)$custom['fee_housecall_additional_total']);
      }

      // 施術報告書交付料
      if (isset($custom['fee_previous_payment_unit']) && $this->hasCoord('fee_previous_payment_unit')) {
        $pdf->SetFontSize($this->coord('fee_previous_payment_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_previous_payment_unit', (string)$custom['fee_previous_payment_unit']);
      }
      if (isset($custom['fee_previous_payment_count']) && $this->hasCoord('fee_previous_payment_count')) {
        $pdf->SetFontSize($this->coord('fee_previous_payment_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_previous_payment_count', (string)$custom['fee_previous_payment_count']);
      }
      if (isset($custom['fee_previous_payment_total']) && $this->hasCoord('fee_previous_payment_total')) {
        $pdf->SetFontSize($this->coord('fee_previous_payment_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_previous_payment_total', (string)$custom['fee_previous_payment_total']);
      }

      // 合計
      if (isset($custom['fee_subtotal']) && $this->hasCoord('fee_subtotal')) {
        $pdf->SetFontSize($this->coord('fee_subtotal', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_subtotal', (string)$custom['fee_subtotal']);
      }

      // 一部負担金
      if (isset($custom['fee_partial_payment']) && $this->hasCoord('fee_partial_payment')) {
        $pdf->SetFontSize($this->coord('fee_partial_payment', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_partial_payment', (string)$custom['fee_partial_payment']);
      }

      // 請求額
      if (isset($custom['fee_total_claim']) && $this->hasCoord('fee_total_claim')) {
        $pdf->SetFontSize($this->coord('fee_total_claim', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_total_claim', (string)$custom['fee_total_claim']);
      }

      $pdf->SetFontSize(10);
      return;
    }

    // 通常モード:治療費データが存在しない場合は警告
    if (!$treatmentFees) {
      \Log::warning('施術料金データがありません - 描画スキップ');
      return;
    }

    // デバッグ:通常モードで施術料金データあり
    \Log::info('施術料金データ取得成功 - 通常モード描画処理', [
      'treatment_fees_id' => $treatmentFees->id ?? null
    ]);

    // 通常モード:施術実績から料金を計算
    $therapyTypeCounts = [];
    $bodypartCounts = []; // 部位ごとのカウント
    $isFirstTreatment = false;

    \Log::info('=== ノーマルモード：料金計算開始 ===', [
      'records_count' => $records->count(),
      'has_treatment_fees' => !empty($treatmentFees)
    ]);

    // 施術実績を集計
    foreach ($records as $index => $record) {
      $therapyContentId = $record->therapy_content_id ?? null;

      // 初検かどうかを判定（最初のレコードのみ）
      if ($index === 0) {
        $isFirstTreatment = true;
      }

      // 施術内容ごとにカウント
      if ($therapyContentId) {
        if (!isset($therapyTypeCounts[$therapyContentId])) {
          $therapyTypeCounts[$therapyContentId] = 0;
        }
        $therapyTypeCounts[$therapyContentId]++;

        // therapy_content_id = 18 (マッサージ)の場合、部位情報を取得
        if ($therapyContentId == 18) {
          $bodyparts = DB::table('bodyparts-records')
            ->where('records_id', $record->id)
            ->pluck('therapy_type_bodyparts_id');

          foreach ($bodyparts as $bodypartId) {
            if (!isset($bodypartCounts[$bodypartId])) {
              $bodypartCounts[$bodypartId] = 0;
            }
            $bodypartCounts[$bodypartId]++;
          }
        }
      }
    }

    \Log::info('施術内容集計結果', [
      'therapy_type_counts' => $therapyTypeCounts,
      'bodypart_counts' => $bodypartCounts,
      'is_first_treatment' => $isFirstTreatment
    ]);

    $totalFee = 0;

    // マッサージ料金（躯幹）bodypart_id: 1
    $count = $bodypartCounts[1] ?? 0;
    $feeKey = $isFirstTreatment ? 'massage_trunk_first' : 'massage_trunk_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    \Log::info('マッサージ料金（躯幹）描画', [
      'bodypart_id' => 1,
      'count' => $count,
      'fee_key' => $feeKey,
      'unit_price' => $unitPrice,
      'total' => $total,
      'has_coord' => $this->hasCoord('fee_massage_trunk_unit')
    ]);

    if ($this->hasCoord('fee_massage_trunk_unit')) {
      $pdf->SetFontSize($this->coord('fee_massage_trunk_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_massage_trunk_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_massage_trunk_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_massage_trunk_total', (string)$total);
      $totalFee += $total;
    }

    // マッサージ料金（右上肢）bodypart_id: 2
    $count = $bodypartCounts[2] ?? 0;
    $feeKey = $isFirstTreatment ? 'massage_upper_limb_r_first' : 'massage_upper_limb_r_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_massage_upper_limb_r_unit')) {
      $pdf->SetFontSize($this->coord('fee_massage_upper_limb_r_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_r_total', (string)$total);
      $totalFee += $total;
    }

    // マッサージ料金（左上肢）bodypart_id: 3
    $count = $bodypartCounts[3] ?? 0;
    $feeKey = $isFirstTreatment ? 'massage_upper_limb_l_first' : 'massage_upper_limb_l_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_massage_upper_limb_l_unit')) {
      $pdf->SetFontSize($this->coord('fee_massage_upper_limb_l_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_massage_upper_limb_l_total', (string)$total);
      $totalFee += $total;
    }

    // マッサージ料金（右下肢）bodypart_id: 4
    $count = $bodypartCounts[4] ?? 0;
    $feeKey = $isFirstTreatment ? 'massage_lower_limb_r_first' : 'massage_lower_limb_r_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_massage_lower_limb_r_unit')) {
      $pdf->SetFontSize($this->coord('fee_massage_lower_limb_r_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_r_total', (string)$total);
      $totalFee += $total;
    }

    // マッサージ料金（左下肢）bodypart_id: 5
    $count = $bodypartCounts[5] ?? 0;
    $feeKey = $isFirstTreatment ? 'massage_lower_limb_l_first' : 'massage_lower_limb_l_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_massage_lower_limb_l_unit')) {
      $pdf->SetFontSize($this->coord('fee_massage_lower_limb_l_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_massage_lower_limb_l_total', (string)$total);
      $totalFee += $total;
    }

    // 変形徒手矯正術 therapy_content_id: 19
    $count = $therapyTypeCounts[19] ?? 0;
    $feeKey = $isFirstTreatment ? 'manual_correction_first' : 'manual_correction_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_manual_correction_unit')) {
      $pdf->SetFontSize($this->coord('fee_manual_correction_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_manual_correction_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_manual_correction_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_manual_correction_total', (string)$total);
      $totalFee += $total;
    }

    // 温罨法 therapy_content_id: 20
    $count = $therapyTypeCounts[20] ?? 0;
    $feeKey = $isFirstTreatment ? 'fomentation_first' : 'fomentation_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_fomentation_unit')) {
      $pdf->SetFontSize($this->coord('fee_fomentation_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_fomentation_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_fomentation_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_fomentation_total', (string)$total);
      $totalFee += $total;
    }

    // 温罨法・電光線器具 therapy_content_id: 21
    $count = $therapyTypeCounts[21] ?? 0;
    $feeKey = $isFirstTreatment ? 'fomentation_and_elec_ray_first' : 'fomentation_and_elec_ray_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $count;

    if ($this->hasCoord('fee_fomentation_electric_light_unit')) {
      $pdf->SetFontSize($this->coord('fee_fomentation_electric_light_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_count', (string)$count);
      $this->drawTextByKey($pdf, 'fee_fomentation_electric_light_total', (string)$total);
      $totalFee += $total;
    }

    // 往療料4km以下（マッサージ関連の施術のみカウント、0 < housecall_distance <= 4）
    $massageContentIds = [18, 19, 20, 21];
    $housecallCount = 0;
    foreach ($records as $record) {
      $distance = $record->housecall_distance ?? 0;
      if ($distance > 0 && $distance <= 4 && in_array($record->therapy_content_id ?? null, $massageContentIds)) {
        $housecallCount++;
      }
    }

    $feeKey = $isFirstTreatment ? 'housecall_max_2km_first' : 'housecall_max_2km_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $housecallCount;

    if ($this->hasCoord('fee_housecall_unit')) {
      $pdf->SetFontSize($this->coord('fee_housecall_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_housecall_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_housecall_count', (string)$housecallCount);
      $this->drawTextByKey($pdf, 'fee_housecall_total', (string)$total);
      $totalFee += $total;
    }

    // 往療料4km超（マッサージ関連の施術のみカウント、housecall_distance > 4で判定）
    $housecallAdditionalCount = 0;
    foreach ($records as $record) {
      if (isset($record->housecall_distance) && $record->housecall_distance > 4 && in_array($record->therapy_content_id ?? null, $massageContentIds)) {
        $housecallAdditionalCount++;
      }
    }

    $feeKey = $isFirstTreatment ? 'housecall_additional_max_4km_first' : 'housecall_additional_max_4km_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $housecallAdditionalCount;

    if ($this->hasCoord('fee_housecall_additional_unit')) {
      $pdf->SetFontSize($this->coord('fee_housecall_additional_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_housecall_additional_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_housecall_additional_count', (string)$housecallAdditionalCount);
      $this->drawTextByKey($pdf, 'fee_housecall_additional_total', (string)$total);
      $totalFee += $total;
    }

    // 合計
    if ($this->hasCoord('fee_subtotal')) {
      $pdf->SetFontSize($this->coord('fee_subtotal', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_subtotal', (string)$totalFee);
    }

    // 一部負担金計算
    $burdenRatio = 0;
    if (isset($insurance->expenses_borne_ratio)) {
      $burdenRatio = (int)$insurance->expenses_borne_ratio;
    }
    $partialPayment = (int)floor($totalFee * $burdenRatio / 100);

    if ($this->hasCoord('fee_partial_payment')) {
      $pdf->SetFontSize($this->coord('fee_partial_payment', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_partial_payment', (string)$partialPayment);
    }

    // 請求額
    $claimAmount = $totalFee - $partialPayment;
    if ($this->hasCoord('fee_total_claim')) {
      $pdf->SetFontSize($this->coord('fee_total_claim', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_total_claim', (string)$claimAmount);
    }

    $pdf->SetFontSize(10);
  }
  /**
   * 摘要欄を埋める
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param \Illuminate\Support\Collection $records 施術記録コレクション
   */
  private function fillAbstractSection(Fpdi $pdf, \Illuminate\Support\Collection $records): void
  {
    // === 摘要 ===
    $abstractText = 'なし'; // デフォルト値

    if ($this->sampleDataMode && isset($this->customSampleData['abstract']) && $this->customSampleData['abstract']) {
      // サンプルデータモード：先頭に全角スペースを追加、末尾に"。"を追加
      $abstractText = '　' . (string)$this->customSampleData['abstract'];
      if (mb_substr($abstractText, -1) !== '。') {
        $abstractText .= '。';
      }
    } elseif ($records->isNotEmpty()) {
      // 通常モード：全レコードの摘要を結合（重複排除）
      // filter()で空文字列を除外し、さらに"。"だけや空白文字だけの要素も除外
      $abstracts = $records->pluck('abstract')
        ->filter(function($abstract) {
          return !empty(trim($abstract)) && trim($abstract) !== '。';
        })
        ->unique()
        ->values() // インデックスを0から振り直す
        ->toArray();

      if (!empty($abstracts)) {
        // "。"で区切る（前後に既に"。"がある場合は重複しないように）
        $abstractText = '　'; // 先頭に全角スペースを挿入
        foreach ($abstracts as $i => $abstract) {
          if ($i > 0) {
            // 前の文字列の末尾と現在の文字列の先頭をチェック
            $lastChar = mb_substr($abstractText, -1);
            $firstChar = mb_substr($abstract, 0, 1);
            if ($lastChar !== '。' && $firstChar !== '。') {
              $abstractText .= '。';
            }
          }
          $abstractText .= $abstract;
        }
        // 最後に"。"を追加（既に"。"で終わっている場合は追加しない）
        if (mb_substr($abstractText, -1) !== '。') {
          $abstractText .= '。';
        }
      }
    }

    // 摘要を描画
    if ($this->hasCoord('abstract')) {
      $fontSize = $this->coord('abstract', 'fontSize');
      $pdf->SetFontSize($fontSize);
      $this->drawTextByKey($pdf, 'abstract', $abstractText);
      $pdf->SetFontSize(10);
    }
  }
  /**
   * 施術者登録番号を埋める
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param object|null $clinicInfo 施術所情報
   */
  private function fillTherapistSection(Fpdi $pdf, $clinicInfo): void
  {
    // === 施術者登録番号 ===
    if ($this->sampleDataMode && isset($this->customSampleData['therapist_registration_number'])) {
      if ($this->hasCoord('therapist_registration_number') && $this->customSampleData['therapist_registration_number']) {
        $pdf->SetFontSize($this->coord('therapist_registration_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'therapist_registration_number', (string)$this->customSampleData['therapist_registration_number']);
        $pdf->SetFontSize(10);
      }
    } else {
      // ノーマルモード：clinic_info.license_massage_numberを使用
      if ($clinicInfo && isset($clinicInfo->license_massage_number)) {
        if ($this->hasCoord('therapist_registration_number') && $clinicInfo->license_massage_number) {
          $pdf->SetFontSize($this->coord('therapist_registration_number', 'fontSize'));
          $this->drawTextByKey($pdf, 'therapist_registration_number', (string)$clinicInfo->license_massage_number);
          $pdf->SetFontSize(10);
        }
      }
    }
  }
  /**
   * 同意記録欄を埋める（統括メソッド）
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param object|null $consent 同意書データ
   * @param object|null $doctor 医師データ
   */
  private function fillConsentRecordSection(Fpdi $pdf, $consent, $doctor): void
  {
    $this->fillConsentDoctorNameSection($pdf, $consent);
    $this->fillConsentDoctorAddressSection($pdf, $consent, $doctor);
    $this->fillConsentDateSection($pdf, $consent);
    $this->fillConsentIllnessAndPeriodSection($pdf, $consent);
  }
  /**
   * 同意医師氏名を埋める
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param object|null $consent 同意書データ
   */
  private function fillConsentDoctorNameSection(Fpdi $pdf, $consent): void
  {
    // 医師氏名
    if ($this->sampleDataMode) {
      if (isset($this->customSampleData['consent_record_doctor_name']) && $this->hasCoord('consent_record_doctor_name') && $this->customSampleData['consent_record_doctor_name']) {
        $pdf->SetFontSize($this->coord('consent_record_doctor_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_doctor_name', (string)$this->customSampleData['consent_record_doctor_name']);
        $pdf->SetFontSize(10);
      }
    } elseif ($consent && isset($consent->consenting_doctor_name)) {
      if ($this->hasCoord('consent_record_doctor_name') && $consent->consenting_doctor_name) {
        $pdf->SetFontSize($this->coord('consent_record_doctor_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_doctor_name', (string)$consent->consenting_doctor_name);
        $pdf->SetFontSize(10);
      }
    }
  }
  /**
   * 同意医師住所を埋める
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param object|null $consent 同意書データ
   * @param object|null $doctor 医師データ
   */
  private function fillConsentDoctorAddressSection(Fpdi $pdf, $consent, $doctor): void
  {
    // 同意医師郵便番号
    if ($this->sampleDataMode) {
      if (isset($this->customSampleData['consent_record_doctor_postal_code']) && $this->hasCoord('consent_record_doctor_postal_code') && $this->customSampleData['consent_record_doctor_postal_code']) {
        $postalCode = preg_replace('/[^0-9]/', '', $this->customSampleData['consent_record_doctor_postal_code']);

        $pdf->SetFontSize($this->coord('consent_record_doctor_postal_code', 'fontSize'));

        // postalCodeGap がある場合は分割描画
        if (strlen($postalCode) === 7 && isset($this->coordinates['consent_record_doctor_postal_code']['postalCodeGap'])) {
          $part1 = substr($postalCode, 0, 3);  // 前半3桁
          $part2 = substr($postalCode, 3, 4);  // 後半4桁
          $gap = $this->coordinates['consent_record_doctor_postal_code']['postalCodeGap'];

          $y = $this->coord('consent_record_doctor_postal_code', 'y');
          $x1 = $this->coord('consent_record_doctor_postal_code', 'x');
          $x2 = $x1 + $gap;

          $pdf->SetXY($x1, $y);
          $pdf->Cell(0, 0, '〒 ' . $part1, 0, 0, 'L');
          $pdf->SetXY($x2, $y);
          $pdf->Cell(0, 0, '- ' . $part2, 0, 0, 'L');
        } else {
          // postalCodeGap がない場合は従来の形式
          if (strlen($postalCode) === 7) {
            $postalCode = '〒 ' . substr($postalCode, 0, 3) . ' - ' . substr($postalCode, 3, 4);
          }
          $this->drawTextByKey($pdf, 'consent_record_doctor_postal_code', $postalCode);
        }
      }
    } else {
      if ($doctor && $this->hasCoord('consent_record_doctor_postal_code') && isset($doctor->postal_code)) {
        $postalCode = preg_replace('/[^0-9]/', '', $doctor->postal_code);

        $pdf->SetFontSize($this->coord('consent_record_doctor_postal_code', 'fontSize'));

        // postalCodeGap がある場合は分割描画
        if (strlen($postalCode) === 7 && isset($this->coordinates['consent_record_doctor_postal_code']['postalCodeGap'])) {
          $part1 = substr($postalCode, 0, 3);  // 前半3桁
          $part2 = substr($postalCode, 3, 4);  // 後半4桁
          $gap = $this->coordinates['consent_record_doctor_postal_code']['postalCodeGap'];

          $y = $this->coord('consent_record_doctor_postal_code', 'y');
          $x1 = $this->coord('consent_record_doctor_postal_code', 'x');
          $x2 = $x1 + $gap;

          $pdf->SetXY($x1, $y);
          $pdf->Cell(0, 0, '〒 ' . $part1, 0, 0, 'L');
          $pdf->SetXY($x2, $y);
          $pdf->Cell(0, 0, '- ' . $part2, 0, 0, 'L');
        } else {
          // postalCodeGap がない場合は従来の形式
          if (strlen($postalCode) === 7) {
            $postalCode = '〒 ' . substr($postalCode, 0, 3) . ' - ' . substr($postalCode, 3, 4);
          }
          $this->drawTextByKey($pdf, 'consent_record_doctor_postal_code', $postalCode);
        }
      }
    }

    // 同意医師住所
    if ($this->sampleDataMode) {
      if (isset($this->customSampleData['consent_record_doctor_address']) && $this->hasCoord('consent_record_doctor_address') && $this->customSampleData['consent_record_doctor_address']) {
        $pdf->SetFontSize($this->coord('consent_record_doctor_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_doctor_address', (string)$this->customSampleData['consent_record_doctor_address']);
      }
    } else {
      if ($doctor && $this->hasCoord('consent_record_doctor_address')) {
        $doctorAddress = ($doctor->address_1 ?? '') . ($doctor->address_2 ?? '') . ($doctor->address_3 ?? '');
        if ($doctorAddress) {
          $pdf->SetFontSize($this->coord('consent_record_doctor_address', 'fontSize'));
          $this->drawTextByKey($pdf, 'consent_record_doctor_address', (string)$doctorAddress);
        }
      }
    }
  }
  /**
   * 同意年月日を埋める
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param object|null $consent 同意書データ
   */
  private function fillConsentDateSection(Fpdi $pdf, $consent): void
  {
    // 同意年月日
    if ($this->sampleDataMode) {
      if (isset($this->customSampleData['consent_record_date_year']) && $this->hasCoord('consent_record_date_year')) {
        $pdf->SetFontSize($this->coord('consent_record_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_date_year', (string)$this->customSampleData['consent_record_date_year']);
        $pdf->SetFontSize(10);
      }
      if (isset($this->customSampleData['consent_record_date_month']) && $this->hasCoord('consent_record_date_month')) {
        $pdf->SetFontSize($this->coord('consent_record_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_date_month', (string)$this->customSampleData['consent_record_date_month']);
        $pdf->SetFontSize(10);
      }
      if (isset($this->customSampleData['consent_record_date_day']) && $this->hasCoord('consent_record_date_day')) {
        $pdf->SetFontSize($this->coord('consent_record_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_date_day', (string)$this->customSampleData['consent_record_date_day']);
        $pdf->SetFontSize(10);
      }
    } elseif ($consent && isset($consent->consenting_date)) {
      [$consentYear, $consentMonth, $consentDay] = explode('-', $consent->consenting_date);
      $consentJapaneseYear = $this->convertToJapaneseYear((int)$consentYear, (int)$consentMonth);

      if ($this->hasCoord('consent_record_date_year')) {
        $pdf->SetFontSize($this->coord('consent_record_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_date_year', (string)$consentJapaneseYear['year']);
        $pdf->SetFontSize(10);
      }
      if ($this->hasCoord('consent_record_date_month')) {
        $pdf->SetFontSize($this->coord('consent_record_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_date_month', (string)(int)$consentMonth);
        $pdf->SetFontSize(10);
      }
      if ($this->hasCoord('consent_record_date_day')) {
        $pdf->SetFontSize($this->coord('consent_record_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_date_day', (string)(int)$consentDay);
        $pdf->SetFontSize(10);
      }
    }
  }
  /**
   * 同意記録の傷病名と要加療期間を埋める
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param object|null $consent 同意書データ
   */
  private function fillConsentIllnessAndPeriodSection(Fpdi $pdf, $consent): void
  {
    // 同意記録の傷病名（illness_nameを使用）
    if ($this->sampleDataMode) {
      if (isset($this->customSampleData['consent_record_illness_name']) && $this->hasCoord('consent_record_illness_name') && $this->customSampleData['consent_record_illness_name']) {
        $pdf->SetFontSize($this->coord('consent_record_illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_illness_name', (string)$this->customSampleData['consent_record_illness_name']);
        $pdf->SetFontSize(10);
      }
    } elseif ($consent && isset($consent->illness_name)) {
      if ($this->hasCoord('consent_record_illness_name') && $consent->illness_name) {
        $pdf->SetFontSize($this->coord('consent_record_illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_illness_name', (string)$consent->illness_name);
        $pdf->SetFontSize(10);
      }
    }

    // 要加療期間
    if ($this->sampleDataMode) {
      if (isset($this->customSampleData['required_treatment_period']) && $this->hasCoord('required_treatment_period') && $this->customSampleData['required_treatment_period']) {
        $pdf->SetFontSize($this->coord('required_treatment_period', 'fontSize'));
        $this->drawTextByKey($pdf, 'required_treatment_period', (string)$this->customSampleData['required_treatment_period']);
      }
    } else {
      if ($this->hasCoord('required_treatment_period')) {
        $therapyPeriodText = '';

        // therapy_period_end_dateをYYYY/MM/DD形式で表示
        if (isset($consent->therapy_period_end_date)) {
          $endDate = new \DateTime($consent->therapy_period_end_date);
          $therapyPeriodText = $endDate->format('Y/m/d');
        } elseif (isset($consent->therapy_period) && $consent->therapy_period) {
          // フォールバック: therapy_periodフィールドがある場合はそのまま使用
          $therapyPeriodText = $consent->therapy_period;
        }

        if ($therapyPeriodText) {
          $pdf->SetFontSize($this->coord('required_treatment_period', 'fontSize'));
          $this->drawTextByKey($pdf, 'required_treatment_period', (string)$therapyPeriodText);
        }
      }
    }
  }
  /**
   * 申請欄を埋める
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param object|null $insurance 保険情報
   * @param string $submissionDate 提出年月日
   */
  private function fillApplicationSection(Fpdi $pdf, $insurance, string $submissionDate): void
  {
    // 申請年月日
    if ($this->sampleDataMode && isset($this->customSampleData['submission_date_year'])) {
      if ($this->hasCoord('submission_date_year')) {
        $pdf->SetFontSize($this->coord('submission_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'submission_date_year', (string)$this->customSampleData['submission_date_year']);
      }
      if ($this->hasCoord('submission_date_month')) {
        $pdf->SetFontSize($this->coord('submission_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'submission_date_month', (string)$this->customSampleData['submission_date_month']);
      }
      if ($this->hasCoord('submission_date_day')) {
        $pdf->SetFontSize($this->coord('submission_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'submission_date_day', (string)$this->customSampleData['submission_date_day']);
      }
      $pdf->SetFontSize(10);
    } else {
      [$subYear, $subMonth, $subDay] = explode('-', $submissionDate);
      $subJapaneseYear = $this->convertToJapaneseYear((int)$subYear, (int)$subMonth);

      if ($this->hasCoord('submission_date_year')) {
        $pdf->SetFontSize($this->coord('submission_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'submission_date_year', (string)$subJapaneseYear['year']);
      }
      if ($this->hasCoord('submission_date_month')) {
        $pdf->SetFontSize($this->coord('submission_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'submission_date_month', (string)(int)$subMonth);
      }
      if ($this->hasCoord('submission_date_day')) {
        $pdf->SetFontSize($this->coord('submission_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'submission_date_day', (string)(int)$subDay);
      }
      $pdf->SetFontSize(10);
    }

    // 保険者名称
    if ($this->sampleDataMode && isset($this->customSampleData['insurer_name'])) {
      if ($this->hasCoord('insurer_name') && $this->customSampleData['insurer_name']) {
        $pdf->SetFontSize($this->coord('insurer_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'insurer_name', (string)$this->customSampleData['insurer_name']);
        $pdf->SetFontSize(10);
      }
    } elseif ($insurance && isset($insurance->insurer_name)) {
      if ($this->hasCoord('insurer_name') && $insurance->insurer_name) {
        $pdf->SetFontSize($this->coord('insurer_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'insurer_name', (string)$insurance->insurer_name);
        $pdf->SetFontSize(10);
      }
    }
  }
  /**
   * 支払機関欄を埋める（統括メソッド）
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param object|null $clinicInfo 施術所情報
   * @param object|null $clinicUser ユーザー情報
   * @param string $submissionDate 提出年月日
   */
  private function fillPaymentSection(Fpdi $pdf, $clinicInfo, $clinicUser, string $submissionDate): void
  {
    $this->fillPaymentCategorySection($pdf);
    $this->fillDepositTypeSection($pdf, $clinicInfo);
    $this->fillFinancialInstitutionNameSection($pdf, $clinicInfo);
    $this->fillFinancialInstitutionTypeSection($pdf, $clinicInfo);
    $this->fillBranchTypeSection($pdf, $clinicInfo);
    $this->fillBankAccountSection($pdf, $clinicInfo);
    $this->fillCommissionDateSection($pdf, $submissionDate);
    $this->fillSignatureApplicantAddressSection($pdf, $clinicUser);
  }
  /**
   * 支払区分を埋める
   */
  private function fillPaymentCategorySection(Fpdi $pdf): void
  {
    if ($this->sampleDataMode) {
      if (isset($this->coordinates['payment_category_furikomi']['isSelected']) && $this->coordinates['payment_category_furikomi']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'payment_category_furikomi');
      } elseif (isset($this->coordinates['payment_category_bank_transfer']['isSelected']) && $this->coordinates['payment_category_bank_transfer']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'payment_category_bank_transfer');
      } elseif (isset($this->coordinates['payment_category_post_transfer']['isSelected']) && $this->coordinates['payment_category_post_transfer']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'payment_category_post_transfer');
      } elseif (isset($this->coordinates['payment_category_local_payment']['isSelected']) && $this->coordinates['payment_category_local_payment']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'payment_category_local_payment');
      } elseif (isset($this->customSampleData['payment_category'])) {
        $paymentCategoryMap = [
          '振込' => 'payment_category_furikomi',
          '銀行送金' => 'payment_category_bank_transfer',
          '郵便局送金' => 'payment_category_post_transfer',
          '当地払' => 'payment_category_local_payment',
        ];
        $key = $paymentCategoryMap[$this->customSampleData['payment_category']] ?? null;
        if ($key) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }
    } else {
      if ($this->hasCoord('payment_category_furikomi')) {
        $this->drawEllipseByKey($pdf, 'payment_category_furikomi');
      }
    }
  }
  /**
   * 預金種別を埋める
   */
  private function fillDepositTypeSection(Fpdi $pdf, $clinicInfo): void
  {
    if ($this->sampleDataMode) {
      if (isset($this->coordinates['deposit_type_ordinary']['isSelected']) && $this->coordinates['deposit_type_ordinary']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'deposit_type_ordinary');
      } elseif (isset($this->coordinates['deposit_type_current']['isSelected']) && $this->coordinates['deposit_type_current']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'deposit_type_current');
      } elseif (isset($this->coordinates['deposit_type_notice']['isSelected']) && $this->coordinates['deposit_type_notice']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'deposit_type_notice');
      } elseif (isset($this->coordinates['deposit_type_betsudan']['isSelected']) && $this->coordinates['deposit_type_betsudan']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'deposit_type_betsudan');
      } elseif (isset($this->customSampleData['deposit_type'])) {
        $depositTypeMap = [
          '普通' => 'deposit_type_ordinary',
          '当座' => 'deposit_type_current',
          '通知' => 'deposit_type_notice',
          '別段' => 'deposit_type_betsudan',
        ];
        $key = $depositTypeMap[$this->customSampleData['deposit_type']] ?? null;
        if ($key) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }
    } else {
      if ($clinicInfo && isset($clinicInfo->bank_account_type)) {
        $accountType = $clinicInfo->bank_account_type;
        if ($accountType === '普通' && $this->hasCoord('deposit_type_ordinary')) {
          $this->drawEllipseByKey($pdf, 'deposit_type_ordinary');
        } elseif ($accountType === '当座' && $this->hasCoord('deposit_type_current')) {
          $this->drawEllipseByKey($pdf, 'deposit_type_current');
        } elseif ($accountType === '通知' && $this->hasCoord('deposit_type_notice')) {
          $this->drawEllipseByKey($pdf, 'deposit_type_notice');
        } elseif ($accountType === '別段' && $this->hasCoord('deposit_type_betsudan')) {
          $this->drawEllipseByKey($pdf, 'deposit_type_betsudan');
        }
      }
    }
  }
  /**
   * 金融機関名を埋める
   */
  private function fillFinancialInstitutionNameSection(Fpdi $pdf, $clinicInfo): void
  {
    if ($this->sampleDataMode && isset($this->customSampleData['financial_institution_name_1'])) {
      if ($this->hasCoord('financial_institution_name_1') && $this->customSampleData['financial_institution_name_1']) {
        $pdf->SetFontSize($this->coord('financial_institution_name_1', 'fontSize'));
        $bankName = (string)$this->customSampleData['financial_institution_name_1'];
        $bankName = preg_replace('/(銀行|金庫|農協)$/', '', $bankName);
        $this->drawTextByKey($pdf, 'financial_institution_name_1', $bankName);
        $pdf->SetFontSize(10);
      }
    } elseif (!$this->sampleDataMode && $clinicInfo && isset($clinicInfo->bank_name)) {
      if ($this->hasCoord('financial_institution_name_1')) {
        $pdf->SetFontSize($this->coord('financial_institution_name_1', 'fontSize'));
        $bankName = (string)$clinicInfo->bank_name;
        $bankName = preg_replace('/(銀行|金庫|農協)$/', '', $bankName);
        $this->drawTextByKey($pdf, 'financial_institution_name_1', $bankName);
        $pdf->SetFontSize(10);
      }
    }

    if ($this->sampleDataMode && isset($this->customSampleData['financial_institution_name_2'])) {
      if ($this->hasCoord('financial_institution_name_2') && $this->customSampleData['financial_institution_name_2']) {
        $pdf->SetFontSize($this->coord('financial_institution_name_2', 'fontSize'));
        $branchName = (string)$this->customSampleData['financial_institution_name_2'];
        $branchName = preg_replace('/(本店|支店|出張所)$/', '', $branchName);
        $this->drawTextByKey($pdf, 'financial_institution_name_2', $branchName);
        $pdf->SetFontSize(10);
      }
    } elseif (!$this->sampleDataMode && $clinicInfo && isset($clinicInfo->bank_branch_name)) {
      if ($this->hasCoord('financial_institution_name_2')) {
        $pdf->SetFontSize($this->coord('financial_institution_name_2', 'fontSize'));
        $branchName = (string)$clinicInfo->bank_branch_name;
        $branchName = preg_replace('/(本店|支店|出張所)$/', '', $branchName);
        $this->drawTextByKey($pdf, 'financial_institution_name_2', $branchName);
        $pdf->SetFontSize(10);
      }
    }
  }
  /**
   * 金融機関種別を埋める
   */
  private function fillFinancialInstitutionTypeSection(Fpdi $pdf, $clinicInfo): void
  {
    if ($this->sampleDataMode) {
      if (isset($this->coordinates['financial_institution_type_bank']['isSelected']) && $this->coordinates['financial_institution_type_bank']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'financial_institution_type_bank');
      } elseif (isset($this->coordinates['financial_institution_type_kinko']['isSelected']) && $this->coordinates['financial_institution_type_kinko']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'financial_institution_type_kinko');
      } elseif (isset($this->coordinates['financial_institution_type_nokyo']['isSelected']) && $this->coordinates['financial_institution_type_nokyo']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'financial_institution_type_nokyo');
      } elseif (isset($this->customSampleData['financial_institution_type'])) {
        $fiTypeMap = [
          '銀行' => 'financial_institution_type_bank',
          '金庫' => 'financial_institution_type_kinko',
          '農協' => 'financial_institution_type_nokyo',
        ];
        $key = $fiTypeMap[$this->customSampleData['financial_institution_type']] ?? null;
        if ($key) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }
    } else {
      if ($clinicInfo && isset($clinicInfo->bank_name)) {
        $bankName = $clinicInfo->bank_name;
        if (str_ends_with($bankName, '銀行') && $this->hasCoord('financial_institution_type_bank')) {
          $this->drawEllipseByKey($pdf, 'financial_institution_type_bank');
        } elseif (str_ends_with($bankName, '金庫') && $this->hasCoord('financial_institution_type_kinko')) {
          $this->drawEllipseByKey($pdf, 'financial_institution_type_kinko');
        } elseif (str_ends_with($bankName, '農協') && $this->hasCoord('financial_institution_type_nokyo')) {
          $this->drawEllipseByKey($pdf, 'financial_institution_type_nokyo');
        }
      }
    }
  }
  /**
   * 支店種別を埋める
   */
  private function fillBranchTypeSection(Fpdi $pdf, $clinicInfo): void
  {
    if ($this->sampleDataMode) {
      if (isset($this->coordinates['branch_type_honten']['isSelected']) && $this->coordinates['branch_type_honten']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'branch_type_honten');
      } elseif (isset($this->coordinates['branch_type_shiten']['isSelected']) && $this->coordinates['branch_type_shiten']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'branch_type_shiten');
      } elseif (isset($this->coordinates['branch_type_shucchoujo']['isSelected']) && $this->coordinates['branch_type_shucchoujo']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'branch_type_shucchoujo');
      } elseif (isset($this->customSampleData['branch_type'])) {
        $branchTypeMap = [
          '本店' => 'branch_type_honten',
          '支店' => 'branch_type_shiten',
          '出張所' => 'branch_type_shucchoujo',
        ];
        $key = $branchTypeMap[$this->customSampleData['branch_type']] ?? null;
        if ($key) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }
    } else {
      if ($clinicInfo && isset($clinicInfo->bank_branch_name)) {
        $branchName = $clinicInfo->bank_branch_name;
        if (str_ends_with($branchName, '本店') && $this->hasCoord('branch_type_honten')) {
          $this->drawEllipseByKey($pdf, 'branch_type_honten');
        } elseif (str_ends_with($branchName, '支店') && $this->hasCoord('branch_type_shiten')) {
          $this->drawEllipseByKey($pdf, 'branch_type_shiten');
        } elseif (str_ends_with($branchName, '出張所') && $this->hasCoord('branch_type_shucchoujo')) {
          $this->drawEllipseByKey($pdf, 'branch_type_shucchoujo');
        }
      }
    }
  }
  /**
   * 口座情報を埋める
   */
  private function fillBankAccountSection(Fpdi $pdf, $clinicInfo): void
  {
    // 口座名義人
    if ($this->sampleDataMode && isset($this->customSampleData['bank_account_holder_kana'])) {
      if ($this->hasCoord('bank_account_holder_kana') && $this->customSampleData['bank_account_holder_kana']) {
        $pdf->SetFontSize($this->coord('bank_account_holder_kana', 'fontSize'));
        $this->drawTextByKey($pdf, 'bank_account_holder_kana', (string)$this->customSampleData['bank_account_holder_kana']);
        $pdf->SetFontSize(10);
      }
    } elseif (!$this->sampleDataMode && $clinicInfo && isset($clinicInfo->bank_account_name_kana)) {
      if ($this->hasCoord('bank_account_holder_kana')) {
        $pdf->SetFontSize($this->coord('bank_account_holder_kana', 'fontSize'));
        $this->drawTextByKey($pdf, 'bank_account_holder_kana', (string)$clinicInfo->bank_account_name_kana);
        $pdf->SetFontSize(10);
      }
    }

    // 口座番号
    if ($this->sampleDataMode && isset($this->customSampleData['bank_account_number'])) {
      if ($this->hasCoord('bank_account_number') && $this->customSampleData['bank_account_number']) {
        $pdf->SetFontSize($this->coord('bank_account_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'bank_account_number', (string)$this->customSampleData['bank_account_number']);
        $pdf->SetFontSize(10);
      }
    } elseif (!$this->sampleDataMode && $clinicInfo && isset($clinicInfo->bank_account_number)) {
      if ($this->hasCoord('bank_account_number')) {
        $pdf->SetFontSize($this->coord('bank_account_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'bank_account_number', (string)$clinicInfo->bank_account_number);
        $pdf->SetFontSize(10);
      }
    } elseif ($this->hasCoord('account_number')) {
      if ($this->sampleDataMode && isset($this->customSampleData['account_number'])) {
        $pdf->SetFontSize($this->coord('account_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'account_number', (string)$this->customSampleData['account_number']);
        $pdf->SetFontSize(10);
      }
    }
  }
  /**
   * 委任年月日を埋める
   */
  private function fillCommissionDateSection(Fpdi $pdf, string $submissionDate): void
  {
    if ($this->sampleDataMode && isset($this->customSampleData['signature_date_year'])) {
      if ($this->hasCoord('signature_date_year')) {
        $pdf->SetFontSize($this->coord('signature_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'signature_date_year', (string)$this->customSampleData['signature_date_year']);
      }
      if ($this->hasCoord('signature_date_month')) {
        $pdf->SetFontSize($this->coord('signature_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'signature_date_month', (string)$this->customSampleData['signature_date_month']);
      }
      if ($this->hasCoord('signature_date_day')) {
        $pdf->SetFontSize($this->coord('signature_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'signature_date_day', (string)$this->customSampleData['signature_date_day']);
      }
      $pdf->SetFontSize(10);
    } elseif (!$this->sampleDataMode && $this->hasCoord('signature_date_year')) {
      $submissionParts = explode('-', $submissionDate);
      $submissionJapaneseYear = $this->convertToJapaneseYear((int)$submissionParts[0], (int)$submissionParts[1]);

      $pdf->SetFontSize($this->coord('signature_date_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'signature_date_year', (string)$submissionJapaneseYear['year']);

      if ($this->hasCoord('signature_date_month')) {
        $pdf->SetFontSize($this->coord('signature_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'signature_date_month', (string)(int)$submissionParts[1]);
      }

      if ($this->hasCoord('signature_date_day')) {
        $pdf->SetFontSize($this->coord('signature_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'signature_date_day', (string)(int)$submissionParts[2]);
      }

      $pdf->SetFontSize(10);
    }
  }
  /**
   * 申請者住所を埋める
   */
  private function fillSignatureApplicantAddressSection(Fpdi $pdf, $clinicUser): void
  {
    // 申請者郵便番号
    if ($this->sampleDataMode) {
      if ($this->hasCoord('signature_applicant_postal_code') && isset($this->customSampleData['signature_applicant_postal_code'])) {
        $postalCode = $this->customSampleData['signature_applicant_postal_code'];
        if ($postalCode) {
          $postalCode = preg_replace('/[^0-9]/', '', $postalCode);

          $pdf->SetFontSize($this->coord('signature_applicant_postal_code', 'fontSize'));

          if (strlen($postalCode) === 7 && isset($this->coordinates['signature_applicant_postal_code']['postalCodeGap'])) {
            $part1 = substr($postalCode, 0, 3);
            $part2 = substr($postalCode, 3, 4);
            $gap = $this->coordinates['signature_applicant_postal_code']['postalCodeGap'];

            $y = $this->coord('signature_applicant_postal_code', 'y');
            $x1 = $this->coord('signature_applicant_postal_code', 'x');
            $x2 = $x1 + $gap;

            $pdf->SetXY($x1, $y);
            $pdf->Cell(0, 0, '〒 ' . $part1, 0, 0, 'L');
            $pdf->SetXY($x2, $y);
            $pdf->Cell(0, 0, '- ' . $part2, 0, 0, 'L');
          } else {
            if (strlen($postalCode) === 7) {
              $postalCode = '〒 ' . substr($postalCode, 0, 3) . ' - ' . substr($postalCode, 3, 4);
            }
            $this->drawTextByKey($pdf, 'signature_applicant_postal_code', $postalCode);
          }

          $pdf->SetFontSize(10);
        }
      }
    } elseif (isset($clinicUser) && !empty($clinicUser->postal_code)) {
      if ($this->hasCoord('signature_applicant_postal_code')) {
        $postalCode = $clinicUser->postal_code;
        $postalCode = preg_replace('/[^0-9]/', '', $postalCode);

        $pdf->SetFontSize($this->coord('signature_applicant_postal_code', 'fontSize'));

        if (strlen($postalCode) === 7 && isset($this->coordinates['signature_applicant_postal_code']['postalCodeGap'])) {
          $part1 = substr($postalCode, 0, 3);
          $part2 = substr($postalCode, 3, 4);
          $gap = $this->coordinates['signature_applicant_postal_code']['postalCodeGap'];

          $y = $this->coord('signature_applicant_postal_code', 'y');
          $x1 = $this->coord('signature_applicant_postal_code', 'x');
          $x2 = $x1 + $gap;

          $pdf->SetXY($x1, $y);
          $pdf->Cell(0, 0, '〒 ' . $part1, 0, 0, 'L');
          $pdf->SetXY($x2, $y);
          $pdf->Cell(0, 0, '- ' . $part2, 0, 0, 'L');
        } else {
          if (strlen($postalCode) === 7) {
            $postalCode = '〒 ' . substr($postalCode, 0, 3) . ' - ' . substr($postalCode, 3, 4);
          }
          $this->drawTextByKey($pdf, 'signature_applicant_postal_code', $postalCode);
        }

        $pdf->SetFontSize(10);
      }
    }

    // 委任申請者住所
    if ($this->sampleDataMode && isset($this->customSampleData['signature_applicant_address'])) {
      if ($this->hasCoord('signature_applicant_address') && $this->customSampleData['signature_applicant_address']) {
        $pdf->SetFontSize($this->coord('signature_applicant_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'signature_applicant_address', (string)$this->customSampleData['signature_applicant_address']);
        $pdf->SetFontSize(10);
      }
    } elseif (isset($clinicUser)) {
      $applicantAddress = ($clinicUser->address_1 ?? '') . ($clinicUser->address_2 ?? '') . ($clinicUser->address_3 ?? '');
      if ($this->hasCoord('signature_applicant_address') && $applicantAddress) {
        $pdf->SetFontSize($this->coord('signature_applicant_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'signature_applicant_address', (string)$applicantAddress);
        $pdf->SetFontSize(10);
      }
    }
  }
  /**
   * 施術所情報セクションを埋める
   */
  private function fillClinicInfoSection(Fpdi $pdf, $clinicInfo, string $submissionDate): void
  {
    if (!$clinicInfo) {
      return;
    }

    // 施術所日付
    if ($this->sampleDataMode && isset($this->customSampleData['clinic_date_year'])) {
      $pdf->SetFontSize($this->coord('clinic_date_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_date_year', (string)$this->customSampleData['clinic_date_year']);
      $pdf->SetFontSize($this->coord('clinic_date_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_date_month', (string)($this->customSampleData['clinic_date_month'] ?? ''));
      $pdf->SetFontSize($this->coord('clinic_date_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_date_day', (string)($this->customSampleData['clinic_date_day'] ?? ''));
    } else {
      $submissionParts = explode('-', $submissionDate);
      $submissionJapaneseYear = $this->convertToJapaneseYear((int)$submissionParts[0], (int)$submissionParts[1]);
      $pdf->SetFontSize($this->coord('clinic_date_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_date_year', (string)$submissionJapaneseYear['year']);
      $pdf->SetFontSize($this->coord('clinic_date_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_date_month', (string)(int)$submissionParts[1]);
      $pdf->SetFontSize($this->coord('clinic_date_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_date_day', (string)(int)$submissionParts[2]);
    }
    $pdf->SetFontSize(10);

    // 施術所郵便番号
    if ($this->hasCoord('clinic_postal_code')) {
      $pdf->SetFontSize($this->coord('clinic_postal_code', 'fontSize'));
      $clinicPostalCode = $this->sampleDataMode && isset($this->customSampleData['clinic_postal_code'])
        ? $this->customSampleData['clinic_postal_code']
        : ($clinicInfo->postal_code ?? '');
      // ハイフンを除去
      $cleanPostalCode = str_replace('-', '', $clinicPostalCode);
      // 7桁の数字を〒 XXX-XXXX形式にフォーマット
      $formattedPostalCode = $clinicPostalCode;
      if (preg_match('/^\d{7}$/', $cleanPostalCode)) {
        $formattedPostalCode = '〒 ' . substr($cleanPostalCode, 0, 3) . '-' . substr($cleanPostalCode, 3, 4);
      }
      $this->drawTextByKey($pdf, 'clinic_postal_code', (string)$formattedPostalCode);
    }

    // 施術所住所
    if ($this->sampleDataMode && isset($this->customSampleData['clinic_address'])) {
      $clinicAddress = $this->customSampleData['clinic_address'];
    } else {
      $clinicAddress = ($clinicInfo->address_1 ?? '') .
                       ($clinicInfo->address_2 ?? '') .
                       ($clinicInfo->address_3 ?? '');
    }
    if ($this->hasCoord('clinic_address')) {
      $pdf->SetFontSize($this->coord('clinic_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_address', (string)$clinicAddress);
      $pdf->SetFontSize(10);
    }

    // 施術所名
    if ($this->sampleDataMode && isset($this->customSampleData['clinic_name'])) {
      $clinicName = $this->customSampleData['clinic_name'];
    } else {
      $clinicName = $clinicInfo->clinic_name ?? '';
    }
    if ($this->hasCoord('clinic_name')) {
      $pdf->SetFontSize($this->coord('clinic_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_name', (string)$clinicName);
      $pdf->SetFontSize(10);
    }

    // 開設者・管理者名
    if ($this->sampleDataMode && isset($this->customSampleData['clinic_manager'])) {
      $manager = $this->customSampleData['clinic_manager'];
    } else {
      $manager = ($clinicInfo->owner_last_name ?? '') . ' ' . ($clinicInfo->owner_first_name ?? '');
      $manager = trim($manager);
    }
    if ($this->hasCoord('clinic_manager')) {
      $pdf->SetFontSize($this->coord('clinic_manager', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_manager', (string)$manager);
      $pdf->SetFontSize(10);
    }

    // 電話番号
    if ($this->sampleDataMode && isset($this->customSampleData['clinic_phone'])) {
      if ($this->hasCoord('clinic_phone') && $this->customSampleData['clinic_phone']) {
        $pdf->SetFontSize($this->coord('clinic_phone', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_phone', (string)$this->customSampleData['clinic_phone']);
        $pdf->SetFontSize(10);
      }
    } else {
      $phone = $clinicInfo->phone ?? '';
      if ($this->hasCoord('clinic_phone') && $phone) {
        $pdf->SetFontSize($this->coord('clinic_phone', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_phone', (string)$phone);
        $pdf->SetFontSize(10);
      }
    }
  }
  /**
   * 申請者情報セクションを埋める
   */
  private function fillApplicantInfoSection(Fpdi $pdf, $clinicUser): void
  {
    if (!isset($clinicUser)) {
      return;
    }

    // 申請者郵便番号（前半3桁・後半4桁に分割）
    if ($this->hasCoord('applicant_postal_code')) {
      $applicantPostalCode = $this->sampleDataMode && isset($this->customSampleData['applicant_postal_code'])
        ? $this->customSampleData['applicant_postal_code']
        : ($clinicUser->postal_code ?? '');

      // ハイフンを削除して数字のみにする
      $postalCodeNumbers = preg_replace('/[^0-9]/', '', $applicantPostalCode);
      $firstPart = substr($postalCodeNumbers, 0, 3);
      $lastPart = substr($postalCodeNumbers, 3, 4);

      $fontSize = $this->coord('applicant_postal_code', 'fontSize');
      $pdf->SetFontSize($fontSize);

      // 前半3桁
      $firstX = $this->coordinates['applicant_postal_code']['firstX'] ?? $this->coord('applicant_postal_code', 'x');
      $firstY = $this->coordinates['applicant_postal_code']['firstY'] ?? $this->coord('applicant_postal_code', 'y');
      $pdf->SetXY($firstX, $firstY);
      $pdf->Cell(0, 0, $firstPart, 0, 0, 'L');

      // 後半4桁
      $lastX = $this->coordinates['applicant_postal_code']['lastX'] ?? ($firstX + ($this->coordinates['applicant_postal_code']['postalCodeGap'] ?? 2));
      $lastY = $this->coordinates['applicant_postal_code']['lastY'] ?? $firstY;
      $pdf->SetXY($lastX, $lastY);
      $pdf->Cell(0, 0, $lastPart, 0, 0, 'L');

      $pdf->SetFontSize(10);
    }

    // 申請者住所
    if ($this->sampleDataMode && isset($this->customSampleData['applicant_address'])) {
      if ($this->hasCoord('applicant_address') && $this->customSampleData['applicant_address']) {
        $pdf->SetFontSize($this->coord('applicant_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'applicant_address', (string)$this->customSampleData['applicant_address']);
        $pdf->SetFontSize(10);
      }
    } else {
      $applicantAddress = ($clinicUser->prefecture ?? '') . ($clinicUser->city ?? '') . ($clinicUser->address_1 ?? '') . ($clinicUser->address_2 ?? '') . ($clinicUser->address_3 ?? '');
      if ($this->hasCoord('applicant_address') && $applicantAddress) {
        $pdf->SetFontSize($this->coord('applicant_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'applicant_address', (string)$applicantAddress);
        $pdf->SetFontSize(10);
      }
    }

    // 申請者氏名
    if ($this->sampleDataMode && isset($this->customSampleData['applicant_name'])) {
      if ($this->hasCoord('applicant_name') && $this->customSampleData['applicant_name']) {
        $pdf->SetFontSize($this->coord('applicant_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'applicant_name', (string)$this->customSampleData['applicant_name']);
        $pdf->SetFontSize(10);
      }
    } else {
      $applicantName = ($clinicUser->last_name ?? '') . ' ' . ($clinicUser->first_name ?? '');
      if ($this->hasCoord('applicant_name') && trim($applicantName)) {
        $pdf->SetFontSize($this->coord('applicant_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'applicant_name', trim($applicantName));
        $pdf->SetFontSize(10);
      }
    }

    // 申請者氏名（フリガナ）
    if ($this->sampleDataMode && isset($this->customSampleData['applicant_name_furigana'])) {
      if ($this->hasCoord('applicant_name_furigana') && $this->customSampleData['applicant_name_furigana']) {
        $pdf->SetFontSize($this->coord('applicant_name_furigana', 'fontSize'));
        $this->drawTextByKey($pdf, 'applicant_name_furigana', (string)$this->customSampleData['applicant_name_furigana']);
        $pdf->SetFontSize(10);
      }
    } else {
      $furigana = ($clinicUser->last_name_furigana ?? '') . ' ' . ($clinicUser->first_name_furigana ?? '');
      if ($this->hasCoord('applicant_name_furigana') && trim($furigana)) {
        $pdf->SetFontSize($this->coord('applicant_name_furigana', 'fontSize'));
        $this->drawTextByKey($pdf, 'applicant_name_furigana', trim($furigana));
        $pdf->SetFontSize(10);
      }
    }

    // 申請者電話番号
    if ($this->sampleDataMode && isset($this->customSampleData['applicant_phone'])) {
      if ($this->hasCoord('applicant_phone') && $this->customSampleData['applicant_phone']) {
        $pdf->SetFontSize($this->coord('applicant_phone', 'fontSize'));
        $this->drawTextByKey($pdf, 'applicant_phone', (string)$this->customSampleData['applicant_phone']);
        $pdf->SetFontSize(10);
      }
    } else {
      $phone = $clinicUser->phone ?? '';
      if ($this->hasCoord('applicant_phone') && $phone) {
        $pdf->SetFontSize($this->coord('applicant_phone', 'fontSize'));
        $this->drawTextByKey($pdf, 'applicant_phone', (string)$phone);
        $pdf->SetFontSize(10);
      }
    }
  }
  /**
   * 代理人情報セクションを埋める
   */
  private function fillAgentInfoSection(Fpdi $pdf, $clinicInfo): void
  {
    if (!$clinicInfo) {
      return;
    }

    // 代理人郵便番号
    if ($this->hasCoord('agent_postal_code')) {
      $pdf->SetFontSize($this->coord('agent_postal_code', 'fontSize'));
      $agentPostalCode = $this->sampleDataMode && isset($this->customSampleData['agent_postal_code'])
        ? $this->customSampleData['agent_postal_code']
        : ($clinicInfo->postal_code ?? '');
      // ハイフンを除去
      $cleanPostalCode = str_replace('-', '', $agentPostalCode);
      // 7桁の数字を〒 XXX-XXXX形式にフォーマット
      $formattedPostalCode = $agentPostalCode;
      if (preg_match('/^\d{7}$/', $cleanPostalCode)) {
        $formattedPostalCode = '〒 ' . substr($cleanPostalCode, 0, 3) . '-' . substr($cleanPostalCode, 3, 4);
      }
      $this->drawTextByKey($pdf, 'agent_postal_code', (string)$formattedPostalCode);
    }

    // 代理人住所
    if ($this->sampleDataMode && isset($this->customSampleData['agent_address'])) {
      $agentAddress = $this->customSampleData['agent_address'];
    } else {
      $agentAddress = ($clinicInfo->address_1 ?? '') .
                      ($clinicInfo->address_2 ?? '') .
                      ($clinicInfo->address_3 ?? '');
    }
    if ($this->hasCoord('agent_address')) {
      $pdf->SetFontSize($this->coord('agent_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'agent_address', (string)$agentAddress);
      $pdf->SetFontSize(10);
    }

    // 代理人氏名
    if ($this->sampleDataMode && isset($this->customSampleData['agent_name'])) {
      $agentName = $this->customSampleData['agent_name'];
    } else {
      $agentName = ($clinicInfo->owner_last_name ?? '') . ' ' . ($clinicInfo->owner_first_name ?? '');
      $agentName = trim($agentName);
    }
    if ($this->hasCoord('agent_name')) {
      $pdf->SetFontSize($this->coord('agent_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'agent_name', (string)$agentName);
      $pdf->SetFontSize(10);
    }

    // 代理人氏名（フリガナ）
    if ($this->sampleDataMode && isset($this->customSampleData['agent_name_furigana'])) {
      if ($this->hasCoord('agent_name_furigana') && $this->customSampleData['agent_name_furigana']) {
        $pdf->SetFontSize($this->coord('agent_name_furigana', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_name_furigana', (string)$this->customSampleData['agent_name_furigana']);
        $pdf->SetFontSize(10);
      }
    } else {
      // ノーマルモード: clinic_infoテーブルにはフリガナフィールドがないため、
      // 代理人氏名フリガナは描画しない（座標調整UI用に座標のみ定義）
      // 必要な場合は、将来的にclinic_infoテーブルにフリガナカラムを追加
    }

    // 代理人電話番号
    if ($this->sampleDataMode && isset($this->customSampleData['agent_phone'])) {
      if ($this->hasCoord('agent_phone') && $this->customSampleData['agent_phone']) {
        $pdf->SetFontSize($this->coord('agent_phone', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_phone', (string)$this->customSampleData['agent_phone']);
        $pdf->SetFontSize(10);
      }
    } else {
      $phone = $clinicInfo->phone ?? '';
      if ($this->hasCoord('agent_phone') && $phone) {
        $pdf->SetFontSize($this->coord('agent_phone', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_phone', (string)$phone);
        $pdf->SetFontSize(10);
      }
    }
  }
  /**
   * 支払機関情報セクションを埋める
   */
  private function fillPaymentInstitutionSection(Fpdi $pdf): void
  {
    // 支払機関名
    if ($this->sampleDataMode && isset($this->customSampleData['payment_institution_name'])) {
      if ($this->hasCoord('payment_institution_name') && $this->customSampleData['payment_institution_name']) {
        $pdf->SetFontSize($this->coord('payment_institution_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'payment_institution_name', (string)$this->customSampleData['payment_institution_name']);
        $pdf->SetFontSize(10);
      }
    }

    // 支払機関住所
    if ($this->sampleDataMode && isset($this->customSampleData['payment_institution_address'])) {
      if ($this->hasCoord('payment_institution_address') && $this->customSampleData['payment_institution_address']) {
        $pdf->SetFontSize($this->coord('payment_institution_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'payment_institution_address', (string)$this->customSampleData['payment_institution_address']);
        $pdf->SetFontSize(10);
      }
    }

    // 支払機関郵便番号
    if ($this->sampleDataMode && isset($this->customSampleData['payment_institution_postal_code'])) {
      $this->fillBoxesByKey($pdf, 'payment_institution_postal_code', (string)$this->customSampleData['payment_institution_postal_code'], 7, 4.5);
    }

    // 支払機関電話番号
    if ($this->sampleDataMode && isset($this->customSampleData['payment_institution_phone'])) {
      if ($this->hasCoord('payment_institution_phone') && $this->customSampleData['payment_institution_phone']) {
        $pdf->SetFontSize($this->coord('payment_institution_phone', 'fontSize'));
        $this->drawTextByKey($pdf, 'payment_institution_phone', (string)$this->customSampleData['payment_institution_phone']);
        $pdf->SetFontSize(10);
      }
    }
  }
  /**
   * 一時被保険者名セクションを埋める
   */
  private function fillTemporaryInsurerNameSection(Fpdi $pdf, string $fullName): void
  {
    // 委任申請者氏名
    if ($this->sampleDataMode && isset($this->customSampleData['temporary_insurer_name'])) {
      if ($this->hasCoord('temporary_insurer_name') && $this->customSampleData['temporary_insurer_name']) {
        $pdf->SetFontSize($this->coord('temporary_insurer_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'temporary_insurer_name', (string)$this->customSampleData['temporary_insurer_name']);
        $pdf->SetFontSize(10);
      }
    } else {
      if ($this->hasCoord('temporary_insurer_name') && $fullName) {
        $pdf->SetFontSize($this->coord('temporary_insurer_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'temporary_insurer_name', $fullName);
        $pdf->SetFontSize(10);
      }
    }
  }
}
