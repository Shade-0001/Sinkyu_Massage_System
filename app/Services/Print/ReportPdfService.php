<?php

namespace App\Services\Print;

use Illuminate\Support\Facades\DB;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * 報告書PDF生成サービス
 */
class ReportPdfService extends BasePdfService
{
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/report_coordinates.json');
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
      $data = $this->fetchData((int)$clinicUserId, $serviceYearMonth, $submissionDate);
      if ($data) {
        $this->addPage($pdf, $data);
      }
    }

    return $pdf->Output('', 'S');
  }

  /**
   * データ取得
   */
  protected function fetchData(int $clinicUserId, string $serviceYearMonth, string $submissionDate = ''): ?array
  {
    if ($this->sampleDataMode) {
      return $this->getSampleData();
    }

    $clinicUser = DB::table('clinic_users')->where('id', $clinicUserId)->first();

    $greetingText = '';
    if ($clinicUser) {
      $userName     = ($clinicUser->last_name ?? '') . '  ' . ($clinicUser->first_name ?? '');
      $greetingText = $userName . '  様についてご報告申し上げます。';
    }

    $report = DB::table('reports')
      ->where('clinic_user_id', $clinicUserId)
      ->when($serviceYearMonth !== '', function ($q) use ($serviceYearMonth) {
        $q->whereRaw("DATE_FORMAT(service_provide_month, '%Y-%m') = ?", [$serviceYearMonth]);
      })
      ->orderBy('updated_at', 'desc')
      ->first();

    if (!$report) {
      return [
        'submission_date'             => $this->convertToJapaneseDate($submissionDate),
        'greeting_text'               => $greetingText,
        'subjective_symptom_and_wish' => '',
        'objective_symptom'           => '',
        'therapy_content'             => '',
        'therapy_plan'                => '',
      ];
    }

    return [
      'submission_date'             => $this->convertToJapaneseDate($submissionDate),
      'greeting_text'               => $greetingText,
      'subjective_symptom_and_wish' => $report->subjective_symptom_and_wish ?? '',
      'objective_symptom'           => $report->objective_symptom ?? '',
      'therapy_content'             => $report->therapy_content ?? '',
      'therapy_plan'                => $report->therapy_plan ?? '',
    ];
  }

  /**
   * ページ追加と描画
   */
  protected function addPage(Fpdi $pdf, array $data): void
  {
    $pdf->AddPage();

    $templatePath = $this->customTemplatePath ?? storage_path('app/templates/others_1/報告書.pdf');

    if (file_exists($templatePath)) {
      $pdf->setSourceFile($templatePath);
      $tplId = $pdf->importPage(1);
      $pdf->useTemplate($tplId, 0, 0, null, null, true);
    }

    $pdf->SetFont('kozminproregular', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    $this->fillFormFields($pdf, $data);
  }

  /**
   * フォームフィールド埋め込み
   */
  protected function fillFormFields(Fpdi $pdf, array $data): void
  {
    $fields = [
      'submission_date',
      'greeting_text',
      'subjective_symptom_and_wish',
      'objective_symptom',
      'therapy_content',
      'therapy_plan',
    ];

    foreach ($fields as $field) {
      if ($this->hasCoord($field) && isset($data[$field]) && $data[$field] !== '') {
        $pdf->SetFontSize($this->coord($field, 'fontSize') ?: 11);
        $this->drawTextByKey($pdf, $field, (string)$data[$field]);
      }
    }
  }

  /**
   * サンプルデータ
   *
   * 【注意】$this->customSampleData を必ず参照すること。
   * 座標調整ツールのサンプルモードで入力されたテキストは
   * JS(customSampleData) → POST(custom_sample_data) → setCustomSampleData()
   * の流れで $this->customSampleData に格納される。
   * ここで参照しないと、入力・指定したテキストがPDFに反映されない。
   */
  protected function getSampleData(): array
  {
    $custom = $this->customSampleData ?? [];

    return [
      'submission_date'             => $custom['report_submission_date']      ?? '令和7年 2月 26日',
      'greeting_text'               => $custom['greeting_text']               ?? '山田  太郎  様についてご報告申し上げます。',
      'subjective_symptom_and_wish' => $custom['subjective_symptom_and_wish'] ?? '腰痛・肩こりの改善を希望。特に朝起きた際の痛みを軽減したい。',
      'objective_symptom'           => $custom['objective_symptom']           ?? '腰部筋緊張、肩甲骨周囲筋の拘縮あり。可動域制限を認める。',
      'therapy_content'             => $custom['therapy_content']             ?? 'マッサージ療法（腰部・肩部中心）、ストレッチ指導。',
      'therapy_plan'                => $custom['therapy_plan']                ?? '週2回の施術継続。3ヶ月後に再評価予定。',
    ];
  }
}
