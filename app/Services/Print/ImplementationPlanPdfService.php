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
  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate, string $remarks = ''): string
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
        $data = $this->fetchData((int) $clinicUserId);
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
  protected function fetchData(int $clinicUserId): ?array
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

    // 実施計画（最新）
    $plan = DB::table('plans')
      ->where('clinic_user_id', $clinicUserId)
      ->orderBy('assessment_date', 'desc')
      ->first();

    if (!$plan) {
      return null;
    }

    // ADL評価レベルマスタ（id => テキスト）
    $levels = DB::table('assistance_levels')->pluck('assistance_level', 'id');

    // 事業所情報
    $clinicInfo = DB::table('clinic_info')->first();

    return [
      'clinic_user' => $clinicUser,
      'plan'        => $plan,
      'levels'      => $levels,
      'clinic_info' => $clinicInfo,
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
      ],
      // サンプルデータでのADL評価値（level_idがnullの場合のフォールバック用）
      'sample_adl_levels' => [
        'eating_assistance_level_id'            => $c['ip_adl_eating_level']            ?? '自立',
        'moving_assistance_level_id'            => $c['ip_adl_moving_level']            ?? '部分介助',
        'personal_grooming_assistance_level_id' => $c['ip_adl_personal_grooming_level'] ?? '自立',
        'using_toilet_assistance_level_id'      => $c['ip_adl_using_toilet_level']      ?? '部分介助',
        'bathing_assistance_level_id'           => $c['ip_adl_bathing_level']           ?? '要介助',
        'walking_assistance_level_id'           => $c['ip_adl_walking_level']           ?? '要監視又は軽監視',
        'using_stairs_assistance_level_id'      => $c['ip_adl_using_stairs_level']      ?? '部分介助',
        'changing_clothes_assistance_level_id'  => $c['ip_adl_changing_clothes_level']  ?? '自立',
        'defecation_assistance_level_id'        => $c['ip_adl_defecation_level']        ?? '自立',
        'urination_assistance_level_id'         => $c['ip_adl_urination_level']         ?? '自立',
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
      $name = trim(($clinicUser->last_name ?? '') . ' ' . ($clinicUser->first_name ?? ''));
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

    // =====================
    // グループ２：ADL評価
    // =====================

    $adlItems = [
      [
        'note_key'     => 'ip_adl_eating_note',
        'level_id_col' => 'eating_assistance_level_id',
        'note_col'     => 'eating_assistance_note',
      ],
      [
        'note_key'     => 'ip_adl_moving_note',
        'level_id_col' => 'moving_assistance_level_id',
        'note_col'     => 'moving_assistance_note',
      ],
      [
        'note_key'     => 'ip_adl_personal_grooming_note',
        'level_id_col' => 'personal_grooming_assistance_level_id',
        'note_col'     => 'personal_grooming_assistance_note',
      ],
      [
        'note_key'     => 'ip_adl_using_toilet_note',
        'level_id_col' => 'using_toilet_assistance_level_id',
        'note_col'     => 'using_toilet_assistance_note',
      ],
      [
        'note_key'     => 'ip_adl_bathing_note',
        'level_id_col' => 'bathing_assistance_level_id',
        'note_col'     => 'bathing_assistance_note',
      ],
      [
        'note_key'     => 'ip_adl_walking_note',
        'level_id_col' => 'walking_assistance_level_id',
        'note_col'     => 'walking_assistance_note',
      ],
      [
        'note_key'     => 'ip_adl_using_stairs_note',
        'level_id_col' => 'using_stairs_assistance_level_id',
        'note_col'     => 'using_stairs_assistance_note',
      ],
      [
        'note_key'     => 'ip_adl_changing_clothes_note',
        'level_id_col' => 'changing_clothes_assistance_level_id',
        'note_col'     => 'changing_clothes_assistance_note',
      ],
      [
        'note_key'     => 'ip_adl_defecation_note',
        'level_id_col' => 'defecation_assistance_level_id',
        'note_col'     => 'defecation_assistance_note',
      ],
      [
        'note_key'     => 'ip_adl_urination_note',
        'level_id_col' => 'urination_assistance_level_id',
        'note_col'     => 'urination_assistance_note',
      ],
    ];

    // ADL評価値：1フィールド(ip_adl_level)の座標を基点に rowLineHeight で縦列挙
    if ($this->hasCoord('ip_adl_level')) {
      $adlX          = $this->coord('ip_adl_level', 'x');
      $adlBaseY      = $this->coord('ip_adl_level', 'y');
      $rowLineHeight = $this->coord('ip_adl_level', 'rowLineHeight', 7);
      $adlFontSize   = $this->coord('ip_adl_level', 'fontSize', 10);

      foreach ($adlItems as $i => $item) {
        $offsetY = $i * $rowLineHeight;

        if ($sampleAdlLevels) {
          $levelText = $sampleAdlLevels[$item['level_id_col']] ?? '';
        } else {
          $levelId   = $plan->{$item['level_id_col']} ?? null;
          $levelText = $levelId ? ($levels[$levelId] ?? '') : '';
        }

        $pdf->SetFont('kozgopromedium', '', $adlFontSize);
        $pdf->SetXY($adlX, $adlBaseY + $offsetY);
        $pdf->Cell(0, 0, $levelText, 0, 0, 'L', false);
      }
    }

    // ADL備考（各行は ip_adl_*_note の座標を使用）
    foreach ($adlItems as $i => $item) {
      $offsetY  = $i * ($this->coord('ip_adl_level', 'rowLineHeight', 7));
      $noteText = $plan->{$item['note_col']} ?? '';
      if ($this->hasCoord($item['note_key'])) {
        $x        = $this->coord($item['note_key'], 'x');
        $y        = $this->coord($item['note_key'], 'y') + $offsetY;
        $fontSize = $this->coord($item['note_key'], 'fontSize', 10);
        $pdf->SetFont('kozgopromedium', '', $fontSize);
        $pdf->SetXY($x, $y);
        $pdf->Cell(0, 0, $noteText, 0, 0, 'L', false);
      }
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
      // 郵便番号（〒XXX-XXXX 形式に整形）
      $postalCode = (string) ($clinicInfo->postal_code ?? '');
      if (strlen($postalCode) === 7 && ctype_digit($postalCode)) {
        $postalCode = '〒' . substr($postalCode, 0, 3) . '-' . substr($postalCode, 3);
      }
      $this->drawTextByKey($pdf, 'ip_clinic_postal_code', $postalCode);

      $address = ($clinicInfo->address_1 ?? '') . ($clinicInfo->address_2 ?? '') . ($clinicInfo->address_3 ?? '');
      $this->drawTextByKey($pdf, 'ip_clinic_address', $address);

      $this->drawTextByKey($pdf, 'ip_clinic_phone', $clinicInfo->phone ?? '');
      $this->drawTextByKey($pdf, 'ip_clinic_name', $clinicInfo->clinic_name ?? '');

      $ownerName = trim(($clinicInfo->owner_last_name ?? '') . ' ' . ($clinicInfo->owner_first_name ?? ''));
      $this->drawTextByKey($pdf, 'ip_clinic_owner_name', $ownerName);
    }
  }
}
