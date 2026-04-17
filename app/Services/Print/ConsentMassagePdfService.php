<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 同意書（あんま･マッサージ）PDF生成サービス
 */
class ConsentMassagePdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/consent_massage_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = ''): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);

    foreach ($clinicUserIds as $clinicUserId) {
      $data = $this->fetchData($clinicUserId, $submissionDate);

      if ($data) {
        $this->addPage($pdf, $data, $submissionDate, $remarks);
      } else {
        $this->addNoConsentPage($pdf, 'massage');
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

    // あんま・マッサージ同意書情報取得（最新）
    $consent = DB::table('consents_massage')
      ->leftJoin('illnesses_massage', 'consents_massage.injury_and_illness_name_id', '=', 'illnesses_massage.id')
      ->where('consents_massage.clinic_user_id', $clinicUserId)
      ->orderBy('consents_massage.consenting_date', 'desc')
      ->select('consents_massage.*', 'illnesses_massage.illness_name')
      ->first();

    // 同意医師情報取得
    $doctor = null;
    if ($consent && $consent->consenting_doctor_id) {
      // consenting_doctor_idを使用してdoctorsテーブルから医師情報を取得
      $doctor = DB::table('doctors')
        ->leftJoin('medical_institutions', 'doctors.medical_institutions_id', '=', 'medical_institutions.id')
        ->where('doctors.id', $consent->consenting_doctor_id)
        ->select('doctors.*', 'medical_institutions.medical_institution_name')
        ->first();
    }

    // 部位情報取得
    $bodyparts = [];
    if ($consent) {
      $bodypartsRecords = DB::table('bodyparts-consents_massage')
        ->leftJoin('bodyparts', 'bodyparts-consents_massage.symtom_1_bodyparts_id', '=', 'bodyparts.id')
        ->where('bodyparts-consents_massage.consents_massage_id', $consent->id)
        ->select('bodyparts-consents_massage.*', 'bodyparts.bodypart as symtom_1_bodypart')
        ->get();

      foreach ($bodypartsRecords as $record) {
        if ($record->symtom_1_bodyparts_id) {
          $bp = DB::table('bodyparts')->where('id', $record->symtom_1_bodyparts_id)->first();
          $bodyparts['symtom_1'][] = $bp ? $bp->bodypart : null;
        }
        if ($record->symtom_2_bodyparts_id) {
          $bp = DB::table('bodyparts')->where('id', $record->symtom_2_bodyparts_id)->first();
          $bodyparts['symtom_2'][] = $bp ? $bp->bodypart : null;
        }
        if ($record->therapy_type_1_bodyparts_id) {
          $bp = DB::table('bodyparts')->where('id', $record->therapy_type_1_bodyparts_id)->first();
          $bodyparts['therapy_type_1'][] = $bp ? $bp->bodypart : null;
        }
        if ($record->therapy_type_2_bodyparts_id) {
          $bp = DB::table('bodyparts')->where('id', $record->therapy_type_2_bodyparts_id)->first();
          $bodyparts['therapy_type_2'][] = $bp ? $bp->bodypart : null;
        }
      }
    }

    return [
      'clinic_user' => $clinicUser,
      'consent' => $consent,
      'doctor' => $doctor,
      'bodyparts' => $bodyparts,
      'submission_date' => $submissionDate,
    ];
  }

  /**
   * PDFページ追加
   */
  protected function addPage(Fpdi $pdf, array $data, string $submissionDate = '', string $remarks): void
  {
    $pdf->AddPage();

    // テンプレートPDF読み込み
    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/acupuncture_and_massage/同意書（あんま･マッサージ）.pdf');

    if (file_exists($templatePath)) {
      $pageCount = $pdf->setSourceFile($templatePath);
      $tplId = $pdf->importPage(1);
      $pdf->useTemplate($tplId, 0, 0, null, null, true);
    }

    // フォント設定
    $pdf->SetFont('kozminproregular', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // データ埋め込み
    $this->fillFormFields($pdf, $data, $submissionDate, $remarks);
  }

  /**
   * フォームフィールド埋め込み
   */
  protected function fillFormFields(Fpdi $pdf, array $data, string $submissionDate = '', string $remarks): void
  {
    $clinicUser = $data['clinic_user'];
    $consent = $data['consent'];
    $doctor = $data['doctor'];
    $bodyparts = $data['bodyparts'] ?? [];

    // 1. 利用者住所
    if (isset($this->customSampleData['user_address']) && $this->customSampleData['user_address']) {
      $pdf->SetFontSize($this->coord('user_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'user_address', (string)$this->customSampleData['user_address']);
    } elseif ($clinicUser) {
      $address = ($clinicUser->address_1 ?? '') . ($clinicUser->address_2 ?? '') . ($clinicUser->address_3 ?? '');
      $pdf->SetFontSize($this->coord('user_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'user_address', $address);
    }

    // 2. 利用者氏名（姓 名形式）
    if (isset($this->customSampleData['user_name']) && $this->customSampleData['user_name']) {
      $pdf->SetFontSize($this->coord('user_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'user_name', (string)$this->customSampleData['user_name']);
    } elseif ($clinicUser) {
      $userName = ($clinicUser->last_name ?? '') . '  ' . ($clinicUser->first_name ?? '');
      $pdf->SetFontSize($this->coord('user_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'user_name', $userName);
    }

    // 3. 利用者生年月日（元号*年 *月 *日形式）
    if (isset($this->customSampleData['user_birthday']) && $this->customSampleData['user_birthday']) {
      $pdf->SetFontSize($this->coord('user_birthday', 'fontSize'));
      $this->drawTextByKey($pdf, 'user_birthday', (string)$this->customSampleData['user_birthday']);
    } elseif ($clinicUser && $clinicUser->birthday) {
      [$year, $month, $day] = explode('-', $clinicUser->birthday);
      $japaneseDate = $this->convertToJapaneseYear((int)$year, (int)$month);
      $birthdayText = $japaneseDate['era'] . $japaneseDate['year'] . '年 ' . (int)$month . '月 ' . (int)$day . '日';
      $pdf->SetFontSize($this->coord('user_birthday', 'fontSize'));
      $this->drawTextByKey($pdf, 'user_birthday', $birthdayText);
    }

    // 3-1. 傷病名（テキスト）
    if (isset($this->customSampleData['consent_massage_illness_name']) && $this->customSampleData['consent_massage_illness_name']) {
      $pdf->SetFontSize($this->coord('consent_massage_illness_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_massage_illness_name', (string)$this->customSampleData['consent_massage_illness_name']);
    } elseif ($consent && $consent->illness_name) {
      $pdf->SetFontSize($this->coord('consent_massage_illness_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_massage_illness_name', $consent->illness_name);
    }

    // 4-10. 傷病名（サークル）
    // サンプルモード時はisSelectedフラグ、通常モード時はDBデータで判定
    $useSampleMode = false;
    for ($i = 1; $i <= 7; $i++) {
      $key = 'illness_name_' . $i;
      if ($this->hasCoord($key) && isset($this->coordinates[$key]['isSelected'])) {
        $useSampleMode = true;
        break;
      }
    }

    if ($useSampleMode) {
      // サンプルモード: isSelectedフラグで判定
      for ($i = 1; $i <= 7; $i++) {
        $key = 'illness_name_' . $i;
        if ($this->hasCoord($key)) {
          $isSelected = $this->coordinates[$key]['isSelected'] ?? false;
          if ($isSelected) {
            $x = $this->coord($key, 'x');
            $y = $this->coord($key, 'y');
            $ellipseWidth = $this->coord($key, 'ellipseWidth') ?: 2.5;
            $ellipseHeight = $this->coord($key, 'ellipseHeight') ?: 2.5;
            $lineWidth = $this->coord($key, 'lineWidth') ?: 0.4;

            $pdf->SetLineWidth($lineWidth);
            $pdf->Ellipse($x, $y, $ellipseWidth, $ellipseHeight, 0, 0, 360, 'D');

            // 「その他」の場合、追記テキストを表示
            if ($i === 7 && isset($this->customSampleData['illness_name_other_text']) && $this->customSampleData['illness_name_other_text']) {
              $pdf->SetFontSize($this->coord('illness_name_other_text', 'fontSize'));
              $this->drawTextByKey($pdf, 'illness_name_other_text', (string)$this->customSampleData['illness_name_other_text']);
            }
          }
        }
      }
    } elseif ($consent && $consent->injury_and_illness_name_id) {
      // 通常モード: DBデータで判定
      $illnessId = $consent->injury_and_illness_name_id;
      for ($i = 1; $i <= 7; $i++) {
        $key = 'illness_name_' . $i;
        if ($this->hasCoord($key)) {
          if ($illnessId == $i) {
            $x = $this->coord($key, 'x');
            $y = $this->coord($key, 'y');
            $ellipseWidth = $this->coord($key, 'ellipseWidth') ?: 2.5;
            $ellipseHeight = $this->coord($key, 'ellipseHeight') ?: 2.5;
            $lineWidth = $this->coord($key, 'lineWidth') ?: 0.4;

            $pdf->SetLineWidth($lineWidth);
            $pdf->Ellipse($x, $y, $ellipseWidth, $ellipseHeight, 0, 0, 360, 'D');
          }
        }
      }
    }

    // 11. 傷病名（その他の内容）
    // 注: マッサージ側にはillness_name_addendumカラムが存在しないため、この機能は無効
    // if ($consent && $consent->injury_and_illness_name_id == 7 && $consent->illness_name_addendum) {
    //   $pdf->SetFontSize($this->coord('illness_name_other_text', 'fontSize'));
    //   $this->drawTextByKey($pdf, 'illness_name_other_text', $consent->illness_name_addendum);
    // }

    // 12. 発病負傷年月日（元号*年 *月 *日形式）
    if (isset($this->customSampleData['onset_date']) && $this->customSampleData['onset_date']) {
      $pdf->SetFontSize($this->coord('onset_date', 'fontSize'));
      $this->drawTextByKey($pdf, 'onset_date', (string)$this->customSampleData['onset_date']);
    } elseif ($consent && $consent->onset_and_injury_date) {
      [$year, $month, $day] = explode('-', $consent->onset_and_injury_date);
      $japaneseDate = $this->convertToJapaneseYear((int)$year, (int)$month);
      $onsetDateText = $japaneseDate['era'] . $japaneseDate['year'] . '年 ' . (int)$month . '月 ' . (int)$day . '日';
      $pdf->SetFontSize($this->coord('onset_date', 'fontSize'));
      $this->drawTextByKey($pdf, 'onset_date', $onsetDateText);
    }

    // 13. 同意区分（モーダル引数 = $remarksパラメータ）
    if (isset($this->customSampleData['consent_category']) && $this->customSampleData['consent_category']) {
      $pdf->SetFontSize($this->coord('consent_category', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_category', (string)$this->customSampleData['consent_category']);
    } elseif ($remarks) {
      $pdf->SetFontSize($this->coord('consent_category', 'fontSize'));
      $this->drawTextByKey($pdf, 'consent_category', $remarks);
    }

    // 13-1. 診察日（元号*年 *月 *日形式）
    if (isset($this->customSampleData['consenting_date']) && $this->customSampleData['consenting_date']) {
      $pdf->SetFontSize($this->coord('consenting_date', 'fontSize'));
      $this->drawTextByKey($pdf, 'consenting_date', (string)$this->customSampleData['consenting_date']);
    } elseif ($consent && $consent->consenting_date) {
      [$year, $month, $day] = explode('-', $consent->consenting_date);
      $japaneseDate = $this->convertToJapaneseYear((int)$year, (int)$month);
      $consentingDateText = $japaneseDate['era'] . $japaneseDate['year'] . '年 ' . (int)$month . '月 ' . (int)$day . '日';
      $pdf->SetFontSize($this->coord('consenting_date', 'fontSize'));
      $this->drawTextByKey($pdf, 'consenting_date', $consentingDateText);
    }

    // === 症状カテゴリ ===
    // 筋麻痺･委縮（楕円描画）
    $this->drawBodypartFields($pdf, 'muscle_paralysis', ['trunk', 'upper_limb_r', 'upper_limb_l', 'lower_limb_r', 'lower_limb_l'], $bodyparts['symtom_1'] ?? []);

    // 関節拘縮（サークル）
    $jointContractureBodyparts = ['shoulder_r', 'elbow_r', 'wrist_r', 'coxa_r', 'knee_r', 'ankle_r', 'shoulder_l', 'elbow_l', 'wrist_l', 'coxa_l', 'knee_l', 'ankle_l', 'others'];
    $this->drawBodypartFields($pdf, 'joint_contracture', $jointContractureBodyparts, $bodyparts['symtom_2'] ?? [], [
      'shoulder_r' => 'right_shoulder',
      'elbow_r' => 'right_elbow',
      'wrist_r' => 'right_wrist',
      'coxa_r' => 'right_hip',
      'knee_r' => 'right_knee',
      'ankle_r' => 'right_ankle',
      'shoulder_l' => 'left_shoulder',
      'elbow_l' => 'left_elbow',
      'wrist_l' => 'left_wrist',
      'coxa_l' => 'left_hip',
      'knee_l' => 'left_knee',
      'ankle_l' => 'left_ankle',
      'others' => 'other'
    ]);

    // 関節拘縮（その他の内容）
    if (isset($this->customSampleData['joint_contracture_other_text']) && $this->customSampleData['joint_contracture_other_text']) {
      $pdf->SetFontSize($this->coord('joint_contracture_other_text', 'fontSize'));
      $this->drawTextByKey($pdf, 'joint_contracture_other_text', (string)$this->customSampleData['joint_contracture_other_text']);
    } elseif ($consent && $consent->is_symptom_2 && $consent->symtom_2_addendum) {
      $pdf->SetFontSize($this->coord('joint_contracture_other_text', 'fontSize'));
      $this->drawTextByKey($pdf, 'joint_contracture_other_text', $consent->symtom_2_addendum);
    }

    // その他
    if (isset($this->customSampleData['symptom_other']) && $this->customSampleData['symptom_other']) {
      $pdf->SetFontSize($this->coord('symptom_other', 'fontSize'));
      $this->drawTextByKey($pdf, 'symptom_other', (string)$this->customSampleData['symptom_other']);
    } elseif ($consent && $consent->is_symptom_3 && $consent->symtom_3_addendum) {
      $pdf->SetFontSize($this->coord('symptom_other', 'fontSize'));
      $this->drawTextByKey($pdf, 'symptom_other', $consent->symtom_3_addendum);
    }

    // === 施術種類･部位カテゴリ ===
    // マッサージ（楕円描画）
    $this->drawBodypartFields($pdf, 'massage', ['trunk', 'upper_limb_r', 'upper_limb_l', 'lower_limb_r', 'lower_limb_l'], $bodyparts['therapy_type_1'] ?? []);

    // 変形徒手矯正術（楕円描画）
    $this->drawBodypartFields($pdf, 'manual_correction', ['upper_limb_r', 'upper_limb_l', 'lower_limb_r', 'lower_limb_l'], $bodyparts['therapy_type_2'] ?? []);

    // === 往療カテゴリ ===
    // 必要有無（楕円描画）
    if (isset($this->customSampleData['housecall_required']) && $this->customSampleData['housecall_required']) {
      $useSampleMode = true;
      foreach (['housecall_required', 'housecall_not_required'] as $key) {
        if ($this->hasCoord($key) && isset($this->coordinates[$key]['isSelected']) && $this->coordinates[$key]['isSelected']) {
          $x = $this->coord($key, 'x');
          $y = $this->coord($key, 'y');
          $ellipseWidth = $this->coord($key, 'ellipseWidth') ?: 6;
          $ellipseHeight = $this->coord($key, 'ellipseHeight') ?: 3;
          $lineWidth = $this->coord($key, 'lineWidth') ?: 0.4;
          $pdf->SetLineWidth($lineWidth);
          $pdf->Ellipse($x, $y, $ellipseWidth, $ellipseHeight, 0, 0, 360, 'D');
        }
      }
    } elseif ($consent) {
      $isRequired = $consent->is_housecall_required ?? null;
      $fieldKey = null;
      if ($isRequired === 1) {
        $fieldKey = 'housecall_required';
      } elseif ($isRequired === 0) {
        $fieldKey = 'housecall_not_required';
      }
      if ($fieldKey && $this->hasCoord($fieldKey)) {
        $x = $this->coord($fieldKey, 'x');
        $y = $this->coord($fieldKey, 'y');
        $ellipseWidth = $this->coord($fieldKey, 'ellipseWidth') ?: 6;
        $ellipseHeight = $this->coord($fieldKey, 'ellipseHeight') ?: 3;
        $lineWidth = $this->coord($fieldKey, 'lineWidth') ?: 0.4;
        $pdf->SetLineWidth($lineWidth);
        $pdf->Ellipse($x, $y, $ellipseWidth, $ellipseHeight, 0, 0, 360, 'D');
      }
    }

    // 往療必要の理由（サークル）
    $useSampleMode = false;
    for ($i = 1; $i <= 3; $i++) {
      $key = 'housecall_reason_' . $i;
      if ($this->hasCoord($key) && isset($this->coordinates[$key]['isSelected'])) {
        $useSampleMode = true;
        break;
      }
    }

    if ($useSampleMode) {
      for ($i = 1; $i <= 3; $i++) {
        $key = 'housecall_reason_' . $i;
        if ($this->hasCoord($key)) {
          $isSelected = $this->coordinates[$key]['isSelected'] ?? false;
          if ($isSelected) {
            $x = $this->coord($key, 'x');
            $y = $this->coord($key, 'y');
            $ellipseWidth = $this->coord($key, 'ellipseWidth') ?: 2.5;
            $ellipseHeight = $this->coord($key, 'ellipseHeight') ?: 2.5;
            $lineWidth = $this->coord($key, 'lineWidth') ?: 0.4;
            $pdf->SetLineWidth($lineWidth);
            $pdf->Ellipse($x, $y, $ellipseWidth, $ellipseHeight, 0, 0, 360, 'D');
          }
        }
      }
    } elseif ($consent && $consent->housecall_reason_id) {
      $reasonId = $consent->housecall_reason_id;
      for ($i = 1; $i <= 3; $i++) {
        $key = 'housecall_reason_' . $i;
        if ($this->hasCoord($key)) {
          if ($reasonId == $i) {
            $x = $this->coord($key, 'x');
            $y = $this->coord($key, 'y');
            $ellipseWidth = $this->coord($key, 'ellipseWidth') ?: 2.5;
            $ellipseHeight = $this->coord($key, 'ellipseHeight') ?: 2.5;
            $lineWidth = $this->coord($key, 'lineWidth') ?: 0.4;
            $pdf->SetLineWidth($lineWidth);
            $pdf->Ellipse($x, $y, $ellipseWidth, $ellipseHeight, 0, 0, 360, 'D');
          }
        }
      }
    }

    // 往療必要の理由（その他の内容）
    if (isset($this->customSampleData['housecall_reason_other_text']) && $this->customSampleData['housecall_reason_other_text']) {
      $pdf->SetFontSize($this->coord('housecall_reason_other_text', 'fontSize'));
      $this->drawTextByKey($pdf, 'housecall_reason_other_text', (string)$this->customSampleData['housecall_reason_other_text']);
    } elseif ($consent && $consent->housecall_reason_addendum) {
      $pdf->SetFontSize($this->coord('housecall_reason_other_text', 'fontSize'));
      $this->drawTextByKey($pdf, 'housecall_reason_other_text', $consent->housecall_reason_addendum);
    }

    // 14. 提出年月日（元号*年 *月 *日形式）
    if (isset($this->customSampleData['submission_date']) && $this->customSampleData['submission_date']) {
      $pdf->SetFontSize($this->coord('submission_date', 'fontSize'));
      $this->drawTextByKey($pdf, 'submission_date', (string)$this->customSampleData['submission_date']);
    } elseif ($submissionDate) {
      [$year, $month, $day] = explode('-', $submissionDate);
      $japaneseDate = $this->convertToJapaneseYear((int)$year, (int)$month);
      $dateText = $japaneseDate['era'] . $japaneseDate['year'] . '年 ' . (int)$month . '月 ' . (int)$day . '日';
      $pdf->SetFontSize($this->coord('submission_date', 'fontSize'));
      $this->drawTextByKey($pdf, 'submission_date', $dateText);
    }

    // 15. 同意医師医療機関名
    if (isset($this->customSampleData['consenting_doctor_medical_institution_name']) && $this->customSampleData['consenting_doctor_medical_institution_name']) {
      $pdf->SetFontSize($this->coord('consenting_doctor_medical_institution_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'consenting_doctor_medical_institution_name', (string)$this->customSampleData['consenting_doctor_medical_institution_name']);
    } elseif ($doctor && $doctor->medical_institution_name) {
      $pdf->SetFontSize($this->coord('consenting_doctor_medical_institution_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'consenting_doctor_medical_institution_name', $doctor->medical_institution_name);
    }

    // 16. 同意医師医療機関住所（doctorsテーブルのaddressを使用）
    if (isset($this->customSampleData['consenting_doctor_address']) && $this->customSampleData['consenting_doctor_address']) {
      $pdf->SetFontSize($this->coord('consenting_doctor_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'consenting_doctor_address', (string)$this->customSampleData['consenting_doctor_address']);
    } elseif ($doctor) {
      $doctorAddress = ($doctor->address_1 ?? '') . ($doctor->address_2 ?? '') . ($doctor->address_3 ?? '');
      $pdf->SetFontSize($this->coord('consenting_doctor_address', 'fontSize'));
      $this->drawTextByKey($pdf, 'consenting_doctor_address', $doctorAddress);
    }

    // 17. 同意医師氏名（姓 名形式）
    if (isset($this->customSampleData['consenting_doctor_name']) && $this->customSampleData['consenting_doctor_name']) {
      $pdf->SetFontSize($this->coord('consenting_doctor_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'consenting_doctor_name', (string)$this->customSampleData['consenting_doctor_name']);
    } elseif ($doctor) {
      $doctorName = ($doctor->last_name ?? '') . '  ' . ($doctor->first_name ?? '');
      $pdf->SetFontSize($this->coord('consenting_doctor_name', 'fontSize'));
      $this->drawTextByKey($pdf, 'consenting_doctor_name', $doctorName);
    }
  }

  /**
   * 部位ごとの楕円描画ヘルパー
   *
   * @param Fpdi $pdf PDFオブジェクト
   * @param string $fieldPrefix フィールドプレフィックス（例: muscle_paralysis, massage）
   * @param array $allBodyparts すべての可能な部位のリスト（例: ['trunk', 'upper_limb_r']）
   * @param array $selectedBodyparts DBから取得した選択済み部位のリスト
   * @param array $bodypartMapping DB部位名→フィールドサフィックスのマッピング（オプション）
   */
  protected function drawBodypartFields(Fpdi $pdf, string $fieldPrefix, array $allBodyparts, array $selectedBodyparts, array $bodypartMapping = []): void
  {
    // サンプルモードチェック
    $useSampleMode = false;
    foreach ($allBodyparts as $bodypart) {
      $suffix = $bodypartMapping[$bodypart] ?? $bodypart;
      $key = $fieldPrefix . '_' . $suffix;
      if ($this->hasCoord($key) && isset($this->coordinates[$key]['isSelected'])) {
        $useSampleMode = true;
        break;
      }
    }

    if ($useSampleMode) {
      // サンプルモード: isSelectedフラグで描画
      foreach ($allBodyparts as $bodypart) {
        $suffix = $bodypartMapping[$bodypart] ?? $bodypart;
        $key = $fieldPrefix . '_' . $suffix;
        if ($this->hasCoord($key)) {
          $isSelected = $this->coordinates[$key]['isSelected'] ?? false;
          if ($isSelected) {
            $x = $this->coord($key, 'x');
            $y = $this->coord($key, 'y');
            $ellipseWidth = $this->coord($key, 'ellipseWidth') ?: 6;
            $ellipseHeight = $this->coord($key, 'ellipseHeight') ?: 3;
            $lineWidth = $this->coord($key, 'lineWidth') ?: 0.4;
            $pdf->SetLineWidth($lineWidth);
            $pdf->Ellipse($x, $y, $ellipseWidth, $ellipseHeight, 0, 0, 360, 'D');
          }
        }
      }
    } else {
      // 通常モード: DBから取得した部位のみ描画
      foreach ($selectedBodyparts as $selectedBodypart) {
        if (!$selectedBodypart) continue;

        $suffix = $bodypartMapping[$selectedBodypart] ?? $selectedBodypart;
        $key = $fieldPrefix . '_' . $suffix;

        if ($this->hasCoord($key)) {
          $x = $this->coord($key, 'x');
          $y = $this->coord($key, 'y');
          $ellipseWidth = $this->coord($key, 'ellipseWidth') ?: 6;
          $ellipseHeight = $this->coord($key, 'ellipseHeight') ?: 3;
          $lineWidth = $this->coord($key, 'lineWidth') ?: 0.4;
          $pdf->SetLineWidth($lineWidth);
          $pdf->Ellipse($x, $y, $ellipseWidth, $ellipseHeight, 0, 0, 360, 'D');
        }
      }
    }
  }
}
