<?php
require 'vendor/autoload.php';
$pdf = new setasign\Fpdi\Tcpdf\Fpdi('L','mm','A4',true,'UTF-8',false);
$pdf->SetFont('kozgopromedium','',8);

$pad = 2.0;
$headers = [
  'status'             => '状態',
  'insurance_type'     => '保険区分',
  'insured_number'     => '被保険者番号',
  'license_date'       => '資格取得日',
  'certification_date' => '認定日',
  'issue_date'         => '発行日',
  'copayment'          => '一部負担',
  'expiry_date'        => '有効期限',
  'household_name'     => '世帯主氏名',
  'insured_name'       => '被保険者氏名',
  'subsidized'         => '助成対象',
  'public_payer'       => '公費負担番号',
  'public_recipient'   => '公費受給番号',
  'insurer_number'     => '保険者番号',
  'insurer_name'       => '保険者名',
];

$COL_SAMPLE_DATA = [
  'status'             => ['最新', '履歴'],
  'insurance_type'     => ['協会けんぽ', '後期高齢者', '国保組合'],
  'insured_number'     => ['12345678901234'],
  'license_date'       => ['2025/12/31'],
  'certification_date' => ['2025/12/31'],
  'issue_date'         => ['2025/12/31'],
  'copayment'          => ['3割'],
  'expiry_date'        => ['2025/12/31'],
  'household_name'     => ['山田　太郎'],
  'insured_name'       => ['山田　太郎'],
  'subsidized'         => ['非対象'],
  'public_payer'       => ['12345678901'],
  'public_recipient'   => ['12345678901'],
  'insurer_number'     => ['123456789'],
  'insurer_name'       => null,
];

$minWidths = [];
$totalMinW = 0;
foreach($headers as $key => $label){
  $labelW = $pdf->GetStringWidth($label);
  if($key === 'insurer_name'){
    $minW = ceil($labelW + $pad);
  } else {
    $sampleData = $COL_SAMPLE_DATA[$key] ?? [];
    if(is_array($sampleData) && !empty($sampleData)){
      $dataW = max(array_map(fn($s)=>$pdf->GetStringWidth($s), $sampleData));
      $minW = ceil(max($labelW, $dataW) + $pad);
    } else {
      $minW = ceil($labelW + $pad);
    }
  }
  $minWidths[$key] = $minW;
  $totalMinW += $minW;
}

echo 'totalMinW='.$totalMinW.' AVAILABLE_W=281'.PHP_EOL;
$overW = $totalMinW - 281;
if($overW > 0){
  echo 'OVER: '.$overW.'mm'.PHP_EOL;
  $minWidths['insurer_name'] = max(10, $minWidths['insurer_name'] - $overW);
} else {
  // 余り分を insurer_name に全振り
  $remainder = 281 - $totalMinW;
  echo 'REMAINDER: '.$remainder.'mm'.PHP_EOL;
  if($remainder > 0){
    $minWidths['insurer_name'] += $remainder;
  }
}
echo 'final_sum='.array_sum($minWidths).PHP_EOL;
echo PHP_EOL;
foreach($minWidths as $k=>$w) echo str_pad($k,20).$w.PHP_EOL;
echo PHP_EOL.'--- テーブル配置 ---'.PHP_EOL;
$x = 8; // MARGIN_X
$curX = $x;
$maxX = 0;
foreach($minWidths as $k=>$w){
  $nextX = $curX + $w;
  echo str_pad($k,20).'X='.$curX.'～'.str_pad($nextX,7).' (幅='.$w.')'.PHP_EOL;
  $curX = $nextX;
  $maxX = max($maxX, $nextX);
}
echo PHP_EOL.'最終X位置: '.$maxX.'mm (A4横=297mm, 右マージン=8mm なら 289mm までOK)'.PHP_EOL;
