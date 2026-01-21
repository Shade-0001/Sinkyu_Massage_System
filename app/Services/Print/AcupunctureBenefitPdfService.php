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
   * サンプルデータモードがONの場合のみ、isSelectedがない場合に最初のフィールドを選択する
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
        
        // グループ内に isSelected: true が1つもない場合は、isSelectedを設定しない
        // （通常モードでは実データから値を取得するため）
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
      ->leftJoin('insurance_types_1', 'insurances.insurance_type_1_id', '=', 'insurance_types_1.id')
      ->leftJoin('insurance_types_3', 'insurances.insurance_type_3_id', '=', 'insurance_types_3.id')
      ->where('insurances.clinic_user_id', $clinicUserId)
      ->orderBy('insurances.created_at', 'desc')
      ->select(
        'insurances.*',
        'insurers.insurer_number',
        'insurers.insurer_name',
        'relationships_with_clinic_user.relationship',
        'expenses_borne_ratios.expenses_borne_ratio',
        'insurance_types_1.insurance_type_1',
        'insurance_types_3.insurance_type_3'
      )
      ->first();

    if (!$insurance) {
      \Log::warning('保険情報が見つかりません', ['clinic_user_id' => $clinicUserId]);
    } else {
      \Log::info('保険情報取得成功', [
        'clinic_user_id' => $clinicUserId,
        'insurance_id' => $insurance->id ?? null,
        'expenses_borne_ratio_id' => $insurance->expenses_borne_ratio_id ?? null,
        'expenses_borne_ratio' => $insurance->expenses_borne_ratio ?? null,
      ]);
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
    $templatePath = storage_path('app/templates/acupuncture_and_massage/療養費支給申請書（はり・きゅう）.pdf');

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
    if ($this->sampleDataMode) {
      // サンプルデータモード：カスタムサンプルデータを使用
      $pdf->SetFontSize($this->coord('title_year_era', 'fontSize'));
      $this->drawTextByKey($pdf, 'title_year_era', (string)($this->customSampleData['title_year_era'] ?? '令和'));

      $pdf->SetFontSize($this->coord('title_year_number', 'fontSize'));
      $this->drawTextByKey($pdf, 'title_year_number', (string)($this->customSampleData['title_year_number'] ?? '7'));

      $pdf->SetFontSize($this->coord('title_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'title_month', (string)($this->customSampleData['title_month'] ?? '12'));
    } else {
      // 通常モード：実データから和暦変換
      $pdf->SetFontSize($this->coord('title_year_era', 'fontSize'));
      $this->drawTextByKey($pdf, 'title_year_era', (string)$japaneseYear['era']);

      $pdf->SetFontSize($this->coord('title_year_number', 'fontSize'));
      $this->drawTextByKey($pdf, 'title_year_number', (string)$japaneseYear['year']);

      $pdf->SetFontSize($this->coord('title_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'title_month', (string)(int)$month);
    }

    // === 機関コード（医療機関番号） ===
    $institutionCode = $this->sampleDataMode && isset($this->customSampleData['institution_code'])
      ? $this->customSampleData['institution_code']
      : ($clinicInfo->medical_institution_number ?? '');
    if ($institutionCode) {
      $pdf->SetFontSize($this->coord('institution_code', 'fontSize'));
      // 医療機関番号は通常7桁
      $this->fillBoxesByKey($pdf, 'institution_code', (string)$institutionCode, 7, 5.6);
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

    // === 一部負担金（楕円） ===
    $expensesBorneRatioKey = null;

    // isSelectedフラグをチェック（サンプルデータの場合）
    if (isset($this->coordinates['expenses_borne_ratio_10']['isSelected']) && $this->coordinates['expenses_borne_ratio_10']['isSelected']) {
      $expensesBorneRatioKey = 'expenses_borne_ratio_10';
      \Log::info('一部負担金: isSelected 10');
    } elseif (isset($this->coordinates['expenses_borne_ratio_20']['isSelected']) && $this->coordinates['expenses_borne_ratio_20']['isSelected']) {
      $expensesBorneRatioKey = 'expenses_borne_ratio_20';
      \Log::info('一部負担金: isSelected 20');
    } elseif (isset($this->coordinates['expenses_borne_ratio_30']['isSelected']) && $this->coordinates['expenses_borne_ratio_30']['isSelected']) {
      $expensesBorneRatioKey = 'expenses_borne_ratio_30';
      \Log::info('一部負担金: isSelected 30');
    } elseif ($insurance && isset($insurance->expenses_borne_ratio)) {
      // 通常モード：保険データから取得
      $ratioValue = (string)$insurance->expenses_borne_ratio;
      \Log::info('一部負担金: 保険データから取得', ['original' => $ratioValue]);
      
      // 半角数字+全角「割」と全角数字+全角「割」の両方に対応
      if ($ratioValue === '１割' || $ratioValue === '1割') $ratioValue = '10';
      if ($ratioValue === '２割' || $ratioValue === '2割') $ratioValue = '20';
      if ($ratioValue === '３割' || $ratioValue === '3割') $ratioValue = '30';

      $expensesBorneRatioMap = [
        '10' => 'expenses_borne_ratio_10',
        '20' => 'expenses_borne_ratio_20',
        '30' => 'expenses_borne_ratio_30',
      ];
      $expensesBorneRatioKey = $expensesBorneRatioMap[$ratioValue] ?? null;
      \Log::info('一部負担金: 変換後', ['converted' => $ratioValue, 'key' => $expensesBorneRatioKey]);
    }

    if ($expensesBorneRatioKey) {
      $this->drawEllipseByKey($pdf, $expensesBorneRatioKey);
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
          $displayText = trim(($symbol ?: '') . ($symbol && $number ? '　' : '') . ($number ?: ''));
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
    $genderKey = null;

    // isSelectedフラグをチェック（サンプルデータの場合）
    if (isset($this->coordinates['patient_gender_male']['isSelected']) && $this->coordinates['patient_gender_male']['isSelected']) {
      $genderKey = 'patient_gender_male';
    } elseif (isset($this->coordinates['patient_gender_female']['isSelected']) && $this->coordinates['patient_gender_female']['isSelected']) {
      $genderKey = 'patient_gender_female';
    } elseif ($this->sampleDataMode && $this->customSampleData && isset($this->customSampleData['gender'])) {
      // サンプルデータモード：customSampleDataから取得
      $gender = $this->customSampleData['gender'];
      if ($gender === '男') {
        $genderKey = 'patient_gender_male';
      } elseif ($gender === '女') {
        $genderKey = 'patient_gender_female';
      }
    } elseif (isset($clinicUser->gender) && $clinicUser->gender) {
      // 通常モード：実データから判定
      if ($clinicUser->gender === '男') {
        $genderKey = 'patient_gender_male';
      } elseif ($clinicUser->gender === '女') {
        $genderKey = 'patient_gender_female';
      }
    }

    if ($genderKey) {
      $this->drawEllipseByKey($pdf, $genderKey);
    }

    // === 生年月日 ===
    if ($this->sampleDataMode && $this->customSampleData) {
      // サンプルデータモード：customSampleDataから取得
      $birthYear = $this->customSampleData['birthday_year'] ?? null;
      $birthMonth = $this->customSampleData['birthday_month'] ?? null;
      $birthDay = $this->customSampleData['birthday_day'] ?? null;

      // isSelectedフラグまたはcustomSampleDataから元号を取得
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
      } elseif (isset($this->customSampleData['birthday_era'])) {
        // customSampleDataから元号を取得
        $era = $this->customSampleData['birthday_era'];
        if ($era === '令和') {
          $birthdayEraKey = 'birthday_era_reiwa';
        } elseif ($era === '平成') {
          $birthdayEraKey = 'birthday_era_heisei';
        } elseif ($era === '昭和') {
          $birthdayEraKey = 'birthday_era_showa';
        } elseif ($era === '大正') {
          $birthdayEraKey = 'birthday_era_taisho';
        } elseif ($era === '明治') {
          $birthdayEraKey = 'birthday_era_meiji';
        }
      }

      if ($birthdayEraKey) {
        $this->drawEllipseByKey($pdf, $birthdayEraKey);
      }

      if ($birthYear) {
        $pdf->SetFontSize($this->coord('birthday_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'birthday_year', (string)$birthYear);
      }

      if ($birthMonth) {
        $pdf->SetFontSize($this->coord('birthday_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'birthday_month', (string)(int)$birthMonth);
      }

      if ($birthDay) {
        $pdf->SetFontSize($this->coord('birthday_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'birthday_day', (string)(int)$birthDay);
      }

      $pdf->SetFontSize(10);
    } elseif (isset($clinicUser->birthday)) {
      // 通常モード：実データから取得
      [$birthYear, $birthMonth, $birthDay] = explode('-', $clinicUser->birthday);
      $birthJapaneseYear = $this->convertToJapaneseYear((int)$birthYear, (int)$birthMonth);

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
    if ($this->sampleDataMode && $this->customSampleData) {
      // サンプルデータモード：customSampleDataから取得
      $onsetYear = $this->customSampleData['onset_date_year'] ?? '';
      $onsetMonth = $this->customSampleData['onset_date_month'] ?? '';
      $onsetDay = $this->customSampleData['onset_date_day'] ?? '';

      if ($onsetYear) {
        $pdf->SetFontSize($this->coord('onset_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date_year', (string)$onsetYear);
      }

      if ($onsetMonth) {
        $pdf->SetFontSize($this->coord('onset_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date_month', (string)$onsetMonth);
      }

      if ($onsetDay) {
        $pdf->SetFontSize($this->coord('onset_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_date_day', (string)$onsetDay);
      }

      $pdf->SetFontSize(10);
    } elseif ($consent && isset($consent->onset_and_injury_date) && $consent->onset_and_injury_date) {
      // 通常モード：実データから取得
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
    if ($this->sampleDataMode && $this->customSampleData) {
      // サンプルデータモード：customSampleDataから取得
      $onsetIllnessName = $this->customSampleData['onset_illness_name'] ?? '';

      if ($onsetIllnessName) {
        $pdf->SetFontSize($this->coord('onset_illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'onset_illness_name', (string)$onsetIllnessName);
        $pdf->SetFontSize(10);
      }
    } elseif ($consent) {
      // 通常モード：実データから取得
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

    // === 発病負傷の原因･経過 ===
    if ($this->sampleDataMode && $this->customSampleData) {
      // サンプルデータモード：customSampleDataから取得
      $conditionId = $this->customSampleData['condition'] ?? '';

      if ($conditionId) {
        // IDから名称を取得
        $condition = \App\Models\Condition::find($conditionId);
        $conditionName = $condition ? $condition->condition_name : '';
        
        if ($conditionName) {
          $pdf->SetFontSize($this->coord('condition', 'fontSize'));
          $this->drawTextByKey($pdf, 'condition', $conditionName);
          $pdf->SetFontSize(10);
        }
      }
    } elseif ($consent && isset($consent->condition) && $consent->condition) {
      // 通常モード：実データから取得
      // IDから名称を取得
      $condition = \App\Models\Condition::find($consent->condition);
      $conditionName = $condition ? $condition->condition_name : '';
      
      if ($conditionName) {
        $pdf->SetFontSize($this->coord('condition', 'fontSize'));
        $this->drawTextByKey($pdf, 'condition', $conditionName);
        $pdf->SetFontSize(10);
      }
    }

    // === 業務上・外、第三者行為の有無 ===
    $workScopeTypeKey = null;

    // isSelectedフラグをチェック（サンプルデータの場合）
    if (isset($this->coordinates['work_scope_type_1']['isSelected']) && $this->coordinates['work_scope_type_1']['isSelected']) {
      $workScopeTypeKey = 'work_scope_type_1';
    } elseif (isset($this->coordinates['work_scope_type_2']['isSelected']) && $this->coordinates['work_scope_type_2']['isSelected']) {
      $workScopeTypeKey = 'work_scope_type_2';
    } elseif (isset($this->coordinates['work_scope_type_3']['isSelected']) && $this->coordinates['work_scope_type_3']['isSelected']) {
      $workScopeTypeKey = 'work_scope_type_3';
    } elseif ($this->sampleDataMode && $this->customSampleData && isset($this->customSampleData['work_scope_type'])) {
      // サンプルデータモード：customSampleDataから取得
      $workScopeType = $this->customSampleData['work_scope_type'];
      if ($workScopeType === '業務上') {
        $workScopeTypeKey = 'work_scope_type_1';
      } elseif ($workScopeType === '第三者行為') {
        $workScopeTypeKey = 'work_scope_type_2';
      } elseif ($workScopeType === 'その他') {
        $workScopeTypeKey = 'work_scope_type_3';
      }
    } elseif ($consent && isset($consent->work_scope_type) && $consent->work_scope_type) {
      // 通常モード：実データから判定
      if ($consent->work_scope_type === '業務上') {
        $workScopeTypeKey = 'work_scope_type_1';
      } elseif ($consent->work_scope_type === '第三者行為') {
        $workScopeTypeKey = 'work_scope_type_2';
      } elseif ($consent->work_scope_type === 'その他') {
        $workScopeTypeKey = 'work_scope_type_3';
      }
    }

    if ($workScopeTypeKey) {
      $this->drawEllipseByKey($pdf, $workScopeTypeKey);
    }

    // === 初療年月日 ===
    if ($this->sampleDataMode && $this->customSampleData) {
      // サンプルデータモード：customSampleDataから取得
      $firstYear = $this->customSampleData['first_treatment_year'] ?? '';
      $firstMonth = $this->customSampleData['first_treatment_month'] ?? '';
      $firstDay = $this->customSampleData['first_treatment_day'] ?? '';

      if ($firstYear) {
        $pdf->SetFontSize($this->coord('first_treatment_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_year', (string)$firstYear);
      }

      if ($firstMonth) {
        $pdf->SetFontSize($this->coord('first_treatment_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_month', (string)$firstMonth);
      }

      if ($firstDay) {
        $pdf->SetFontSize($this->coord('first_treatment_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'first_treatment_day', (string)$firstDay);
      }

      $pdf->SetFontSize(10);
    } elseif ($records->isNotEmpty()) {
      // 通常モード：実データから取得
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
      if ($startYear) {
        $pdf->SetFontSize($this->coord('treatment_start_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_start_year', (string)$startYear);
      }

      if ($startMonth) {
        $pdf->SetFontSize($this->coord('treatment_start_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_start_month', (string)$startMonth);
      }

      if ($startDay) {
        $pdf->SetFontSize($this->coord('treatment_start_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_start_day', (string)$startDay);
      }

      // 至：終了日
      if ($endYear) {
        $pdf->SetFontSize($this->coord('treatment_end_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_end_year', (string)$endYear);
      }

      if ($endMonth) {
        $pdf->SetFontSize($this->coord('treatment_end_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_end_month', (string)$endMonth);
      }

      if ($endDay) {
        $pdf->SetFontSize($this->coord('treatment_end_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_end_day', (string)$endDay);
      }

      // 実日数
      if ($treatmentDays) {
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

    // === 実日数（施術内容欄） ===
    if ($this->hasCoord('treatment_day_count')) {
      if ($this->sampleDataMode && isset($this->customSampleData['treatment_days'])) {
        $dayCount = $this->customSampleData['treatment_days'];
      } else {
        // はり・きゅう関連の施術（therapy_content_id: 11-16）のみカウント
        $acupunctureContentIds = [11, 12, 13, 14, 15, 16];
        $dayCount = $records->filter(function ($record) use ($acupunctureContentIds) {
          return in_array($record->therapy_content_id ?? null, $acupunctureContentIds);
        })->count();
      }

      $pdf->SetFontSize($this->coord('treatment_day_count', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_day_count', (string)$dayCount);
      $pdf->SetFontSize(10);
    }

    // === 請求区分（新規・継続） ===
    $billCategoryKey = null;

    // isSelectedフラグをチェック（サンプルデータの場合）
    if (isset($this->coordinates['bill_category_new']['isSelected']) && $this->coordinates['bill_category_new']['isSelected']) {
      $billCategoryKey = 'bill_category_new';
    } elseif (isset($this->coordinates['bill_category_continued']['isSelected']) && $this->coordinates['bill_category_continued']['isSelected']) {
      $billCategoryKey = 'bill_category_continued';
    } elseif ($this->sampleDataMode && $this->customSampleData && isset($this->customSampleData['bill_category'])) {
      // サンプルデータモード：customSampleDataから取得
      $billCategory = $this->customSampleData['bill_category'];
      if ($billCategory === '新規') {
        $billCategoryKey = 'bill_category_new';
      } elseif ($billCategory === '継続') {
        $billCategoryKey = 'bill_category_continued';
      }
    } elseif ($consent && isset($consent->bill_category) && $consent->bill_category) {
      // 通常モード：実データから判定
      if ($consent->bill_category === '新規') {
        $billCategoryKey = 'bill_category_new';
      } elseif ($consent->bill_category === '継続') {
        $billCategoryKey = 'bill_category_continued';
      }
    }

    // 楕円を描画
    if ($billCategoryKey) {
      $this->drawEllipseByKey($pdf, $billCategoryKey);
    }

    // === 転帰（継続・治癒・中止・転医） ===
    $outcomeKey = null;

    // isSelected フラグをチェック（サンプルデータの場合）
    if (isset($this->coordinates['outcome_continued']['isSelected']) && $this->coordinates['outcome_continued']['isSelected']) {
      $outcomeKey = 'outcome_continued';
    } elseif (isset($this->coordinates['outcome_cured']['isSelected']) && $this->coordinates['outcome_cured']['isSelected']) {
      $outcomeKey = 'outcome_cured';
    } elseif (isset($this->coordinates['outcome_discontinued']['isSelected']) && $this->coordinates['outcome_discontinued']['isSelected']) {
      $outcomeKey = 'outcome_discontinued';
    } elseif (isset($this->coordinates['outcome_transferred']['isSelected']) && $this->coordinates['outcome_transferred']['isSelected']) {
      $outcomeKey = 'outcome_transferred';
    } elseif ($this->sampleDataMode && $this->customSampleData && isset($this->customSampleData['outcome'])) {
      // サンプルデータモード：customSampleDataから取得
      $outcome = $this->customSampleData['outcome'];
      if ($outcome === '継続') {
        $outcomeKey = 'outcome_continued';
      } elseif ($outcome === '治癒') {
        $outcomeKey = 'outcome_cured';
      } elseif ($outcome === '中止') {
        $outcomeKey = 'outcome_discontinued';
      } elseif ($outcome === '転医') {
        $outcomeKey = 'outcome_transferred';
      }
    } elseif ($consent && isset($consent->outcome) && $consent->outcome) {
      // 通常モード：実データから判定
      if ($consent->outcome === '継続') {
        $outcomeKey = 'outcome_continued';
      } elseif ($consent->outcome === '治癒') {
        $outcomeKey = 'outcome_cured';
      } elseif ($consent->outcome === '中止') {
        $outcomeKey = 'outcome_discontinued';
      } elseif ($consent->outcome === '転医') {
        $outcomeKey = 'outcome_transferred';
      }
    }

    // 楕円を描画
    if ($outcomeKey) {
      $this->drawEllipseByKey($pdf, $outcomeKey);
    }

    // === 傷病名（施術内容欄のチェックボックス） ===
    if ($this->sampleDataMode) {
      // サンプルデータモード：isSelectedフラグまたはcustomSampleDataをチェック
      $illnessSelected = false;

      // まずisSelectedフラグをチェック
      for ($i = 1; $i <= 7; $i++) {
        $key = 'illness_name_' . $i;
        if (isset($this->coordinates[$key]['isSelected']) && $this->coordinates[$key]['isSelected']) {
          $this->drawEllipseByKey($pdf, $key);
          $illnessSelected = true;

          // 「その他」の場合、追記テキストを表示
          if ($i === 7 && isset($this->customSampleData['illness_name_other_text']) && $this->customSampleData['illness_name_other_text']) {
            $pdf->SetFontSize($this->coord('illness_name_other_text', 'fontSize'));
            $this->drawTextByKey($pdf, 'illness_name_other_text', (string)$this->customSampleData['illness_name_other_text']);
            $pdf->SetFontSize(10);
          }
          break; // 1つだけ選択
        }
      }

      // isSelectedがない場合、customSampleDataから取得
      if (!$illnessSelected && isset($this->customSampleData['illness_name']) && $this->customSampleData['illness_name']) {
        $illnessId = (int)$this->customSampleData['illness_name'];
        if ($illnessId >= 1 && $illnessId <= 7) {
          $this->drawEllipseByKey($pdf, 'illness_name_' . $illnessId);

          // 「その他」の場合、追記テキストを表示
          if ($illnessId === 7 && isset($this->customSampleData['illness_name_other_text']) && $this->customSampleData['illness_name_other_text']) {
            $pdf->SetFontSize($this->coord('illness_name_other_text', 'fontSize'));
            $this->drawTextByKey($pdf, 'illness_name_other_text', (string)$this->customSampleData['illness_name_other_text']);
            $pdf->SetFontSize(10);
          }
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

    // === 摘要 ===
    $abstractText = 'なし'; // デフォルト値

    if ($records->isNotEmpty()) {
      // 全レコードの摘要を結合（重複排除）
      $abstracts = $records->pluck('abstract')->filter()->unique()->toArray();
      if (!empty($abstracts)) {
        // "。"で区切る（前後に既に"。"がある場合は重複しないように）
        $abstractText = '';
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
      }
    }

    // 摘要を描画
    if ($this->hasCoord('abstract')) {
      $x = $this->coord('abstract', 'x');
      $y = $this->coord('abstract', 'y');
      $fontSize = $this->coord('abstract', 'fontSize');
      $width = $this->coordinates['abstract']['width'] ?? 180; // デフォルト180mm
      $lineHeight = $this->coordinates['abstract']['lineHeight'] ?? 5; // デフォルト5mm

      $pdf->SetFontSize($fontSize);
      $pdf->SetXY($x, $y);
      $pdf->MultiCell($width, $lineHeight, $abstractText, 0, 'L', false, 1);
    }

    // === 施術所情報 ===
    if ($clinicInfo) {
      // 施術証明年月日
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
        $this->drawTextByKey($pdf, 'clinic_postal_code', (string)$clinicPostalCode);
      }

      // 施術所住所
      if ($this->sampleDataMode && isset($this->customSampleData['clinic_address'])) {
        $clinicAddress = $this->customSampleData['clinic_address'];
      } else {
        $clinicAddress = ($clinicInfo->address_1 ?? '') .
                         ($clinicInfo->address_2 ?? '') .
                         ($clinicInfo->address_3 ?? '');
      }
      $pdf->SetFontSize($this->coord('clinic_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_address', (string)$clinicAddress);
      $pdf->SetFontSize(10);

      // 施術所名称
      $pdf->SetFontSize($this->coord('clinic_name', 'fontSize'));
      $clinicName = $this->sampleDataMode && isset($this->customSampleData['clinic_name'])
        ? $this->customSampleData['clinic_name']
        : ($clinicInfo->clinic_name ?? '');
      $this->drawTextByKey($pdf, 'clinic_name', (string)$clinicName);
      $pdf->SetFontSize(10);

      // 施術管理者氏名（施術者情報から取得）
      if ($this->sampleDataMode && isset($this->customSampleData['clinic_manager'])) {
        $therapistName = $this->customSampleData['clinic_manager'];
        $pdf->SetFontSize($this->coord('clinic_manager', 'fontSize'));
        $this->drawTextByKey($pdf, 'clinic_manager', (string)$therapistName);
        $pdf->SetFontSize(10);
      } else {
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
      }

      // 電話番号
      $clinicPhone = $this->sampleDataMode && isset($this->customSampleData['clinic_phone'])
        ? $this->customSampleData['clinic_phone']
        : ($clinicInfo->phone ?? '');
      if (empty($clinicPhone)) {
        \Log::warning('施術所電話番号が設定されていません', ['clinic_info' => $clinicInfo]);
      }
      $pdf->SetFontSize($this->coord('clinic_phone', 'fontSize'));
      $this->drawTextByKey($pdf, 'clinic_phone', (string)$clinicPhone);
      $pdf->SetFontSize(10);

      // === 保健所登録区分 ===
      // isSelectedフラグをチェック（座標調整ツールで選択された場合）
      if (isset($this->coordinates['health_center_registration_1']['isSelected']) && $this->coordinates['health_center_registration_1']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'health_center_registration_1');
      } elseif (isset($this->coordinates['health_center_registration_2']['isSelected']) && $this->coordinates['health_center_registration_2']['isSelected']) {
        $this->drawEllipseByKey($pdf, 'health_center_registration_2');
      } elseif ($this->sampleDataMode && isset($this->customSampleData['health_center_registration'])) {
        // サンプルデータモード：customSampleDataから取得
        $healthCenterRegistration = $this->customSampleData['health_center_registration'];
        if (strpos($healthCenterRegistration, '施術所') !== false) {
          $this->drawEllipseByKey($pdf, 'health_center_registration_1');
        } elseif (strpos($healthCenterRegistration, '出張') !== false) {
          $this->drawEllipseByKey($pdf, 'health_center_registration_2');
        }
      } else {
        // 通常モード：DBから取得
        $healthCenterRegistration = $clinicInfo->health_center_registerd_location ?? '';
        if (strpos($healthCenterRegistration, '施術所') !== false) {
          $this->drawEllipseByKey($pdf, 'health_center_registration_1');
        } elseif (strpos($healthCenterRegistration, '出張') !== false) {
          $this->drawEllipseByKey($pdf, 'health_center_registration_2');
        }
      }

      // === 登録記号番号（施術者番号） ===
      $therapistNumber = $this->sampleDataMode && isset($this->customSampleData['therapist_registration_number'])
        ? $this->customSampleData['therapist_registration_number']
        : ($clinicInfo->therapist_number ?? '');
      if ($therapistNumber) {
        $pdf->SetFontSize($this->coord('therapist_registration_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'therapist_registration_number', (string)$therapistNumber);
        $pdf->SetFontSize(10);
      }
    }

    // === 同意記録欄 ===
    // 同意医師氏名
    $consentDoctorName = $this->sampleDataMode && isset($this->customSampleData['consent_doctor_name'])
      ? $this->customSampleData['consent_doctor_name']
      : ($consent->consenting_doctor_name ?? '');
    if ($consentDoctorName) {
      $pdf->SetFontSize($this->coord('consent_doctor_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_doctor_name', (string)$consentDoctorName);
      $pdf->SetFontSize(10);
    }

    // 同意年月日
    if ($this->sampleDataMode && isset($this->customSampleData['consent_date_year'])) {
      $pdf->SetFontSize($this->coord('consent_date_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_date_year', (string)$this->customSampleData['consent_date_year']);

      $pdf->SetFontSize($this->coord('consent_date_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_date_month', (string)($this->customSampleData['consent_date_month'] ?? ''));

      $pdf->SetFontSize($this->coord('consent_date_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_date_day', (string)($this->customSampleData['consent_date_day'] ?? ''));

      $pdf->SetFontSize(10);
    } elseif ($consent && isset($consent->consenting_date) && $consent->consenting_date) {
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

    // 同意書の傷病名
    $consentIllnessName = $this->sampleDataMode && isset($this->customSampleData['consent_illness_name'])
      ? $this->customSampleData['consent_illness_name']
      : '';
    if (!$consentIllnessName && $consent && isset($consent->illness_name_acupuncture_id) && $consent->illness_name_acupuncture_id) {
      $illness = DB::table('illnesses_acupuncture')
        ->where('id', $consent->illness_name_acupuncture_id)
        ->first();
      if ($illness && isset($illness->illness_name_acupuncture)) {
        $consentIllnessName = $illness->illness_name_acupuncture;
        if (isset($consent->illness_name_acupuncture_addendum) && $consent->illness_name_acupuncture_addendum) {
          $consentIllnessName .= '、' . $consent->illness_name_acupuncture_addendum;
        }
      }
    }
    if ($consentIllnessName) {
      $pdf->SetFontSize($this->coord('consent_illness_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_illness_name', (string)$consentIllnessName);
      $pdf->SetFontSize(10);
    }

    // 要加療期間
    $therapyPeriod = $this->sampleDataMode && isset($this->customSampleData['therapy_period'])
      ? $this->customSampleData['therapy_period']
      : ($consent->therapy_period ?? '');
    if ($therapyPeriod) {
      $pdf->SetFontSize($this->coord('therapy_period', 'fontSize'));
      $this->drawTextByKey($pdf, 'therapy_period', (string)$therapyPeriod);
      $pdf->SetFontSize(10);
    }

    // === 申請欄：提出年月日 ===
    if ($this->sampleDataMode && isset($this->customSampleData['submission_date_year'])) {
      // サンプルデータモード：customSampleDataを使用
      $pdf->SetFontSize($this->coord('submission_date_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'submission_date_year', (string)$this->customSampleData['submission_date_year']);

      if (isset($this->customSampleData['submission_date_month'])) {
        $pdf->SetFontSize($this->coord('submission_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'submission_date_month', (string)$this->customSampleData['submission_date_month']);
      }

      if (isset($this->customSampleData['submission_date_day'])) {
        $pdf->SetFontSize($this->coord('submission_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'submission_date_day', (string)$this->customSampleData['submission_date_day']);
      }

      $pdf->SetFontSize(10);
    } else {
      // 通常モード：実データから和暦変換
      $submissionParts = explode('-', $submissionDate);
      $submissionJapaneseYear = $this->convertToJapaneseYear((int)$submissionParts[0], (int)$submissionParts[1]);

      $pdf->SetFontSize($this->coord('submission_date_year', 'fontSize'));
      $this->drawTextByKey($pdf, 'submission_date_year', (string)$submissionJapaneseYear['year']);

      $pdf->SetFontSize($this->coord('submission_date_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'submission_date_month', (string)(int)$submissionParts[1]);

      $pdf->SetFontSize($this->coord('submission_date_day', 'fontSize'));
      $this->drawTextByKey($pdf, 'submission_date_day', (string)(int)$submissionParts[2]);

      $pdf->SetFontSize(10);
    }

    // === 申請者情報 ===
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
      $address = $this->customSampleData['applicant_address'];
    } else {
      $address = ($clinicUser->address_1 ?? '') .
                 ($clinicUser->address_2 ?? '') .
                 ($clinicUser->address_3 ?? '');
    }
    $pdf->SetFontSize($this->coord('applicant_address', 'fontSize'));
    $this->drawTextByKey($pdf, 'applicant_address', (string)$address);

    // 申請者氏名
    $applicantName = $this->sampleDataMode && isset($this->customSampleData['applicant_name'])
      ? $this->customSampleData['applicant_name']
      : $fullName;
    $pdf->SetFontSize($this->coord('applicant_name', 'fontSize'));
    $this->drawTextByKey($pdf, 'applicant_name', (string)$applicantName);

    // 患者住所（申請欄）
    if ($this->hasCoord('patient_address')) {
      $patientAddress = $this->sampleDataMode && isset($this->customSampleData['address'])
        ? $this->customSampleData['address']
        : (($clinicUser->address_1 ?? '') . ($clinicUser->address_2 ?? '') . ($clinicUser->address_3 ?? ''));
      if ($patientAddress) {
        $pdf->SetFontSize($this->coord('patient_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'patient_address', (string)$patientAddress);
      }
    }

    // 電話番号
    if ($this->hasCoord('patient_phone')) {
      $phone = $this->sampleDataMode && isset($this->customSampleData['patient_phone'])
        ? $this->customSampleData['patient_phone']
        : ($clinicUser->phone ?? '');
      if ($phone) {
        $pdf->SetFontSize($this->coord('patient_phone', 'fontSize'));
        $this->drawTextByKey($pdf, 'patient_phone', (string)$phone);
      }
    }

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

    // 支払区分
    $paymentMethodKey = null;
    if (isset($this->coordinates['payment_category_furikomi']['isSelected']) && $this->coordinates['payment_category_furikomi']['isSelected']) {
      $paymentMethodKey = 'payment_category_furikomi';
    } elseif (isset($this->coordinates['payment_category_bank_transfer']['isSelected']) && $this->coordinates['payment_category_bank_transfer']['isSelected']) {
      $paymentMethodKey = 'payment_category_bank_transfer';
    } elseif (isset($this->coordinates['payment_category_post_transfer']['isSelected']) && $this->coordinates['payment_category_post_transfer']['isSelected']) {
      $paymentMethodKey = 'payment_category_post_transfer';
    } elseif (isset($this->coordinates['payment_category_local_payment']['isSelected']) && $this->coordinates['payment_category_local_payment']['isSelected']) {
      $paymentMethodKey = 'payment_category_local_payment';
    }

    if ($paymentMethodKey) {
      $this->drawEllipseByKey($pdf, $paymentMethodKey);
    }

    // 預金の種類
    $depositTypeKey = null;
    if (isset($this->coordinates['deposit_type_ordinary']['isSelected']) && $this->coordinates['deposit_type_ordinary']['isSelected']) {
      $depositTypeKey = 'deposit_type_ordinary';
    } elseif (isset($this->coordinates['deposit_type_current']['isSelected']) && $this->coordinates['deposit_type_current']['isSelected']) {
      $depositTypeKey = 'deposit_type_current';
    } elseif (isset($this->coordinates['deposit_type_notice']['isSelected']) && $this->coordinates['deposit_type_notice']['isSelected']) {
      $depositTypeKey = 'deposit_type_notice';
    } elseif (isset($this->coordinates['deposit_type_betsudan']['isSelected']) && $this->coordinates['deposit_type_betsudan']['isSelected']) {
      $depositTypeKey = 'deposit_type_betsudan';
    }

    if ($depositTypeKey) {
      $this->drawEllipseByKey($pdf, $depositTypeKey);
    }

    // 金融機関種類
    $financialInstitutionTypeKey = null;
    if (isset($this->coordinates['financial_institution_type_bank']['isSelected']) && $this->coordinates['financial_institution_type_bank']['isSelected']) {
      $financialInstitutionTypeKey = 'financial_institution_type_bank';
    } elseif (isset($this->coordinates['financial_institution_type_kinko']['isSelected']) && $this->coordinates['financial_institution_type_kinko']['isSelected']) {
      $financialInstitutionTypeKey = 'financial_institution_type_kinko';
    } elseif (isset($this->coordinates['financial_institution_type_nokyo']['isSelected']) && $this->coordinates['financial_institution_type_nokyo']['isSelected']) {
      $financialInstitutionTypeKey = 'financial_institution_type_nokyo';
    }

    if ($financialInstitutionTypeKey) {
      $this->drawEllipseByKey($pdf, $financialInstitutionTypeKey);
    }

    // 本店支店出張所
    $branchTypeKey = null;
    if (isset($this->coordinates['branch_type_honten']['isSelected']) && $this->coordinates['branch_type_honten']['isSelected']) {
      $branchTypeKey = 'branch_type_honten';
    } elseif (isset($this->coordinates['branch_type_shiten']['isSelected']) && $this->coordinates['branch_type_shiten']['isSelected']) {
      $branchTypeKey = 'branch_type_shiten';
    } elseif (isset($this->coordinates['branch_type_shucchoujo']['isSelected']) && $this->coordinates['branch_type_shucchoujo']['isSelected']) {
      $branchTypeKey = 'branch_type_shucchoujo';
    }

    if ($branchTypeKey) {
      $this->drawEllipseByKey($pdf, $branchTypeKey);
    }

    // === 被保険者情報 ===
    if ($this->hasCoord('temporary_insurer_name')) {
      // サンプルデータモード時は専用の値を使用、実データモードでは申請者氏名と同じデータを参照
      $tempInsurerName = $this->sampleDataMode 
        ? ($this->customSampleData['temporary_insurer_name'] ?? '')
        : $fullName;

      if ($tempInsurerName) {
        $pdf->SetFontSize($this->coord('temporary_insurer_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'temporary_insurer_name', (string)$tempInsurerName);
        $pdf->SetFontSize(10);
      }
    }

    // === 施術月（実データモード） ===
    if (!$this->sampleDataMode && $this->hasCoord('treatment_month')) {
      [$year, $month] = explode('-', $data['service_year_month']);
      $pdf->SetFontSize($this->coord('treatment_month', 'fontSize'));
      $this->drawTextByKey($pdf, 'treatment_month', (string)(int)$month);
      $pdf->SetFontSize(10);
    }

    // === 申請先名称（実データモード） ===
    if (!$this->sampleDataMode && $this->hasCoord('insurer_name') && $insurance && isset($insurance->insurer_name)) {
      $pdf->SetFontSize($this->coord('insurer_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'insurer_name', (string)$insurance->insurer_name);
      $pdf->SetFontSize(10);
    }

    // === 同意記録（実データモード） ===
    if (!$this->sampleDataMode && $consent) {
      // 同意医師氏名
      if ($this->hasCoord('consent_record_doctor_name') && isset($consent->consenting_doctor_name)) {
        $pdf->SetFontSize($this->coord('consent_record_doctor_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_doctor_name', (string)$consent->consenting_doctor_name);
      }

      // 同意医師住所・郵便番号（doctorsテーブルから取得）
      if (isset($consent->consenting_doctor_name)) {
        // 医師名から姓名を分割（半角・全角・Unicodeスペースに対応）
        $nameParts = preg_split('/[\s\x{2000}-\x{200B}\x{3000}]+/u', trim($consent->consenting_doctor_name));
        if (count($nameParts) >= 2) {
          $lastName = $nameParts[0];
          $firstName = $nameParts[1];

          // doctorsテーブルから該当医師を検索
          $doctor = DB::table('doctors')
            ->where('last_name', $lastName)
            ->where('first_name', $firstName)
            ->first();

          if ($doctor) {
            // 同意医師郵便番号
            if ($this->hasCoord('consent_record_doctor_postal_code') && isset($doctor->postal_code)) {
              $postalCode = $doctor->postal_code;
              // ハイフンなしの場合はXXX-XXXXフォーマットに変換
              if (strlen($postalCode) === 7 && strpos($postalCode, '-') === false) {
                $postalCode = substr($postalCode, 0, 3) . '-' . substr($postalCode, 3);
              }
              $pdf->SetFontSize($this->coord('consent_record_doctor_postal_code', 'fontSize'));
              $this->drawTextByKey($pdf, 'consent_record_doctor_postal_code', (string)$postalCode);
            }

            // 同意医師住所
            if ($this->hasCoord('consent_record_doctor_address')) {
              $doctorAddress = ($doctor->address_1 ?? '') . ($doctor->address_2 ?? '') . ($doctor->address_3 ?? '');
              if ($doctorAddress) {
                $pdf->SetFontSize($this->coord('consent_record_doctor_address', 'fontSize'));
                $this->drawTextByKey($pdf, 'consent_record_doctor_address', (string)$doctorAddress);
              }
            }
          }
        }
      }

      // 同意年月日
      if ($this->hasCoord('consent_record_date_year') && isset($consent->consenting_date)) {
        [$consentYear, $consentMonth, $consentDay] = explode('-', $consent->consenting_date);
        $consentJapaneseYear = $this->convertToJapaneseYear((int)$consentYear, (int)$consentMonth);

        $pdf->SetFontSize($this->coord('consent_record_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_date_year', (string)$consentJapaneseYear['year']);

        if ($this->hasCoord('consent_record_date_month')) {
          $pdf->SetFontSize($this->coord('consent_record_date_month', 'fontSize'));
          $this->drawTextByKey($pdf, 'consent_record_date_month', (string)(int)$consentMonth);
        }

        if ($this->hasCoord('consent_record_date_day')) {
          $pdf->SetFontSize($this->coord('consent_record_date_day', 'fontSize'));
          $this->drawTextByKey($pdf, 'consent_record_date_day', (string)(int)$consentDay);
        }
      }

      // 傷病名（同意記録）
      if ($this->hasCoord('consent_record_illness_name') && isset($consent->illness_name_acupuncture_id)) {
        $illness = DB::table('illnesses_acupuncture')
          ->where('id', $consent->illness_name_acupuncture_id)
          ->first();
        $illnessName = $illness->illness_name_acupuncture ?? '';
        if (isset($consent->illness_name_acupuncture_addendum) && $consent->illness_name_acupuncture_addendum) {
          $illnessName .= ($illnessName ? '、' : '') . $consent->illness_name_acupuncture_addendum;
        }
        if ($illnessName) {
          $pdf->SetFontSize($this->coord('consent_record_illness_name', 'fontSize'));
          $this->drawTextByKey($pdf, 'consent_record_illness_name', (string)$illnessName);
        }
      }

      // 要加療期間
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

      $pdf->SetFontSize(10);
    }

    // === 支払機関欄：金融機関情報（実データモード） ===
    if ($clinicInfo) {
      // 金融機関名1
      if ($this->hasCoord('financial_institution_name_1') && isset($clinicInfo->bank_name)) {
        $pdf->SetFontSize($this->coord('financial_institution_name_1', 'fontSize'));
        $bankName = (string)$clinicInfo->bank_name;
        // 末尾の「銀行」「金庫」「農協」を除去
        $bankName = preg_replace('/(銀行|金庫|農協)$/', '', $bankName);
        $this->drawTextByKey($pdf, 'financial_institution_name_1', $bankName);
      }

      // 金融機関種別（末尾文字列から推測）
      if ($this->hasCoord('financial_institution_type_bank') && isset($clinicInfo->bank_name)) {
        $bankName = $clinicInfo->bank_name;
        if (str_ends_with($bankName, '銀行')) {
          $this->drawEllipseByKey($pdf, 'financial_institution_type_bank');
        } elseif (str_ends_with($bankName, '金庫')) {
          $this->drawEllipseByKey($pdf, 'financial_institution_type_kinko');
        } elseif (str_ends_with($bankName, '農協')) {
          $this->drawEllipseByKey($pdf, 'financial_institution_type_nokyo');
        }
      }

      // 金融機関名2（支店名）
      if ($this->hasCoord('financial_institution_name_2') && isset($clinicInfo->bank_branch_name)) {
        $pdf->SetFontSize($this->coord('financial_institution_name_2', 'fontSize'));
        $branchName = (string)$clinicInfo->bank_branch_name;
        // 末尾の「本店」「支店」「出張所」を除去
        $branchName = preg_replace('/(本店|支店|出張所)$/', '', $branchName);
        $this->drawTextByKey($pdf, 'financial_institution_name_2', $branchName);
      }

      // 支店種別（末尾文字列から推測）
      if ($this->hasCoord('branch_type_honten') && isset($clinicInfo->bank_branch_name)) {
        $branchName = $clinicInfo->bank_branch_name;
        if (str_ends_with($branchName, '本店')) {
          $this->drawEllipseByKey($pdf, 'branch_type_honten');
        } elseif (str_ends_with($branchName, '支店')) {
          $this->drawEllipseByKey($pdf, 'branch_type_shiten');
        } elseif (str_ends_with($branchName, '出張所')) {
          $this->drawEllipseByKey($pdf, 'branch_type_shucchoujo');
        }
      }

      // 口座名義（カナ）
      if ($this->hasCoord('bank_account_holder_kana') && isset($clinicInfo->bank_account_name_kana)) {
        $pdf->SetFontSize($this->coord('bank_account_holder_kana', 'fontSize'));
        $this->drawTextByKey($pdf, 'bank_account_holder_kana', (string)$clinicInfo->bank_account_name_kana);
      }

      // 口座番号
      if ($this->hasCoord('bank_account_number') && isset($clinicInfo->bank_account_number)) {
        $pdf->SetFontSize($this->coord('bank_account_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'bank_account_number', (string)$clinicInfo->bank_account_number);
      }

      $pdf->SetFontSize(10);
    }

    // === 支払機関欄：支払区分・預金種別（実データモード） ===
    // 支払区分は「振込」で固定
    if ($this->hasCoord('payment_category_furikomi')) {
      $this->drawEllipseByKey($pdf, 'payment_category_furikomi');
    }

    // 預金種別はclinic_info.bank_account_typeを参照
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

    // === 代理人情報（実データモード） ===
    if (!$this->sampleDataMode && $clinicInfo) {
      // 代理人情報はclinic_infoテーブルを参照
      if ($this->hasCoord('agent_postal_code') && isset($clinicInfo->postal_code)) {
        $pdf->SetFontSize($this->coord('agent_postal_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_postal_code', (string)$clinicInfo->postal_code);
      }

      if ($this->hasCoord('agent_address') && isset($clinicInfo->address_1)) {
        $agentAddress = ($clinicInfo->address_1 ?? '') . ($clinicInfo->address_2 ?? '') . ($clinicInfo->address_3 ?? '');
        $pdf->SetFontSize($this->coord('agent_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_address', (string)$agentAddress);
      }

      if ($this->hasCoord('agent_name') && isset($clinicInfo->clinic_name)) {
        $pdf->SetFontSize($this->coord('agent_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'agent_name', (string)$clinicInfo->clinic_name);
      }
    }

    // === 委任欄（実データモード） ===
    if (!$this->sampleDataMode) {
      // 委任申請者郵便番号・住所は当該利用者のデータを参照
      if ($this->hasCoord('signature_applicant_postal_code') && isset($clinicUser->postal_code)) {
        $pdf->SetFontSize($this->coord('signature_applicant_postal_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'signature_applicant_postal_code', (string)$clinicUser->postal_code);
      }

      if ($this->hasCoord('signature_applicant_address')) {
        $signatureAddress = ($clinicUser->address_1 ?? '') . ($clinicUser->address_2 ?? '') . ($clinicUser->address_3 ?? '');
        if ($signatureAddress) {
          $pdf->SetFontSize($this->coord('signature_applicant_address', 'fontSize'));
          $this->drawTextByKey($pdf, 'signature_applicant_address', (string)$signatureAddress);
        }
      }

      // 委任年月日は提出年月日と同じ値を使用
      if ($this->hasCoord('signature_date_year')) {
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
    $letterSpacing = 0; // 追加間隔（現在は使用しない）
    $cellWidth = $this->coord('treatment_days', 'circleSpacing') ?? 6.45; // 円の間隔
    $circleRadius = $this->coord('treatment_days', 'circleRadius') ?? 1.2;
    $innerRadius = $this->coord('treatment_days', 'doubleCircleInnerRadius') ?? 0.4;
    
    // はり･きゅう版：therapy_content_id 11-16のみ描画
    $acupunctureContentIds = [11, 12, 13, 14, 15, 16];
    
    foreach ($records as $record) {
      // 施術内容がはり･きゅう関連でない場合はスキップ
      if (!in_array($record->therapy_content_id, $acupunctureContentIds)) {
        continue;
      }
      
      $day = (int)date('d', strtotime($record->date));

      $x = $this->coord('treatment_days', 'x') + ($day - 1) * ($cellWidth + $letterSpacing);
      $y = $this->coord('treatment_days', 'y');

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
    // キーが存在しない場合は何もしない
    if (!$this->hasCoord($key)) {
      \Log::warning("描画スキップ: キーが存在しない", ['key' => $key, 'text' => $text]);
      return;
    }

    $x = $this->coord($key, 'x');
    $y = $this->coord($key, 'y');
    
    // デバッグ：座標0,0付近の描画を検出
    if ($x < 5 && $y < 5) {
      \Log::warning("座標0,0付近の描画検出", ['key' => $key, 'x' => $x, 'y' => $y, 'text' => $text]);
    }
    
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
    // キーが存在しない場合は何もしない
    if (!$this->hasCoord($key)) {
      return;
    }

    // ellipseX/ellipseYがある場合はそれを優先、なければx/yを使用
    $x = $this->coordinates[$key]['ellipseX'] ?? $this->coord($key, 'x');
    $y = $this->coordinates[$key]['ellipseY'] ?? $this->coord($key, 'y');
    $ellipseWidth = $this->coordinates[$key]['ellipseWidth'] ?? 2.5;
    $ellipseHeight = $this->coordinates[$key]['ellipseHeight'] ?? 2.5;
    $lineWidth = $this->coordinates[$key]['lineWidth'] ?? 0.5;

    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth($lineWidth);
    $pdf->Ellipse($x, $y, $ellipseWidth, $ellipseHeight, 0, 0, 360, 'D');
  }

  /**
   * 円をキーで描画
   *
   * @param Fpdi $pdf
   * @param string $key
   * @return void
   */
  protected function drawCircleByKey(Fpdi $pdf, string $key): void
  {
    $x = $this->coord($key, 'x');
    $y = $this->coord($key, 'y');
    $radius = $this->coordinates[$key]['circleRadius'] ?? 1.2;
    $lineWidth = $this->coordinates[$key]['lineWidth'] ?? 0.5;

    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth($lineWidth);
    $pdf->Ellipse($x, $y, $radius, $radius, 0, 0, 360, 'D');
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
      'insurer_name' => $custom['insurer_name'] ?? 'サンプル健康保険組合',
      'insurance_type_1_id' => 1,
      'insurance_type_1' => $custom['insurance_type_1'] ?? '社･国･組',
      'insurance_type_3' => $custom['insurance_type_3'] ?? '本外',
      'expenses_borne_ratio' => $custom['expenses_borne_ratio'] ?? '３割',
      'code_number' => $custom['insurance_symbol_kigou'] ?? '12345',
      'account_number' => $custom['insurance_symbol_bangou'] ?? '67890',
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
    $abstractText = $custom['abstract'] ?? '特記事項なし';
    // therapy_content_id を付与してエラー回避＆描画対象判定を可能にする
    // 11:はり, 12:きゅう, 14:電気針, 15:電気温灸器, 16:電気光線器具
    $records = collect([
      (object)['date' => $serviceYearMonth . '-01', 'therapy_category' => 1, 'therapy_content_id' => 11, 'abstract' => $abstractText],
      (object)['date' => $serviceYearMonth . '-05', 'therapy_category' => 1, 'therapy_content_id' => 11, 'abstract' => $abstractText],
      (object)['date' => $serviceYearMonth . '-10', 'therapy_category' => 2, 'therapy_content_id' => 12, 'abstract' => $abstractText],
      (object)['date' => $serviceYearMonth . '-15', 'therapy_category' => 1, 'therapy_content_id' => 14, 'abstract' => $abstractText],
      (object)['date' => $serviceYearMonth . '-20', 'therapy_category' => 2, 'therapy_content_id' => 15, 'abstract' => $abstractText],
      (object)['date' => $serviceYearMonth . '-25', 'therapy_category' => 1, 'therapy_content_id' => 16, 'abstract' => $abstractText],
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
    } elseif ($year >= 1989 && ($year > 1989 || $month >= 1)) {
      return ['era' => '平成', 'year' => $year - 1988];
    } elseif ($year >= 1926 && ($year > 1926 || $month >= 12)) {
      return ['era' => '昭和', 'year' => $year - 1925];
    } elseif ($year >= 1912 && ($year > 1912 || $month >= 7)) {
      return ['era' => '大正', 'year' => $year - 1911];
    } elseif ($year >= 1868) {
      return ['era' => '明治', 'year' => $year - 1867];
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

      // 初検料
      if (isset($custom['fee_initial_examination']) && $custom['fee_initial_examination']) {
        $initialExamType = $custom['fee_initial_examination'];
        $keyMap = [
          'はり' => 'fee_initial_examination_hari',
          'きゅう' => 'fee_initial_examination_kyu',
          'はり･きゅう併用' => 'fee_initial_examination_combined'
        ];

        $key = $keyMap[$initialExamType] ?? null;
        if ($key && isset($this->coordinates[$key])) {
          // 楕円描画（初検料サークル）
          $this->drawEllipseByKey($pdf, $key);

          // 初検料金額描画（別の座標に描画）
          if (isset($custom['fee_initial_examination_amount']) && isset($this->coordinates['fee_initial_examination_amount'])) {
            $pdf->SetFontSize($this->coord('fee_initial_examination_amount', 'fontSize'));
            $this->drawTextByKey($pdf, 'fee_initial_examination_amount', (string)$custom['fee_initial_examination_amount']);
          }
        }
      }

      // 電療料（サンプルデータモード：個別チェックボックス）
      $therapyContentFields = ['therapy_content_electric_needle', 'therapy_content_electric_moxa', 'therapy_content_electric_light'];
      foreach ($therapyContentFields as $field) {
        if (isset($custom[$field]) && $custom[$field] === true) {
          $this->drawEllipseByKey($pdf, $field);
        }
      }

      // 新規追加フィールド（サンプルデータモード）

      // 施術月（サンプルデータモード）
      if (isset($custom['treatment_month'])) {
        $pdf->SetFontSize($this->coord('treatment_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'treatment_month', (string)$custom['treatment_month']);
      }

      // 申請年月日は上部のメイン処理で既に描画済みのため、ここでは不要

      // 申請先名称（サンプルデータモード）
      if (isset($custom['insurer_name'])) {
        $pdf->SetFontSize($this->coord('insurer_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'insurer_name', $custom['insurer_name']);
      }

      // 支払区分（ラジオグループ）
      if (isset($custom['payment_category'])) {
        $paymentCategoryMap = [
          '振込' => 'payment_category_furikomi',
          '銀行送金' => 'payment_category_bank_transfer',
          '郵便局送金' => 'payment_category_post_transfer',
          '当地払' => 'payment_category_local_payment'
        ];
        $key = $paymentCategoryMap[$custom['payment_category']] ?? null;
        if ($key && isset($this->coordinates[$key])) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }

      // 預金種別（ラジオグループ）
      if (isset($custom['deposit_type'])) {
        $depositTypeMap = [
          '普通' => 'deposit_type_ordinary',
          '当座' => 'deposit_type_current',
          '通知' => 'deposit_type_notice',
          '別段' => 'deposit_type_betsudan'
        ];
        $key = $depositTypeMap[$custom['deposit_type']] ?? null;
        if ($key && isset($this->coordinates[$key])) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }

      // 金融機関名1
      if (isset($custom['financial_institution_name_1'])) {
        $pdf->SetFontSize($this->coord('financial_institution_name_1', 'fontSize'));
        $bankName = $custom['financial_institution_name_1'];
        // 末尾の「銀行」「金庫」「農協」を除去
        $bankName = preg_replace('/(銀行|金庫|農協)$/', '', $bankName);
        $this->drawTextByKey($pdf, 'financial_institution_name_1', $bankName);
      }

      // 金融機関種別（金融機関名１サークル）
      if (isset($custom['financial_institution_type'])) {
        $institutionTypeMap = [
          '銀行' => 'financial_institution_type_bank',
          '金庫' => 'financial_institution_type_kinko',
          '農協' => 'financial_institution_type_nokyo',
        ];
        $key = $institutionTypeMap[$custom['financial_institution_type']] ?? null;
        if ($key && isset($this->coordinates[$key])) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }

      // 金融機関名2
      if (isset($custom['financial_institution_name_2'])) {
        $pdf->SetFontSize($this->coord('financial_institution_name_2', 'fontSize'));
        $branchName = $custom['financial_institution_name_2'];
        // 末尾の「本店」「支店」「出張所」を除去
        $branchName = preg_replace('/(本店|支店|出張所)$/', '', $branchName);
        $this->drawTextByKey($pdf, 'financial_institution_name_2', $branchName);
      }

      // 支店種別（ラジオグループ）
      if (isset($custom['branch_type'])) {
        $branchTypeMap = [
          '本店' => 'branch_type_honten',
          '支店' => 'branch_type_shiten',
          '出張所' => 'branch_type_shucchoujo'
        ];
        $key = $branchTypeMap[$custom['branch_type']] ?? null;
        if ($key && isset($this->coordinates[$key])) {
          $this->drawEllipseByKey($pdf, $key);
        }
      }

      // 口座名義
      if (isset($custom['bank_account_holder_kana'])) {
        $pdf->SetFontSize($this->coord('bank_account_holder_kana', 'fontSize'));
        $this->drawTextByKey($pdf, 'bank_account_holder_kana', $custom['bank_account_holder_kana']);
      }

      // 口座番号
      if (isset($custom['bank_account_number'])) {
        $pdf->SetFontSize($this->coord('bank_account_number', 'fontSize'));
        $this->drawTextByKey($pdf, 'bank_account_number', (string)$custom['bank_account_number']);
      }

      // 同意医師氏名（同意記録）
      if (isset($custom['consent_record_doctor_name'])) {
        $pdf->SetFontSize($this->coord('consent_record_doctor_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_doctor_name', $custom['consent_record_doctor_name']);
      }

      // 同意医師郵便番号（同意記録）
      if (isset($custom['consent_record_doctor_postal_code'])) {
        $pdf->SetFontSize($this->coord('consent_record_doctor_postal_code', 'fontSize'));
        $postalCode = $custom['consent_record_doctor_postal_code'];
        // 7桁の数字を "XXX - XXXX" 形式に変換
        if (strlen($postalCode) === 7 && ctype_digit($postalCode)) {
          $postalCode = substr($postalCode, 0, 3) . ' - ' . substr($postalCode, 3);
        }
        $this->drawTextByKey($pdf, 'consent_record_doctor_postal_code', $postalCode);
      }

      // 同意医師住所（同意記録）
      if (isset($custom['consent_record_doctor_address'])) {
        $pdf->SetFontSize($this->coord('consent_record_doctor_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_doctor_address', $custom['consent_record_doctor_address']);
      }

      // 同意年月日（同意記録）
      if (isset($custom['consent_record_date_year'])) {
        $pdf->SetFontSize($this->coord('consent_record_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_date_year', (string)$custom['consent_record_date_year']);
      }
      if (isset($custom['consent_record_date_month'])) {
        $pdf->SetFontSize($this->coord('consent_record_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_date_month', (string)$custom['consent_record_date_month']);
      }
      if (isset($custom['consent_record_date_day'])) {
        $pdf->SetFontSize($this->coord('consent_record_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_date_day', (string)$custom['consent_record_date_day']);
      }

      // 傷病名（同意記録）
      if (isset($custom['consent_record_illness_name'])) {
        $pdf->SetFontSize($this->coord('consent_record_illness_name', 'fontSize'));
        $this->drawTextByKey($pdf, 'consent_record_illness_name', $custom['consent_record_illness_name']);
      }

      // 要加療期間
      if (isset($custom['required_treatment_period'])) {
        $pdf->SetFontSize($this->coord('required_treatment_period', 'fontSize'));
        $this->drawTextByKey($pdf, 'required_treatment_period', $custom['required_treatment_period']);
      }

      // 年月日（署名）
      if (isset($custom['signature_date_year'])) {
        $pdf->SetFontSize($this->coord('signature_date_year', 'fontSize'));
        $this->drawTextByKey($pdf, 'signature_date_year', (string)$custom['signature_date_year']);
      }
      if (isset($custom['signature_date_month'])) {
        $pdf->SetFontSize($this->coord('signature_date_month', 'fontSize'));
        $this->drawTextByKey($pdf, 'signature_date_month', (string)$custom['signature_date_month']);
      }
      if (isset($custom['signature_date_day'])) {
        $pdf->SetFontSize($this->coord('signature_date_day', 'fontSize'));
        $this->drawTextByKey($pdf, 'signature_date_day', (string)$custom['signature_date_day']);
      }

      // 申請者郵便番号（署名）
      if (isset($custom['signature_applicant_postal_code'])) {
        $pdf->SetFontSize($this->coord('signature_applicant_postal_code', 'fontSize'));
        $this->drawTextByKey($pdf, 'signature_applicant_postal_code', $custom['signature_applicant_postal_code']);
      }

      // 申請者住所（署名）
      if (isset($custom['signature_applicant_address'])) {
        $pdf->SetFontSize($this->coord('signature_applicant_address', 'fontSize'));
        $this->drawTextByKey($pdf, 'signature_applicant_address', $custom['signature_applicant_address']);
      }

      $pdf->SetFontSize(10);
      return;
    }

    // 通常モード：施術実績から料金を計算
    $therapyTypeCounts = [];
    $isFirstTreatment = false;

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
      }
    }

    $totalFee = 0;

    // はり料金（therapy_content_id: 11=はり のみ、13は含めない）
    $hariCount = $therapyTypeCounts[11] ?? 0;
    $feeKey = $isFirstTreatment ? 'hari_first' : 'hari_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $hariCount;

    if (isset($this->coordinates['fee_hari_unit'])) {
      $pdf->SetFontSize($this->coord('fee_hari_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_hari_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_hari_count', (string)$hariCount);
      $this->drawTextByKey($pdf, 'fee_hari_total', (string)$total);
      $totalFee += $total;
    }

    // きゅう料金（therapy_content_id: 12=きゅう のみ、13は含めない）
    $kyuCount = $therapyTypeCounts[12] ?? 0;
    $feeKey = $isFirstTreatment ? 'kyu_first' : 'kyu_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $kyuCount;

    if (isset($this->coordinates['fee_kyu_unit'])) {
      $pdf->SetFontSize($this->coord('fee_kyu_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_kyu_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_kyu_count', (string)$kyuCount);
      $this->drawTextByKey($pdf, 'fee_kyu_total', (string)$total);
      $totalFee += $total;
    }

    // はり・きゅう併用料金（therapy_content_id: 13）
    $combinedCount = $therapyTypeCounts[13] ?? 0;
    $feeKey = $isFirstTreatment ? 'hari_and_kyu_first' : 'hari_and_kyu_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $combinedCount;

    if (isset($this->coordinates['fee_hari_kyu_unit'])) {
      $pdf->SetFontSize($this->coord('fee_hari_kyu_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_hari_kyu_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_hari_kyu_count', (string)$combinedCount);
      $this->drawTextByKey($pdf, 'fee_hari_kyu_total', (string)$total);
      $totalFee += $total;
    }

    // === 電療料（サークル描画 + 金額計算） ===
    // therapy_content_id: 14=電気針, 15=電気温灸器, 16/17=電気光線器具
    $therapyContentMap = [
      14 => 'therapy_content_electric_needle',
      15 => 'therapy_content_electric_moxa',
      16 => 'therapy_content_electric_light',
      17 => 'therapy_content_electric_light', // 重複ID対応
    ];

    // 電療料の種類をカウント（複数種類があるかチェック）
    $electricTypes = [];
    if (isset($therapyTypeCounts[14]) && $therapyTypeCounts[14] > 0) $electricTypes[] = 14;
    if (isset($therapyTypeCounts[15]) && $therapyTypeCounts[15] > 0) $electricTypes[] = 15;
    if ((isset($therapyTypeCounts[16]) && $therapyTypeCounts[16] > 0) ||
        (isset($therapyTypeCounts[17]) && $therapyTypeCounts[17] > 0)) $electricTypes[] = 16;

    $multipleElectricTypes = count($electricTypes) > 1;

    foreach ($therapyContentMap as $contentId => $fieldKey) {
      if (isset($therapyTypeCounts[$contentId]) && $therapyTypeCounts[$contentId] > 0) {
        $this->drawEllipseByKey($pdf, $fieldKey);
      }
    }

    // 電療料（複数種類がない場合のみ描画、複数の場合は手書き用に空欄）
    // 電気針（はり+電気針）: 14、電気温灸器（きゅう+電気温灸器）: 15、電気光線器具: 16/17
    $electricCount = 0;
    $electricUnitPrice = 0;
    if (!$multipleElectricTypes) {
      if (isset($therapyTypeCounts[14]) && $therapyTypeCounts[14] > 0) {
        $electricCount = $therapyTypeCounts[14];
        $feeKey = $isFirstTreatment ? 'hari_and_elec_needle_first' : 'hari_and_elec_needle_normal';
        $electricUnitPrice = (int)($treatmentFees->$feeKey ?? 0);
      } elseif (isset($therapyTypeCounts[15]) && $therapyTypeCounts[15] > 0) {
        $electricCount = $therapyTypeCounts[15];
        $feeKey = $isFirstTreatment ? 'kyu_and_elec_moxa_heater_first' : 'kyu_and_elec_moxa_heater_normal';
        $electricUnitPrice = (int)($treatmentFees->$feeKey ?? 0);
      } elseif ((isset($therapyTypeCounts[16]) && $therapyTypeCounts[16] > 0) ||
                (isset($therapyTypeCounts[17]) && $therapyTypeCounts[17] > 0)) {
        $electricCount = ($therapyTypeCounts[16] ?? 0) + ($therapyTypeCounts[17] ?? 0);
        $feeKey = $isFirstTreatment ? 'fomentation_and_elec_ray_first' : 'fomentation_and_elec_ray_normal';
        $electricUnitPrice = (int)($treatmentFees->$feeKey ?? 0);
      }
    }
    // 電療料がない場合は単価のみ設定（デフォルトは電気針の料金）
    if ($electricCount === 0 && $electricUnitPrice === 0) {
      $feeKey = $isFirstTreatment ? 'hari_and_elec_needle_first' : 'hari_and_elec_needle_normal';
      $electricUnitPrice = (int)($treatmentFees->$feeKey ?? 0);
    }
    $electricTotal = $electricUnitPrice * $electricCount;

    if (isset($this->coordinates['fee_electric_unit'])) {
      $pdf->SetFontSize($this->coord('fee_electric_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_electric_unit', (string)$electricUnitPrice);
      $this->drawTextByKey($pdf, 'fee_electric_count', (string)$electricCount);
      $this->drawTextByKey($pdf, 'fee_electric_total', (string)$electricTotal);
      $totalFee += $electricTotal;
    }

    // 往療料金4km以下（recordsのhousecall_distanceから判定、はり・きゅう関連の施術のみカウント、0 < distance <= 4）
    $acupunctureContentIds = [11, 12, 13, 14, 15, 16];
    $housecallCount = 0;
    foreach ($records as $record) {
      $distance = $record->housecall_distance ?? 0;
      if ($distance > 0 && $distance <= 4 && in_array($record->therapy_content_id ?? null, $acupunctureContentIds)) {
        $housecallCount++;
      }
    }

    $feeKey = $isFirstTreatment ? 'housecall_max_2km_first' : 'housecall_max_2km_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $housecallCount;

    if (isset($this->coordinates['fee_housecall_unit'])) {
      $pdf->SetFontSize($this->coord('fee_housecall_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_housecall_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_housecall_count', (string)$housecallCount);
      $this->drawTextByKey($pdf, 'fee_housecall_total', (string)$total);
      $totalFee += $total;
    }

    // 往療料（4km超、はり・きゅう関連の施術のみカウント）
    $housecallAdditionalCount = 0;
    foreach ($records as $record) {
      if (isset($record->housecall_distance) && $record->housecall_distance > 4 && in_array($record->therapy_content_id ?? null, $acupunctureContentIds)) {
        $housecallAdditionalCount++;
      }
    }

    $feeKey = $isFirstTreatment ? 'housecall_additional_max_4km_first' : 'housecall_additional_max_4km_normal';
    $unitPrice = (int)($treatmentFees->$feeKey ?? 0);
    $total = $unitPrice * $housecallAdditionalCount;

    if (isset($this->coordinates['fee_housecall_additional_unit'])) {
      $pdf->SetFontSize($this->coord('fee_housecall_additional_unit', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_housecall_additional_unit', (string)$unitPrice);
      $this->drawTextByKey($pdf, 'fee_housecall_additional_count', (string)$housecallAdditionalCount);
      $this->drawTextByKey($pdf, 'fee_housecall_additional_total', (string)$total);
      $totalFee += $total;
    }

    // 初検料（初回施術時のみ描画）
    if ($isFirstTreatment) {
      $initialExaminationFee = 1500;

      // 施術タイプを判定（正しいIDを使用）
      $hariCount = ($therapyTypeCounts[11] ?? 0) + ($therapyTypeCounts[13] ?? 0);
      $kyuCount = ($therapyTypeCounts[12] ?? 0) + ($therapyTypeCounts[13] ?? 0);

      $key = null;
      if ($hariCount > 0 && $kyuCount > 0) {
        $key = 'fee_initial_examination_combined';
      } elseif ($hariCount > 0) {
        $key = 'fee_initial_examination_hari';
      } elseif ($kyuCount > 0) {
        $key = 'fee_initial_examination_kyu';
      }

      if ($key && isset($this->coordinates[$key])) {
        // 楕円描画（初検料サークル）
        $this->drawEllipseByKey($pdf, $key);

        // 初検料金額描画（別の座標に描画）
        if (isset($this->coordinates['fee_initial_examination_amount'])) {
          $pdf->SetFontSize($this->coord('fee_initial_examination_amount', 'fontSize'));
          $this->drawTextByKey($pdf, 'fee_initial_examination_amount', (string)$initialExaminationFee);
        }
        
        $totalFee += $initialExaminationFee;
      }
    }

    // 合計（fee_subtotal）
    if (isset($this->coordinates['fee_subtotal'])) {
      $pdf->SetFontSize($this->coord('fee_subtotal', 'fontSize'));
      $this->drawTextByKey($pdf, 'fee_subtotal', (string)$totalFee);
    }

    // 一部負担金（保険負担割合から計算）
    if (isset($this->coordinates['fee_partial_payment'])) {
      // expenses_borne_ratioから数値を抽出（"2割" → 20）
      $ratioText = (string)($insurance->expenses_borne_ratio ?? '3割');
      $expensesBorneRatio = 30; // デフォルト3割
      
      if (preg_match('/(\d+)割/', $ratioText, $matches)) {
        $expensesBorneRatio = (int)$matches[1] * 10;
      }

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


