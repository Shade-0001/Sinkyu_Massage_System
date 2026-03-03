<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 実施計画書PDF生成サービス
 */
class ImplementationPlanPdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/implementation_plan_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    $configPath = storage_path('app/config/implementation_plan_coordinates.json');
    if (file_exists($configPath)) {
      $json = file_get_contents($configPath);
      return json_decode($json, true) ?? [];
    }
    return [];
  }

  /**
   * PDF生成
   *
   * @param array  $clinicUserIds    利用者ID配列
   * @param string $serviceYearMonth サービス提供年月 (YYYY-MM)
   * @param string $submissionDate   提出年月日 (YYYY-MM-DD)
   * @param string $remarks          備考
   * @return string PDFバイナリデータ
   */
  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = ''): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);

    if ($this->sampleDataMode) {
      $this->addPage($pdf, $this->getSampleData());
    } else {
      foreach ($clinicUserIds as $clinicUserId) {
        $data = $this->fetchData((int) $clinicUserId, $serviceYearMonth);
        if ($data) {
          $this->addPage($pdf, $data);
        }
      }
    }

    return $pdf->Output('', 'S');
  }

  /**
   * 利用者データ取得
   */
  protected function fetchData(int $clinicUserId, string $serviceYearMonth = ''): ?array
  {
    // 利用者情報（性別JOIN）
    $clinicUser = DB::table('clinic_users')
      ->leftJoin('gender', 'clinic_users.gender_id', '=', 'gender.id')
      ->where('clinic_users.id', $clinicUserId)
      ->select('clinic_users.*', 'gender.gender')
      ->first();

    if (!$clinicUser) {
      return null;
    }

    // 実施計画（指定年月のassessment_dateのうちcreated_at最新）
    $planQuery = DB::table('plans')->where('clinic_user_id', $clinicUserId);
    if (!empty($serviceYearMonth)) {
      $planQuery->whereRaw("DATE_FORMAT(assessment_date, '%Y-%m') = ?", [$serviceYearMonth]);
    }
    $plan = $planQuery->orderBy('created_at', 'desc')->first();

    if (!$plan) {
      return null;
    }

    // ADL評価レベルマスタ（id => テキスト）
    $levels = DB::table('assistance_levels')->pluck('assistance_level', 'id');

    // 事業所情報
    $clinicInfo = DB::table('clinic_info')->first();

    // 傷病名（consents_massage から最新のレコードをJOINして取得）
    $consentMassage = DB::table('consents_massage')
      ->leftJoin('illnesses_massage', 'consents_massage.injury_and_illness_name_id', '=', 'illnesses_massage.id')
      ->where('consents_massage.clinic_user_id', $clinicUserId)
      ->orderBy('consents_massage.created_at', 'desc')
      ->select('illnesses_massage.illness_name')
      ->first();
    $illnessName = $consentMassage->illness_name ?? '';

    return [
      'clinic_user'  => $clinicUser,
      'plan'         => $plan,
      'levels'       => $levels,
      'clinic_info'  => $clinicInfo,
      'illness_name' => $illnessName,
    ];
  }

  /**
   * サンプルデータ生成（座標調整ツール用）
   */
  protected function getSampleData(): array
  {
    $c = $this->customSampleData ?? [];

    return [
      'clinic_user' => (object) [
        'last_name'  => $c['ip_patient_name'] ?? '田中',
        'first_name' => '',
        'gender'     => $c['ip_gender']    ?? '男',
        'birthday'   => '1955-03-15',
      ],
      'plan' => (object) [
        'assessment_date'                        => '2025-01-15',
        'assessor'                               => $c['ip_assessor'] ?? '鈴木 一郎',
        'eating_assistance_level_id'             => null,
        'eating_assistance_note'                 => $c['ip_adl_eating_note']             ?? '',
        'moving_assistance_level_id'             => null,
        'moving_assistance_note'                 => $c['ip_adl_moving_note']             ?? 'T字杖使用',
        'personal_grooming_assistance_level_id'  => null,
        'personal_grooming_assistance_note'      => $c['ip_adl_personal_grooming_note']  ?? '',
        'using_toilet_assistance_level_id'       => null,
        'using_toilet_assistance_note'           => $c['ip_adl_using_toilet_note']       ?? '一部見守り',
        'bathing_assistance_level_id'            => null,
        'bathing_assistance_note'                => $c['ip_adl_bathing_note']            ?? '全介助',
        'walking_assistance_level_id'            => null,
        'walking_assistance_note'                => $c['ip_adl_walking_note']            ?? '',
        'using_stairs_assistance_level_id'       => null,
        'using_stairs_assistance_note'           => $c['ip_adl_using_stairs_note']       ?? '',
        'changing_clothes_assistance_level_id'   => null,
        'changing_clothes_assistance_note'       => $c['ip_adl_changing_clothes_note']   ?? '',
        'defecation_assistance_level_id'         => null,
        'defecation_assistance_note'             => $c['ip_adl_defecation_note']         ?? '',
        'urination_assistance_level_id'          => null,
        'urination_assistance_note'              => $c['ip_adl_urination_note']          ?? '',
        'communication_note'                     => $c['ip_communication_note']          ?? '意思疎通可能',
        'wish_of_user_and_familiy'               => $c['ip_wish_of_user_and_family']     ?? '自宅での生活を継続したい',
        'care_purpose'                           => $c['ip_care_purpose']                ?? '筋力維持・関節可動域の拡大',
        'rehabilitation_program'                 => $c['ip_rehabilitation_program']      ?? 'ROM訓練・筋力強化訓練',
        'home_rehabilitation'                    => $c['ip_home_rehabilitation']         ?? '毎日30分の体操',
        'change_since_previous_planning'         => $c['ip_change_since_previous_planning'] ?? '歩行能力が向上',
        'note'                                   => $c['ip_note']                        ?? '転倒に注意',
      ],
      'levels' => collect([
        1 => '要介助',
        2 => '部分介助',
        3 => '要監視又は軽監視',
        4 => '準備のみ',
        5 => '声がけのみ',
        6 => '見守りのみ',
        7 => '昼間のみ自立',
        8 => '自立',
        9 => '昼夜問わず自立',
      ]),
      'clinic_info' => (object) [
        'postal_code'      => '1000001',
        'address_1'        => $c['ip_clinic_address']     ?? '東京都千代田区千代田1-1-1',
        'address_2'        => '',
        'address_3'        => '',
        'phone'            => $c['ip_clinic_phone']       ?? '03-1234-5678',
        'clinic_name'      => $c['ip_clinic_name']        ?? 'サンプル鍼灸マッサージ院',
        'owner_last_name'  => '鈴木',
        'owner_first_name' => '一郎',
      ],      'illness_name' => $c['ip_illness_name'] ?? '腰痛・肩こり',      // サンプルデータでのADL評価値（assistance_levels の level_id 数値で指定）
      'sample_adl_levels' => [
        'eating_assistance_level_id'            => 8,  // 自立
        'moving_assistance_level_id'            => 3,  // 部分介助
        'personal_grooming_assistance_level_id' => 8,  // 自立
        'using_toilet_assistance_level_id'      => 3,  // 部分介助
        'bathing_assistance_level_id'           => 1,  // 要介助
        'walking_assistance_level_id'           => 6,  // 要監視又は軽監視
        'using_stairs_assistance_level_id'      => 3,  // 部分介助
        'changing_clothes_assistance_level_id'  => 8,  // 自立
        'defecation_assistance_level_id'        => 8,  // 自立
        'urination_assistance_level_id'         => 8,  // 自立
      ],
    ];
  }

  /**
   * ページを追加して描画
   */
  protected function addPage(Fpdi $pdf, array $data): void
  {
    $pdf->AddPage();

    // テンプレート読み込み
    $path = $this->customTemplatePath;
    if ($path && file_exists($path)) {
      $pdf->setSourceFile($path);
      $tpl = $pdf->importPage(1);
      $pdf->useTemplate($tpl, 0, 0, null, null, true);
    }

    $pdf->SetFont('kozgopromedium', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    $this->fillFormFields($pdf, $data);
  }

  /**
   * フォームフィールドへの描画
   */
  protected function fillFormFields(Fpdi $pdf, array $data): void
  {
    $clinicUser     = $data['clinic_user'];
    $plan           = $data['plan'];
    $levels         = $data['levels'];
    $clinicInfo     = $data['clinic_info'];
    $illnessName    = $data['illness_name'] ?? '';
    $sampleAdlLevels = $data['sample_adl_levels'] ?? null;

    // =====================
    // グループ１：基本情報
    // =====================

    // 評価日
    if (!empty($plan->assessment_date)) {
      $this->drawTextByKey($pdf, 'ip_assessment_date', $this->convertToJapaneseDate($plan->assessment_date));
    }

    // 利用者氏名（末尾「　様」）
    if ($sampleAdlLevels) {
      // サンプルモード：customSampleDataのip_patient_nameをそのまま使用
      $name = $this->customSampleData['ip_patient_name'] ?? '田中 太郎';
      $this->drawTextByKey($pdf, 'ip_patient_name', $name);
    } else {
      $name = trim(($clinicUser->last_name ?? '') . '  ' . ($clinicUser->first_name ?? ''));
      if ($name) {
        $this->drawTextByKey($pdf, 'ip_patient_name', $name . '　様');
      }
    }

    // 性別
    $this->drawTextByKey($pdf, 'ip_gender', $clinicUser->gender ?? '');

    // 生年月日
    if (!empty($clinicUser->birthday)) {
      $this->drawTextByKey($pdf, 'ip_birthdate', $this->convertToJapaneseDate($clinicUser->birthday));
    }

    // 傷病名
    $this->drawTextByKey($pdf, 'ip_illness_name', $illnessName);

    // =====================
    // グループ２：ADL評価
    // =====================

    $adlItems = [
      [
        'level_id_col' => 'eating_assistance_level_id',
        'note_col'     => 'eating_assistance_note',
        'bi_item'      => 'eating',
      ],
      [
        'level_id_col' => 'moving_assistance_level_id',
        'note_col'     => 'moving_assistance_note',
        'bi_item'      => 'moving',
      ],
      [
        'level_id_col' => 'personal_grooming_assistance_level_id',
        'note_col'     => 'personal_grooming_assistance_note',
        'bi_item'      => 'personal_grooming',
      ],
      [
        'level_id_col' => 'using_toilet_assistance_level_id',
        'note_col'     => 'using_toilet_assistance_note',
        'bi_item'      => 'using_toilet',
      ],
      [
        'level_id_col' => 'bathing_assistance_level_id',
        'note_col'     => 'bathing_assistance_note',
        'bi_item'      => 'bathing',
      ],
      [
        'level_id_col' => 'walking_assistance_level_id',
        'note_col'     => 'walking_assistance_note',
        'bi_item'      => 'walking',
      ],
      [
        'level_id_col' => 'using_stairs_assistance_level_id',
        'note_col'     => 'using_stairs_assistance_note',
        'bi_item'      => 'using_stairs',
      ],
      [
        'level_id_col' => 'changing_clothes_assistance_level_id',
        'note_col'     => 'changing_clothes_assistance_note',
        'bi_item'      => 'changing_clothes',
      ],
      [
        'level_id_col' => 'defecation_assistance_level_id',
        'note_col'     => 'defecation_assistance_note',
        'bi_item'      => 'defecation',
      ],
      [
        'level_id_col' => 'urination_assistance_level_id',
        'note_col'     => 'urination_assistance_note',
        'bi_item'      => 'urination',
      ],
    ];

    // ADL評価値：1フィールド(ip_adl_level)の座標を基点に rowLineHeight で縦列挙
    if ($this->hasCoord('ip_adl_level')) {
      $adlX          = $this->coord('ip_adl_level', 'x');
      $adlBaseY      = $this->coord('ip_adl_level', 'y');
      $rowLineHeight = $this->coord('ip_adl_level', 'rowLineHeight', 7);
      $adlFontSize   = $this->coord('ip_adl_level', 'fontSize', 10);
      $adlWidth      = $this->coord('ip_adl_level', 'width', 15);
      $adlAlign      = strtoupper(substr($this->coord('ip_adl_level', 'textAlign', 'L'), 0, 1));

      foreach ($adlItems as $i => $item) {
        $offsetY = $i * $rowLineHeight;

        if ($sampleAdlLevels) {
          $levelId = (int) ($sampleAdlLevels[$item['level_id_col']] ?? 0);
        } else {
          $levelId = (int) ($plan->{$item['level_id_col']} ?? 0);
        }
        $scoreText = $levelId ? (string) $this->getBarthelScore($item['bi_item'], $levelId) : '';

        $pdf->SetFont('kozgopromedium', '', $adlFontSize);
        $pdf->SetXY($adlX, $adlBaseY + $offsetY);
        $pdf->Cell($adlWidth, 0, $scoreText, 0, 0, $adlAlign, false);
      }
    }

    // ADL備考（1フィールド ip_adl_note の座標を基点に rowLineHeight で縦列挙）
    if ($this->hasCoord('ip_adl_note')) {
      $noteX          = $this->coord('ip_adl_note', 'x');
      $noteBaseY      = $this->coord('ip_adl_note', 'y');
      $noteLineHeight = $this->coord('ip_adl_note', 'rowLineHeight', 7);
      $noteFontSize   = $this->coord('ip_adl_note', 'fontSize', 10);

      foreach ($adlItems as $i => $item) {
        $noteText = $plan->{$item['note_col']} ?? '';
        $pdf->SetFont('kozgopromedium', '', $noteFontSize);
        $pdf->SetXY($noteX, $noteBaseY + $i * $noteLineHeight);
        $pdf->Cell(0, 0, $noteText, 0, 0, 'L', false);
      }
    }

    // ADL合計値
    if ($this->hasCoord('ip_adl_total')) {
      $total = 0;
      foreach ($adlItems as $item) {
        if ($sampleAdlLevels) {
          $levelId = (int) ($sampleAdlLevels[$item['level_id_col']] ?? 0);
        } else {
          $levelId = (int) ($plan->{$item['level_id_col']} ?? 0);
        }
        if ($levelId) {
          $total += $this->getBarthelScore($item['bi_item'], $levelId);
        }
      }
      $this->drawTextByKey($pdf, 'ip_adl_total', (string) $total);
    }

    // コミュニケーション
    $this->drawTextByKey($pdf, 'ip_communication_note', $plan->communication_note ?? '');

    // =====================
    // グループ３：計画情報
    // =====================

    $this->drawTextByKey($pdf, 'ip_wish_of_user_and_family', $plan->wish_of_user_and_familiy ?? '');
    $this->drawTextByKey($pdf, 'ip_care_purpose', $plan->care_purpose ?? '');
    $this->drawTextByKey($pdf, 'ip_rehabilitation_program', $plan->rehabilitation_program ?? '');
    $this->drawTextByKey($pdf, 'ip_home_rehabilitation', $plan->home_rehabilitation ?? '');
    $this->drawTextByKey($pdf, 'ip_change_since_previous_planning', $plan->change_since_previous_planning ?? '');
    $this->drawTextByKey($pdf, 'ip_note', $plan->note ?? '');
    $this->drawTextByKey($pdf, 'ip_assessor', $plan->assessor ?? '');

    // =====================
    // グループ４：事業所情報
    // =====================

    if ($clinicInfo) {
      // 郵便番号（〒XXX-XXXX 形式に整形、ハイフンあり/なし両方対応）
      $postalCode = (string) ($clinicInfo->postal_code ?? '');
      $postalCodeNumbers = preg_replace('/[^0-9]/', '', $postalCode);
      if (strlen($postalCodeNumbers) === 7) {
        $postalCode = '〒' . substr($postalCodeNumbers, 0, 3) . '-' . substr($postalCodeNumbers, 3, 4);
      } elseif ($postalCode !== '') {
        $postalCode = '〒' . $postalCode;
      }
      $this->drawTextByKey($pdf, 'ip_clinic_postal_code', $postalCode);

      $address = ($clinicInfo->address_1 ?? '') . ($clinicInfo->address_2 ?? '') . ($clinicInfo->address_3 ?? '');
      $this->drawTextByKey($pdf, 'ip_clinic_address', $address);

      $this->drawTextByKey($pdf, 'ip_clinic_phone', $this->formatPhoneNumber($clinicInfo->phone ?? ''));
      $this->drawTextByKey($pdf, 'ip_clinic_name', $clinicInfo->clinic_name ?? '');

      $ownerName = trim(($clinicInfo->owner_last_name ?? '') . '  ' . ($clinicInfo->owner_first_name ?? ''));
      $this->drawTextByKey($pdf, 'ip_clinic_owner_name', $ownerName);
    }
  }

  /**
   * バーセルインデックス（BI）スコアを返す
   *
   * assistance_levels の id と ADL項目名を元に点数を計算する。
   *
   * assistance_levels の id 対応:
   *   1=要介助, 2=全介助, 3=部分介助, 4=中等度介助,
   *   5=中等度以上の介助又は不能, 6=要監視又は軽監視,
   *   7=車椅子使用, 8=自立, 9=昼夜問わず自立
   *
   * @param string $item    ADL項目キー（eating, moving, ... など）
   * @param int    $levelId assistance_levels.id
   * @return int            バーセルインデックス点数
   */
  protected function getBarthelScore(string $item, int $levelId): int
  {
    $map = [
      // 食事（max 10）: 0 / 5 / 10
      'eating' => [
        1 => 0,  2 => 0,  3 => 5,  4 => 5,  5 => 0,
        6 => 5,  7 => 10, 8 => 10, 9 => 10,
      ],
      // 移乗（max 15）: 0 / 5 / 10 / 15
      'moving' => [
        1 => 0,  2 => 0,  3 => 10, 4 => 5,  5 => 0,
        6 => 5,  7 => 5,  8 => 15, 9 => 15,
      ],
      // 整容（max 5）: 0 / 5
      'personal_grooming' => [
        1 => 0,  2 => 0,  3 => 0,  4 => 0,  5 => 0,
        6 => 5,  7 => 5,  8 => 5,  9 => 5,
      ],
      // トイレ動作（max 10）: 0 / 5 / 10
      'using_toilet' => [
        1 => 0,  2 => 0,  3 => 5,  4 => 5,  5 => 0,
        6 => 5,  7 => 5,  8 => 10, 9 => 10,
      ],
      // 入浴（max 5）: 0 / 5
      'bathing' => [
        1 => 0,  2 => 0,  3 => 0,  4 => 0,  5 => 0,
        6 => 5,  7 => 5,  8 => 5,  9 => 5,
      ],
      // 歩行（max 15）: 0 / 5 / 10 / 15
      'walking' => [
        1 => 0,  2 => 0,  3 => 5,  4 => 5,  5 => 0,
        6 => 10, 7 => 5,  8 => 15, 9 => 15,
      ],
      // 階段昇降（max 10）: 0 / 5 / 10
      'using_stairs' => [
        1 => 0,  2 => 0,  3 => 5,  4 => 5,  5 => 0,
        6 => 5,  7 => 0,  8 => 10, 9 => 10,
      ],
      // 着替え（max 10）: 0 / 5 / 10
      'changing_clothes' => [
        1 => 0,  2 => 0,  3 => 5,  4 => 5,  5 => 0,
        6 => 5,  7 => 5,  8 => 10, 9 => 10,
      ],
      // 排便コントロール（max 10）: 0 / 5 / 10
      'defecation' => [
        1 => 0,  2 => 0,  3 => 5,  4 => 5,  5 => 0,
        6 => 5,  7 => 5,  8 => 10, 9 => 10,
      ],
      // 排尿コントロール（max 10）: 0 / 5 / 10
      'urination' => [
        1 => 0,  2 => 0,  3 => 5,  4 => 5,  5 => 0,
        6 => 5,  7 => 5,  8 => 10, 9 => 10,
      ],
    ];

    return $map[$item][$levelId] ?? 0;
  }

  /**
   * 電話番号フォーマット（ハイフンを挿入）
   */
  protected function formatPhoneNumber(string $phone): string
  {
    $digitsOnly = preg_replace('/[^0-9]/', '', $phone);

    if (empty($digitsOnly)) {
      return '';
    }

    // 10桁: 03は2-4-4、それ以外は3-3-4
    if (strlen($digitsOnly) === 10) {
      if (substr($digitsOnly, 0, 2) === '03') {
        return substr($digitsOnly, 0, 2) . '-' . substr($digitsOnly, 2, 4) . '-' . substr($digitsOnly, 6);
      } else {
        return substr($digitsOnly, 0, 3) . '-' . substr($digitsOnly, 3, 3) . '-' . substr($digitsOnly, 6);
      }
    }

    // 11桁: 3-4-4
    if (strlen($digitsOnly) === 11) {
      return substr($digitsOnly, 0, 3) . '-' . substr($digitsOnly, 3, 4) . '-' . substr($digitsOnly, 7);
    }

    // その他はそのまま返す
    return $phone;
  }
}
