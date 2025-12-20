<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * はり・きゅう療養費支給申請書PDF生成サービス
 */
class AcupunctureBenefitPdfService
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
    $configPath = storage_path('app/config/acupuncture_benefit_coordinates.json');

    if (file_exists($configPath)) {
      $json = file_get_contents($configPath);
      $this->coordinates = json_decode($json, true);
      
      // ラジオグループの整合性を確保（複数の isSelected: true が残っていないか確認）
      $this->ensureRadioGroupIntegrity();
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
        
        // グループ内に isSelected: true が1つもない場合は、最初のフィールドを true にする
        if ($firstSelectedKey === null) {
          foreach ($this->coordinates as $k => $f) {
            if (isset($f['radioGroup']) && $f['radioGroup'] === $groupName) {
              $this->coordinates[$k]['isSelected'] = true;
              break;
            }
          }
        }
      }
    }
  }

  /**
   * デフォルト座標を取得
   */
  protected function getDefaultCoordinates(): array
  {
    // JSONファイルと同じ構造でデフォルト値を返す
    $configPath = storage_path('app/config/acupuncture_benefit_coordinates.json');
    $json = file_get_contents($configPath);
    return json_decode($json, true);
  }

  /**
   * 座標値を取得するヘルパーメソッド
   */
  protected function coord(string $key, string $property = 'x')
  {
    return $this->coordinates[$key][$property] ?? 0;
  }

  /**
   * 座標が存在するかチェック
   */
  protected function hasCoord(string $key): bool
  {
    return isset($this->coordinates[$key]);
  }

  /**
   * PDF生成
   *
   * @param array $clinicUserIds 利用者ID配列
   * @param string $serviceYearMonth サービス提供年月 (YYYY-MM)
   * @param string $submissionDate 提出年月日 (YYYY-MM-DD)
   * @return string PDFバイナリデータ
   */
  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);

    foreach ($clinicUserIds as $clinicUserId) {
      $data = $this->fetchData($clinicUserId, $serviceYearMonth);

      if ($data) {
        $this->addPage($pdf, $data, $submissionDate);
      }
    }

    return $pdf->Output('', 'S'); // バイナリとして返却
  }

  /**
   * データ取得
   *
   * @param int $clinicUserId
   * @param string $serviceYearMonth
   * @return array|null
   */
  protected function fetchData(int $clinicUserId, string $serviceYearMonth): ?array
  {
    // サンプルデータ表示モードの場合
    if ($this->sampleDataMode) {
      return $this->getSampleData($serviceYearMonth);
    }

    // 利用者情報取得（性別情報もJOIN）
    $clinicUser = DB::table('clinic_users')
      ->leftJoin('gender', 'clinic_users.gender_id', '=', 'gender.id')
      ->where('clinic_users.id', $clinicUserId)
      ->select('clinic_users.*', 'gender.gender')
      ->first();

    if (!$clinicUser) {
      \Log::error('利用者情報が見つかりません', ['clinic_user_id' => $clinicUserId]);
      return null;
    }

    // 保険情報取得（保険者情報、続柄、給付割合もJOIN）
    $insurance = DB::table('insurances')
      ->leftJoin('insurers', 'insurances.insurers_id', '=', 'insurers.id')
      ->leftJoin('relationships_with_clinic_user', 'insurances.relationship_with_clinic_user_id', '=', 'relationships_with_clinic_user.id')
      ->leftJoin('expenses_borne_ratios', 'insurances.expenses_borne_ratio_id', '=', 'expenses_borne_ratios.id')
      ->where('insurances.clinic_user_id', $clinicUserId)
      ->orderBy('insurances.created_at', 'desc')
      ->select(
        'insurances.*',
        'insurers.insurer_number',
        'insurers.insurer_name',
        'relationships_with_clinic_user.relationship',
        'expenses_borne_ratios.expenses_borne_ratio'
      )
      ->first();

    if (!$insurance) {
      \Log::warning('保険情報が見つかりません', ['clinic_user_id' => $clinicUserId]);
    }

    // はり・きゅう同意書情報取得（請求区分、転帰、業務上区分もJOIN）
    $consent = DB::table('consents_acupuncture')
      ->leftJoin('bill_categories', 'consents_acupuncture.bill_category_id', '=', 'bill_categories.id')
      ->leftJoin('outcomes', 'consents_acupuncture.outcome_id', '=', 'outcomes.id')
      ->leftJoin('work_scope_types', 'consents_acupuncture.work_scope_type_id', '=', 'work_scope_types.id')
      ->where('consents_acupuncture.clinic_user_id', $clinicUserId)
      ->orderBy('consents_acupuncture.consenting_date', 'desc')
      ->select(
        'consents_acupuncture.*',
        'bill_categories.bill_category',
        'outcomes.outcome',
        'work_scope_types.work_scope_type'
      )
      ->first();

    if (!$consent) {
      \Log::warning('はり・きゅう同意書情報が見つかりません', ['clinic_user_id' => $clinicUserId]);
    }

    // 施術実績取得（対象年月）
    $records = DB::table('records')
      ->where('clinic_user_id', $clinicUserId)
      ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$serviceYearMonth])
      ->orderBy('date')
      ->get();

    if ($records->isEmpty()) {
      \Log::warning('施術実績が見つかりません', [
        'clinic_user_id' => $clinicUserId,
        'service_year_month' => $serviceYearMonth,
      ]);
    }

    // 施術所情報取得
    $clinicInfo = DB::table('clinic_info')->first();

    if (!$clinicInfo) {
      \Log::error('施術所情報が見つかりません');
    }

    // 施術料金データ取得（最新のデータ）
    $treatmentFees = DB::table('treatment_fees')
      ->orderBy('created_at', 'desc')
      ->first();

    if (!$treatmentFees) {
      \Log::warning('施術料金データが見つかりません');
    }

    return [
      'clinic_user' => $clinicUser,
      'insurance' => $insurance,
      'consent' => $consent,
      'records' => $records,
      'clinic_info' => $clinicInfo,
      'treatment_fees' => $treatmentFees,
      'service_year_month' => $serviceYearMonth,
    ];
  }

  /**
   * PDFページ追加
   *
   * @param Fpdi $pdf
   * @param array $data
   * @param string $submissionDate
   * @return void
   */
  protected function addPage(Fpdi $pdf, array $data, string $submissionDate): void
  {
    $pdf->AddPage();

    // テンプレートPDF読み込み
    $templatePath = storage_path('app/templates/acupuncture_benefit_form.pdf');

    if (file_exists($templatePath)) {
      $pageCount = $pdf->setSourceFile($templatePath);
      $tplId = $pdf->importPage(1);
      $pdf->useTemplate($tplId, 0, 0, null, null, true);
    }


    // フォント設定（日本語フォント: kozminproregular）
    $pdf->SetFont('kozminproregular', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // データ埋め込み（座標は後で調整が必要）
    $this->fillFormFields($pdf, $data, $submissionDate);
  }

  /**
   * フォームフィールド埋め込み
   *
   * @param Fpdi $pdf
   * @param array $data
   * @param string $submissionDate
   * @return void
   */
  protected function fillFormFields(Fpdi $pdf, array $data, string $submissionDate): void
  {
    $clinicUser = $data['clinic_user'];
    $insurance = $data['insurance'];
    $consent = $data['consent'];
    $records = $data['records'];
    $clinicInfo = $data['clinic_info'];

    // サービス提供年月を分解
    [$year, $month] = explode('-', $data['service_year_month']);
    $japaneseYear = $this->convertToJapaneseYear($year, $month);

    // === 上部：年月 ===
    // タイトル行「療養費支給申請書（　年　月分）」
    // 年：上段に元号、下段に年数
    $pdf->SetFontSize($this->coord('title_year_era', 'fontSize'));
    $this->drawTextByKey($pdf, 'title_year_era', (string)$japaneseYear['era']);
    $pdf->SetFontSize($this->coord('title_year_number', 'fontSize'));
    $this->drawTextByKey($pdf, 'title_year_number', (string)$japaneseYear['year']);

    // 月
    $pdf->SetFontSize($this->coord('title_month', 'fontSize'));
    $this->drawTextByKey($pdf, 'title_month', (string)(int)$month);

    // === 機関コード（医療機関番号） ===
    if ($clinicInfo && isset($clinicInfo->medical_institution_number)) {
      $pdf->SetFontSize($this->coord('institution_code', 'fontSize'));
      // 医療機関番号は通常7桁
      $this->fillBoxesByKey($pdf, 'institution_code', (string)$clinicInfo->medical_institution_number, 7, 5.6);
      $pdf->SetFontSize(10);
    } else {
      \Log::warning('医療機関番号が設定されていません', ['clinic_info' => $clinicInfo]);
    }

    // === 公費負担者番号（8桁） ===
    if ($insurance && isset($insurance->public_funds_payer_code) && $insurance->public_funds_payer_code) {
      $pdf->SetFontSize($this->coord('public_funds_payer_number', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'public_funds_payer_number', $insurance->public_funds_payer_code, 8, 5.6);
      $pdf->SetFontSize(10);
    }

    // === 公費受給者番号（7桁） ===
    if ($insurance && isset($insurance->public_funds_recipient_code) && $insurance->public_funds_recipient_code) {
      $pdf->SetFontSize($this->coord('public_funds_recipient_number', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'public_funds_recipient_number', $insurance->public_funds_recipient_code, 7, 5.6);
      $pdf->SetFontSize(10);
    }

    // === 区市町村番号（6桁） ===
    if ($insurance && isset($insurance->locality_code) && $insurance->locality_code) {
      $pdf->SetFontSize($this->coord('locality_code', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'locality_code', $insurance->locality_code, 6, 5.6);
      $pdf->SetFontSize(10);
    }

    // === 受給者番号（区市町村番号と種類の下） ===
    if ($insurance && isset($insurance->recipient_code) && $insurance->recipient_code) {
      $pdf->SetFontSize($this->coord('recipient_number', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'recipient_number', $insurance->recipient_code, 6, 5.6);
      $pdf->SetFontSize(10);
    } else {
      \Log::warning('受給者番号が設定されていません', ['insurance' => $insurance]);
    }

    // === 保険者番号 ===
    if ($insurance && isset($insurance->insurer_number) && $insurance->insurer_number) {
      $pdf->SetFontSize($this->coord('insurer_number', 'fontSize'));
      $this->fillBoxesByKey($pdf, 'insurer_number', $insurance->insurer_number, 8, 5.6);
      $pdf->SetFontSize(10);
    } else {
      \Log::warning('保険者番号が設定されていません', ['insurance' => $insurance]);
    }

    // === 被保険者証記号番号 ===
    // 保険種別1によって表示形式が異なる
    // 社・国・組(ID:1): 記号(code_number) + 番号(account_number)
    // 公費(ID:2), 後期(ID:3), 退職(ID:4): 被保険者番号(insured_number)のみ
    if ($insurance) {
      $insuranceType1Id = $insurance->insurance_type_1_id ?? null;
      $displayText = '';

      if ($insuranceType1Id == 1) {
        // 社・国・組の場合: 記号・番号形式
        $symbol = $insurance->code_number ?? '';
        $number = $insurance->account_number ?? '';
        if ($symbol || $number) {
          $displayText = trim(($symbol ?: '') . ($symbol && $number ? '・' : '') . ($number ?: ''));
        }
      } else {
        // 公費・後期・退職の場合: 被保険者番号のみ
        $displayText = $insurance->insured_number ?? '';
      }

      if ($displayText) {
        $pdf->SetFontSize($this->coord('insurance_symbol', 'fontSize'));
        $this->drawTextByKey($pdf, 'insurance_symbol', (string)$displayText);
        $pdf->SetFontSize(10);
      } else {
        \Log::warning('被保険者証記号番号が設定されていません', [
          'insurance_type_1_id' => $insuranceType1Id,
          'insurance' => $insurance
        ]);
      }
    } else {
      \Log::warning('保険情報がありません');
    }

    // === 療養を受けた者の氏名 ===
    $fullName = ($clinicUser->last_name ?? '') . ' ' . ($clinicUser->first_name ?? '');
    $fullNameKana = ($clinicUser->last_kana ?? '') . ' ' . ($clinicUser->first_kana ?? '');

    if (empty($fullName)) {
      \Log::warning('患者氏名が設定されていません', ['clinic_user' => $clinicUser]);
    }
    if (empty($fullNameKana)) {
      \Log::warning('患者氏名（カナ）が設定されていません', ['clinic_user' => $clinicUser]);
    }

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

    // === 性別（男・女に○を表示） ===
    // isSelectedフラグをチェック（サンプルデータの場合）
    $genderKey = null;
    if (isset($this->coordinates['patient_gender_male']['isSelected']) && $this->coordinates['patient_gender_male']['isSelected']) {
      $genderKey = 'patient_gender_male';
    } elseif (isset($this->coordinates['patient_gender_female']['isSelected']) && $this->coordinates['patient_gender_female']['isSelected']) {
      $genderKey = 'patient_gender_female';
    }
    
    if ($genderKey) {
      // isSelectedで指定されたフィールドに楕円を表示
      $this->drawEllipseByKey($pdf, $genderKey);
    } elseif (isset($clinicUser->gender) && $clinicUser->gender) {
      // isSelectedがない場合は実データから判定
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
        }
      }

      $pdf->SetFontSize($this->coord('birthday_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'birthday_year', (string)$birthJapaneseYear['year']);

      $pdf->SetFontSize($this->coord('birthday_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'birthday_month', (string)(int)$birthMonth);

      $pdf->SetFontSize($this->coord('birthday_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'birthday_day', (string)(int)$birthDay);

      $pdf->SetFontSize(10);
    } else {
      \Log::warning('生年月日が設定されていません', ['clinic_user' => $clinicUser]);
    }

    // === 発病又は負傷年月日 ===
    if ($consent && isset($consent->onset_and_injury_date) && $consent->onset_and_injury_date) {
      [$onsetYear, $onsetMonth, $onsetDay] = explode('-', $consent->onset_and_injury_date);
      $onsetJapaneseYear = $this->convertToJapaneseYear((int)$onsetYear, (int)$onsetMonth);

      $pdf->SetFontSize($this->coord('onset_date_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'onset_date_year', (string)$onsetJapaneseYear['year']);

      $pdf->SetFontSize($this->coord('onset_date_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'onset_date_month', (string)(int)$onsetMonth);

      $pdf->SetFontSize($this->coord('onset_date_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'onset_date_day', (string)(int)$onsetDay);

      $pdf->SetFontSize(10);
    }

    // === 傷病名（発病又は負傷年月日の隣） ===
    if ($consent) {
      $onsetIllnessName = '';

      // illness_name_acupuncture_idから病名を取得
      if (isset($consent->illness_name_acupuncture_id) && $consent->illness_name_acupuncture_id) {
        $illness = DB::table('illnesses_acupuncture')
          ->where('id', $consent->illness_name_acupuncture_id)
          ->first();
        if ($illness && isset($illness->illness_name_acupuncture)) {
          $onsetIllnessName = $illness->illness_name_acupuncture;
        }
      }

      // 追記がある場合は追加
      if (isset($consent->illness_name_acupuncture_addendum) && $consent->illness_name_acupuncture_addendum) {
        $onsetIllnessName .= ($onsetIllnessName ? '、' : '') . $consent->illness_name_acupuncture_addendum;
      }

      if ($onsetIllnessName) {
        $pdf->SetFontSize($this->coord('onset_illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_illness_name', (string)$onsetIllnessName);
        $pdf->SetFontSize(10);
      }
    }

    // === 業務上・外、第三者行為の有無 ===
    // isSelectedフラグをチェック（サンプルデータの場合）
    $workScopeTypeKey = null;
    if (isset($this->coordinates['work_scope_type_1']['isSelected']) && $this->coordinates['work_scope_type_1']['isSelected']) {
      $workScopeTypeKey = 'work_scope_type_1';
    } elseif (isset($this->coordinates['work_scope_type_2']['isSelected']) && $this->coordinates['work_scope_type_2']['isSelected']) {
      $workScopeTypeKey = 'work_scope_type_2';
    } elseif (isset($this->coordinates['work_scope_type_3']['isSelected']) && $this->coordinates['work_scope_type_3']['isSelected']) {
      $workScopeTypeKey = 'work_scope_type_3';
    }
    
    if ($workScopeTypeKey) {
      // isSelectedで指定されたフィールドに楕円を表示
      $this->drawEllipseByKey($pdf, $workScopeTypeKey);
    } elseif ($consent && isset($consent->work_scope_type) && $consent->work_scope_type) {
      // isSelectedがない場合は実データから判定
      // 楕円を表示 (1.業務上 2.第三者行為である 3.その他)
      if ($consent->work_scope_type === '業務上') {
        $this->drawEllipseByKey($pdf, 'work_scope_type_1');
      } elseif ($consent->work_scope_type === '第三者行為である') {
        $this->drawEllipseByKey($pdf, 'work_scope_type_2');
      } elseif ($consent->work_scope_type === 'その他') {
        $this->drawEllipseByKey($pdf, 'work_scope_type_3');
      }
    }

    // === 初療年月日 ===
    if ($records->isNotEmpty()) {
      $firstRecord = $records->first();
      [$firstYear, $firstMonth, $firstDay] = explode('-', $firstRecord->date);
      $firstJapaneseYear = $this->convertToJapaneseYear((int)$firstYear, (int)$firstMonth);

      $pdf->SetFontSize($this->coord('first_treatment_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'first_treatment_year', (string)$firstJapaneseYear['year']);

      $pdf->SetFontSize($this->coord('first_treatment_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'first_treatment_month', (string)(int)$firstMonth);

      $pdf->SetFontSize($this->coord('first_treatment_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'first_treatment_day', (string)(int)$firstDay);

      $pdf->SetFontSize(10);
    }

    // === 施術期間 ===
    if ($records->isNotEmpty()) {
      $firstDate = $records->first()->date;
      $lastDate = $records->last()->date;

      [$startYear, $startMonth, $startDay] = explode('-', $firstDate);
      [$endYear, $endMonth, $endDay] = explode('-', $lastDate);

      $startJapaneseYear = $this->convertToJapaneseYear((int)$startYear, (int)$startMonth);
      $endJapaneseYear = $this->convertToJapaneseYear((int)$endYear, (int)$endMonth);

      // 自：開始日
      $pdf->SetFontSize($this->coord('treatment_start_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_start_year', (string)$startJapaneseYear['year']);

      $pdf->SetFontSize($this->coord('treatment_start_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_start_month', (string)(int)$startMonth);

      $pdf->SetFontSize($this->coord('treatment_start_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_start_day', (string)(int)$startDay);

      // 至：終了日
      $pdf->SetFontSize($this->coord('treatment_end_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_end_year', (string)$endJapaneseYear['year']);

      $pdf->SetFontSize($this->coord('treatment_end_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_end_month', (string)(int)$endMonth);

      $pdf->SetFontSize($this->coord('treatment_end_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_end_day', (string)(int)$endDay);

      // 実日数
      $pdf->SetFontSize($this->coord('treatment_days', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_days', (string)$records->count());

      $pdf->SetFontSize(10);
    }

    // === 請求区分（新規・継続） ===
    // isSelectedフラグをチェック（サンプルデータの場合）
    $billCategoryKey = null;
    if (isset($this->coordinates['bill_category_new']['isSelected']) && $this->coordinates['bill_category_new']['isSelected']) {
      $billCategoryKey = 'bill_category_new';
    } elseif (isset($this->coordinates['bill_category_continued']['isSelected']) && $this->coordinates['bill_category_continued']['isSelected']) {
      $billCategoryKey = 'bill_category_continued';
    }
    
    // 楕円を描画（線を太くする）
    $pdf->SetLineWidth(0.5);
    
    if ($billCategoryKey) {
      // isSelectedで指定されたフィールドに楕円を表示
      $x = $this->coord($billCategoryKey, 'x');
      $y = $this->coord($billCategoryKey, 'y');
      $width = $this->coord($billCategoryKey, 'ellipseWidth') ?? 8;
      $height = $this->coord($billCategoryKey, 'ellipseHeight') ?? 5;
      $pdf->Ellipse($x, $y, $width / 2, $height / 2, 0, 0, 360, 'D');
    } elseif ($consent && isset($consent->bill_category) && $consent->bill_category) {
      // isSelectedがない場合は実データから判定
      if ($consent->bill_category === '新規') {
        $x = $this->coord('bill_category_new', 'x');
        $y = $this->coord('bill_category_new', 'y');
        $width = $this->coord('bill_category_new', 'ellipseWidth') ?? 8;
        $height = $this->coord('bill_category_new', 'ellipseHeight') ?? 5;
        $pdf->Ellipse($x, $y, $width / 2, $height / 2, 0, 0, 360, 'D');
      } elseif ($consent->bill_category === '継続') {
        $x = $this->coord('bill_category_continued', 'x');
        $y = $this->coord('bill_category_continued', 'y');
        $width = $this->coord('bill_category_continued', 'ellipseWidth') ?? 8;
        $height = $this->coord('bill_category_continued', 'ellipseHeight') ?? 5;
        $pdf->Ellipse($x, $y, $width / 2, $height / 2, 0, 0, 360, 'D');
      }
    }
    
    $pdf->SetLineWidth(0.2);

    // === 転帰（継続・治癒・中止・転医） ===
    // 楕円を描画（線を太くする）
    $pdf->SetLineWidth(0.5);

    // isSelected フラグをチェック（サンプルデータの場合）
    $outcomeKey = null;
    if (isset($this->coordinates['outcome_continued']['isSelected']) && $this->coordinates['outcome_continued']['isSelected']) {
      $outcomeKey = 'outcome_continued';
    } elseif (isset($this->coordinates['outcome_cured']['isSelected']) && $this->coordinates['outcome_cured']['isSelected']) {
      $outcomeKey = 'outcome_cured';
    } elseif (isset($this->coordinates['outcome_discontinued']['isSelected']) && $this->coordinates['outcome_discontinued']['isSelected']) {
      $outcomeKey = 'outcome_discontinued';
    } elseif (isset($this->coordinates['outcome_transferred']['isSelected']) && $this->coordinates['outcome_transferred']['isSelected']) {
      $outcomeKey = 'outcome_transferred';
    }

    // outcomeKeyが設定されたら、そのオプションの座標を使用
    if ($outcomeKey) {
      $x = $this->coord($outcomeKey, 'x');
      $y = $this->coord($outcomeKey, 'y');
      $width = $this->coord($outcomeKey, 'ellipseWidth') ?? 8;
      $height = $this->coord($outcomeKey, 'ellipseHeight') ?? 5;
      $pdf->Ellipse($x, $y, $width / 2, $height / 2, 0, 0, 360, 'D');
    } elseif ($consent && isset($consent->outcome) && $consent->outcome) {
      // isSelected フラグがない場合は実データから判定
      if ($consent->outcome === '継続') {
        $x = $this->coord('outcome_continued', 'x');
        $y = $this->coord('outcome_continued', 'y');
        $width = $this->coord('outcome_continued', 'ellipseWidth') ?? 8;
        $height = $this->coord('outcome_continued', 'ellipseHeight') ?? 5;
        $pdf->Ellipse($x, $y, $width / 2, $height / 2, 0, 0, 360, 'D');
      } elseif ($consent->outcome === '治癒') {
        $x = $this->coord('outcome_cured', 'x');
        $y = $this->coord('outcome_cured', 'y');
        $width = $this->coord('outcome_cured', 'ellipseWidth') ?? 8;
        $height = $this->coord('outcome_cured', 'ellipseHeight') ?? 5;
        $pdf->Ellipse($x, $y, $width / 2, $height / 2, 0, 0, 360, 'D');
      } elseif ($consent->outcome === '中止') {
        $x = $this->coord('outcome_discontinued', 'x');
        $y = $this->coord('outcome_discontinued', 'y');
        $width = $this->coord('outcome_discontinued', 'ellipseWidth') ?? 8;
        $height = $this->coord('outcome_discontinued', 'ellipseHeight') ?? 5;
        $pdf->Ellipse($x, $y, $width / 2, $height / 2, 0, 0, 360, 'D');
      } elseif ($consent->outcome === '転医') {
        $x = $this->coord('outcome_transferred', 'x');
        $y = $this->coord('outcome_transferred', 'y');
        $width = $this->coord('outcome_transferred', 'ellipseWidth') ?? 8;
        $height = $this->coord('outcome_transferred', 'ellipseHeight') ?? 5;
        $pdf->Ellipse($x, $y, $width / 2, $height / 2, 0, 0, 360, 'D');
      }
    }
    $pdf->SetLineWidth(0.2);

    // === 傷病名（施術内容欄のチェックボックス） ===
    // サンプルデータ表示モードの場合はisSelectedフラグを使用
    if ($this->sampleDataMode) {
      // isSelectedフラグをチェック
      for ($i = 1; $i <= 7; $i++) {
        $key = 'illness_name_' . $i;
        if (isset($this->coordinates[$key]['isSelected']) && $this->coordinates[$key]['isSelected']) {
          $this->drawEllipseByKey($pdf, $key);

          // 「その他」の場合、追記テキストを表示
          if ($i === 7 && isset($this->customSampleData['disease']) && $this->customSampleData['disease']) {
            $pdf->SetFontSize($this->coord('illness_name_other_text', 'fontSize'));
            $this->drawTextByKey($pdf, 'illness_name_other_text', (string)$this->customSampleData['disease']);
            $pdf->SetFontSize(10);
          }
          break; // 1つだけ選択
        }
      }
    } elseif ($consent && isset($consent->illness_name_acupuncture_id) && $consent->illness_name_acupuncture_id) {
      // 実データモード：illness_name_acupuncture_idに応じて楕円を表示
      // 1:神経痛, 2:リウマチ, 3:頸腕症候群, 4:五十肩, 5:腰痛症, 6:頸椎捻挫後遺症, 7:その他
      $illnessId = (int)$consent->illness_name_acupuncture_id;

      if ($illnessId >= 1 && $illnessId <= 7) {
        $this->drawEllipseByKey($pdf, 'illness_name_' . $illnessId);
      }

      // 「その他」の場合、追記テキストを表示
      if ($illnessId === 7 && isset($consent->illness_name_acupuncture_addendum) && $consent->illness_name_acupuncture_addendum) {
        $pdf->SetFontSize($this->coord('illness_name_other_text', 'fontSize'));
        $this->drawTextByKey($pdf, 'illness_name_other_text', (string)$consent->illness_name_acupuncture_addendum);
        $pdf->SetFontSize(10);
      }
    }

    // === 施術日カレンダー（1-31日） ===
    $this->fillServiceDates($pdf, $records);

    // === 施術所情報 ===
    if ($clinicInfo) {
      // 施術日（年月日）
      $submissionParts = explode('-', $submissionDate);
      $submissionJapaneseYear = $this->convertToJapaneseYear((int)$submissionParts[0], (int)$submissionParts[1]);

      $pdf->SetFontSize($this->coord('clinic_date_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_date_year', (string)$submissionJapaneseYear['year']);

      $pdf->SetFontSize($this->coord('clinic_date_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_date_month', (string)(int)$submissionParts[1]);

      $pdf->SetFontSize($this->coord('clinic_date_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_date_day', (string)(int)$submissionParts[2]);

      $pdf->SetFontSize(10);

      // 施術所郵便番号
      if ($this->hasCoord('clinic_postal_code')) {
        $pdf->SetFontSize($this->coord('clinic_postal_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_postal_code', (string)($clinicInfo->postal_code ?? ''));
      }

      // 施術所住所
      $clinicAddress = ($clinicInfo->address_1 ?? '') .
                       ($clinicInfo->address_2 ?? '') .
                       ($clinicInfo->address_3 ?? '');
      $pdf->SetFontSize($this->coord('clinic_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_address', (string)$clinicAddress);
      $pdf->SetFontSize(10);

      // 施術所名称
      $pdf->SetFontSize($this->coord('clinic_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_name', (string)($clinicInfo->clinic_name ?? ''));
      $pdf->SetFontSize(10);

      // 施術管理者氏名（施術者情報から取得）
      $therapist = DB::table('therapists')->first();
      if ($therapist) {
        $therapistName = ($therapist->last_name ?? '') . ' ' . ($therapist->first_name ?? '');
        if (empty(trim($therapistName))) {
          \Log::warning('施術管理者氏名が設定されていません', ['therapist' => $therapist]);
        }
        $pdf->SetFontSize($this->coord('clinic_manager', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_manager', (string)$therapistName);
        $pdf->SetFontSize(10);
      } else {
        \Log::warning('施術者情報が見つかりません');
      }

      // 電話番号
      if (empty($clinicInfo->phone ?? '')) {
        \Log::warning('施術所電話番号が設定されていません', ['clinic_info' => $clinicInfo]);
      }
      $pdf->SetFontSize($this->coord('clinic_phone', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_phone', (string)($clinicInfo->phone ?? ''));
      $pdf->SetFontSize(10);

      // === 保健所登録区分 ===
      if (isset($clinicInfo->health_center_registerd_location) && $clinicInfo->health_center_registerd_location) {
        // 1.施術所所在地 or 2.出張専門施術者住所地 の○を表示
        if (strpos($clinicInfo->health_center_registerd_location, '施術所') !== false) {
          $x = $this->coord('health_center_registration_1', 'x');
          $y = $this->coord('health_center_registration_1', 'y');
          $radius = $this->coord('health_center_registration_1', 'circleRadius') ?? 1.2;
          $pdf->Ellipse($x, $y, $radius, $radius, 0, 0, 360, 'D');
        } elseif (strpos($clinicInfo->health_center_registerd_location, '出張') !== false) {
          $x = $this->coord('health_center_registration_2', 'x');
          $y = $this->coord('health_center_registration_2', 'y');
          $radius = $this->coord('health_center_registration_2', 'circleRadius') ?? 1.2;
          $pdf->Ellipse($x, $y, $radius, $radius, 0, 0, 360, 'D');
        }
      }

      // === 登録記号番号（施術者番号） ===
      if (isset($clinicInfo->therapist_number) && $clinicInfo->therapist_number) {
        $pdf->SetFontSize($this->coord('therapist_registration_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'therapist_registration_number', (string)$clinicInfo->therapist_number);
        $pdf->SetFontSize(10);
      }
    }

    // === 同意記録欄 ===
    if ($consent) {
      // 同意医師氏名
      if (isset($consent->consenting_doctor_name) && $consent->consenting_doctor_name) {
        $pdf->SetFontSize($this->coord('consent_doctor_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_doctor_name', (string)$consent->consenting_doctor_name);
        $pdf->SetFontSize(10);
      }

      // 同意年月日
      if (isset($consent->consenting_date) && $consent->consenting_date) {
        [$consentYear, $consentMonth, $consentDay] = explode('-', $consent->consenting_date);
        $consentJapaneseYear = $this->convertToJapaneseYear((int)$consentYear, (int)$consentMonth);

        $pdf->SetFontSize($this->coord('consent_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_date_year', (string)$consentJapaneseYear['year']);

        $pdf->SetFontSize($this->coord('consent_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_date_month', (string)(int)$consentMonth);

        $pdf->SetFontSize($this->coord('consent_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_date_day', (string)(int)$consentDay);

        $pdf->SetFontSize(10);
      }

      // 同意書の傷病名（illness_nameと同じ内容を使用）
      if (isset($consent->illness_name_acupuncture_id) && $consent->illness_name_acupuncture_id) {
        $illness = DB::table('illnesses_acupuncture')
          ->where('id', $consent->illness_name_acupuncture_id)
          ->first();
        if ($illness && isset($illness->illness_name_acupuncture)) {
          $consentIllnessName = $illness->illness_name_acupuncture;
          if (isset($consent->illness_name_acupuncture_addendum) && $consent->illness_name_acupuncture_addendum) {
            $consentIllnessName .= '、' . $consent->illness_name_acupuncture_addendum;
          }
          $pdf->SetFontSize($this->coord('consent_illness_name', 'fontSize'));
          $this->drawTextByKey($pdf, 'consent_illness_name', (string)$consentIllnessName);
          $pdf->SetFontSize(10);
        }
      }

      // 要加療期間
      if (isset($consent->therapy_period) && $consent->therapy_period) {
        $pdf->SetFontSize($this->coord('therapy_period', 'fontSize'));
        $this->drawTextByKey($pdf, 'therapy_period', (string)$consent->therapy_period);
        $pdf->SetFontSize(10);
      }
    }

    // === 申請欄：提出年月日 ===
    $submissionParts = explode('-', $submissionDate);
    $submissionJapaneseYear = $this->convertToJapaneseYear((int)$submissionParts[0], (int)$submissionParts[1]);

    $pdf->SetFontSize($this->coord('submission_date_year', 'fontSize'));
    $this->drawTextByKey($pdf, 'submission_date_year', (string)$submissionJapaneseYear['year']);

    $pdf->SetFontSize($this->coord('submission_date_month', 'fontSize'));
    $this->drawTextByKey($pdf, 'submission_date_month', (string)(int)$submissionParts[1]);

    $pdf->SetFontSize($this->coord('submission_date_day', 'fontSize'));
    $this->drawTextByKey($pdf, 'submission_date_day', (string)(int)$submissionParts[2]);

    $pdf->SetFontSize(10);

    // === 申請者情報 ===
    // 申請者郵便番号
    if ($this->hasCoord('applicant_postal_code')) {
      $pdf->SetFontSize($this->coord('applicant_postal_code', 'fontSize'));
      $this->drawTextByKey($pdf, 'applicant_postal_code', (string)($clinicUser->postal_code ?? ''));
    }

    // 申請者住所
    $address = ($clinicUser->address_1 ?? '') .
               ($clinicUser->address_2 ?? '') .
               ($clinicUser->address_3 ?? '');
    $pdf->SetFontSize($this->coord('applicant_address', 'fontSize'));
    $this->drawTextByKey($pdf, 'applicant_address', (string)$address);
    $this->drawTextByKey($pdf, 'applicant_name', (string)$fullName);
    $pdf->SetFontSize(10);

    // === 代理人情報 ===
    if ($this->hasCoord('agent_postal_code') || $this->hasCoord('agent_address') || $this->hasCoord('agent_name')) {
      // カスタムサンプルデータから取得、なければ空文字
      $agentPostalCode = $this->customSampleData['agent_postal_code'] ?? '';
      $agentAddress = $this->customSampleData['agent_address'] ?? '';
      $agentName = $this->customSampleData['agent_name'] ?? '';

      if ($this->hasCoord('agent_postal_code') && $agentPostalCode) {
        $pdf->SetFontSize($this->coord('agent_postal_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_postal_code', (string)$agentPostalCode);
      }

      if ($this->hasCoord('agent_address') && $agentAddress) {
        $pdf->SetFontSize($this->coord('agent_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_address', (string)$agentAddress);
      }

      if ($this->hasCoord('agent_name') && $agentName) {
        $pdf->SetFontSize($this->coord('agent_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_name', (string)$agentName);
      }

      $pdf->SetFontSize(10);
    }

    // === 支払機関情報 ===
    // 支払区分
    if ($this->hasCoord('payment_method')) {
      $paymentMethod = $this->customSampleData['payment_method'] ?? '';
      if ($paymentMethod) {
        $pdf->SetFontSize($this->coord('payment_method', 'fontSize'));
        $this->drawTextByKey($pdf, 'payment_method', $paymentMethod);
      }
    }

    // 預金の種類
    if ($this->hasCoord('deposit_type')) {
      $depositType = $this->customSampleData['deposit_type'] ?? '';
      if ($depositType) {
        $pdf->SetFontSize($this->coord('deposit_type', 'fontSize'));
        $this->drawTextByKey($pdf, 'deposit_type', $depositType);
      }
    }

    // 金融機関名（種類）
    if ($this->hasCoord('financial_institution_type')) {
      $financialInstitutionType = $this->customSampleData['financial_institution_type'] ?? '';
      if ($financialInstitutionType) {
        $pdf->SetFontSize($this->coord('financial_institution_type', 'fontSize'));
        $this->drawTextByKey($pdf, 'financial_institution_type', $financialInstitutionType);
      }
    }

    // 金融機関名（詳細）
    if ($this->hasCoord('financial_institution_name')) {
      $financialInstitutionName = $this->customSampleData['financial_institution_name'] ?? '';
      if ($financialInstitutionName) {
        $pdf->SetFontSize($this->coord('financial_institution_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'financial_institution_name', $financialInstitutionName);
      }
    }

    // 本店支店出張所（種類）
    if ($this->hasCoord('branch_type')) {
      $branchType = $this->customSampleData['branch_type'] ?? '';
      if ($branchType) {
        $pdf->SetFontSize($this->coord('branch_type', 'fontSize'));
        $this->drawTextByKey($pdf, 'branch_type', $branchType);
      }
    }

    // 支店名
    if ($this->hasCoord('branch_name')) {
      $branchName = $this->customSampleData['branch_name'] ?? '';
      if ($branchName) {
        $pdf->SetFontSize($this->coord('branch_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'branch_name', $branchName);
      }
    }

    // 口座番号
    if ($this->hasCoord('account_number')) {
      $accountNumber = $this->customSampleData['account_number'] ?? '';
      if ($accountNumber) {
        $pdf->SetFontSize($this->coord('account_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'account_number', $accountNumber);
      }
    }

    // 口座名義
    if ($this->hasCoord('account_holder')) {
      $accountHolder = $this->customSampleData['account_holder'] ?? '';
      if ($accountHolder) {
        $pdf->SetFontSize($this->coord('account_holder', 'fontSize'));
        $this->drawTextByKey($pdf, 'account_holder', $accountHolder);
      }
    }

    $pdf->SetFontSize(10);

    // === 支払機関欄の楕円描画 ===
    $pdf->SetLineWidth(0.5);

    // 支払区分
    $paymentMethodKey = null;
    if (isset($this->coordinates['payment_method_transfer']['isSelected']) && $this->coordinates['payment_method_transfer']['isSelected']) {
      $paymentMethodKey = 'payment_method_transfer';
    } elseif (isset($this->coordinates['payment_method_bank']['isSelected']) && $this->coordinates['payment_method_bank']['isSelected']) {
      $paymentMethodKey = 'payment_method_bank';
    } elseif (isset($this->coordinates['payment_method_post']['isSelected']) && $this->coordinates['payment_method_post']['isSelected']) {
      $paymentMethodKey = 'payment_method_post';
    } elseif (isset($this->coordinates['payment_method_checking']['isSelected']) && $this->coordinates['payment_method_checking']['isSelected']) {
      $paymentMethodKey = 'payment_method_checking';
    }

    if ($paymentMethodKey) {
      $x = $this->coord($paymentMethodKey, 'x');
      $y = $this->coord($paymentMethodKey, 'y');
      $width = $this->coord($paymentMethodKey, 'ellipseWidth') ?? 8;
      $height = $this->coord($paymentMethodKey, 'ellipseHeight') ?? 4;
      $pdf->Ellipse($x, $y, $width / 2, $height / 2, 0, 0, 360, 'D');
    }

    // 預金の種類
    $depositTypeKey = null;
    if (isset($this->coordinates['deposit_type_normal']['isSelected']) && $this->coordinates['deposit_type_normal']['isSelected']) {
      $depositTypeKey = 'deposit_type_normal';
    } elseif (isset($this->coordinates['deposit_type_checking']['isSelected']) && $this->coordinates['deposit_type_checking']['isSelected']) {
      $depositTypeKey = 'deposit_type_checking';
    } elseif (isset($this->coordinates['deposit_type_notice']['isSelected']) && $this->coordinates['deposit_type_notice']['isSelected']) {
      $depositTypeKey = 'deposit_type_notice';
    }

    if ($depositTypeKey) {
      $x = $this->coord($depositTypeKey, 'x');
      $y = $this->coord($depositTypeKey, 'y');
      $width = $this->coord($depositTypeKey, 'ellipseWidth') ?? 8;
      $height = $this->coord($depositTypeKey, 'ellipseHeight') ?? 4;
      $pdf->Ellipse($x, $y, $width / 2, $height / 2, 0, 0, 360, 'D');
    }

    // 金融機関種類
    $financialInstitutionTypeKey = null;
    if (isset($this->coordinates['financial_institution_type_bank']['isSelected']) && $this->coordinates['financial_institution_type_bank']['isSelected']) {
      $financialInstitutionTypeKey = 'financial_institution_type_bank';
    } elseif (isset($this->coordinates['financial_institution_type_credit']['isSelected']) && $this->coordinates['financial_institution_type_credit']['isSelected']) {
      $financialInstitutionTypeKey = 'financial_institution_type_credit';
    } elseif (isset($this->coordinates['financial_institution_type_coop']['isSelected']) && $this->coordinates['financial_institution_type_coop']['isSelected']) {
      $financialInstitutionTypeKey = 'financial_institution_type_coop';
    }

    if ($financialInstitutionTypeKey) {
      $x = $this->coord($financialInstitutionTypeKey, 'x');
      $y = $this->coord($financialInstitutionTypeKey, 'y');
      $width = $this->coord($financialInstitutionTypeKey, 'ellipseWidth') ?? 8;
      $height = $this->coord($financialInstitutionTypeKey, 'ellipseHeight') ?? 4;
      $pdf->Ellipse($x, $y, $width / 2, $height / 2, 0, 0, 360, 'D');
    }

    // 本店支店出張所
    $branchTypeKey = null;
    if (isset($this->coordinates['branch_type_head']['isSelected']) && $this->coordinates['branch_type_head']['isSelected']) {
      $branchTypeKey = 'branch_type_head';
    } elseif (isset($this->coordinates['branch_type_branch']['isSelected']) && $this->coordinates['branch_type_branch']['isSelected']) {
      $branchTypeKey = 'branch_type_branch';
    } elseif (isset($this->coordinates['branch_type_office']['isSelected']) && $this->coordinates['branch_type_office']['isSelected']) {
      $branchTypeKey = 'branch_type_office';
    }

    if ($branchTypeKey) {
      $x = $this->coord($branchTypeKey, 'x');
      $y = $this->coord($branchTypeKey, 'y');
      $width = $this->coord($branchTypeKey, 'ellipseWidth') ?? 8;
      $height = $this->coord($branchTypeKey, 'ellipseHeight') ?? 4;
      $pdf->Ellipse($x, $y, $width / 2, $height / 2, 0, 0, 360, 'D');
    }

    $pdf->SetLineWidth(0.2);

    // === 被保険者情報 ===
    if ($this->hasCoord('temporary_insurer_name')) {
      $tempInsurerName = $this->customSampleData['temporary_insurer_name'] ?? '';

      if ($tempInsurerName) {
        $pdf->SetFontSize($this->coord('temporary_insurer_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'temporary_insurer_name', (string)$tempInsurerName);
        $pdf->SetFontSize(10);
      }
    }

    // === 施術料金情報 ===
    $this->fillTreatmentFees($pdf, $data);
  }

  /**
   * ボックスに数字を均等配置
   *
   * @param Fpdi $pdf
   * @param float $startX
   * @param float $y
   * @param string $text
   * @param int $boxCount
   * @param float $boxWidth
   * @return void
   */
  /**
   * ボックスに数字を均等配置（座標キーベース版・文字間隔とテキスト配置対応）
   *
   * @param Fpdi $pdf
   * @param string $key 座標キー
   * @param string $text テキスト
   * @param int $boxCount ボックス数
   * @param float $boxWidth ボックス幅
   * @return void
   */
  protected function fillBoxesByKey(Fpdi $pdf, string $key, string $text, int $boxCount, float $boxWidth): void
  {
    $startX = $this->coord($key, 'x');
    $y = $this->coord($key, 'y');
    $letterSpacing = $this->coordinates[$key]['letterSpacing'] ?? 0;
    $textAlign = $this->coordinates[$key]['textAlign'] ?? 'left';
    
    $this->fillBoxes($pdf, $startX, $y, $text, $boxCount, $boxWidth, (float)$letterSpacing, $textAlign);
  }

  /**
   * ボックスに数字を均等配置（文字間隔オプション対応）
   *
   * @param Fpdi $pdf
   * @param float $startX
   * @param float $y
   * @param string $text
   * @param int $boxCount
   * @param float $boxWidth
   * @param float $letterSpacing (mm) 追加の文字間隔
   * @param string $textAlign テキスト配置（left, center, right）
   * @return void
   */
  protected function fillBoxes(Fpdi $pdf, float $startX, float $y, string $text, int $boxCount, float $boxWidth, float $letterSpacing = 0, string $textAlign = 'left'): void
  {
    // マルチバイトを安全に分割
    $chars = preg_split('//u', (string)$text, -1, PREG_SPLIT_NO_EMPTY);

    // テキスト配置で左揃え・文字間隔なしの場合は従来のボックス幅で配置
    if ($letterSpacing == 0 && $textAlign === 'left') {
      for ($i = 0; $i < min(count($chars), $boxCount); $i++) {
        $x = $startX + ($i * $boxWidth);
        $pdf->Text($x, $y, $chars[$i]);
      }
      return;
    }

    // 文字間隔またはテキスト配置がある場合
    // 全幅を計算
    $totalWidth = 0;
    foreach ($chars as $char) {
      $width = $pdf->GetStringWidth($char);
      $totalWidth += $width + $letterSpacing;
    }
    if ($totalWidth > 0) {
      $totalWidth -= $letterSpacing; // 最後の文字間隔は不要
    }
    
    // 配置領域の幅（ボックス数 × ボックス幅）
    $alignmentWidth = $boxCount * $boxWidth;
    
    // テキスト配置に基づいて開始位置を調整
    $x = $startX;
    if ($textAlign === 'center') {
      $x = $startX + ($alignmentWidth - $totalWidth) / 2;
    } elseif ($textAlign === 'right') {
      $x = $startX + ($alignmentWidth - $totalWidth);
    }

    for ($i = 0; $i < min(count($chars), $boxCount); $i++) {
      $pdf->Text($x, $y, $chars[$i]);
      $width = $pdf->GetStringWidth($chars[$i]);
      $x += $width + $letterSpacing;
    }
  }

  /**
   * 施術日をカレンダーに記入
   *
   * @param Fpdi $pdf
   * @param \Illuminate\Support\Collection $records
   * @return void
   */
  protected function fillServiceDates(Fpdi $pdf, $records): void
  {
    $letterSpacing = $this->coord('calendar_start', 'letterSpacing') ?? 0;
    $cellWidth = $this->coord('calendar_start', 'cellWidth');
    $circleRadius = $this->coord('calendar_start', 'circleRadius') ?? 1.2;
    $innerRadius = $this->coord('calendar_start', 'doubleCircleInnerRadius') ?? 0.4;
    
    foreach ($records as $record) {
      $day = (int)date('d', strtotime($record->date));

      $x = $this->coord('calendar_start', 'x') + ($day - 1) * ($cellWidth + $letterSpacing);
      $y = $this->coord('calendar_start', 'y');

      // therapy_category: 1=通院（○）、2=往療（◎）
      if ($record->therapy_category == 2) {
        // 往療: 外側と内側の2つの円を描画
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        // 外側の円
        $pdf->Ellipse($x, $y, $circleRadius, $circleRadius, 0, 0, 360, 'D');
        // 内側の円
        $pdf->Ellipse($x, $y, $innerRadius, $innerRadius, 0, 0, 360, 'D');
      } else {
        // 通院: 単純な円
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->Ellipse($x, $y, $circleRadius, $circleRadius, 0, 0, 360, 'D');
      }
    }
  }

  /**
   * デバッグ用グリッド表示
   *
   * @param Fpdi $pdf
   * @return void
   */
  protected function drawDebugGrid(Fpdi $pdf): void
  {
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.1);

    // 縦線（10mm間隔）
    for ($x = 0; $x <= 210; $x += 10) {
      $pdf->Line($x, 0, $x, 297);
      $pdf->SetFontSize(6);
      $pdf->SetTextColor(150, 150, 150);
      $pdf->Text($x + 0.5, 5, (string)$x);
    }

    // 横線（10mm間隔）
    for ($y = 0; $y <= 297; $y += 10) {
      $pdf->Line(0, $y, 210, $y);
      $pdf->SetFontSize(6);
      $pdf->SetTextColor(150, 150, 150);
      $pdf->Text(2, $y + 3, (string)$y);
    }

    // テキスト色を戻す
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFontSize(10);
  }

  /**
   * 文字間隔を考慮したテキスト描画（内部ユーティリティ）
   *
   * @param Fpdi $pdf
   * @param float $startX
   * @param float $y
   * @param string $text
   * @param float $letterSpacing 追加の文字間隔（mm）
   * @return void
   */
  protected function drawTextWithSpacing(Fpdi $pdf, float $startX, float $y, string $text, float $letterSpacing, string $textAlign = 'left', float $alignmentWidth = 0): void
  {
    // マルチバイト対応で1文字ずつに分割
    $chars = preg_split('//u', (string)$text, -1, PREG_SPLIT_NO_EMPTY);
    
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
    if ($textAlign === 'center' && $alignmentWidth > 0) {
      // 中央揃え
      $x = $startX + ($alignmentWidth - $totalWidth) / 2;
    } elseif ($textAlign === 'right' && $alignmentWidth > 0) {
      // 右揃え
      $x = $startX + ($alignmentWidth - $totalWidth);
    }
    // 左揃え（textAlign === 'left'）はそのまま

    foreach ($chars as $char) {
      $pdf->Text($x, $y, $char);
      // GetStringWidth は現在のフォントサイズ・フォントを考慮した幅を返す（単位はmm）
      $width = $pdf->GetStringWidth($char);
      $x += $width + $letterSpacing;
    }
  }

  /**
   * 座標キーに基づいて文字間隔設定を反映してテキストを描画する（主に既存コードの置換用）
   *
   * @param Fpdi $pdf
   * @param string $key
   * @param string $text
   * @return void
   */
  protected function drawTextByKey(Fpdi $pdf, string $key, string $text): void
  {
    $x = $this->coord($key, 'x');
    $y = $this->coord($key, 'y');
    $letterSpacing = $this->coordinates[$key]['letterSpacing'] ?? 0;
    $textAlign = $this->coordinates[$key]['textAlign'] ?? 'left';
    $alignmentWidth = $this->coordinates[$key]['alignmentWidth'] ?? 0;

    // alignmentWidth が指定されていない場合はPDFのページ幅を使用
    if ($alignmentWidth <= 0) {
      $alignmentWidth = $pdf->GetPageWidth();
    }

    if (empty($letterSpacing) && $textAlign === 'left') {
      $pdf->Text($x, $y, $text);
      return;
    }


    $this->drawTextWithSpacing($pdf, $x, $y, $text, (float)$letterSpacing, $textAlign, (float)$alignmentWidth);
  }

  /**
   * 楕円をキーで描画
   *
   * @param Fpdi $pdf
   * @param string $key
   * @return void
   */
  protected function drawEllipseByKey(Fpdi $pdf, string $key): void
  {
    $x = $this->coord($key, 'x');
    $y = $this->coord($key, 'y');
    $ellipseWidth = $this->coordinates[$key]['ellipseWidth'] ?? 2.5;
    $ellipseHeight = $this->coordinates[$key]['ellipseHeight'] ?? 2.5;

    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.2);
    $pdf->Ellipse($x, $y, $ellipseWidth, $ellipseHeight, 0, 0, 360, 'D');
  }

  /**
   * サンプルデータを生成
   *
   * @param string $serviceYearMonth
   * @return array
   */
  protected function getSampleData(string $serviceYearMonth): array
  {
    // カスタムサンプルデータがあればそれを優先的に使用
    $custom = $this->customSampleData;

    // サンプル利用者情報
    $clinicUser = (object)[
      'id' => 999,
      'last_name' => $custom['last_name'] ?? '山田',
      'first_name' => $custom['first_name'] ?? '太郎',
      'last_kana' => $custom['last_kana'] ?? 'ヤマダ',
      'first_kana' => $custom['first_kana'] ?? 'タロウ',
      'gender' => $custom['gender'] ?? '男',
      'birthday' => $custom['birthdate'] ?? '1950-04-15',
      'postal_code' => '123-4567',
      'address_1' => $custom['address'] ?? '東京都新宿区西新宿1-2-3',
      'address_2' => '',
      'address_3' => '',
    ];

    // サンプル保険情報
    $insurance = (object)[
      'insurer_number' => $custom['insurer_number'] ?? '12345678',
      'insurer_name' => 'サンプル健康保険組合',
      'insurance_type_1_id' => 1,
      'code_number' => $custom['insurance_symbol'] ?? '12345',
      'account_number' => '67890',
      'insured_number' => $custom['insurance_number'] ?? '1234567890',
      'relationship' => $custom['relationship'] ?? '本人',
      'public_funds_payer_code' => '12345678',
      'public_funds_recipient_code' => '1234567',
      'locality_code' => '123456',
      'recipient_code' => '123456',
    ];

    // サンプル同意書情報
    $consent = (object)[
      'consenting_date' => $custom['consent_date'] ?? date('Y-m-d'),
      'consenting_doctor_name' => $custom['doctor_name'] ?? '鈴木医師',
      'illness_name_acupuncture_id' => 1,
      'illness_name_acupuncture_addendum' => $custom['disease'] ?? '追加症状',
      'onset_and_injury_date' => '2024-01-01',
      'therapy_period' => '6ヶ月',
      'bill_category' => '新規',
      'outcome' => '継続',
      'work_scope_type' => 'その他',
    ];

    // サンプル施術実績（月の1日、5日、10日、15日、20日、25日）
    $treatmentDays = $custom['treatment_days'] ?? 15;
    $records = collect([
      (object)['date' => $serviceYearMonth . '-01', 'therapy_category' => 1],
      (object)['date' => $serviceYearMonth . '-05', 'therapy_category' => 1],
      (object)['date' => $serviceYearMonth . '-10', 'therapy_category' => 2],
      (object)['date' => $serviceYearMonth . '-15', 'therapy_category' => 1],
      (object)['date' => $serviceYearMonth . '-20', 'therapy_category' => 2],
      (object)['date' => $serviceYearMonth . '-25', 'therapy_category' => 1],
    ]);

    // サンプル施術所情報
    $clinicInfo = (object)[
      'medical_institution_number' => $custom['institution_code'] ?? '1234567',
      'clinic_name' => $custom['clinic_name'] ?? 'サンプル鍼灸院',
      'postal_code' => '100-0001',
      'address_1' => $custom['clinic_address'] ?? '東京都千代田区千代田1-1-1',
      'address_2' => '',
      'address_3' => '',
      'phone' => $custom['clinic_phone'] ?? '03-1234-5678',
      'therapist_number' => '東京-12345',
      'health_center_registerd_location' => '施術所所在地',
    ];

    // サンプル施術料金データを取得
    $treatmentFees = DB::table('treatment_fees')
      ->orderBy('created_at', 'desc')
      ->first();

    return [
      'clinic_user' => $clinicUser,
      'insurance' => $insurance,
      'consent' => $consent,
      'records' => $records,
      'clinic_info' => $clinicInfo,
      'treatment_fees' => $treatmentFees,
      'service_year_month' => $serviceYearMonth,
    ];
  }

  /**
   * 西暦を和暦に変換
   *
   * @param int $year
   * @param int $month
   * @return array
   */
  protected function convertToJapaneseYear(int $year, int $month): array
  {
    if ($year >= 2019 && ($year > 2019 || $month >= 5)) {
      return ['era' => '令和', 'year' => $year - 2018];
    } elseif ($year >= 1989) {
      return ['era' => '平成', 'year' => $year - 1988];
    } elseif ($year >= 1926) {
      return ['era' => '昭和', 'year' => $year - 1925];
    }
    return ['era' => '', 'year' => $year];
  }

  /**
   * 日付を和暦形式に変換
   *
   * @param string $date YYYY-MM-DD形式
   * @return string
   */
  protected function convertToJapaneseDate(string $date): string
  {
    [$year, $month, $day] = explode('-', $date);
    $japaneseYear = $this->convertToJapaneseYear((int)$year, (int)$month);

    return $japaneseYear['era'] . $japaneseYear['year'] . '年' . (int)$month . '月' . (int)$day . '日';
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

    if (!$treatmentFees) {
      \Log::warning('施術料金データがありません');
      return;
    }

    // サンプルデータモードでカスタムサンプルデータがある場合は直接使用
    if ($this->sampleDataMode && $this->customSampleData) {
      $custom = $this->customSampleData;

      \Log::info('サンプルデータモードで施術料金印字', [
        'sampleDataMode' => $this->sampleDataMode,
        'fee_hari_unit' => $custom['fee_hari_unit'] ?? 'なし',
        'coordinates_exists' => isset($this->coordinates['fee_hari_unit']),
      ]);

      // はり料金
      if (isset($custom['fee_hari_unit']) && isset($this->coordinates['fee_hari_unit'])) {
        $pdf->SetFontSize($this->coord('fee_hari_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_hari_unit', (string)($custom['fee_hari_unit'] ?? ''));
      }
      if (isset($custom['fee_hari_count']) && isset($this->coordinates['fee_hari_count'])) {
        $pdf->SetFontSize($this->coord('fee_hari_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_hari_count', (string)($custom['fee_hari_count'] ?? ''));
      }
      if (isset($custom['fee_hari_total']) && isset($this->coordinates['fee_hari_total'])) {
        $pdf->SetFontSize($this->coord('fee_hari_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_hari_total', (string)($custom['fee_hari_total'] ?? ''));
      }

      // きゅう料金
      if (isset($custom['fee_kyu_unit']) && isset($this->coordinates['fee_kyu_unit'])) {
        $pdf->SetFontSize($this->coord('fee_kyu_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_kyu_unit', (string)($custom['fee_kyu_unit'] ?? ''));
      }
      if (isset($custom['fee_kyu_count']) && isset($this->coordinates['fee_kyu_count'])) {
        $pdf->SetFontSize($this->coord('fee_kyu_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_kyu_count', (string)($custom['fee_kyu_count'] ?? ''));
      }
      if (isset($custom['fee_kyu_total']) && isset($this->coordinates['fee_kyu_total'])) {
        $pdf->SetFontSize($this->coord('fee_kyu_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_kyu_total', (string)($custom['fee_kyu_total'] ?? ''));
      }

      // 往療料金
      if (isset($custom['fee_housecall_unit']) && isset($this->coordinates['fee_housecall_unit'])) {
        $pdf->SetFontSize($this->coord('fee_housecall_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_unit', (string)($custom['fee_housecall_unit'] ?? ''));
      }
      if (isset($custom['fee_housecall_count']) && isset($this->coordinates['fee_housecall_count'])) {
        $pdf->SetFontSize($this->coord('fee_housecall_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_count', (string)($custom['fee_housecall_count'] ?? ''));
      }
      if (isset($custom['fee_housecall_total']) && isset($this->coordinates['fee_housecall_total'])) {
        $pdf->SetFontSize($this->coord('fee_housecall_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_total', (string)($custom['fee_housecall_total'] ?? ''));
      }

      // はり・きゅう併用料金
      if (isset($custom['fee_hari_kyu_unit']) && isset($this->coordinates['fee_hari_kyu_unit'])) {
        $pdf->SetFontSize($this->coord('fee_hari_kyu_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_hari_kyu_unit', (string)($custom['fee_hari_kyu_unit'] ?? ''));
      }
      if (isset($custom['fee_hari_kyu_count']) && isset($this->coordinates['fee_hari_kyu_count'])) {
        $pdf->SetFontSize($this->coord('fee_hari_kyu_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_hari_kyu_count', (string)($custom['fee_hari_kyu_count'] ?? ''));
      }
      if (isset($custom['fee_hari_kyu_total']) && isset($this->coordinates['fee_hari_kyu_total'])) {
        $pdf->SetFontSize($this->coord('fee_hari_kyu_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_hari_kyu_total', (string)($custom['fee_hari_kyu_total'] ?? ''));
      }

      // 電療料
      if (isset($custom['fee_electric_unit']) && isset($this->coordinates['fee_electric_unit'])) {
        $pdf->SetFontSize($this->coord('fee_electric_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_electric_unit', (string)($custom['fee_electric_unit'] ?? ''));
      }
      if (isset($custom['fee_electric_count']) && isset($this->coordinates['fee_electric_count'])) {
        $pdf->SetFontSize($this->coord('fee_electric_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_electric_count', (string)($custom['fee_electric_count'] ?? ''));
      }
      if (isset($custom['fee_electric_total']) && isset($this->coordinates['fee_electric_total'])) {
        $pdf->SetFontSize($this->coord('fee_electric_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_electric_total', (string)($custom['fee_electric_total'] ?? ''));
      }

      // 往療料4km超
      if (isset($custom['fee_housecall_additional_unit']) && isset($this->coordinates['fee_housecall_additional_unit'])) {
        $pdf->SetFontSize($this->coord('fee_housecall_additional_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_additional_unit', (string)($custom['fee_housecall_additional_unit'] ?? ''));
      }
      if (isset($custom['fee_housecall_additional_count']) && isset($this->coordinates['fee_housecall_additional_count'])) {
        $pdf->SetFontSize($this->coord('fee_housecall_additional_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_additional_count', (string)($custom['fee_housecall_additional_count'] ?? ''));
      }
      if (isset($custom['fee_housecall_additional_total']) && isset($this->coordinates['fee_housecall_additional_total'])) {
        $pdf->SetFontSize($this->coord('fee_housecall_additional_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_housecall_additional_total', (string)($custom['fee_housecall_additional_total'] ?? ''));
      }

      // 施術給付金支払
      if (isset($custom['fee_previous_payment_unit']) && isset($this->coordinates['fee_previous_payment_unit'])) {
        $pdf->SetFontSize($this->coord('fee_previous_payment_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_previous_payment_unit', (string)($custom['fee_previous_payment_unit'] ?? ''));
      }
      if (isset($custom['fee_previous_payment_count']) && isset($this->coordinates['fee_previous_payment_count'])) {
        $pdf->SetFontSize($this->coord('fee_previous_payment_count', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_previous_payment_count', (string)($custom['fee_previous_payment_count'] ?? ''));
      }
      if (isset($custom['fee_previous_payment_total']) && isset($this->coordinates['fee_previous_payment_total'])) {
        $pdf->SetFontSize($this->coord('fee_previous_payment_total', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_previous_payment_total', (string)($custom['fee_previous_payment_total'] ?? ''));
      }

      // 合計
      if (isset($custom['fee_subtotal']) && isset($this->coordinates['fee_subtotal'])) {
        $pdf->SetFontSize($this->coord('fee_subtotal', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_subtotal', (string)($custom['fee_subtotal'] ?? ''));
      }

      // 一部負担金
      if (isset($custom['fee_partial_payment']) && isset($this->coordinates['fee_partial_payment'])) {
        $pdf->SetFontSize($this->coord('fee_partial_payment', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_partial_payment', (string)($custom['fee_partial_payment'] ?? ''));
      }

      // 請求額
      if (isset($custom['fee_total_claim']) && isset($this->coordinates['fee_total_claim'])) {
        $pdf->SetFontSize($this->coord('fee_total_claim', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_total_claim', (string)($custom['fee_total_claim'] ?? ''));
      }

      $pdf->SetFontSize(10);
      return;
    }

    // 通常モード：施術実績から料金を計算
    $therapyTypeCounts = [];
    $isFirstTreatment = false;

    // 施術実績を集計
    foreach ($records as $index => $record) {
      $therapyContentId = $record->therapy_conetnt_id ?? null;

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
      }
    }

    $totalFee = 0;

    // はり料金
    if (isset($therapyTypeCounts[1]) || isset($therapyTypeCounts[2])) {
      $hariCount = ($therapyTypeCounts[1] ?? 0) + ($therapyTypeCounts[2] ?? 0);
      $feeKey = $isFirstTreatment ? 'hari_first' : 'hari_normal';
      $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
      $total = $unitPrice * $hariCount;

      if ($hariCount > 0 && isset($this->coordinates['fee_hari_unit'])) {
        $pdf->SetFontSize($this->coord('fee_hari_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_hari_unit', (string)$unitPrice);
        $this->drawTextByKey($pdf, 'fee_hari_count', (string)$hariCount);
        $this->drawTextByKey($pdf, 'fee_hari_total', (string)$total);
        $totalFee += $total;
      }
    }

    // きゅう料金
    if (isset($therapyTypeCounts[3]) || isset($therapyTypeCounts[4])) {
      $kyuCount = ($therapyTypeCounts[3] ?? 0) + ($therapyTypeCounts[4] ?? 0);
      $feeKey = $isFirstTreatment ? 'kyu_first' : 'kyu_normal';
      $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
      $total = $unitPrice * $kyuCount;

      if ($kyuCount > 0 && isset($this->coordinates['fee_kyu_unit'])) {
        $pdf->SetFontSize($this->coord('fee_kyu_unit', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_kyu_unit', (string)$unitPrice);
        $this->drawTextByKey($pdf, 'fee_kyu_count', (string)$kyuCount);
        $this->drawTextByKey($pdf, 'fee_kyu_total', (string)$total);
        $totalFee += $total;
      }
    }

    // 往療料金（recordsのhousecall_distanceから判定）
    $housecallCount = 0;
    foreach ($records as $record) {
      if (isset($record->housecall_distance) && $record->housecall_distance > 0) {
        $housecallCount++;
      }
    }

    if ($housecallCount > 0 && isset($this->coordinates['fee_housecall_unit'])) {
      $feeKey = $isFirstTreatment ? 'housecall_max_2km_first' : 'housecall_max_2km_normal';
      $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
      $total = $unitPrice * $housecallCount;

      $pdf->SetFontSize($this->coord('fee_housecall_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_housecall_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_housecall_count', (string)$housecallCount);
      $this->drawTextByKey($pdf, 'fee_housecall_total', (string)$total);
      $totalFee += $total;
    }

    // 一部負担金（保険負担割合から計算）
    if (isset($this->coordinates['fee_partial_payment'])) {
      $expensesBorneRatio = (int)($insurance->expenses_borne_ratio ?? 30); // デフォルト3割負担
      $partialPayment = (int)($totalFee * ($expensesBorneRatio / 100));

      $pdf->SetFontSize($this->coord('fee_partial_payment', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_partial_payment', (string)$partialPayment);

      // 請求額（総額 - 一部負担金）
      $claimAmount = $totalFee - $partialPayment;

      if (isset($this->coordinates['fee_total_claim'])) {
        $pdf->SetFontSize($this->coord('fee_total_claim', 'fontSize'));
        $this->drawTextByKey($pdf, 'fee_total_claim', (string)$claimAmount);
      }
    }

    $pdf->SetFontSize(10);
  }
}
