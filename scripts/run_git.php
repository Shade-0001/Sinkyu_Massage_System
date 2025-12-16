<?php
$cmds = [
  'git add app/Http/Controllers/PrintsController.php app/Services/Print/AcupunctureBenefitPdfService.php public/js/prints.js resources/views/prints/prints_index.blade.php resources/views/prints/coordinate_adjuster.blade.php routes/web.php composer.json composer.lock database/migrations/2025_12_16_150407_change_insurance_number_columns_to_bigint_in_insurances_table.php 2>&1',
  'git commit -m "プリント: 座標調整ツール追加とプレビュー/出力の修正" 2>&1',
  'git push origin HEAD 2>&1'
];
$out = [];
foreach ($cmds as $c) {
    $out[] = "== CMD: $c";
    $o = [];
    $r = 0;
    exec($c, $o, $r);
    $out = array_merge($out, $o);
    $out[] = "== EXIT: $r";
}
file_put_contents(__DIR__ . '/../git_commit_output.txt', implode("\n", $out));
echo "DONE\n";
