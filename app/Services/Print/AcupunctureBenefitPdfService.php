<?php

namespace App\Services\Print;

use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * はり・きゅう療養費支給申請書PDF生成サービス
 */
class AcupunctureBenefitPdfService
{
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
    $pdf = new Fpdi();
    $pdf->SetAutoPageBreak(false);

    // 日本語フォント設定（SJIS-winエンコーディング）
    // 注: mbstringが有効である必要がある
    $pdf->AddFont('ipaexg', '', 'ipaexg.php');

    foreach ($clinicUserIds as $clinicUserId) {
      $data = $this->fetchData($clinicUserId, $serviceYearMonth);

      if ($data) {
        $this->addPage($pdf, $data, $submissionDate);
      }
    }

    return $pdf->Output('S'); // バイナリとして返却
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
    // 利用者情報取得
    $clinicUser = DB::table('clinic_users')->where('id', $clinicUserId)->first();

    if (!$clinicUser) {
      return null;
    }

    // 保険情報取得
    $insurance = DB::table('insurances')
      ->where('clinic_user_id', $clinicUserId)
      ->orderBy('created_at', 'desc')
      ->first();

    // はり・きゅう同意書情報取得
    $consent = DB::table('consents_acupuncture')
      ->where('clinic_user_id', $clinicUserId)
      ->orderBy('consent_date', 'desc')
      ->first();

    // 施術実績取得（対象年月）
    $records = DB::table('records')
      ->where('clinic_user_id', $clinicUserId)
      ->whereRaw("DATE_FORMAT(service_date, '%Y-%m') = ?", [$serviceYearMonth])
      ->orderBy('service_date')
      ->get();

    // 施術所情報取得
    $clinicInfo = DB::table('clinic_info')->first();

    return [
      'clinic_user' => $clinicUser,
      'insurance' => $insurance,
      'consent' => $consent,
      'records' => $records,
      'clinic_info' => $clinicInfo,
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
      $pdf->setSourceFile($templatePath);
      $tplId = $pdf->importPage(1);
      $pdf->useTemplate($tplId);
    }

    // フォント設定
    $pdf->SetFont('ipaexg', '', 10);
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
    $pdf->SetXY(100, 15);
    $pdf->Write(0, $japaneseYear['era'] . $japaneseYear['year'] . '年' . (int)$month . '月分');

    // === 機関コード ===
    if ($clinicInfo && isset($clinicInfo->institution_code)) {
      $pdf->SetXY(150, 20);
      $pdf->Write(0, $clinicInfo->institution_code);
    }

    // === 保険者番号 ===
    if ($insurance && isset($insurance->insurer_number)) {
      $pdf->SetXY(50, 35);
      $pdf->Write(0, $insurance->insurer_number);
    }

    // === 受給者番号 ===
    if ($insurance && isset($insurance->recipient_number)) {
      $pdf->SetXY(50, 42);
      $pdf->Write(0, $insurance->recipient_number);
    }

    // === 被保険者証記号番号 ===
    if ($insurance && isset($insurance->insurance_symbol) && isset($insurance->insurance_number)) {
      $pdf->SetXY(50, 50);
      $pdf->Write(0, $insurance->insurance_symbol . '・' . $insurance->insurance_number);
    }

    // === 療養を受けた者の氏名 ===
    $fullName = ($clinicUser->last_name ?? '') . ($clinicUser->first_name ?? '');
    $fullNameKana = ($clinicUser->last_kana ?? '') . ($clinicUser->first_kana ?? '');

    $pdf->SetXY(50, 58);
    $pdf->Write(0, $fullName);

    $pdf->SetXY(50, 54);
    $pdf->SetFontSize(8);
    $pdf->Write(0, '(' . $fullNameKana . ')');
    $pdf->SetFontSize(10);

    // === 生年月日 ===
    if (isset($clinicUser->birthday)) {
      $birthDate = $this->convertToJapaneseDate($clinicUser->birthday);
      $pdf->SetXY(100, 60);
      $pdf->Write(0, $birthDate);
    }

    // === 傷病名（同意書から取得） ===
    if ($consent && isset($consent->illness_name)) {
      $pdf->SetXY(50, 75);
      $pdf->Write(0, $consent->illness_name);
    }

    // === 初療年月日 ===
    if ($records->isNotEmpty()) {
      $firstRecord = $records->first();
      $firstServiceDate = $this->convertToJapaneseDate($firstRecord->service_date);
      $pdf->SetXY(50, 82);
      $pdf->Write(0, $firstServiceDate);
    }

    // === 施術期間 ===
    if ($records->isNotEmpty()) {
      $firstDate = $this->convertToJapaneseDate($records->first()->service_date);
      $lastDate = $this->convertToJapaneseDate($records->last()->service_date);

      $pdf->SetXY(50, 90);
      $pdf->Write(0, $firstDate . '～' . $lastDate);

      $pdf->SetXY(120, 90);
      $pdf->Write(0, $records->count() . '日');
    }

    // === 施術日カレンダー（1-31日） ===
    $this->fillServiceDates($pdf, $records);

    // === 施術所情報 ===
    if ($clinicInfo) {
      // 施術所名
      $pdf->SetXY(50, 180);
      $pdf->Write(0, $clinicInfo->clinic_name ?? '');

      // 施術所住所
      $pdf->SetXY(50, 188);
      $clinicAddress = ($clinicInfo->postal_code ?? '') . ' ' .
                       ($clinicInfo->address_1 ?? '') .
                       ($clinicInfo->address_2 ?? '') .
                       ($clinicInfo->address_3 ?? '');
      $pdf->Write(0, $clinicAddress);

      // 電話番号
      $pdf->SetXY(150, 188);
      $pdf->Write(0, $clinicInfo->phone ?? '');
    }

    // === 申請者情報 ===
    $pdf->SetXY(50, 210);
    $address = ($clinicUser->postal_code ?? '') . ' ' .
               ($clinicUser->address_1 ?? '') .
               ($clinicUser->address_2 ?? '') .
               ($clinicUser->address_3 ?? '');
    $pdf->Write(0, $address);

    $pdf->SetXY(50, 218);
    $pdf->Write(0, $fullName);

    // === 提出年月日 ===
    $submissionJapaneseDate = $this->convertToJapaneseDate($submissionDate);
    $pdf->SetXY(50, 202);
    $pdf->Write(0, $submissionJapaneseDate);
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
    // 施術日カレンダーの座標設定（1日～31日）
    $calendarStartX = 30;
    $calendarStartY = 110;
    $cellWidth = 5;

    foreach ($records as $record) {
      $day = (int)date('d', strtotime($record->service_date));

      // 日付に応じて○を記入
      $x = $calendarStartX + ($day - 1) * $cellWidth;
      $y = $calendarStartY;

      $pdf->SetXY($x, $y);
      $pdf->Write(0, '○');
    }
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
}
