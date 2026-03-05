<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;
use App\Models\Deposit;

/**
 * 入金管理表（保険）PDF生成サービス
 */
class PaymentListPdfService
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

  // 動的カラム幅（generate()内で確定）
  protected array $colWidths = [];

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

    $outputDate = date('Y-m-d H:i:s');
    $data       = $this->fetchData($serviceYearMonth);
    $pdf->SetFont('kozgopromedium', '', 9);
    $this->colWidths = $this->calcColWidths($pdf, $data);
    $this->renderPdf($pdf, $data, $serviceYearMonth, $outputDate);

    // 2ページ以上の場合、全ページにページ番号を描画（後処理）
    $totalPages = $pdf->getNumPages();
    if ($totalPages >= 2) {
      for ($p = 1; $p <= $totalPages; $p++) {
        $pdf->setPage($p);
        $pageText = '-' . "\u{2002}" . "\u{2002}" . $p . ' / ' . $totalPages . "\u{2002}" . "\u{2002}" . '-';
        $pdf->SetFont('kozgopromedium', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        // A4横向き297mm幅、高さ210mm、下端7mm上
        $pdf->SetXY(0, 203);
        $pdf->Cell(297, 0, $pageText, 0, 0, 'C');
      }
    }

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
        $rawDates = $deposit->treatment_dates ?? [];
        // 不正な日付文字列を除外（例: 2026-03-32 など）
        $treatmentDates = array_values(array_filter($rawDates, function ($d) {
          try {
            $dt = new \DateTime($d);
            return $dt->format('Y-m-d') === $d;
          } catch (\Exception $e) {
            return false;
          }
        }));
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

        // 入金日フォーマット（元号年 月 日）
        if ($deposit->deposit_date) {
          $date = $deposit->deposit_date;
          $year  = (int)$date->format('Y');
          $month = (int)$date->format('n');
          $day   = (int)$date->format('j');
          if ($date >= new \DateTime('2019-05-01')) {
            $gengo     = '令和';
            $gengoYear = $year - 2018;
          } elseif ($date >= new \DateTime('1989-01-08')) {
            $gengo     = '平成';
            $gengoYear = $year - 1988;
          } else {
            $gengo     = '昭和';
            $gengoYear = $year - 1925;
          }
          $depositDate = sprintf('%s%2d年%2d月%2d日', $gengo, $gengoYear, $month, $day);
        } else {
          $depositDate = '';
        }

        return [
          'insurer_name'              => $deposit->insurer->insurer_name ?? '',
          'insured_name'              => $deposit->insured_name ?: ($insurance->insured_name ?? ''),
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
   * 各カラム幅を動的計算して返す（折り返し許可：保険者）
   */
  protected function calcColWidths(Fpdi $pdf, array $data): array
  {
    $pad     = 1.6 * 2;
    $availW  = 281;
    $wrapKey = 'insurer';

    $headers = [
      'no'       => 'No.',
      'insurer'  => '保険者',
      'insured'  => '被保険者氏名',
      'user'     => '受療者氏名',
      'period'   => '治療期間',
      'therapy'  => '施術',
      'total'    => '療養費',
      'selfpay'  => '自己負担額',
      'billing'  => '保険請求額',
      'deposit'  => '入金額',
      'dep_date' => '入金日',
    ];

    $pdf->SetFont('kozgopromedium', '', 10);
    $minW = [];
    foreach ($headers as $key => $label) {
      $minW[$key] = $pdf->GetStringWidth($label) + $pad;
    }

    $pdf->SetFont('kozgopromedium', '', 9);
    foreach ($data['rows'] as $i => $row) {
      $texts = [
        'no'       => (string)($i + 1),
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
      foreach ($texts as $key => $text) {
        $minW[$key] = max($minW[$key], $pdf->GetStringWidth($text) + $pad);
      }
    }

    $fixedTotal = array_sum(array_filter($minW, fn($k) => $k !== $wrapKey, ARRAY_FILTER_USE_KEY));
    $remaining  = $availW - $fixedTotal;

    if ($remaining >= 8.0) {
      $minW[$wrapKey] = round($remaining, 4);
      foreach ($minW as $k => $v) {
        if ($k !== $wrapKey) {
          $minW[$k] = round($v, 4);
        }
      }
    } else {
      $totalW = array_sum($minW) ?: 1;
      foreach ($minW as $k => $v) {
        $minW[$k] = round($v * $availW / $totalW, 4);
      }
    }

    $diff           = $availW - array_sum($minW);
    $minW[$wrapKey] = round($minW[$wrapKey] + $diff, 4);

    return $minW;
  }

  /**
   * PDFを描画
   */
  protected function renderPdf(Fpdi $pdf, array $data, string $serviceYearMonth, string $outputDate = ''): void
  {
    // A4横向き：297mm × 210mm、左右マージン8mmで利用可能幅281mm
    $startX        = 8;
    $startY        = 30;
    $availableWidth = 281;
    $rowHeight      = 8;

    $pdf->SetFont('kozgopromedium', '', 13);
    $pdf->SetTextColor(0, 0, 0);

    // タイトル（左上）
    $pdf->SetFont('kozgopromedium', '', 17);
    $pdf->Text($startX, 15, '入金管理表（保険扱い）');

    // 元号年月（右上）
    $titleYearMonth = $this->formatJapaneseYearMonth($serviceYearMonth);
    $pdf->SetFont('kozgopromedium', '', 15);
    $titleYearMonthWidth = $pdf->GetStringWidth($titleYearMonth);
    $oneCharWidth        = $pdf->GetStringWidth('年');
    $pdf->Text($startX + $availableWidth - $titleYearMonthWidth - $oneCharWidth, 15, $titleYearMonth);

    // PDF出力日時（右上）
    if ($outputDate) {
      $ts      = strtotime($outputDate);
      $dateStr = '〈 PDF出力日時 │ ' . date('Y/m/d', $ts) . "\u{2002}" . date('H:i', $ts) . ' 〉';
      $pdf->SetFont('kozgopromedium', '', 8);
      $pdf->SetXY($startX, 6);
      $pdf->Cell($availableWidth, 0, $dateStr, 0, 0, 'R');
    }

    // カラム幅（自動計算）
    $colWidths = $this->colWidths;

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

    // ヘッダー行描画クロージャ
    $renderHeaderRow = function (float $y) use ($pdf, $startX, $colWidths, $headers, $rowHeight): void {
      $pdf->SetFont('kozgopromedium', '', 10);
      $pdf->SetFillColor(220, 220, 220);
      $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);

      // TCPDFのcell_height_ratio=1.25を考慮した垂直中央配置(10pt)
      $headerFontMm  = 10 * 0.352 * 1.25;
      $headerOffsetY = ($rowHeight - $headerFontMm) / 2;

      $x = $startX;
      foreach ($headers as $header) {
        $w = $colWidths[$header['key']];
        $pdf->Rect($x, $y, $w, $rowHeight, 'F');
        $pdf->SetXY($x, $y + $headerOffsetY);
        $pdf->Cell($w, 0, $header['text'], 0, 0, 'C', false);
        $pdf->Line($x,      $y,              $x + $w, $y);
        $pdf->Line($x,      $y + $rowHeight, $x + $w, $y + $rowHeight);
        $pdf->Line($x,      $y,              $x,      $y + $rowHeight);
        $pdf->Line($x + $w, $y,              $x + $w, $y + $rowHeight);
        $x += $w;
      }
    };

    $renderHeaderRow($currentY);

    $currentY += $rowHeight;

    // データ行
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetFont('kozgopromedium', '', 9);

    // TCPDFのcell_height_ratio=1.25を考慮した垂直中央配置(9pt)
    $dataFontMm  = 9 * 0.352 * 1.25;
    $dataOffsetY = ($rowHeight - $dataFontMm) / 2;

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
        $renderHeaderRow($currentY);
        $currentY += $rowHeight;
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

        // テキスト幅がセル幅を超える場合はフォントサイズを縮小
        $cellInnerW = $w - $padding * 2 - 1;
        $baseFontSize = 9;
        $fontSize = $baseFontSize;
        if ($text !== '' && $pdf->GetStringWidth($text) > $cellInnerW) {
          // セル幅に収まるまで0.5ptずつ縮小（最小9pt）
          while ($fontSize > 9 && $pdf->GetStringWidth($text) > $cellInnerW) {
            $fontSize -= 0.5;
            $pdf->SetFontSize($fontSize);
          }
        }

        $pdf->SetXY($x + $padding, $currentY + $dataOffsetY);
        $pdf->Cell($w - $padding * 2, 0, $text, 0, 0, $align, false);

        // フォントサイズを元に戻す
        if ($fontSize !== $baseFontSize) {
          $pdf->SetFontSize($baseFontSize);
        }

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
      $renderHeaderRow($currentY);
      $currentY += $rowHeight;
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

        $pdf->SetXY($x + $padding, $currentY + $dataOffsetY);
        $pdf->Cell($w - $padding * 2, 0, $text, 0, 0, $align, false);

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
