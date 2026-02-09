<?php
$acupuncture = file_get_contents('C:\Users\user\.claude\projects\i--XAMPP-htdocs-Sinkyu-Massage-System\bb5207b1-e772-4784-8cbd-973dfaad7958\tool-results\toolu_013cCYymBUMrv1TpstWjaGdy.txt');
$massage = file_get_contents('C:\Users\user\.claude\projects\i--XAMPP-htdocs-Sinkyu-Massage-System\bb5207b1-e772-4784-8cbd-973dfaad7958\tool-results\toolu_014DvaKP3MsNUdnmEFwnMwgV.txt');

preg_match_all("/drawTextByKey\([^,]+, '([^']+)'/", $acupuncture, $acuMatches);
preg_match_all("/drawTextByKey\([^,]+, '([^']+)'/", $massage, $massMatches);

$acuFields = array_unique($acuMatches[1]);
$massFields = array_unique($massMatches[1]);

sort($acuFields);
sort($massFields);

echo "はり・きゅう版のみに存在するフィールド:\n";
foreach (array_diff($acuFields, $massFields) as $field) {
  echo "  - $field\n";
}

echo "\nマッサージ版のみに存在するフィールド:\n";
foreach (array_diff($massFields, $acuFields) as $field) {
  echo "  - $field\n";
}

echo "\n共通フィールド: " . count(array_intersect($acuFields, $massFields)) . "個\n";
