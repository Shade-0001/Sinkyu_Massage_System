<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;
use App\Models\Deposit;

/**
 * 入金管理票（保険）PDF生成サービス
 */
class InsurancePaymentPdfService
{
  /**
   * therapy_content_id → treatment_feesカラム名マッピング
   */
  protected $columnMap = [
    11 => 'hari_normal',
    12 => 'kyu_normal',
    13 => 'hari_and_kyu_normal',
    14 => 'hari_and_elec_needle_normal',
    15 => 'kyu_and_elec_moxa_heater_normal',
    16 => 'hari_and_kyu_elec_ray_normal',
    18 => 'massage_trunk_normal',
    19 => 'manual_correction_normal',
    20 => 'fomentation_normal',
    21 => 'fomentation_and_elec_ray_normal',
  ];

  /**
   * 負担割合マップ
   */
  protected $ratioMap = [1 => 0.1, 2 => 0.2, 3 => 0.3];

  /**
   * PDF生成
   *
   * @param string $serviceYearMonth サービス提供年月（Y-m形式）
   * @return string PDFバイナリ
   */
  public function generate(string $serviceYearMonth): string
  {
    // A4横向き
    $pdf = new Fpdi('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    $pdf->AddPage();

    $data = $this->fetchData($serviceYearMonth);
    $this->renderPdf($pdf, $data, $serviceYearMonth);

    return $pdf->Output('', 'S');
  }

  /**
   * データベースからデータを取得
   */
  protected function fetchData(string $serviceYearMonth): array
  {
    // 施術料金マスタを取得（最新）
    $treatmentFees = DB::table('treatment_fees')->orderBy('id', 'desc')->first();

    $deposits = Deposit::where('year_month', $serviceYearMonth)
      ->with(['clinicUser', 'insurer'])
      ->orderBy('id')
      ->get()
      ->map(function ($deposit) use ($treatmentFees) {
        // 療養費合計を算出
        $totalAmount = 0;
        if ($deposit->clinic_user_id && !empty($deposit->treatment_dates) && $treatmentFees) {
          $records = DB::table('records')
            ->where('clinic_user_id', $deposit->clinic_user_id)
            ->whereIn('date', $deposit->treatment_dates)
            ->where('therapy_type', $deposit->treatment_type)
            ->get();

          foreach ($records as $record) {
            $column = $this->columnMap[$record->therapy_content_id] ?? null;
            if ($column && isset($treatmentFees->$column)) {
              $totalAmount += (int)$treatmentFees->$column;
            }
          }
        }

        // 負担割合を取得
        $insurance = DB::table('insurances')
          ->where('clinic_user_id', $deposit->clinic_user_id)
          ->orderBy('id', 'desc')
          ->first();
        $ratioId = $insurance->expenses_borne_ratio_id ?? 1;
        $ratio = $this->ratioMap[$ratioId] ?? 0.1;

        // 自己負担額：療養費合計 × 負担割合（10円単位四捨五入）
        $selfpayAmount = (int)round($totalAmount * $ratio, -1);

        // 保険請求額：療養費合計 − 自己負担額
        $insuranceBillingAmount = $totalAmount - $selfpayAmount;

        // 治療期間（最初と最後の施術日）
        $treatmentDates = $deposit->treatment_dates ?? [];
        sort($treatmentDates);
        $periodStart = '';
        $periodEnd = '';
        if (!empty($treatmentDates)) {
          $firstDate = new \DateTime($treatmentDates[0]);
          $lastDate  = new \DateTime(end($treatmentDates));
          $m1 = (int)$firstDate->format('n');
          $d1 = (int)$firstDate->format('j');
          $m2 = (int)$lastDate->format('n');
          $d2 = (int)$lastDate->format('j');
          // 1桁の場合はスペースでパディング
          $periodStart = sprintf('%2d/%2d', $m1, $d1);
          $periodEnd   = sprintf('%2d/%2d', $m2, $d2);
        }

        // 施術種別テキスト
        $therapyText = $deposit->treatment_type == 1 ? '鍼灸' : '按摩';

        // 入金日フォーマット
        $depositDate = $deposit->deposit_date ? $deposit->deposit_date->format('Y/m/d') : '';

        return [
          'insurer_name'              => $deposit->insurer->insurer_name ?? '',
          'insured_name'              => $deposit->insured_name ?? '',
          'clinic_user_name'          => $deposit->clinicUser
            ? ($deposit->clinicUser->last_name . '  ' . $deposit->clinicUser->first_name)
            : '',
          'period_start'              => $periodStart,
          'period_end'                => $periodEnd,
          'therapy_text'              => $therapyText,
          'total_amount'              => $totalAmount,
          'selfpay_amount'            => $selfpayAmount,
          'insurance_billing_amount'  => $insuranceBillingAmount,
          'deposit_amount'            => $deposit->deposit_amount ?? 0,
          'deposit_date'              => $depositDate,
        ];
      })
      ->values()
      ->all();

    return ['rows' => $deposits];
  }

  /**
   * PDFを描画
   */
  protected function renderPdf(Fpdi $pdf, array $data, string $serviceYearMonth): void
  {
    // A4横向き：297mm × 210mm、左右マージン10mmで利用可能幅277mm
    $startX        = 10;
    $startY        = 30;
    $availableWidth = 277;
    $rowHeight      = 8;

    $pdf->SetFont('kozgopromedium', '', 13);
    $pdf->SetTextColor(0, 0, 0);

    // タイトル（左上）
    $pdf->SetFont('kozgopromedium', '', 17);
    $pdf->Text($startX, 15, '入金管理票（保険扱い）');

    // 元号年月（右上）
    $titleYearMonth = $this->formatJapaneseYearMonth($serviceYearMonth);
    $pdf->SetFont('kozgopromedium', '', 15);
    $titleYearMonthWidth = $pdf->GetStringWidth($titleYearMonth);
    $oneCharWidth        = $pdf->GetStringWidth('年');
    $pdf->Text($startX + $availableWidth - $titleYearMonthWidth - $oneCharWidth, 15, $titleYearMonth);

    // カラム幅（合計277mm）
    // No.:10, 保険者:40, 被保険者氏名:30, 受療者氏名:30, 治療期間:28, 施術:14,
    // 療養費:22, 自己負担額:23, 保険請求額:23, 入金額:22, 入金日:35
    $colWidths = [
      'no'        => 10,
      'insurer'   => 40,
      'insured'   => 30,
      'user'      => 30,
      'period'    => 28,
      'therapy'   => 14,
      'total'     => 22,
      'selfpay'   => 23,
      'billing'   => 23,
      'deposit'   => 22,
      'dep_date'  => 35,
    ];
    // 合計確認
    // 10+40+30+30+28+14+22+23+23+22+35 = 277 ✓

    $headers = [
      ['text' => 'No.',      'key' => 'no'],
      ['text' => '保険者',   'key' => 'insurer'],
      ['text' => '被保険者氏名', 'key' => 'insured'],
      ['text' => '受療者氏名',  'key' => 'user'],
      ['text' => '治療期間',    'key' => 'period'],
      ['text' => '施術',        'key' => 'therapy'],
      ['text' => '療養費',      'key' => 'total'],
      ['text' => '自己負担額',  'key' => 'selfpay'],
      ['text' => '保険請求額',  'key' => 'billing'],
      ['text' => '入金額',      'key' => 'deposit'],
      ['text' => '入金日',      'key' => 'dep_date'],
    ];

    $currentY = $startY;

    // ヘッダー行
    $pdf->SetFont('kozgopromedium', '', 10);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);

    $x = $startX;
    foreach ($headers as $header) {
      $w = $colWidths[$header['key']];
      $pdf->Rect($x, $currentY, $w, $rowHeight, 'F');
      $pdf->SetXY($x, $currentY);
      $pdf->Cell($w, $rowHeight, $header['text'], 0, 0, 'C', false);
      // 枠線（実線）
      $pdf->Line($x,     $currentY,            $x + $w, $currentY);
      $pdf->Line($x,     $currentY + $rowHeight, $x + $w, $currentY + $rowHeight);
      $pdf->Line($x,     $currentY,            $x,      $currentY + $rowHeight);
      $pdf->Line($x + $w, $currentY,           $x + $w, $currentY + $rowHeight);
      $x += $w;
    }

    $currentY += $rowHeight;

    // データ行
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetFont('kozgopromedium', '', 9);

    $rows = $data['rows'];

    $grandTotal        = 0;
    $grandSelfpay      = 0;
    $grandBilling      = 0;

    foreach ($rows as $i => $row) {
      $grandTotal   += $row['total_amount'];
      $grandSelfpay += $row['selfpay_amount'];
      $grandBilling += $row['insurance_billing_amount'];
      // A4横向きの有効高：210mm、下マージン10mm → 200mm まで
      if ($currentY + $rowHeight > 200) {
        $pdf->AddPage();
        $currentY = 20;
      }

      $no = $i + 1;
      $cells = [
        'no'       => (string)$no,
        'insurer'  => $row['insurer_name'],
        'insured'  => $row['insured_name'],
        'user'     => $row['clinic_user_name'],
        'period'   => $row['period_start'] . ' ～ ' . $row['period_end'],
        'therapy'  => $row['therapy_text'],
        'total'    => $row['total_amount'] > 0 ? number_format($row['total_amount']) : '',
        'selfpay'  => $row['selfpay_amount'] > 0 ? number_format($row['selfpay_amount']) : '',
        'billing'  => $row['insurance_billing_amount'] > 0 ? number_format($row['insurance_billing_amount']) : '',
        'deposit'  => $row['deposit_amount'] > 0 ? number_format($row['deposit_amount']) : '',
        'dep_date' => $row['deposit_date'],
      ];

      // 右揃えにするカラム
      $rightAlignKeys = ['total', 'selfpay', 'billing', 'deposit'];
      // 中央揃えにするカラム
      $centerAlignKeys = ['no', 'period', 'therapy', 'dep_date'];

      $x = $startX;
      foreach ($headers as $header) {
        $key = $header['key'];
        $w   = $colWidths[$key];
        $text = $cells[$key];

        if (in_array($key, $centerAlignKeys)) {
          $align = 'C';
        } elseif (in_array($key, $rightAlignKeys)) {
          $align = 'R';
        } else {
          $align = 'L';
        }

        // 数値右揃えのパディング
        $padding = in_array($key, $rightAlignKeys) ? 1 : 0;

        $pdf->SetXY($x + $padding, $currentY);
        $pdf->Cell($w - $padding * 2, $rowHeight, $text, 0, 0, $align, false);

        // 枠線（実線）
        $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
        $pdf->Line($x,     $currentY,             $x + $w, $currentY);
        $pdf->Line($x,     $currentY + $rowHeight, $x + $w, $currentY + $rowHeight);
        $pdf->Line($x,     $currentY,             $x,      $currentY + $rowHeight);
        $pdf->Line($x + $w, $currentY,            $x + $w, $currentY + $rowHeight);

        $x += $w;
      }

      $currentY += $rowHeight;
    }

    // 総計行（施術・療養費・自己負担額・保険請求額のみ描画、他は枠線も不要）
    if ($currentY + $rowHeight > 200) {
      $pdf->AddPage();
      $currentY = 20;
    }

    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);

    // 総計行で描画するカラムとその内容
    $totalCells = [
      'therapy' => '総計',
      'total'   => number_format($grandTotal),
      'selfpay' => number_format($grandSelfpay),
      'billing' => number_format($grandBilling),
    ];

    $x = $startX;
    foreach ($headers as $header) {
      $key = $header['key'];
      $w   = $colWidths[$key];

      if (array_key_exists($key, $totalCells)) {
        $text    = $totalCells[$key];
        $align   = ($key === 'therapy') ? 'C' : 'R';
        $padding = ($key !== 'therapy') ? 1 : 0;

        $pdf->SetXY($x + $padding, $currentY);
        $pdf->Cell($w - $padding * 2, $rowHeight, $text, 0, 0, $align, false);

        // 枠線
        $pdf->Line($x,      $currentY,             $x + $w, $currentY);
        $pdf->Line($x,      $currentY + $rowHeight, $x + $w, $currentY + $rowHeight);
        $pdf->Line($x,      $currentY,             $x,      $currentY + $rowHeight);
        $pdf->Line($x + $w, $currentY,             $x + $w, $currentY + $rowHeight);
      }

      $x += $w;
    }
  }

  /**
   * 和暦年月フォーマット（例：令和6年12月分）
   */
  protected function formatJapaneseYearMonth(string $yearMonth): string
  {
    $date      = $yearMonth . '-01';
    $timestamp = strtotime($date);
    $year      = (int)date('Y', $timestamp);
    $month     = (int)date('n', $timestamp);

    $era = $this->getJapaneseEra($year, $month, 1);

    return $era['era'] . $era['year'] . '年 ' . $month . '月分';
  }

  /**
   * 和暦情報を取得
   */
  protected function getJapaneseEra(int $year, int $month, int $day): array
  {
    $date = sprintf('%04d%02d%02d', $year, $month, $day);

    if ($date >= '20190501') {
      return ['era' => '令和', 'year' => $year - 2018];
    } elseif ($date >= '19890108') {
      return ['era' => '平成', 'year' => $year - 1988];
    } elseif ($date >= '19261225') {
      return ['era' => '昭和', 'year' => $year - 1925];
    } elseif ($date >= '19120730') {
      return ['era' => '大正', 'year' => $year - 1911];
    } else {
      return ['era' => '明治', 'year' => $year - 1867];
    }
  }
}
