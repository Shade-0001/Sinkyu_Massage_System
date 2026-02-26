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

  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate, string $remarks = ''): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);

    foreach ($clinicUserIds as $clinicUserId) {
      $data = $this->fetchData((int)$clinicUserId, $serviceYearMonth);
      if ($data) {
        $this->addPage($pdf, $data);
      }
    }

    return $pdf->Output('', 'S');
  }

  /**
   * データ取得
   */
  protected function fetchData(int $clinicUserId, string $serviceYearMonth): ?array
  {
    if ($this->sampleDataMode) {
      return $this->getSampleData();
    }

    $report = DB::table('reports')
      ->where('clinic_user_id', $clinicUserId)
      ->when($serviceYearMonth !== '', function ($q) use ($serviceYearMonth) {
        $q->where('service_provide_month', $serviceYearMonth);
      })
      ->orderBy('updated_at', 'desc')
      ->first();

    if (!$report) {
      return [
        'subjective_symptom_and_wish' => '',
        'objective_symptom'           => '',
        'therapy_content'             => '',
        'therapy_plan'                => '',
      ];
    }

    return [
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
      $pageCount = $pdf->setSourceFile($templatePath);
      $tplId     = $pdf->importPage(1);
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
   */
  protected function getSampleData(): array
  {
    $custom = $this->customSampleData ?? [];

    return [
      'subjective_symptom_and_wish' => $custom['subjective_symptom_and_wish'] ?? '腰痛・肩こりの改善を希望。特に朝起きた際の痛みを軽減したい。',
      'objective_symptom'           => $custom['objective_symptom']           ?? '腰部筋緊張、肩甲骨周囲筋の拘縮あり。可動域制限を認める。',
      'therapy_content'             => $custom['therapy_content']             ?? 'マッサージ療法（腰部・肩部中心）、ストレッチ指導。',
      'therapy_plan'                => $custom['therapy_plan']                ?? '週2回の施術継続。3ヶ月後に再評価予定。',
    ];
  }
}
